<?php
include '../config.php';

include '../../../../autoload.php';

use \ZuluCrypto\StellarSdk\XdrModel\TransactionEnvelope;
use \ZuluCrypto\StellarSdk\Xdr\XdrBuffer;

$expire_in = 86400;

$token = $_GET['token'];
$jwt = new Mobius\Client\Auth\Jwt(JWT_SECRET);
$token = $jwt->decode($token);  

$price = 5;
$app = new Mobius\Client\App(SECRET_KEY, $token->public_key);
$app->charge($price);
echo $app->user_balance();
die;