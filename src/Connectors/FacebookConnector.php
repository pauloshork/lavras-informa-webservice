<?php

namespace Connectors;

use Facebook\Facebook;

class FacebookConnector extends DatabaseConnector {
	public function __construct(array $config = []) {
		$config = array_merge_recursive ( [ 
				'schema' => [ 
						'user_data' => 'facebook_oauth' 
				] 
		], $config );
		parent::__construct ( $config );
		$this->fb = new Facebook ( $config ['facebook'] );
	}
	public function getUser($user_id) {
		$sql = sprintf ( 'SELECT * FROM %s AS t INNER JOIN %s AS d 
				ON t.id = d.id_usuario 
				WHERE d.userid=:user_id', $this->config ['user_table'], $this->config ['user_data'] );
		$stmt = $this->db->prepare ( $sql );
		$stmt->execute ( compact ( 'user_id' ) );
		
		if (! $userInfo = $stmt->fetch ( \PDO::FETCH_ASSOC )) {
			return false;
		}
		
		// the default behavior is to use "username" as the user_id
		return array_merge ( array (
				'user_id' => $user_id
		), $userInfo );
	}
	public function setUser($id_usuario, $user_id, $admin = false) {
	}
	public function autenticar($userId, $accessToken) {
		$helper = $fb->getRedirectLoginHelper ();
		
		echo '<h3>Access Token</h3>';
		var_dump ( $accessToken->getValue () );
		
		$oac = $fb->getOAuth2Client ();
		$metadata = $oac->debugToken ( $accessToken );
		echo '<h3>Metadata</h3>';
		var_dump ( $tokenMetadata );
		
		$metadata->validateAppId ( $config ['facebook'] ['app_id'] );
		$metadata->validateUserId ( $userId );
		$metadata->validateExpiration ();
		
		if (! $accessToken->isLongLived ()) {
			// Exchanges a short-lived access token for a long-lived one
			try {
				$accessToken = $oAuth2Client->getLongLivedAccessToken ( $accessToken );
			} catch ( Facebook\Exceptions\FacebookSDKException $e ) {
				echo "<p>Error getting long-lived access token: " . $helper->getMessage () . "</p>\n\n";
				exit ();
			}
			
			echo '<h3>Long-lived</h3>';
			var_dump ( $accessToken->getValue () );
		}
		
		// TODO recuperar do banco o usuario que tem o user_id especificado acima
		// TODO criar conta caso o usuário não seja cadastrado
		// TODO gerar um token de acesso do nosso sistema para o usuário
	}
}