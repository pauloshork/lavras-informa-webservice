<?php

namespace Connectors;

use OAuth2\Autoloader;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Request;
use OAuth2\Server;
use OAuth2\Storage\Pdo;

class OAuth {

	function __construct() {


		Autoloader::register();

		// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
		$storage = new Pdo($config['oauth']);

		// Pass a storage object or array of storage objects to the OAuth2 server class
		$this->server = new Server($storage);

		$this->server->addGrantType(new UserCredentials($storage));

		// Add the "Authorization Code" grant type (this is where the oauth magic happens)
		$this->server->addGrantType(new AuthorizationCode($storage));

	}

	/*
	 * Permite que usuários façam login no sistema.
	 */
	function token() {
		$request = Request::createFromGlobals();
		$response = $this->server->handleAuthorizeRequest($request);
		$response->send();
	}

	/*
	 * Permite que usuários acessem os relatos no sistema.
	 */
	function resource() {
		
	}
}