<?php
namespace Mobius;

use \ZuluCrypto\StellarSdk\Horizon\ApiClient;
use \ZuluCrypto\StellarSdk\Server;
use \Mobius\Blockchain\KeyPairFactory;
use ZuluCrypto\StellarSdk\XdrModel\Asset;
use ZuluCrypto\StellarSdk\XdrModel\AccountId;

class Client{

    /**
     * Mobius API host
     */
    CONST MOBIUS_HOST = "https://mobius.network";

    /**
     * Strict Time Interval in seconds for transaction time bounds
     */
    CONST STRICT_INTERVAL = 10;

    /**
     * Challenge expires in (seconds)
     */
    CONST CHALLENGE_EXPIRES_IN = 60 * 60 * 24; 

    private $asset_code = 'MOBI';

    private $asset_issuer = null;

    private $stellar_asset;

    /**
     * Converts given argument to KeyPair
     * 
     * @return object Keypair
     */
    public static function to_keypair($subject){
        return KeyPairFactory::produce($subject);
    }

    /**
     * Sets Asset code
     */
    public function set_asset_code($asset_code){
        $this->asset_code = $asset_code;
    }

    /**
     * Returns Asset Code
     * 
     * @return string
     */
    public function get_asset_code(){
        return $this->asset_code;
    }

    /**
     * Sets Asset Issuer
     */
    public function set_asset_issuer($asset_issuer){
        $this->asset_issuer = $asset_issuer;
    }

    /**
     * Returns Asset Issuer
     * 
     * @return string
     */
    public function get_asset_issuer(){
        if($this->asset_issuer){
            return $this->asset_issuer;
        }
        else{
            if(Client::isTestNet()){
                return "GDRWBLJURXUKM4RWDZDTPJNX6XBYFO3PSE4H4GPUL6H6RCUQVKTSD4AT";
            }
            else{
                return "GA6HCMBLTZS5VYYBCATRBRZ3BZJMAFUDKYYF6AH6MVCMGWMRDNSWJPIH";   
            }
        }
    }

    public static function isTestNet(){
        if(STELLAR_PUBLICNET == false){
            return true;
        }
        else{
            return false;
        }
    }

    public static function getServer(){
        if(STELLAR_PUBLICNET == false){
            $server = Server::testNet();
        }
        else{
            $server = Server::publicNet();
        }
        return $server;
    }

    public function stellar_asset(){
        if(!$this->stellar_asset){
            $asset = new Asset(Asset::TYPE_ALPHANUM_4);
            $asset->setAssetCode($this->get_asset_code());
            $account_id = new AccountId($this->get_asset_issuer());
            $asset->setIssuer($account_id);
            $this->stellar_asset = $asset;
        }
        return $this->stellar_asset;
    }
}