<?php
namespace Mobius\Client\Auth;

use \Mobius\Client;
use \phpseclib\Math\BigInteger;
use \ZuluCrypto\StellarSdk\Keypair;
use \ZuluCrypto\StellarSdk\Server;
use \ZuluCrypto\StellarSdk\XdrModel\Memo;
use ZuluCrypto\StellarSdk\XdrModel\DecoratedSignature;
use \ZuluCrypto\StellarSdk\Signing;
use \ZuluCrypto\StellarSdk\Util\Hash;
use \ZuluCrypto\StellarSdk\Transaction\TransactionBuilder;
use \ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope;
use \ZuluCrypto\StellarSdk\Xdr\XdrBuffer;
use \Base32\Base32;
use \DateTime;
use \DateInterval;
use \DateTimeZone;

class Challenge {

    /**
     * Developers private key
     * 
     * @var string
     */
    public $seed;

    private $keypair;

    CONST MAX_SEQ_NUMBER = 2**63 - 1;

    CONST RANDOM_LIMITS = 65535;

    /**
     * Generates challenge transaction signed by developers private key. Minimum valid time bound is set to current time.
     * Maximum valid time bound is set to `expire_in` seconds from now.
     * 
     * @param string $seed
     * @param int $expire_in Session expiration time (seconds from now). 0 means "never"
     * @return string base64-encoded transaction envelope
     */
    public static function generate_challenge($seed, $expire_in = Client::CHALLENGE_EXPIRES_IN){

        $server = Client::getServer();

        // Source Keypair
        $sourceKeypair = Keypair::newFromSeed($seed);

        // Destination Keypair
        $destinationKeypair = Keypair::newFromSeed($seed);

        // Random Keypair
        $randomKeypair = Keypair::newFromRandom();

        // Memo
        $memo = new Memo(1, "Mobius authentication");

        // Create Transaction for amount 1 XLM
        $txBuilder = $server->buildTransaction($randomKeypair)
                              ->addLumenPayment($destinationKeypair, 1, $sourceKeypair);

        // Set Sequence Number                              
        $txBuilder->setSequenceNumber(Challenge::random_sequence());

        // Set memo
        $txBuilder->setMemo($memo);

        //Set Lower Timebound
        $date = new DateTime("now", new \DateTimeZone("UTC"));
        $txBuilder->setLowerTimebound($date);

        // Set Upper Timebound
        $date->add(new DateInterval('P1D'));
        $txBuilder->setUpperTimebound($date);

        // Sign Transaction with Keypair
        $txEnvelope = $txBuilder->sign($sourceKeypair);
        
        // Convert Envevelop to Base64 XDR
        $base64 = $txEnvelope->toBase64();
        
        return $base64;
    }

    /**
     * @return int Random sequence number
     */
    public static function random_sequence(){
        $seq = Challenge::MAX_SEQ_NUMBER - rand(0, Challenge::RANDOM_LIMITS);
        $seq = number_format($seq, 0, '', '');
        $a =  new BigInteger($seq);
        return $a;
    }

    /**
     * @return object Keypair
     */
    public function keypair(){
        if(!$this->keypair){
            $this->keypair = Keypair::newFromSeed($this->seed);
        }
        return $this->keypair;
    }

}