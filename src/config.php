<?php
$config = [ 
		'security' => [
				'algo' => PASSWORD_BCRYPT,
				'options' => [
						'cost' => 10
				]
		],
		'facebook' => [ 
				'app_id' => '1079309248816648',
				'app_secret' => '56ea4f2258c792113f3b7f53564dff57',
				'default_graph_version' => 'v2.6' 
		],
		'database' => [ 
				'dsn' => 'mysql:dbname=lavras_informa;host=localhost',
				'username' => 'lavras_informa',
				'password' => 'senha_para_lavras_informa',
				'option' => null 
		],
		'mobile_app' => [
				'app_id' => 'id do aplicativo',
				'app_secret' => 'segredo do aplicativo'
		]
];
