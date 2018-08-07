<?php
include '../config.php';

include_once '../../../../autoload.php';

$token = $_GET['token'];
$jwt = new Mobius\Client\Auth\Jwt(JWT_SECRET);
$token = $jwt->decode($token);  

$app = new Mobius\Client\App(SECRET_KEY, $token->public_key);
echo $app->balance();
die;