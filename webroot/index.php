<?php
require_once dirname ( __DIR__ ) . '/src/base.php';

use Connectors\DatabaseConnector;
use Connectors\OAuth;
use Connectors\FacebookConnector;

// Mapeamento de caminhos e controladores
$map = [ 
		'/' => 'home',
		'/init' => 'init',
		'/cadastro' => 'cadastro',
		'/login' => 'login',
		'/loginFacebook' => 'loginFacebook',
		'/relatos' => null
];

// Leitura do caminho
if (array_key_exists ( 'PATH_INFO', $_SERVER )) {
	$path = $_SERVER ['PATH_INFO'];
} else {
	$path = '/';
}

// Ativação do controlador
call_user_func ( $map [$path], $config );

/*
 * Tela inicial do webservice.
 */
function home(array $config) {
	echo 'o sistema está online';
}
function init(array $config) {
	$storage = new DatabaseConnector($config);
	$storage->create_database();
}
/*
 * Controlador de cadastro.
 */
function cadastro(array $config) {
	$email = 'oi';
	$senha = 'oi';
	$nome = 'oi';
	
	$storage = new DatabaseConnector ( $config );
	if (!$storage->getUser($email)) {
		$storage->setUser($email, $senha, $nome);
		login($config);
	} else {
		$msg = ['error' => 'Email está em uso.'];
		echo json_encode($msg);
	}
}
/*
 * Controlador de login.
 */
function login(array $config) {
	$email = 'oi';
	$senha = 'oi';
	
	$storage = new DatabaseConnector ( $config );
	$auth = new OAuth($storage);
	
	$auth->token();
	/*try {
		$token = $dbc->autenticar ( $email, $senha );
		$msg = [ 
				'token' => $token 
		];
	} catch ( Exception $e ) {
		$msg = [ 
				'error' => $e->getMessage () 
		];
	}
	
	echo json_encode ( $msg );*/
}
/*
 * Controlador de login pelo facebook.
 */
function loginFacebook(array $config) {
	$user_id = 'oi';
	$token = 'oi';
	
	$storage = new FacebookConnector( $config );
	$auth = new OAuth($storage);
	
	$auth->token();
}