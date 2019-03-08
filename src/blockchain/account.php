<?php
namespace Mobius\Blockchain;

use \ZuluCrypto\StellarSdk\Server;
use \ZuluCrypto\StellarSdk\Keypair;
use \Mobius\Stellar\Account as Stellar_Account;
use \phpseclib\Math\BigInteger;
use \Mobius\Client;

class Account{

    /**
     * Account keypair
     * 
     * @var object Keypair
     */
    public $keypair;

    private $info = null;

    public function __construct($keypair){
        $this->keypair = $keypair;
    }

    /**
     * Returns true if given keypair is added as cosigner to current account
     * 
     * @param object Keypair
     * @return boolean true if cosigner added
     */
    public function authorized($to_keypair){
        return $this->find_signer($to_keypair->getPublicKey());
    }

    /**
     * Returns Stellar::Account instance for given keypair
     * 
     * @return object Account
     */
    public function account(){

        if(STELLAR_PUBLICNET){
            // PublicNet Server
            $server = Server::publicNet();
        }
        else{
            // TestNet Server
            $server = Server::testNet();
        }
        return Account::getAccount($this->keypair, $server);
    }

    /**
     * Caches Stellar::Account information from network.
     * @return object Account
     */
    public function info(){
        if(!$this->info){
            $this->info = $this->account();
        }
        return $this->info;
    }

    /**
     * Invalidates account information cache
     */
    public function reload(){
        $this->info = null;
    }

    /**
     * Invalidates cache and returns next sequence value for given account
     * 
     * @return object BigInteger
     */
    public function next_sequence_value(){
        $this->reload();
        return new BigInteger($this->info()->getSequence() + 1);
    }

    public static function getAccount($accountId, $server)
    {
        // Cannot be empty
        if (!$accountId) throw new InvalidArgumentException('Empty accountId');

        if ($accountId instanceof Keypair) {
            $accountId = $accountId->getPublicKey();
        }

        try {
            $response = $server->getApiClient()->get(sprintf('/accounts/%s', $accountId));
        }
        catch (HorizonException $e) {
            // Account not found, return null
            if ($e->getHttpStatusCode() === 404) {
                return null;
            }

            // A problem we can't handle, rethrow
            throw $e;
        }

        $account = Stellar_Account::fromHorizonResponse($response);
        $account->setApiClient($server->getApiClient());

        return $account;
    }

    public function find_signer($address){
        $signers = $this->info()->getSigners();
        if($signers){
            foreach($signers as $signer){
                if($signer['key'] == $address){
                    return $signer;
                }
            }
        }
        return false;
    }

    public function trustline_exists($asset = null){
        if(!$asset){
            $asset = (new Client())->stellar_asset();
        }
        $balance = $this->find_balance($asset);
        return $balance && $balance->getLimit()->getScaledValue() > 0;
    }

    public function find_balance($asset){
        $balances = $this->info()->getBalances();
        if($balances){
            foreach($balances as $balance){
                if($this->balance_matches($asset, $balance)){
                    return $balance;
                }
            }
        }
        throw new \Exception("Account Missing");
    }

    public function balance_matches($asset, $balance){
        if($asset->isNative()){
            return $balance->getAssetType() === "native";
        }
        else{
            $code = $balance->getAssetCode();
            $issuer = $balance->getAssetIssuerAccountId();
            return $code == $asset->getAssetCode() && $issuer == $asset->getIssuer()->getAccountIdString();
        }
    }
}
