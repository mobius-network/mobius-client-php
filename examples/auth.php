<?php

// Define STELLAR_PUBLICNET
define('STELLAR_PUBLICNET', false); // System is testnet. Change it to true for publicnet

// Define SECRET_KEY
define('SECRET_KEY', 'SDGHOTL4QLVXNT6JKG444AY2YWCBBAZW3INXH3RBPPO2ZMFE7GRBAKFX');

// Define JWT Secret
define('JWT_SECRET', '431714aa54beec753975eaffba3db12d43d4ee52cafeb3ddcdbea05903e3a3ee78ff1f49d56b23df16597bc15f6d6099aef2f668aa38f957ffc960a5445aa8fb');

include '../vendor/autoload.php';

if(!isset($_GET['xdr'])){
    $ch = Mobius\Client\Auth\Challenge::generate_challenge(SECRET_KEY, $expire_in);
    echo $ch;
}
else if(isset($_GET['xdr']) && $_GET['public_key']){

    $xdr = base64_decode($_GET['xdr']); // It must be base64 decoded to pass to Token class

    $public_key = $_GET['public_key'];   

    $token = new Mobius\Client\Auth\Token(SECRET_KEY, $xdr, $public_key);

    $token->validate(); // Validate Token


    $jwt_token = new Mobius\Client\Auth\JWT(JWT_SECRET);
    echo $jwt_token->encode($token); // We can also store the token string in PHP $_SESSION
}
die;