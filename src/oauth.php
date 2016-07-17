<?php

namespace Database;

use OAuth2\Autoloader;

class OAuth {

	function __construct() {


		Autoloader::register();

		// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
		$storage = new OAuth2\Storage\Pdo($config['oauth']);

		// Pass a storage object or array of storage objects to the OAuth2 server class
		$this->server = new OAuth2\Server($storage);

		$this->server->addGrantType(new OAuth2\GrantType\UserCredentials($storage));

		// Add the "Client Credentials" grant type (it is the simplest of the grant types)
		$this->server->addGrantType(new OAuth2\GrantType\Implicit($storage));

		// Add the "Authorization Code" grant type (this is where the oauth magic happens)
		$this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

	}

	function token() {
		
	}

	function authorize() {

	}

	function resource() {

	}
}