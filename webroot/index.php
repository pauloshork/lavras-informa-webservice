<?php
require_once dirname(__DIR__) . '/src/base.php';

use Connectors\DatabaseConnector;

$map = [
	'/login' => null,
	'/loginFacebook' => null,
];

$path = $_SERVER['PATH_INFO'];

call_user_func($map[$path]);

function login() {
	$email = 'oi';
	$senha = 'oi';
	
	$dbc = new DatabaseConnector($config);
	
	try {
		$token = $dbc->autenticar($email, $senha);
		$msg = ['token' => $token];
	} catch (Exception $e) {
		$msg = ['error' => $e->getMessage()];
	}
	
	echo json_encode($msg);
}

function loginFacebook() {
	
}