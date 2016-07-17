<?php
require_once 'base.php';
require_once 'facebook.php';

$facebookUsuario = json_decode($_POST['json']);
$accessToken = $facebookUsuario['token'];
$fb->setDefaultAccessToken($accessToken);


