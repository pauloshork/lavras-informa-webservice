<?php
require_once dirname(__DIR__) . '/src/base.php';

use Connectors\LocalConnector;
use Connectors\OAuth;
use Connectors\FacebookConnector;
use Connectors\ConnectorException;
use Models\Usuario;

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
if (array_key_exists('PATH_INFO', $_SERVER)) {
    $path = $_SERVER['PATH_INFO'];
} else {
    $path = '/';
}

// Ativação do controlador
call_user_func(getController($path, $map));

function getController($name, $map)
{
    if (isset($map[$name])) {
        if (! is_null($map[$name])) {
            return $map[$name];
        } else {
            return 'not_implemented';
        }
    } else {
        return 'fallback';
    }
}

function not_implemented()
{
    http_response_code(404);
    echo 'not implemented yet';
}

function fallback()
{
    http_response_code(404);
    echo 'does not exist';
}

/**
 * Tela inicial do webservice.
 */
function home()
{
    echo 'the system is online';
}

function init()
{
    $storage = new LocalConnector(\Config::$config);
    $storage->drop_database();
    $storage->create_database();
}

/**
 * Controlador de cadastro.
 */
function cadastro()
{
    $storage = new LocalConnector(\Config::$config);

    $u = new Usuario();
//     echo $_SERVER['CONTENT_TYPE'];
    switch ($_SERVER['CONTENT_TYPE']) {
        case 'application/json':
            $json = json_decode(file_get_contents('php://input'));
            $u->setEmail($json->email);
            $u->setSenha($json->senha);
            $u->setNome($json->nome);
            break;
        case 'application/x-www-form-urlencoded':
            $u->setEmail($_POST['email']);
            $u->setSenha($_POST['senha']);
            $u->setNome($_POST['nome']);
            break;
        default:
            unset($u);
            echo '{"error":{"message":json ou urlencoded}}';
    }
    
    if (isset($u)) {
        if (! $storage->getUser($u->getEmail())) {
            try {
                $storage->setUser($u);
            } catch (ConnectorException $e) {
                echo $e->toJson();
            }
        } else {
            $e = new ConnectorException('O email fornecido já foi cadastrado');
            echo $e->toJson();
        }
    }
}

/**
 * Controlador de login.
 */
function login()
{
    $storage = new LocalConnector(\Config::$config);
    $auth = new OAuth($storage);
    
    $auth->token();
}

/**
 * Controlador de login pelo facebook.
 */
function loginFacebook()
{
    $storage = new FacebookConnector(\Config::$config);
    $auth = new OAuth($storage);
    
    $auth->token();
}
