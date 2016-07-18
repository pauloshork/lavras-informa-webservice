<?php

namespace Connectors;

use Facebook\Facebook;
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookSDKException;

class FacebookConnector extends DatabaseConnector {
	protected $fb;
	public function __construct(array $config = []) {
		$config = array_merge ( [ 
				'facebook' => [ ] 
		], $config );
		parent::__construct ( $config );
		$this->config ['facebook'] = $config ['facebook'];
		$this->fb = new Facebook ( $config ['facebook'] );
	}
	/* UserCredentials Interface */
	public function checkUserCredentials($username, $password) {
		if ($this->checkToken ( $username, $password )) {
			if (! $this->getUser ( $username )) {
				return $this->setUser ( $username );
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	public function getUserDetails($username) {
		return $this->getUser ( $username );
	}
	protected function checkToken($user_id, $token) {
		$accessToken = new AccessToken ( $token );
		
		//echo '<h3>Access Token</h3>';
		//var_dump ( $accessToken->getValue () );
		
		$oac = $this->fb->getOAuth2Client ();
		$metadata = $oac->debugToken ( $accessToken );
		//echo '<h3>Metadata</h3>';
		//var_dump ( $metadata );
		
		try {
			$metadata->validateAppId ( $this->config ['facebook'] ['app_id'] );
			$metadata->validateUserId ( $user_id );
			$metadata->validateExpiration ();
		} catch ( FacebookSDKException $ex ) {
			//echo '<p> Error validating access token: ' . $ex->getMessage () . "</p>\n\n";
			return false;
		}
		
		if (! $accessToken->isLongLived ()) {
			// Exchanges a short-lived access token for a long-lived one
			try {
				$accessToken = $oac->getLongLivedAccessToken ( $accessToken );
			} catch ( FacebookSDKException $e ) {
				//echo '<p>Error getting long-lived access token: ' . $ex->getMessage () . "</p>\n\n";
				return false;
			}
			
			//echo '<h3>Long-lived</h3>';
			//var_dump ( $accessToken->getValue () );
		}
		
		return true;
	}
	public function getUser($user_id) {
		$sql = sprintf ( 'SELECT * FROM %s AS t INNER JOIN %s AS d 
				ON t.id = d.id_usuario 
				WHERE d.fb_user_id=:user_id', $this->config ['user_table'], $this->config ['facebook_data'] );
		$stmt = $this->db->prepare ( $sql );
		$stmt->execute ( compact ( 'user_id' ) );
		
		if (! $userInfo = $stmt->fetch ( \PDO::FETCH_ASSOC )) {
			return false;
		}
		
		// the default behavior is to use "username" as the user_id
		return array_merge ( array (
				'user_id' => $userInfo ['id'] 
		), $userInfo );
	}
	public function setUser($user_id, $admin = null) {
		// if it exists, update it.
		if ($this->getUser ( $user_id )) {
			if (! is_null ( $admin )) {
				$sql = sprintf ( 'UPDATE %s as t, %s as d INNER JOIN t
						ON t.id = d.id_usuario
						SET t.admin=:admin
						WHERE d.fb_user_id=:user_id', $this->config ['user_table'], $this->config ['facebook_data'] );
				$stmt = $this->db->prepare ( $sql );
				return $stmt->execute ( compact ( 'user_id', 'admin' ) );
			} else {
				return true;
			}
		} else {
			$admin = $admin || false;
			try {
				$this->db->beginTransaction ();
				$sql = sprintf ( 'INSERT INTO %s (admin) 
						VALUES (:admin)', $this->config ['user_table'] );
				$stmt = $this->db->prepare ( $sql );
				$execute = $stmt->execute ( compact ( 'admin' ) );
				if (! $execute) {
					throw new \Exception ( $stmt->errorInfo () );
				}
				$sql = sprintf ( 'INSERT INTO %s (id_usuario, fb_user_id) 
						VALUES (LAST_INSERT_ID(), :user_id)', $this->config ['facebook_data'] );
				$stmt = $this->db->prepare ( $sql );
				$execute = $stmt->execute ( compact ( 'user_id' ) );
				if (! $execute) {
					throw new \Exception ( $stmt->errorInfo () );
				}
				$this->db->commit ();
			} catch ( \Exception $ex ) {
				$this->db->rollBack ();
				throw $ex;
				return false;
			}
		}
	}
}
