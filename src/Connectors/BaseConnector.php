<?php
namespace Connectors;

use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\UserCredentialsInterface;
use Models\BuscaRelato;
use Models\Usuario;
use Models\Relato;
use Models\Comentario;
use Models\BuscaComentario;

abstract class BaseConnector implements AccessTokenInterface, UserCredentialsInterface
{

    const DATE_FORMAT = 'Y-m-d H:i:s';

    protected $config;

    protected $db;

    public function __construct(array $config)
    {
        $connection = array_merge([
            'username' => null,
            'password' => null,
            'options' => [],
            'access_token_table' => 'login_sessions',
            'user_table' => 'usuarios',
            'user_data' => 'local_oauth',
            'facebook_data' => 'facebook_oauth'
        ], $config['database']);
        
        $this->db = new \PDO($connection['dsn'], $connection['username'], $connection['password'], $connection['options']);
        
        // debugging
        //$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
        $this->config = array_merge([
            'security' => $config['security']
        ], $connection);
    }

    /* AccessTokenInterface */
    public function getAccessToken($access_token)
    {
        $sql = sprintf('SELECT * FROM %s WHERE token=:access_token', $this->config['access_token_table']);
        $stmt = $this->db->prepare($sql);
        
        if (! $stmt->execute(compact('access_token'))) {
            throw ConnectorException::fromStmt($stmt, 'Falha ao recuperar token.');
        }
        if ($token = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return [
                'access_token' => $token['token'],
                'expires' => strtotime($token['expira']),
                'user_id' => $token['id_usuario'],
                'client_id' => $token['client_id'],
                'scope' => $token['escopo']
            ];
        } else {
            return false;
        }
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        // convert expires to datestring
        $expires = date(BaseConnector::DATE_FORMAT, $expires);
        
        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            $sql = sprintf('UPDATE %s
					SET expira=:expires, client_id=:client_id, id_usuario=:user_id, escopo=:scope
					WHERE token=:access_token', $this->config['access_token_table']);
            $stmt = $this->db->prepare($sql);
        } else {
            $sql = sprintf('INSERT INTO %s (token, expira, id_usuario, client_id, escopo)
					VALUES (:access_token, :expires, :user_id, :client_id, :scope)', $this->config['access_token_table']);
            $stmt = $this->db->prepare($sql);
        }
        if (! $stmt->execute(compact('access_token', 'client_id', 'user_id', 'expires', 'scope'))) {
            throw ConnectorException::fromStmt($stmt, 'Falha ao gravar token no banco');
        }
    }

    /* UserCredentials Interface */
    public abstract function checkUserCredentials($username, $password);

    public function getUserDetails($username)
    {
        if ($user = $this->getUser($username)) {
            return [
                'user_id' => $user->id,
                'scope' => 'usuario lista_relatos set_relato lista_comentarios set_comentario'
            ];
        } else {
            return false;
        }
    }

    /* Connector Methods */
    /**
     * Busca um usuário com relação a uma coluna.
     * As colunas podem ser da tabela de usuarios 'u', da tabela de dados local
     * 'l' ou da tabela de dados do facebook 'f'.
     *
     * @param string $key
     *            Nome da coluna de busca
     * @param mixed $value
     *            Valor com que a coluna será comparada
     * @return Usuario|NULL Usuário encontrado na tabela
     */
    protected function getUserOn($key, $value)
    {
        $sql = sprintf('SELECT * FROM %s AS u LEFT JOIN %s AS l
				ON u.id = l.id_usuario LEFT JOIN %s as f
				ON u.id = f.id_usuario
				WHERE %s=:value', $this->config['user_table'], $this->config['user_data'], $this->config['facebook_data'], $key);
        $stmt = $this->db->prepare($sql);
        if (! $stmt->execute(compact('value'))) {
            throw ConnectorException::fromStmt($stmt, 'Falha ao realizar busca de usuários');
        }
        if (! $row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return null;
        } else {
            return new Usuario($row);
        }
    }

    public function getUserById($id_usuario)
    {
        return $this->getUserOn('u.id', $id_usuario);
    }

    /**
     * Busca um usuário no banco de dados.
     *
     * @param string $username
     *            Username do usuário.
     * @return boolean|Usuario Os dados do usuário no banco de dados ou false
     *         caso o usuário não seja encontrado.
     */
    public abstract function getUser($username);

    /**
     * Insere dados de um usuário no banco de dados.
     *
     * @param Usuario $usuario
     *            Dados do usuário para inserir no banco de dados
     * @throws ConnectorException Caso ocorra algum problema na inserção dos dados.
     * @return boolean Valor indicando se a alteração foi bem sucedida
     */
    public function setUser(Usuario $usuario)
    {
        $this->db->beginTransaction();
        if ($usuario->hasLocalOAuth() || $usuario->hasFacebookOAuth()) {
            if (is_null($usuario->id)) {
                // Caso o usuário não exista
                if (is_null($usuario->admin)) {
                    $usuario->admin = false;
                }
                $sql = sprintf('INSERT INTO %s (admin) VALUES (:admin)', $this->config['user_table']);
                $stmt = $this->db->prepare($sql);
                if (! $stmt->execute($usuario->getUsuariosArray([
                    'admin'
                ]))) {
                    throw ConnectorException::fromStmt($stmt, 'Erro ao inserir usuário na tabela base');
                }
                
                if ($usuario->hasLocalOAuth()) {
                    $sql = sprintf('INSERT INTO %s (id_usuario, email, senha, nome)
                            VALUES (LAST_INSERT_ID(), :email, :senha, :nome)', $this->config['user_data']);
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($usuario->getLocalOAuthArray([
                        'email',
                        'senha',
                        'nome'
                    ]))) {
                        throw ConnectorException::fromStmt($stmt, 'Erro ao inserir usuário na tabela de oauth local');
                    }
                }
                
                if ($usuario->hasFacebookOAuth()) {
                    $sql = sprintf('INSERT INTO %s (id_usuario, fb_user_id, fb_email, fb_nome)
                            VALUES (LAST_INSERT_ID(), :fb_user_id, :fb_email, :fb_nome)', $this->config['facebook_data']);
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($usuario->getFacebookOAuthArray([
                        'fb_user_id',
                        'fb_email',
                        'fb_nome'
                    ]))) {
                        throw ConnectorException::fromStmt($stmt, 'Erro ao inserir usuário na tabela de oauth do facebook');
                    }
                }
            } else {
                // Caso o usuário exista
                if (! is_null($usuario->admin)) {
                    $sql = sprintf('UPDATE %s
								SET admin=:admin
								WHERE id=:id', $this->config['user_table']);
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($usuario->getUsuariosArray())) {
                        throw ConnectorException::fromStmt($stmt, 'Erro ao atualizar usuário na tabela base');
                    }
                }
                
                if ($usuario->hasLocalOAuth()) {
                    $sql = sprintf('UPDATE %s
								SET email=:email,senha=:senha,nome=:nome
								WHERE id_usuario=:id', $this->config['user_data']);
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($usuario->getLocalOAuthArray())) {
                        throw ConnectorException::fromStmt($stmt, 'Erro ao atualizar usuário na tabela de oauth local');
                    }
                } elseif ($usuario->hadLocalOAuth()) {
                    $sql = sprintf('DROP %s
								WHERE id_usuario=:id', $this->config['user_data']);
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($usuario->getLocalOAuthArray())) {
                        throw ConnectorException::fromStmt($stmt, 'Erro ao remover usuário na tabela de oauth local');
                    }
                }
                
                if ($usuario->hasFacebookOAuth()) {
                    $sql = sprintf('UPDATE %s
								SET fb_user_id=:fb_user_id,fb_email=:fb_email,fb_nome=:fb_nome
								WHERE id_usuario=:id', $this->config['facebook_data']);
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($usuario->getFacebookOAuthArray())) {
                        throw ConnectorException::fromStmt($stmt, 'Erro ao atualizar usuário na tabela de oauth do facebook');
                    }
                } elseif ($usuario->hadFacebookOAuth()) {
                    $sql = sprintf('DROP %s
								WHERE id_usuario=:id', $this->config['facebook_data']);
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($usuario->getFacebookOAuthArray())) {
                        throw ConnectorException::fromStmt($stmt, 'Erro ao remover usuário na tabela de oauth do facebook');
                    }
                }
            }
        } elseif (! is_null($usuario->id)) {
            // Caso o usuário deva ser removido
            $sql = sprintf('DROP %s WHERE id=:id', $this->config['user_table']);
            $stmt = $this->db->prepare($sql);
            if (! $stmt->execute($usuario->getUsuariosArray())) {
                throw ConnectorException::fromStmt($stmt, 'Erro ao remover usuário');
            }
        }
        $this->db->commit();
        return true;
    }

    public function getRelatoById($id) {
        $sql = sprintf('SELECT
            COALESCE(f.fb_nome, l.nome) AS nome_usuario,
            p.id AS id, p.titulo AS titulo, p.descricao AS descricao,
            p.data AS data, p.status AS status, p.classificacao AS classificacao,
            p.foto AS foto, p.latitude AS latitude, p.longitude AS longitude,
            p.id_usuario AS id_usuario
            FROM ' . $this->config['user_table'] . ' AS u
            LEFT JOIN ' . $this->config['user_data'] . ' AS l ON u.id = l.id_usuario
            LEFT JOIN ' . $this->config['facebook_data'] . ' AS f ON u.id = f.id_usuario
            RIGHT JOIN relatos AS p ON u.id = p.id_usuario
            WHERE p.id=:id');
        $stmt = $this->db->prepare($sql);
        if (! $stmt->execute(compact('id'))) {
            throw ConnectorException::fromStmt($stmt, 'Falha ao realizar busca de relatos');
        }
        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return new Relato($row);
        } else {
            return null;
        }
    }
    
    /**
     * Lista um conjunto de relatos que satisfazem os requisitos da busca.
     *
     * @param BuscaRelato $busca
     *            Parâmetros da busca
     * @return Relato[] Lista com os relatos encontrados.
     */
    public function listRelatos(BuscaRelato $busca)
    {
        $where = [];
        if (! is_null($busca->titulo)) {
            $where[] = 'titulo LIKE CONCAT(\'%\', :titulo, \'%\')';
        }
        if (! is_null($busca->autor)) {
            $where[] = 'nome_usuario LIKE %:autor%';
        }
        if (! is_null($busca->foto)) {
            $where[] = 'foto = :foto';
        }
        if (! is_null($busca->status)) {
            $where[] = 'status = :status';
        }
        if (! is_null($busca->classificacao)) {
            $where[] = 'classificacao = :classificacao';
        }
        if (! is_null($busca->data)) {
            $where[] = 'data = :data + INTERVAL 1 DAY';
        }
        if (! is_null($busca->id_usuario)) {
            $where[] = 'u.id = :id_usuario';
        }
        if (! empty($where)) {
            $where = 'WHERE ' . implode(' AND ', $where);
        } else {
            $where = '';
        }
        $sql = sprintf('SELECT 
            COALESCE(f.fb_nome, l.nome) AS nome_usuario,
            p.id AS id, p.titulo AS titulo, p.descricao AS descricao,
            p.data AS data, p.status AS status, p.classificacao AS classificacao,
            p.foto AS foto, p.latitude AS latitude, p.longitude AS longitude,
            p.id_usuario AS id_usuario
            FROM ' . $this->config['user_table'] . ' AS u
            LEFT JOIN ' . $this->config['user_data'] . ' AS l ON u.id = l.id_usuario
            LEFT JOIN ' . $this->config['facebook_data'] . ' AS f ON u.id = f.id_usuario
            RIGHT JOIN relatos AS p ON u.id = p.id_usuario
            %s ORDER BY data DESC', $where);
        $stmt = $this->db->prepare($sql);
        if (! $stmt->execute($busca->toArray(null, false))) {
            throw ConnectorException::fromStmt($stmt, 'Falha ao realizar busca de relatos');
        }
        
        $resultados = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $resultados[] = new Relato($row);
        }
        return $resultados;
    }
    
    /**
     * Insere dado de um relato no banco de dados.
     *
     * @param Relato $relato
     *            Dados do relato que serão inseridos
     * @throws ConnectorException Caso o relato não possa ser inserido
     * @return boolean Se o relato foi inserido ou não
     */
    public function setRelato(Relato &$relato)
    {
        try {
            if (is_null($relato->id)) {
                // Caso o relato não exista
                $relato->data = date(BaseConnector::DATE_FORMAT);
                $sql = 'INSERT INTO relatos(data, titulo, descricao, status, classificacao, foto, latitude, longitude, id_usuario)
                    VALUES (:data, :titulo, :descricao, :status, :classificacao, :foto, :latitude, :longitude, :id_usuario)';
                $stmt = $this->db->prepare($sql);
                if (! $stmt->execute($relato->toArray(['data', 'titulo', 'descricao', 'status', 'classificacao', 'foto', 'latitude', 'longitude', 'id_usuario' ]))) {
                    throw ConnectorException::fromStmt($stmt, 'Falha ao inserir novo relato');
                }
                $sql = 'SELECT LAST_INSERT_ID() AS id';
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $relato->id = $stmt->fetchColumn();
            } else {
                // Caso o relato exista
                if (!is_null($relato->titulo)) {
                    // Relato deve ser atualizado
                    $sql = 'UPDATE relatos 
                        SET data=:data,titulo=:titulo,descricao=:descricao,status=:status,classificacao=:classificacao,foto=:foto,latitude=:latitude,longitude=:longitude,id_usuario=:id_usuario
                        WHERE id=:id';
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($relato->toArray(['id', 'data', 'titulo', 'descricao', 'status', 'classificacao', 'foto', 'latitude', 'longitude', 'id_usuario']))) {
                        throw ConnectorException::fromStmt($stmt, 'Falha ao atualizar relato');
                    }
                } else {
                    // Relato deve ser removido
                    $sql = 'DELETE FROM relatos WHERE id=:id';
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($relato->toArray(['id']))) {
                        throw ConnectorException::fromStmt($stmt, 'Falha ao remover relato');
                    }
                }
            }
        } catch (ConnectorException $e) {
            throw $e;
            return false;
        }
        $relato->clearDirty();
        return true;
    }

    /**
     * Busca por comentários no banco de dados.
     *
     * @param BuscaComentario $busca
     *            Parametros da busca
     * @throws ConnectorException Caso ocorra alguma falha na busca
     * @return Comentario[] Lista com os comentários encontrados
     */
    public function listComentarios(BuscaComentario $busca)
    {
        $where = [];
        if (! is_null($busca->id_relato)) {
            $where[] = 'id_relato = :id_relato';
        }
        if (! is_null($busca->id_usuario)) {
            $where[] = 'u.id = :id_usuario';
        }
        if (! empty($where)) {
            $where = 'WHERE ' . implode(' AND ', $where);
        } else {
            $where = '';
        }
        $sql = sprintf('SELECT 
            COALESCE(f.fb_nome, l.nome) AS nome_usuario,
            c.id AS id, c.data AS data, c.texto AS texto,
            c.id_relato AS id_relato, c.id_usuario AS id_usuario
            FROM ' . $this->config['user_table'] . ' AS u 
            LEFT JOIN ' . $this->config['user_data'] . ' AS l ON u.id = l.id_usuario
            LEFT JOIN ' . $this->config['facebook_data'] . ' AS f ON u.id = f.id_usuario
            RIGHT JOIN comentarios AS c ON u.id = c.id_usuario
            %s ORDER BY data DESC', $where);
        $stmt = $this->db->prepare($sql);
        if (! $stmt->execute($busca->toArray(null, false))) {
            throw ConnectorException::fromStmt($stmt, 'Falha ao realizar busca de comentários');
        }
        
        $resultados = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $resultados[] = new Comentario($row);
        }
        return $resultados;
    }

    /**
     * Insere um comentário no banco de dados.
     *
     * @param Comentario $comentario
     *            Comentário a ser inserido
     * @throws ConnectorException Caso o comentário não possa ser inserido
     * @return boolean Se o comentário foi inserido ou não
     */
    public function setComentario(Comentario &$comentario)
    {
        try {
            if (is_null($comentario->id)) {
                // Caso o comentário não exista
                $comentario->data = date(BaseConnector::DATE_FORMAT);
                $sql = 'INSERT INTO comentarios(texto, data, id_relato, id_usuario)
                    VALUES (:texto, :data, :id_relato, :id_usuario)';
                $stmt = $this->db->prepare($sql);
                if (! $stmt->execute($comentario->toArray([
                    'texto',
                    'data',
                    'id_relato',
                    'id_usuario'
                ]))) {
                    throw ConnectorException::fromStmt($stmt, 'Falha ao inserir comentário');
                }
                $sql = 'SELECT LAST_INSERT_ID() AS id';
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $comentario->id = $stmt->fetchColumn();
            } else {
                // Caso o comentário exista
                if (! is_null($comentario->texto)) {
                    // O comentário deve ser atualizado
                    $sql = 'UPDATE comentarios
                        SET texto=:texto, data=:data, id_usuario=:id_usuario, id_relato=:id_relato
                        WHERE id=:id';
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($comentario->toArray())) {
                        throw ConnectorException::fromStmt($stmt, 'Falha ao atualizar comentário');
                    }
                } else {
                    // O comentário deve ser removido
                    $sql = 'DELETE FROM comentarios WHERE id=:id';
                    $stmt = $this->db->prepare($sql);
                    if (! $stmt->execute($comentario->toArray(['id']))) {
                        throw ConnectorException::fromStmt($stmt, 'Falha ao remover comentário');
                    }
                }
            }
        } catch (ConnectorException $e) {
            throw $e;
            return false;
        }
        $comentario->clearDirty();
        return true;
    }

    protected function exec_multi_query($sql)
    {
        $queries = explode(';', $sql);
        foreach ($queries as $query) {
            echo $query . '<br>';
            $stmt = $this->db->prepare($query);
            if (! $stmt->execute()) {
                throw ConnectorException::fromStmt($stmt, 'Falha ao executar múltiplas queries');
            }
        }
    }

    public function drop_database()
    {
        $sql = <<<EOT
DROP EVENT IF EXISTS remove_expired_tokens;
DROP TABLE IF EXISTS comentarios;
DROP TABLE IF EXISTS relatos;
DROP TABLE IF EXISTS {$this->config ['access_token_table']};
DROP TABLE IF EXISTS facebook_oauth;
DROP TABLE IF EXISTS {$this->config ['user_data']};
DROP TABLE IF EXISTS {$this->config ['user_table']}
EOT;
        
        $this->exec_multi_query($sql);
    }

    public function create_database()
    {
        $sql = <<<EOT
CREATE TABLE IF NOT EXISTS {$this->config['user_table']} (
	id int NOT NULL AUTO_INCREMENT,
	admin bool NOT NULL DEFAULT 0,
	CONSTRAINT pk_{$this->config['user_table']} PRIMARY KEY (id),
	INDEX ik_admin (admin)
);

CREATE TABLE IF NOT EXISTS {$this->config['user_data']} (
	id_usuario int NOT NULL,
	email varchar(255) NOT NULL,
	senha varchar(255) NOT NULL,
	nome varchar(255) NOT NULL,
	CONSTRAINT pk_{$this->config['user_data']} PRIMARY KEY (id_usuario),
	CONSTRAINT fk_{$this->config['user_data']}_{$this->config['user_table']} FOREIGN KEY (id_usuario)
		REFERENCES {$this->config['user_table']}(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT uk_email UNIQUE KEY (email)
);

CREATE TABLE IF NOT EXISTS {$this->config['facebook_data']} (
	id_usuario int NOT NULL,
	fb_user_id varchar(255) NOT NULL,
	fb_email varchar(255) NOT NULL,
	fb_nome varchar(255) NOT NULL,
	CONSTRAINT pk_{$this->config['facebook_data']} PRIMARY KEY (id_usuario),
	CONSTRAINT fk_{$this->config['facebook_data']}_{$this->config['user_table']} FOREIGN KEY (id_usuario)
		REFERENCES {$this->config['user_table']}(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT uk_fb_user_id UNIQUE KEY (fb_user_id),
	INDEX ik_fb_email (fb_email)
);

CREATE TABLE IF NOT EXISTS {$this->config['access_token_table']} (
	token varchar(255) NOT NULL,
	id_usuario int,
	expira datetime NOT NULL,
	client_id varchar(50) NOT NULL,
	escopo varchar(2000),
	CONSTRAINT pk_{$this->config['access_token_table']} PRIMARY KEY (token),
	INDEX ik_usuario (id_usuario),
	CONSTRAINT fk_{$this->config['access_token_table']}_{$this->config['user_table']} FOREIGN KEY (id_usuario)
		REFERENCES {$this->config['user_table']}(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS relatos (
	id int NOT NULL AUTO_INCREMENT,
	data datetime NOT NULL,
	titulo varchar(50) NOT NULL,
	descricao varchar(1024) NOT NULL,
	status enum('pendente', 'em-andamento', 'finalizado') NOT NULL,
	classificacao enum('infraestrutura', 'saude', 'seguranca') NOT NULL,
	foto bool NOT NULL,
	latitude double NOT NULL,
	longitude double NOT NULL,
	id_usuario int NOT NULL,
	CONSTRAINT pk_relatos PRIMARY KEY (id),
	INDEX ik_usuario (id_usuario),
	CONSTRAINT fk_relatos_{$this->config ['user_table']} FOREIGN KEY (id_usuario)
		REFERENCES {$this->config ['user_table']}(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS comentarios (
	id int NOT NULL AUTO_INCREMENT,
	texto varchar(1024) NOT NULL,
	data datetime NOT NULL,
	id_relato int NOT NULL,
	id_usuario int NOT NULL,
	CONSTRAINT pk_comentarios PRIMARY KEY (id),
	INDEX ik_relato (id_relato),
	CONSTRAINT fk_comentarios_relatos FOREIGN KEY (id_relato)
		REFERENCES relatos(id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_comentarios_{$this->config['user_table']} FOREIGN KEY (id_usuario)
		REFERENCES {$this->config['user_table']}(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE EVENT IF NOT EXISTS remove_expired_tokens
    ON SCHEDULE EVERY 1 HOUR
    DO
        DELETE FROM {$this->config['access_token_table']} WHERE expira < NOW()
EOT;
        
        $this->exec_multi_query($sql);
    }
}
