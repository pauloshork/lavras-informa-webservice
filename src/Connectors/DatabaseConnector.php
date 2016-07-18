<?php

namespace Connectors;

use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\UserCredentialsInterface;
use OAuth2\Storage\ClientCredentialsInterface;

class DatabaseConnector implements AccessTokenInterface, UserCredentialsInterface, ClientCredentialsInterface {
	public function __construct(array $config) {
		$connection = array_merge ( [ 
				'username' => null,
				'password' => null,
				'options' => [ ],
				'schema' => [ 
						'access_token_table' => 'login_sessions',
						'user_table' => 'usuarios',
						'user_data' => 'local_oauth'
				] 
		], $config ['database'] );
		
		$this->db = new \PDO ( $connection ['dsn'], $connection ['username'], $connection ['password'], $connection ['options'] );
		
		// debugging
		$this->db->setAttribute ( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		
		$this->config = array_merge ( [ 
				'security' => $config ['security'],
				'mobile_app' => $config ['mobile_app']
		], $connection );
	}
	
	/* AccessTokenInterface */
	public function getAccessToken($access_token) {
		$sql = sprintf ( 'SELECT * from %s WHERE token = :access_token', $this->config ['access_token_table'] );
		$stmt = $this->db->prepare ( $sql );
		
		$token = $stmt->execute ( compact ( 'access_token' ) );
		if ($token = $stmt->fetch ( \PDO::FETCH_ASSOC )) {
			// convert date string back to timestamp
			$token ['expires'] = strtotime ( $token ['expires'] );
		}
		
		return $token;
	}
	public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {
		// convert expires to datestring
		$expires = date ( 'Y-m-d H:i:s', $expires );
		
		// if it exists, update it.
		if ($this->getAccessToken ( $access_token )) {
			$sql = sprintf ( 'UPDATE %s 
					SET expira=:expires, id_cliente=:client_id, id_usuario=:user_id, escopo=:scope 
					WHERE token=:access_token', $this->config ['access_token_table'] );
			$stmt = $this->db->prepare ( $sql );
		} else {
			$sql = sprintf ( 'INSERT INTO %s (token, expira, id_usuario) 
					VALUES (:access_token, :expires, :user_id)', $this->config ['access_token_table'] );
			$stmt = $this->db->prepare ( $sql );
		}
		
		return $stmt->execute ( compact ( 'access_token', 'client_id', 'user_id', 'expires', 'scope' ) );

	/* UserCredentials Interface */
	public function checkUserCredentials($username, $password) {
		if ($user = $this->getUser ( $username )) {
			return $this->checkPassword ( $user, $password );
		}
		
		return false;
	}
	public function getUserDetails($username) {
		return $this->getUser ( $username );
	}
	protected function checkPassword($user, $password) {
		if (! password_verify ( $password, $user ['password'] )) {
			return false;
		} else if (password_needs_rehash ( $user ['password'], $this->config ['security'] ['algo'], $this->config ['security'] ['options'] )) {
			setUser ( $user ['username'], $password, $user ['name'], $user ['admin'] );
		}
		return true;
	}
	public function getUser($username) {
		$sql = sprintf ( 'SELECT * FROM %s AS t INNER JOIN %s AS d
				ON t.id = d.id_usuario
				WHERE d.email=:username', $this->config ['user_table'], $this->config ['user_data'] );
		$stmt = $this->db->prepare ( $sql );
		$stmt->execute ( compact ( 'username' ) );
		
		if (! $userInfo = $stmt->fetch ( \PDO::FETCH_ASSOC )) {
			return false;
		}
		
		// the default behavior is to use "username" as the user_id
		return array_merge ( array (
				'user_id' => $username 
		), $userInfo );
	}
	public function setUser($email, $senha, $nome, $admin = null) {
		// gera um hash para a senha fornecida
		$senha = password_hash ( $senha, $this->config ['security'] ['algo'], $this->config ['security'] ['options'] );
		
		// if it exists, update it.
		if ($this->getUser ( $email )) {
			if (is_null ( $admin )) {
				$sql = sprintf ( 'UPDATE %s
						SET senha=:senha, nome=:nome
						WHERE email=:email', $this->config ['user_data'] );
			} else {
				$sql = sprintf ( 'UPDATE %s as t, %s as d INNER JOIN t
						ON t.id = d.id_usuario
						SET d.senha=:senha, d.nome=:nome, d.admin=:admin
						WHERE d.email=:email', $this->config ['user_table'], $this->config ['user_data'] );
			}
			$stmt = $this->db->prepare ( $sql );
			return $stmt->execute ( compact ( 'email', 'senha', 'nome', 'admin' ) );
		} else {
			try {
				$this->db->beginTransaction ();
				$sql = sprintf ( 'INSERT INTO %s (admin) VALUES (:admin)', $this->config ['user_table'] );
				$stmt = $this->db->prepare ( $sql );
				$execute = $stmt->execute ( compact ( 'admin' ) );
				if (!$execute) {
					throw new \Exception($stmt->errorInfo());
				}
				$sql = sprintf ( 'INSERT INTO %s (id_usuario, email, senha, nome) VALUES (LAST_INSERT_ID(), :email, :senha, :nome)' );
				$execute = $stmt->execute ( compact ( 'email', 'senha', 'nome' ) );
				if (!$execute) {
					throw new \Exception($stmt->errorInfo());
				}
				$this->db->commit ();
			} catch ( \Exception $ex ) {
				$this->db->rollBack ();
				throw $ex;
				return false;
			}
		}
	}
	/* ClientCredentialsInterface */
	public function checkClientCredentials($client_id, $client_secret = null) {
		return $this->config['mobile_app']['app_id'] === $client_id &&
			$this->config['mobile_app']['app_secret'] === $client_secret;
	}
	public function isPublicClient($client_id) {
		return false;
	}
	function create_database() {
		$sql = 
		'CREATE TABLE IF NOT EXISTS ' . $this->config['user_table'] . ' (
			id int NOT NULL AUTO_INCREMENT,
			admin tinyint NOT NULL DEFAULT 0,
			CONSTRAINT pk_usuarios PRIMARY KEY (id),
			CONSTRAINT ik_admin index key (admin)
		);

		CREATE TABLE IF NOT EXISTS ' . $this->config['user_data'] . ' (
			id_usuario int NOT NULL,
			email varchar(255) NOT NULL,
			senha varchar(255) NOT NULL,
			nome varchar(255) NOT NULL,
			CONSTRAINT pk_' . $this->config['user_data'] . ' PRIMARY KEY (id_usuario),
			CONSTRAINT fk_' . $this->config['user_data'] . '_id_usuario (id_usuario)
				REFERENCES ' . $this->config['user_table'] . '(id) ON DELETE CASCADE ON UPDATE CASCADE,
			CONSTRAINT uk_email UNIQUE KEY (email),
		);

		CREATE TABLE IF NOT EXISTS facebook_oauth (
			id_usuario int NOT NULL,
			user_id varchar(255) NOT NULL,
			CONSTRAINT pk_facebook_oauth PRIMARY KEY (id_usuario),
			CONSTRAINT fk_' . $this->config['user_table'] . '_id_usuario (id_usuario)
				REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
			CONSTRAINT uk_user_id UNIQUE KEY (user_id)
		);

		CREATE TABLE IF NOT EXISTS ' . $this->config['access_token_table'] . ' (
			token varchar(255) NOT NULL,
			id_usuario int NOT NULL,
			expira varchar(50) NOT NULL,
			CONSTRAINT pk_' . $this->config['access_token_table'] . ' PRIMARY KEY (token),
			CONSTRAINT ik_usuario INDEX KEY (id_usuario),
			CONSTRAINT fk_' . $this->config['user_table'] . '_id_usuario (id_usuario)
				REFERENCES ' . $this->config['user_table'] . '(id) ON DELETE CASCADE ON UPDATE CASCADE
		);

		CREATE TABLE IF NOT EXISTS problemas (
			id int NOT NULL,
			data date NOT NULL,
			titulo varchar(50) NOT NULL,
			descricao varchar(1024) NOT NULL,
			status enum('Pentende', 'Em Andamento', 'Finalizado') NOT NULL,
			classificacao enum('Infraestrutura', 'Saude', 'Seguranca') NOT NULL,
			foto varchar(1024) NOT NULL,
			latitude double NOT NULL,
			longitude double NOT NULL,
			id_usuario int NOT NULL,
			CONSTRAINT pk_problema PRIMARY KEY (id),
			CONSTRAINT ik_usuario INDEX KEY (id_usuario),
			CONSTRAINT fk_' . $this->config['user_table'] . '_id_usuario FOREIGN KEY (id_usuario)
				REFERENCES ' . $this->config['user_table'] . '(id) ON DELETE CASCADE ON UPDATE CASCADE
		);

		CREATE TABLE IF NOT EXISTS comentarios (
			id int NOT NULL,
			texto varchar(1024) NOT NULL,
			id_problema int NOT NULL,
			id_usuario int NOT NULL,
			CONSTRAINT pk_comentario PRIMARY KEY (id),
			CONSTRAINT ik_problema INDEX KEY (id_problema),
			CONSTRAINT fk_problemas_id_problema FOREIGN KEY (id_problema) 
				REFERENCES problemas(id),
			CONSTRAINT fk_' . $this->client['user_table'] . '_id_usuario FOREIGN KEY (id_usuario) 
				REFERENCES ' . $this->client['user_table'] . 'usuarios(id)
		)';
		return $sql;
	}
}
