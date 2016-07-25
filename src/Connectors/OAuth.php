<?php
namespace Connectors;

use Models\BuscaComentario;
use Models\BuscaRelato;
use Models\Comentario;
use Models\Relato;
use Models\Usuario;
use OAuth2\Autoloader;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Request;
use OAuth2\RequestInterface;
use OAuth2\Response;
use OAuth2\ResponseInterface;
use OAuth2\Scope;
use OAuth2\Server;
use OAuth2\Storage\Memory;

class OAuth
{

    const scope = [
        'supported_scopes' => [
            'usuario',
            'lista_relatos',
            'set_relato',
            'lista_comentarios',
            'set_comentario'
        ]
    ];

    private $server;

    private $storage;

    public function __construct($user_type = null)
    {
        $memoryStorage = new Memory([
            'client_credentials' => \Config::config['client_storage'],
            'scope' => static::scope
        ]);
        
        Autoloader::register();
        
        $config = \Config::config;
        switch ($user_type) {
            case 'facebook':
                $this->storage = new FacebookConnector($config);
                break;
            default:
                $this->storage = new LocalConnector($config);
        }
        
        $this->server = new Server();
        $this->server->setConfig('enforce_state', false);
        
        $this->server->addStorage($this->storage);
        $this->server->addStorage($memoryStorage, 'client_credentials');
        $this->server->addStorage($memoryStorage, 'scope');
        
        $this->server->addGrantType(new UserCredentials($this->storage));
    }

    /**
     * Permite que usuários façam login no sistema.
     */
    public function token()
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        try {
            $response = $this->server->handleTokenRequest($request, $response);
        } catch (ConnectorException $e) {
            $response->setError(500, 'connector_exception', $e->getMessage());
        }
        $response->send();
    }

    protected function validateRequestSetUsuario(RequestInterface $request, ResponseInterface $response)
    {
        if (! $request->request('email')) {
            $response->setError(400, 'malformed_request', 'O campo \'email\' não foi encontrado na requisição.');
            return false;
        }
        
        if (! $request->request('senha')) {
            $response->setError(400, 'malformed_request', 'O campo \'senha\' não foi encontrado na requisição.');
            return false;
        }
        
        if (! $request->request('nome')) {
            $response->setError(400, 'malformed_request', 'O campo \'nome\' não foi encontrado na requisição.');
            return false;
        }
        
        if ($this->storage->getUser($request->request('email'))) {
            $response->setError(401, 'unavailable_email', 'O email fornecido já está em uso.');
            return false;
        }
        
        return true;
    }

    protected function validateRequestRecursoRelatos(RequestInterface $requsest, ResponseInterface $response)
    {
        return true;
    }

    protected function validateRequestRecursoSetRelato(RequestInterface $requsest, ResponseInterface $response)
    {
        return true;
    }

    protected function validateRequestRecursoComentarios(RequestInterface $requsest, ResponseInterface $response)
    {
        return true;
    }

    protected function validateRequestRecursoSetComentario(RequestInterface $requsest, ResponseInterface $response)
    {
        return true;
    }

    public function cadastroLocal()
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        try {
            if ($this->validateRequestSetUsuario($request, $response)) {
                $usuario = (new Usuario())->initFromRequest($request);
                $this->storage->setUser($usuario);
                $response->setStatusCode(200);
            }
        } catch (ConnectorException $e) {
            $response->setError(500, 'connector_exception', $e->getMessage());
        }
        $response->send();
    }

    protected function usuario(RequestInterface $request, ResponseInterface $response) {
        $access_token = $this->server->getAccessTokenData($request, $response);
        $usuario = $this->storage->getUserById($access_token['user_id']);
        $response->setStatusCode(200);
        $response->addParameters($usuario->toSafeArray());
    }
    
    protected function lista_relatos(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoRelatos($request, $response)) {
            $busca = (new BuscaRelato())->initFromRequest($request);
            $relatos = $this->storage->listRelatos($busca);
            $response->setStatusCode(200);
            $response->addParameters($relatos);
        }
    }

    protected function set_relato(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoSetRelato($request, $response)) {
            $relato = (new Relato())->initFromRequest($request);
            $this->storage->setRelato($relato);
            $response->setStatusCode(200);
        }
    }

    protected function lista_comentarios(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoComentarios($request, $response)) {
            $busca = (new BuscaComentario())->initFromRequest($request);
            $comentarios = $this->storage->listComentarios($busca);
            $response->setStatusCode(200);
            $response->addParameters($comentarios);
        }
    }

    protected function set_comentario(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoSetComentario($request, $response)) {
            $comentario = (new Comentario())->initFromRequest($request);
            $this->storage->setComentario($comentario);
            $response->setStatusCode(200);
        }
    }

    /**
     * Permite que usuários acessem os relatos e os comentários no sistema.
     */
    public function resource($escopo = null)
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        
        if ($this->server->verifyResourceRequest($request, $response, $escopo)) {
            try {
                switch ($escopo) {
                    case 'usuario':
                        $this->usuario($request, $response);
                        break;
                    case 'lista_relatos':
                        $this->lista_relatos($request, $response);
                        break;
                    case 'set_relato':
                        $this->set_relato($request, $response);
                        break;
                    case 'lista_comentarios':
                        $this->lista_comentarios($request, $response);
                        break;
                    case 'set_comentario':
                        $this->set_comentario($request, $response);
                        break;
                    default:
                        $response->setError(500, 'recurso_desconhecido', 'O recurso requisitado não é conhecido.');
                }
            } catch (ConnectorException $e) {
                $response->setError(500, 'connector_exception', $e->getMessage());
            }
        }
        $response->send();
    }
}
