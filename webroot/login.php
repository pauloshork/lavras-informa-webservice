<?php

require_once dirname(__DIR__) . '/src/base.php';

$email = 'oi';
$senha = 'oi';

use Connectors\DatabaseConnector;

$dbc = new DatabaseConnector($config);

try {
	$token = $dbc->autenticar($email, $senha);
	$msg = ['token' => $token];
} catch (Exception $e) {
	$msg = ['error' => $e->getMessage()];
}

echo json_encode($msg);