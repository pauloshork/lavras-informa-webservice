<?php
namespace Connectors;

class DatabaseConnector {

	public function __construct(array $config) {
		$dbconf = $config['database'];
		$this->db = new \mysqli(
			$dbconf['hostname'], 
			$dbconf['username'], 
			$dbconf['password'], 
			$dbconf['database'], 
			$dbconf['port'], 
			$dbconf['socket']);

		if ($this->db->connect_error) {
			throw new Exception('Connect Error (' . $this->db->connect_errno . ') ' . $this->db->connect_error);
		}
	}

	function create_database() {
		/*'create table if not exists usuarios (
			id int not null auto_increment,
			admin tinyint not null default 0,
			constraint pk_usuarios primary key (id),
			constraint id_admin index key (admin)
		)';

		'create table if not exists local_oauth (
			id_usuario int not null,
			email varchar(255) not null,
			senha varchar(255) not null,
			nome varchar(255) not null,
			constraint pk_local_oauth primary key (id_usuario),
			constraint fk_local_oauth_id_usuario (id_usuario)
				references usuarios(id) on delete cascade on update cascade,
			constraint uk_email unique key (email),
		)'

		'create table if not exists facebook_oauth (
			id_usuario int not null,
			userid varchar(255) not null,
			constraint pk_facebook_oauth primary key (id_usuario),
			constraint fk_facebook_oauth_id_usuario (id_usuario) 
				references usuarios(id) on delete cascade on update cascade,
			constraint id_userid unique key (userid)
		)'

		'create table if not exists login_sessions (
			token varchar(255) not null,
			id_usuario int not null,
			expire int not null default token_expire(),
			constraint pk_login_sessions primary key (token),
			constraint fk_login_sessions_id_usuario (id_usuario) references usuarios(id)
		)'
	}

	protected function getUsuarioByEmail($email) {
		$query = 'select * from usuarios as u right join local_oauth as d on d.id_usuario = u.id where email = ?';
		$stmt = $this->db->prepare($query);

	}

	protected functionn getUsuarioByUserId($userId) {
		''*/
	}

	public function autenticar($email, $senha) {
		// $query = 'select id from usuarios where email = ? and senha = ?';
		// $stmt = $this->db->prepare($query);

		// $query = 'select count(*)'

		return '1234567890abcdef';
	}

	public function cadastrar($email, $senha, $nome, $admin=false) {
		// 'insert into usuarios (admin) values (?)';
		// 'select '
		// 'insert into local_oauth () values ()'
	}
}