<?php
namespace Mobius\Client\Auth;

use \Mobius\Client;
use \ZuluCrypto\StellarSdk\Server;
use \ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope;
use \ZuluCrypto\StellarSdk\XdrModel\TimeBounds;
use \ZuluCrypto\StellarSdk\Transaction\Transaction;
use \ZuluCrypto\StellarSdk\Keypair;
use \ZuluCrypto\StellarSdk\Xdr\XdrBuffer;
use \DateTime;
use \DateInterval;
use \DateTimeZone;

class Token{

    /**
     * Developers private key
     * 
     * @var string
     */
    public $seed;

    /**
     * Auth transaction XDR
     * 
     * @var string
     */
    public $xdr;

    /**
     * User public key
     * 
     * @var string
     */
    public $address;

    public $transaction;

    public $envelope;

    public $keypair;

    public $their_keypair;

    public $time_bounds;

    /**
     * Constructor
     * 
     * @param string $seed
     * @param string|XdrBuffer  if is string then must be base64 decoded.
     */
    public function __construct($seed, $xdr, $address){
        $this->seed = $seed;
        if(is_a($xdr, 'XdrBuffer')){
            $this->xdr = $xdr;
        }
        else{
            $this->xdr = new XdrBuffer($xdr);
        }
        $this->address = $address;
    }

    /**
     * Validates transaction signed by developer and user.
     * 
     * @return boolean true if transaction is valid, raises exception otherwise 
     */
    public function validate($strict = true){
        $time_bounds = $this->time_bounds();
        $current_time = new DateTime("now", new \DateTimeZone("UTC"));
        if(!$this->time_now_covers($time_bounds)){
            throw new \Exception('Token expired!');
        }
        if($strict && $this->too_old($time_bounds)){
            throw new \Exception('Token too old!');
        }
        return true;
    }


    /**
     * Transation from xdr
     * 
     * @return Transaction
     */
    public function getTransaction(){
        if(!$this->transaction){
            $xdr = clone $this->xdr; // There is some problem with Zulucrypto Stellar SDK. So we have to clone xdr.
            $this->transaction = Transaction::fromXdr($this->xdr);
            $this->xdr = $xdr;
        }
        return $this->transaction;
    }

    /**
     * @return string transaction hash
     */
    public function hash($format = 'binary'){

        $this->validate();

        $builder = $this->builder();
        $hash = $builder->hash();
        if($format == 'binary'){
            return $hash;
        }
        else if($format == 'hex'){
            return unpack("H*", $hash)[1];
        }

        return false;
    }

    /**
     * @return TransactionEnvelope TransactionEnvelope of challenge transaction
     */
    public function envelope(){
        if(!$this->envelope){
            $xdr = clone $this->xdr; // There is some problem with Zulucrypto Stellar SDK. So we have to clone xdr.
            $this->envelope = TransactionEnvelope::fromXdr($this->xdr);
            $this->xdr = $xdr;
        }
        return $this->envelope;
    }

    /**
     * @return Keypair KeyPair object for given seed
     */
    public function keypair(){
        if(!$this->keypair){
            $this->keypair = Keypair::newFromSeed($this->seed);
        }
        return $this->keypair;
    }

    /**
     * @return Keypair Keypair of user being authorized
     */
    public function their_keypair(){
        if(!$this->their_keypair){
            $this->their_keypair = new Keypair();
            $this->their_keypair->setPublicKey($this->address);
        }
        return $this->their_keypair;
    }

    /**
     * Verifies Keypairs are signed correctly
     * 
     * @return boolean true if transaction is signed by both parties
     */
    public function signed_correctly(){
        $hash = $this->builder()->hash();
        $signatures = $this->envelope()->getDecoratedSignatures();
        if(!$signatures){
            return false;
        }
        $keypairs = array(
            $this->keypair(),
            $this->their_keypair()
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

    /**
     * @return TransactionBuilder
     */
    public function builder(){
        $server = Client::getServer();
        return $this->getTransaction()->toTransactionBuilder($server);
    }

    /**
     * Returns time bounds for given transaction.
     * 
     * @return TimeBounds Time bounds for given transaction
     */
    public function time_bounds(){
        if(!$this->time_bounds){
            $this->time_bounds = $this->getTransaction()->getTimeBounds();
        }
        if(!$this->signed_correctly()){
            throw new \Exception('Given transaction signature invalid');
        }
        if($this->time_bounds->isEmpty()){
            throw new \Exception('MalformedTransaction');
        }
        return $this->time_bounds;
    }

    /**
     * @return boolean true if current time is within transaction time bounds
     */
    public function time_now_covers($time_bounds){
        $current_time = new DateTime("now", new \DateTimeZone("UTC"));
        if($current_time > $time_bounds->getMinTime() && $current_time < $time_bounds->getMaxTime()){
            return true;
        }
        return false;
    }

    /**
     * @return boolean true if transaction is created more than n secods from now
     */
    public function too_old($time_bounds){
        $current_time = new DateTime("now", new \DateTimeZone("UTC"));
        $min_time = clone $time_bounds->getMinTime();
        $min_time->add(new DateInterval('PT'.Client::STRICT_INTERVAL.'S'));
        if($current_time > $min_time){
            return true;
        }
        return false;
    }

}
