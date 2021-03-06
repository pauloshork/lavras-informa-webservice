<?php
namespace Models;

class Comentario extends AbstractModel
{

    public function __construct(array $data = [])
    {
        $fields = [
            'id',
            'texto',
            'data',
            'id_usuario',
            'id_relato',
            'nome_usuario'
        ];
        parent::__construct($fields, $data);
    }
}