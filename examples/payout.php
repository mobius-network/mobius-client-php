<?php
// Define STELLAR_PUBLICNET
define('STELLAR_PUBLICNET', false); // System is testnet. Change it to true for publicnet

// Define SECRET_KEY
define('SECRET_KEY', 'SDGHOTL4QLVXNT6JKG444AY2YWCBBAZW3INXH3RBPPO2ZMFE7GRBAKFX');

// Define JWT Secret
define('JWT_SECRET', '431714aa54beec753975eaffba3db12d43d4ee52cafeb3ddcdbea05903e3a3ee78ff1f49d56b23df16597bc15f6d6099aef2f668aa38f957ffc960a5445aa8fb');

include_once '../vendor/autoload.php';

$token = $_GET['token']; // Get it from $_SESSION if you have stored there.
$jwt = new Mobius\Client\Auth\Jwt(JWT_SECRET);
$token = $jwt->decode($token);  

$price = 5; // Lets payout for amount 5
$app = new Mobius\Client\App(SECRET_KEY, $token->public_key);
$app->payout($price, $third_party_address); // Send balance from app account to third party address
die;