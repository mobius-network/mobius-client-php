<?php
namespace Mobius\Blockchain;

use \ZuluCrypto\StellarSdk\Keypair;
use \ZuluCrypto\StellarSdk\Transaction\TransactionBuilder;
use \ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope;
use \ZuluCrypto\StellarSdk\XdrModel\Operation\Operation;
use \ZuluCrypto\StellarSdk\XdrModel\Operation\SetOptionsOp;

class AddCosigner{

    public static function call($keypair, $consigner_keypair, $weight = 1){
        return AddCosigner::tx($keypair, $consigner_keypair, $weight)->submit($keypair);
    }

    public static function tx($keypair, $consigner_keypair, $weight){
        $txBuilder = Client::getServer()->buildTransaction($keypair);
        $txBuilder->setSequenceNumber((new Account($keypair))->next_sequence_value());

        $operation = new SetOptionsOperation(null, null);
        $operation->setSignerKey($keypair->getPublicKey());
        $operation->setSignerWeight($weight);
        $operation->setMasterKeyWeight(10);
        $operation->setHighThreshold(10);
        $operation->setMediumThreshold(1);
        $operation->setLowThreshold(1);
        $txBuilder->addOperation($operation);

        return $txBuilder;
    }

}