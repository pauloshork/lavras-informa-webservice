<?php

namespace Connectors;

use OAuth2\Autoloader;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Request;
use OAuth2\Server;
use OAuth2\Response;

class OAuth {

	function __construct($storage) {
		Autoloader::register();
		$this->server = new Server();
		$this->server->setConfig('enforce_state', false);
		$this->server->addStorage($storage);
		$this->server->addGrantType(new UserCredentials($storage));
	}

	/*
	 * Permite que usuários façam login no sistema.
	 */
	function token() {
		$request = Request::createFromGlobals();
		$response = $this->server->handleTokenRequest($request);
		$response->send();
	}

	/*
	 * Permite que usuários acessem os relatos e os comentários no sistema.
	 */
	function resource() {
		// Handle a request to a resource and authenticate the access token
		$request = Request::createFromGlobals();
		if (!$this->server->verifyResourceRequest($request)) {
			$server->getResponse()->send();
		} else {
			// TODO processar parametros para selecionar recursos
			echo json_encode(array('success' => true, 'message' => 'You accessed my APIs!'));
		}
	}
}