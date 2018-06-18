<?php
namespace Mobius\Blockchain;

use \ZuluCrypto\StellarSdk\Keypair;

class FriendBot{

    public function call($keypair){
        return file_get_contents('https://horizon-testnet.stellar.org/friendbot?addr=' . $keypair->getPublicKey());
    }
}
    