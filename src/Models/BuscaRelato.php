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
            'foto'
        ];
        parent::__construct($fields, $data);
    }
}