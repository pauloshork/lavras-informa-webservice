<?php
namespace Connectors;

use Facebook\Facebook;
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Models\Usuario;

class FacebookConnector extends BaseConnector
{

    protected $fb;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $config = array_merge([
            'facebook' => []
        ], $config);
        $this->config['facebook'] = $config['facebook'];
        $this->fb = new Facebook($config['facebook']);
    }

    public function checkUserCredentials($username, $password)
    {
        if ($this->checkToken($username, $password)) {
            $user = $this->getUser($username);
            if (! $user) {
                $user = new Usuario();
                $user->setFbUserId($username);
            }
            $fb_user = $this->fetchUserData();
            $user->setFbEmail($fb_user->getEmail());
            $user->setFbNome($fb_user->getName());
            
            return $this->setUser($user);
        } else {
            return false;
        }
    }

    public function checkToken($user_id, $token)
    {
        $accessToken = new AccessToken($token);
        $this->fb->setDefaultAccessToken($accessToken);
        
        // echo '<h3>Access Token</h3>';
        // var_dump ( $accessToken->getValue () );
        
        $oac = $this->fb->getOAuth2Client();
        $metadata = $oac->debugToken($accessToken);
        // echo '<h3>Metadata</h3>';
        // var_dump ( $metadata );
        
        try {
            $metadata->validateAppId($this->config['facebook']['app_id']);
            $metadata->validateUserId($user_id);
            $metadata->validateExpiration();
        } catch (FacebookSDKException $ex) {
//             echo '<p> Error validating access token: ' . $ex->getMessage () . "</p>\n\n";
            throw new ConnectorException('FacebookSDKException: ' . $ex->getMessage(), $ex->getCode(), $ex);
            return false;
        }
        
        if (! $accessToken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one
            try {
                $accessToken = $oac->getLongLivedAccessToken($accessToken);
                $this->fb->setDefaultAccessToken($accessToken);
            } catch (FacebookSDKException $ex) {
//                 echo '<p>Error getting long-lived access token: ' . $ex->getMessage () . "</p>\n\n";
                throw new ConnectorException('FacebookSDKException: ' . $ex->getMessage(), $ex->getCode(), $ex);
                return false;
            }
            
            // echo '<h3>Long-lived</h3>';
            // var_dump ( $accessToken->getValue () );
        }
        
        return true;
    }

    protected function fetchUserData()
    {
        try {
            $response = $this->fb->get('/me?fields=id,name,email');
        } catch (FacebookResponseException $e) {
            throw new ConnectorException('FacebookResponseException: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (FacebookSDKException $e) {
            throw new ConnectorException('FacebookSDKException: ' . $e->getMessage(), $e->getCode(), $e);
        }
        
        return $response->getGraphUser();
    }

    public function getUser($user_id)
    {
        if ($user = $this->getUserOn('f.fb_user_id', $user_id)) {
            return $user;
        }
        return false;
    }
}
