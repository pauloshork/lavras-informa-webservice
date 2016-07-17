<?php
require_once 'base.php';
require_once 'config.php';

use Facebook\Facebook;

namespace Connectors;

class FacebookConnector extends DatabaseConnector {

	function __construct() {
		$this->fb = new Facebook($config['facebook']);
	}

	public function autenticar($userId, $accessToken) {
		$helper = $fb->getRedirectLoginHelper();

		echo '<h3>Access Token</h3>';
		var_dump($accessToken->getValue());

		$oac = $fb->getOAuth2Client();
		$metadata = $oac->debugToken($accessToken);
		echo '<h3>Metadata</h3>';
		var_dump($tokenMetadata);

		$metadata->validateAppId($config['facebook']['app_id']);
		$metadata->validateUserId($userId);
		$metadata->validateExpiration();

		if (!$accessToken->isLongLived()) {
			// Exchanges a short-lived access token for a long-lived one
			try {
				$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			} catch (Facebook\Exceptions\FacebookSDKException $e) {
				echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
				exit;
			}

			echo '<h3>Long-lived</h3>';
			var_dump($accessToken->getValue());
		}
	}
}