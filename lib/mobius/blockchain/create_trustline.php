<?php
namespace Mobius\Blockchain;

use \Mobius\Client;
use \ZuluCrypto\StellarSdk\XdrModel\Asset;
use \ZuluCrypto\StellarSdk\XdrModel\Operation\ChangeTrustOp;
use \ZuluCrypto\StellarSdk\XdrModel\Operation\Operation;
use \ZuluCrypto\StellarSdk\Model\AssetAmount;
use \ZuluCrypto\StellarSdk\Transaction\TransactionBuilder;


class CreateTrustline{

    public static function call($keypair, $asset = null){
        if(!$asset){
            $asset = (new Client())->stellar_asset();
        }
        return CreateTrustline::tx()->submit($keypair);
    }
    
    public static function tx($keypair, $asset){
        $txBuilder = Client::getServer()->buildTransaction($keypair);
        $txBuilder->setSequenceNumber((new Account($keypair))->next_sequence_value());
        $operation = new ChangeTrustOp(null, null);
        $operation->setAsset($asset);
        $txBuilder->addOperation($operation);
        return $txBuilder;
    }
}