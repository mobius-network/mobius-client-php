<?php
include 'flappy/config.php';

include '../../autoload.php';

$expire_in = 86400;
if(!isset($_GET['xdr'])){
    $ch = Mobius\Client\Auth\Challenge::generate_challenge(SECRET_KEY, $expire_in);
    echo $ch;
}
else if(isset($_GET['xdr']) && $_GET['public_key']){
    $xdr = base64_decode($_GET['xdr']);
    $public_key = $_GET['public_key'];   
    
    $token = new Mobius\Client\Auth\Token(SECRET_KEY, $xdr, $public_key);
    $token->validate();

    $jwt_token = new Mobius\Client\Auth\JWT(JWT_SECRET);
    echo $jwt_token->encode($token);
}
die;