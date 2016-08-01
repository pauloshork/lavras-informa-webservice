<?php
require_once dirname(__DIR__) . '/src/base.php';

use Connectors\LocalConnector;
use Connectors\OAuth;

// Mapeamento de caminhos e controladores
$map = [
    '/' => 'home',
    '/init' => 'init',
    '/cadastro' => 'cadastro',
    '/login' => 'login',
    '/loginFacebook' => 'loginFacebook',
    '/usuario' => 'usuario',
    '/relatos' => 'relatos',
    '/relatos/set' => 'set_relato',
    '/comentarios' => 'comentarios',
    '/comentarios/set' => 'set_comentario',
    '/imagem' => 'imagem'
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
    $storage = new LocalConnector(\Config::config);
    $storage->drop_database();
    $storage->create_database();
}

/**
 * Controlador de cadastro.
 */
function cadastro()
{
    $auth = new OAuth('local');
    $auth->cadastroLocal();
}

/**
 * Controlador de login.
 */
function login()
{
    $auth = new OAuth('local');
    $auth->token();
}

/**
 * Controlador de login pelo facebook.
 */
function loginFacebook()
{
    $auth = new OAuth('facebook');   
    $auth->token();
}

function usuario() {
    $auth = new OAuth();
    $auth->usuario();
}

/**
 * Controlador de busca de relatos no sistema.
 */
function relatos() {
    $auth = new OAuth();
    $auth->lista_relatos();
}

/**
 * Controlador de registro de relatos no sistema.
 */
function set_relato() {
    $auth = new OAuth();
    $auth->set_relato();
}

/**
 * Controlador de busca de comentarios no sistema.
 */
function comentarios() {
    $auth = new OAuth();
    $auth->lista_comentarios();
}

/**
 * Controlador de busca de comentarios no sistema.
 */
function set_comentario() {
    $auth = new OAuth();
    $auth->set_comentario();
}

function imagem() {
    $auth = new OAuth();
    $auth->get_foto();
}
