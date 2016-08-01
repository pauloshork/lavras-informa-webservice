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
    
    private $config;

    public function __construct($user_type = null)
    {
        Autoloader::register();
        
        $this->config = \Config::config;
        
        $memoryStorage = new Memory([
            'client_credentials' => $this->config['client_storage'],
            'scope' => static::scope
        ]);
        
        switch ($user_type) {
            case 'facebook':
                $this->storage = new FacebookConnector($this->config);
                break;
            default:
                $this->storage = new LocalConnector($this->config);
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

    protected function validateRequestRecursoRelatos(RequestInterface $request, ResponseInterface $response)
    {
        $validStatus = ['pendente', 'em-andamento', 'finalizado', null];
        if (!in_array($request->request('status'), $validStatus)) {
            $response->setError(400, 'malformed_parameters', 'O parâmetro \'status\' não contém um valor válido.');
            return false;
        }
        
        $validClassificacao = ['infraestrutura', 'saude', 'seguranca', null];
        if (!in_array($request->request('classificacao'), $validClassificacao)) {
            $response->setError(400, 'malformed_parameters', 'O parâmetro \'classificacao\' não contém um valor válido.');
            return false;
        }
        
        return true;
    }

    protected function validateRequestRecursoSetRelato(RequestInterface $request, ResponseInterface $response)
    {
        $validStatus = ['pendente', 'em-andamento', 'finalizado', null];
        if (!in_array($request->request('status'), $validStatus)) {
            $response->setError(400, 'malformed_parameters', 'O parâmetro \'status\' não contém um valor válido.');
            return false;
        }
        
        $validClassificacao = ['infraestrutura', 'saude', 'seguranca', null];
        if (!in_array($request->request('classificacao'), $validClassificacao)) {
            $response->setError(400, 'malformed_parameters', 'O parâmetro \'classificacao\' não contém um valor válido.');
            return false;
        }
        
        return true;
    }

    protected function validateRequestRecursoComentarios(RequestInterface $request, ResponseInterface $response)
    {
        if ($id = $request->request('id_usuario')) {
            if (!$this->storage->getUserById($id)) {
                $response->setError(400, 'malformed_parameters', 'O parâmetro \'id_usuario\' não contém um id válido.');
                return false;
            }
        }
        
        if ($id = $request->request('id_relato')) {
            if (!$this->storage->getRelatoById($id)) {
                $response->setError(400, 'malformed_parameters', 'O parâmetro \'id_relato\' não contém um calor válido.');
                return false;
            }
        }
        
        return true;
    }

    protected function validateRequestRecursoSetComentario(RequestInterface $request, ResponseInterface $response)
    {
        
        if ($id = $request->request('id_usuario')) {
            if (!$this->storage->getUserById($id)) {
                $response->setError(400, 'malformed_parameters', 'O parâmetro \'id_usuario\' não contém um id válido.');
                return false;
            }
        }
        
        if (!$request->request('id_relato')) {
            $response->setError(400, 'malformed_parameters', 'O parâmetro \'id_relato\' não contém um calor válido.');
            return false;
        }
        
        $id = $request->request('id_relato'); 
        
        if (!$this->storage->getRelatoById($id)) {
            $response->setError(400, 'malformed_parameters', 'O parâmetro \'id_relato\' não contém um calor válido.');
            return false;
        }
        
        return true;
    }

    protected function validateRequestRecursoFoto(RequestInterface $request, ResponseInterface $response)
    {
        if (strtolower($request->server('REQUEST_METHOD')) != 'get') {
            $response->setError(405, 'invalid_request', 'O método de requisição deve ser GET quando acessando uma foto.');
            $response->addHttpHeaders(array(
                'Allow' => 'GET'
            ));
            return false;
        }
        
        if (! $request->query('id')) {
            $response->setError(400, 'malformed_request', 'O campo \'id\' não foi encontrado na requisição.');
            return false;
        }
        
        $relato = $this->storage->getRelatoById($request->query('id'));
        
        if (! $relato) {
            $response->setError(400, 'malformed_request', 'O relato informado não foi encontrado.');
            return false;
        }
        
        if (! $relato->foto) {
            $response->setError(404, 'image_not_found', 'O relato informado não contém foto.');
            return false;
        }
        
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

    protected function handle_usuario(RequestInterface $request, ResponseInterface $response)
    {
        $access_token = $this->server->getAccessTokenData($request, $response);
        $usuario = $this->storage->getUserById($access_token['user_id']);
        $response->setStatusCode(200);
        $response->addParameters($usuario->toSafeArray());
    }

    protected function handle_lista_relatos(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoRelatos($request, $response)) {
            $busca = (new BuscaRelato())->initFromRequest($request);
            if ($request->request('meus')) {
                $accessToken = $this->server->getAccessTokenData($request);
                $busca->id_usuario = $accessToken['user_id'];
            }
            $relatos = $this->storage->listRelatos($busca);
            $response->setStatusCode(200);
            $response->addParameters($relatos);
        }
    }

    protected function handle_set_relato(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoSetRelato($request, $response)) {
            $relato = (new Relato())->initFromRequest($request);
            if (!$relato->id_usuario) {
                $accessToken = $this->server->getAccessTokenData($request, $response);
                $relato->id_usuario = $accessToken['user_id'];
            }
            if (!$relato->data) {
                $relato->data = date(BaseConnector::DATE_FORMAT);
            }
            $this->storage->setRelato($relato);
            
            $dir = ROOT . $this->config['upload-dir'];
            
            if ($blob = $request->request('dados_foto')) {
                $pos = strpos($blob, ',') + 1;
                $blob = substr($blob, $pos);
                $blob = base64_decode($blob);
            
                $f = fopen($dir . '/' . $relato->id, 'wb');
                fwrite($f, $blob);
                fclose($f);
            }
            
            $response->setStatusCode(200);
        }
    }

    protected function handle_get_foto(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoFoto($request, $response)) {
            $id = $request->query('id');
            $filename = ROOT . $this->config['upload-dir'] . '/' . $id;
            $file = fopen($filename, 'rb');
            
            http_response_code(200);
            header('Content-Type: ' . mime_content_type($filename));
            header('Content-Length: ' . filesize($filename));
            fpassthru($file);
            fclose($file);
            return true;
        } else {
            return false;
        }
    }

    protected function handle_lista_comentarios(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoComentarios($request, $response)) {
            $busca = (new BuscaComentario())->initFromRequest($request);
            $comentarios = $this->storage->listComentarios($busca);
            $response->setStatusCode(200);
            $response->addParameters($comentarios);
        }
    }

    protected function handle_set_comentario(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->validateRequestRecursoSetComentario($request, $response)) {
            $comentario = (new Comentario())->initFromRequest($request);
            if (!$comentario->id_usuario) {
                $accessToken = $this->server->getAccessTokenData($request);
                $comentario->id_usuario = $accessToken['user_id'];
            }
            if (!$comentario->data) {
                $comentario->data = date(BaseConnector::DATE_FORMAT);
            }
            $this->storage->setComentario($comentario);
            $response->setStatusCode(200);
        }
    }

    public function usuario()
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        
        if ($this->server->verifyResourceRequest($request, $response, 'usuario')) {
            try {
                $this->handle_usuario($request, $response);
            } catch (ConnectorException $e) {
                $response->setError(500, 'connector_exception', $e->getMessage());
            }
        }
        $response->send();
    }

    public function lista_relatos()
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        
        if ($this->server->verifyResourceRequest($request, $response, 'lista_relatos')) {
            try {
                $this->handle_lista_relatos($request, $response);
            } catch (ConnectorException $e) {
                $response->setError(500, 'connector_exception', $e->getMessage());
            }
        }
        $response->send();
    }

    public function set_relato()
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        
        if ($this->server->verifyResourceRequest($request, $response, 'set_relato')) {
            try {
                $this->handle_set_relato($request, $response);
            } catch (ConnectorException $e) {
                $response->setError(500, 'connector_exception', $e->getMessage());
            }
        }
        $response->send();
    }

    public function lista_comentarios()
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        
        if ($this->server->verifyResourceRequest($request, $response, 'lista_comentarios')) {
            try {
                $this->handle_lista_comentarios($request, $response);
            } catch (ConnectorException $e) {
                $response->setError(500, 'connector_exception', $e->getMessage());
            }
        }
        $response->send();
    }

    public function set_comentario()
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        
        if ($this->server->verifyResourceRequest($request, $response, 'set_comentario')) {
            try {
                $this->handle_set_comentario($request, $response);
            } catch (ConnectorException $e) {
                $response->setError(500, 'connector_exception', $e->getMessage());
            }
        }
        $response->send();
    }

    public function get_foto()
    {
        $request = Request::createFromGlobals();
        $response = new Response();
        
        $suppress = false;
        if ($this->server->verifyResourceRequest($request, $response, 'lista_relatos')) {
            try {
                $suppress = $this->handle_get_foto($request, $response);
            } catch (ConnectorException $e) {
                $response->setError(500, 'connector_exception', $e->getMessage());
            }
        }
        if (! $suppress) {
            $response->send();
        }
    }
}
