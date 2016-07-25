<?php

class Config
{

    const config = [
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
        'database-test' => [
            'dsn' => 'mysql:dbname=lavras_informa_test;host=localhost',
            'username' => 'lavras_informa',
            'password' => 'senha_para_lavras_informa',
            'option' => null
        ],
        'client_storage' => [
            'id_do_aplicativo' => [
                'client_secret' => 'segredo_do_aplicativo',
                'redirect_url' => '/',
                'grant_types' => 'password client_credentials',
                'scope' => 'lista-relatos set-relato lista-comentarios set-comentario'
            ]
        ]
    ];
}
