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
	}
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
		/*
		 * 'create table if not exists usuarios (
		 * id int not null auto_increment,
		 * admin tinyint not null default 0,
		 * constraint pk_usuarios primary key (id),
		 * constraint id_admin index key (admin)
		 * )';
		 *
		 * 'create table if not exists local_oauth (
		 * id_usuario int not null,
		 * email varchar(255) not null,
		 * senha varchar(255) not null,
		 * nome varchar(255) not null,
		 * constraint pk_local_oauth primary key (id_usuario),
		 * constraint fk_local_oauth_id_usuario (id_usuario)
		 * references usuarios(id) on delete cascade on update cascade,
		 * constraint uk_email unique key (email),
		 * )'
		 *
		 * 'create table if not exists facebook_oauth (
		 * id_usuario int not null,
		 * userid varchar(255) not null,
		 * constraint pk_facebook_oauth primary key (id_usuario),
		 * constraint fk_facebook_oauth_id_usuario (id_usuario)
		 * references usuarios(id) on delete cascade on update cascade,
		 * constraint id_userid unique key (userid)
		 * )'
		 *
		 * 'create table if not exists login_sessions (
		 * token varchar(255) not null,
		 * id_usuario int not null,
		 * expira varchar(50) not null,
		 * constraint pk_login_sessions primary key (token),
		 * constraint fk_login_sessions_id_usuario (id_usuario) references usuarios(id)
		 * )'
		 * }
		 *
		 * protected function getUsuarioByEmail($email) {
		 * $sql = 'select * from usuarios as u right join local_oauth as d on d.id_usuario = u.id where email = ?';
		 * $stmt = $this->db->prepare($sql);
		 *
		 * }
		 *
		 * protected functionn getUsuarioByUserId($userId) {
		 * ''
		 */
	}
}