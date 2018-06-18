<?php

namespace Mobius\Blockchain;

use \ZuluCrypto\StellarSdk\Keypair;
use \ZuluCrypto\StellarSdk\SignerKey;

class KeyPairFactory{

    /**
     * Generates Keypair from subject
     * 
     * @param string|Account|Keypair|SignerKey
     * 
     * @throws Exception  Unkonwn keypair type
     */
    public static function produce($subject){
        if(is_string($subject)){
            return KeyPairFactory::from_string($subject);
        }
        else if(is_a($subject, 'Account')){
            return $subject->keypair;
        }
        else if(is_a($subject, 'Keypair')){
            return $subject;
        }
        else if(is_a($subject, 'SignerKey')){
            return from_secret_key($subject);
        }
        else{
            throw new \Exception("Unknown KeyPair type");
        }
        return false;
    }
    
    public static function from_string($subject){
        if($subject[0] == "S"){
           return Keypair::newFromSeed($subject);
        }
        else{
            $keypair = new Keypair();
            $keypair->setPublicKey($subject);
            return $keypair;
        }

    }

    public static function from_secret_key($subject){
        return Keypair::newFromRawSeed($subject->getKey());
    }

}
