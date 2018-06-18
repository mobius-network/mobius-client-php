<?php

namespace Mobius\Client\Auth;

use \Firebase\JWT\JWT as FirebaseJWT;

class JWT{

    /**
     * JWT secret
     * 
     * @var string
     */
    public $secret;

    public function __construct($secret){
        $this->secret = $secret;
    }

    /**
     * Returns JWT token
     * 
     * @param Token $token
     * @return string
     */
    public function encode($token){
        $time_bounds = $token->time_bounds();
        $payload = array(
            'hash'  => $token->hash('hex'),
            'public_key'    => $token->address,
            'min_time'  => $token->time_bounds()->getMinTime()->getTimeStamp(),
            'max_time'  => $token->time_bounds()->getMaxTime()->getTimestamp(),
        );

        return FirebaseJWT::encode($payload, $this->secret, 'HS512');
    }

    /**
     * Returns decoded JWT token
     * 
     * @param string $token
     * @return object The JWT's payload as a PHP object
     */
    public function decode($token){
        $decoded = FirebaseJWT::decode($token, $this->secret, array('HS512'));
        return $decoded;
    }

}
