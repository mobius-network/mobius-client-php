<?php
namespace Mobius\Client\Auth;

use \ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope;
use \ZuluCrypto\StellarSdk\Transaction\TransactionBuilder;
use \ZuluCrypto\StellarSdk\Transaction\Transaction;
use \ZuluCrypto\StellarSdk\Xdr\XdrBuffer;
use \ZuluCrypto\StellarSdk\Keypair;
use \Mobius\Client;

class Sign{

    public static function call($seed, $xdr, $address){
        $xdr = new XdrBuffer($xdr);
        $developer_keypair = Keypair::newFromPublicKey($address);
        $envelope = TransactionEnvelope::fromXdr($xdr);
        Sign::validate($developer_keypair, $envelope);
        
        $keypair = Keypair::newFromSeed($seed);
        $txBuilder = Sign::builder($envelope);
        
        // Sign Transaction with Keypair
        $txEnvelope = $txBuilder->sign($keypair);
        
        // Convert Envevelop to Base64 XDR
        return $txEnvelope->toBase64();
    }
    
    public static function validate($developer_keypair, $envelope){
        if(Sign::signed_correctly($developer_keypair, $envelope)){
            return true;
        }
        throw new \Exception("Unauthorized!");
    }

    public function signed_correctly($developer_keypair, $envelope){
        $hash = Sign::builder($envelope)->hash();
        $signatures = $envelope->getDecoratedSignatures();
        if(!$signatures){
            return false;
        }
        $keypairs = array(
            $developer_keypair,
        );

        $key_index = array();
        foreach($keypairs as $kp){
            $key_index[$kp->getHint()] = $kp;
        }

        foreach($signatures as $sig){
            $hint = substr($sig->toXdr(), 0, 4);
            if(isset($key_index[$hint])){
                $keypair = $key_index[$hint];
                if(!$keypair->verifySignature($sig->getRawSignature(), $hash)){
                    return false;
                }
            }
            else{
                return false;
            }
        }

        return true;
    }

    public static function builder($xdr){
        $server = Client::getServer();
        return Transaction::fromXdr($xdr)->toTransactionBuilder($server);
    }
}