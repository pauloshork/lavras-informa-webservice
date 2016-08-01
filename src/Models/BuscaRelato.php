<?php
namespace Models;

class BuscaRelato extends AbstractModel
{

    public function __construct(array $data = [])
    {
        $fields = [
            'titulo',
            'autor',
            'data',
            'status',
            'classificacao',
            'foto',
            'id_usuario'
        ];
        parent::__construct($fields, $data);
    }
}