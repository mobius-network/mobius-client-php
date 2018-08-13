<?php
namespace Mobius\Client;

use Mobius\Client;
use Mobius\Blockchain\Account;
use \ZuluCrypto\StellarSdk\Server;
use ZuluCrypto\StellarSdk\XdrModel\Asset;
use ZuluCrypto\StellarSdk\XdrModel\AccountId;
use \ZuluCrypto\StellarSdk\Signing\PrivateKeySigner;
use \ZuluCrypto\StellarSdk\Model\RestApiModel;

class App{

    /**
     * Developers private key
     * 
     * @var string
     */
    public $seed;

    /**
     * Users public key.
     * 
     * @var string
     */
    public $address;

    public $client;

    public $user_account;

    public $app_account;

    public function __construct($seed, $address, $client = null){
        $this->seed = $seed;
        $this->address = $address;
        $this->client = $client;
    }

    /**
     * Checks if developer is authorized to use an application.
     * @return boolean|array of signer
     */
    public function authorized(){
        return $this->user_account()->authorized($this->app_keypair());
    }

    /**
     * Returns user balance
     * 
     * @return float User balance
     */
    public function user_balance(){
        $this->validate();
        return $this->balance_object()->getBalance();
    }

    /**
     * Returns app balance
     * 
     * @return float App balance
     */
    public function app_balance(){
        return $this->app_balance_object()->getBalance();
    }

    /**
     * Makes payment
     * 
     * @param float $amount
     * @param string $target_address Optional: third party receiver address
     * 
     * @return PostTransactionResponse
     * 
     * @throws \Exception If Current Balance is insufficient
     */
    public function charge($amount, $target_address = null){
        $current_balance = $this->balance();
        if($current_balance < $amount){
            throw new \Exception("Insufficient Funds");
        }
        $txBuilder = $this->payment_tx($amount, $target_address);

        $response = $txBuilder->submit($this->app_keypair());
        $this->reload_user_account();
        $this->reload_app_account();
        return $response;
    }

    /**
     * Sends money from user's account to third party.
     * 
     * @param float $amount
     * @param string $target_address
     * 
     * @return PostTransactionResponse
     * 
     * @throws \Exception If Current Balance is insufficient
     */
    public function transfer($amount, $target_address){
        $current_balance = $this->balance();
        if($current_balance < $amount){
            throw new \Exception("Insufficient Funds");
        }
        $txBuilder = $this->payment_tx($amount, $target_address);

        $response = $txBuilder->submit($this->user_keypair());
        $this->reload_user_account();
        $this->reload_app_account();
        return $response;
    }

    /**
     * Sends money from application account to third party
     * 
     * @param float $amount
     * @param string $target_address
     * 
     * @return PostTransactionResponse
     * 
     * @throws \Exception If Current Balance is insufficient
     */
    public function payout($amount, $address){
        $current_balance = $this->app_balance();
        if($current_balance < $amount){
            throw new \Exception("Insufficient Funds");
        }
        $txBuilder = $this->payout_tx($amount, $address);

        $response = $txBuilder->submit($this->app_keypair());
        $this->reload_user_account();
        $this->reload_app_account();
        return $response;
    }

    public function payout_tx($amount, $address){
        $server = Client::getServer();

        $sequence = $this->user_account()->next_sequence_value();

        $asset = new Asset(Asset::TYPE_ALPHANUM_4);
        $asset->setAssetCode($this->getClient()->get_asset_code());
        $account_id = new AccountId($this->getClient()->get_asset_issuer());
        $asset->setIssuer($account_id);

        $txBuilder = $server->buildTransaction($this->user_keypair())
                            ->addCustomAssetPaymentOp($asset, $amount, Client::to_keypair($address));
        return $txBuilder;
    }

    public function payment_tx($amount, $target_address){
        $server = Client::getServer();
        $sequence = $this->user_account()->next_sequence_value();
        
        $asset = new Asset(Asset::TYPE_ALPHANUM_4);
        $asset->setAssetCode($this->getClient()->get_asset_code());
        $account_id = new AccountId($this->getClient()->get_asset_issuer());
        $asset->setIssuer($account_id);
            
        $txBuilder = $server->buildTransaction($this->user_keypair())
                            ->addCustomAssetPaymentOp($asset, $amount, $this->app_keypair());
                            
        // Set Sequence Number                              
        $txBuilder->setSequenceNumber($this->user_account()->next_sequence_value());

        if($target_address){
            $txBuilder = $txBuilder->addCustomAssetPaymentOp($asset, $amount, Client::to_keypair($target_address));
        }
        return $txBuilder;        
    }

    public function user_keypair(){
        return Client::to_keypair($this->address);
    }

    public function app_keypair(){
        return Client::to_keypair($this->seed);
    }

    public function balance_object(){
        $account = $this->user_account();
        $stellar_account = $account->info();
        $balances = $stellar_account->getBalances();
        $balance = $this->find_balance($balances);
        return $balance;
    }

    public function app_balance_object(){
        $account = $this->app_account();
        $stellar_account = $account->info();
        $balances = $stellar_account->getBalances();
        $balance = $this->find_balance($balances);
        return $balance;
    }

    public function getClient(){
        if(!$this->client){
            $this->client = new Client();
        }
        return $this->client;
    }

    public function find_balance($balances){
        $amounts = array();
        if($balances){
            foreach($balances as $balance){
                if($balance->getAssetIssuerAccountId() == $this->getClient()->get_asset_issuer() && $balance->getAssetCode() == $this->getClient()->get_asset_code()){
                    return $balance;
                }
            }
        }
        return $balance;
    }

    public function user_account(){
        if(!$this->user_account){
            $this->user_account = new Account($this->user_keypair());
        }
        return $this->user_account;
    }

    public function app_account(){
        if(!$this->app_account){
            $this->app_account = new Account($this->app_keypair());
        }
        return $this->app_account;
    } 

    public function reload_user_account(){
        $this->user_account = null;       
    }
    
    public function reload_app_account(){
        $this->app_account = null;       
    }

    public function limit(){
        $this->balance_object()->getLimit()->getScaledValue();
    }

    public function validate(){
        if(!$this->authorized()){
            throw new \Exception("Authorisation missing");
        }
        if(!$this->user_account()->trustline_exists()){
            throw new \Exception("Trustline not found");
        }
        return true;
    }

}
