<?php
namespace Models;

class BuscaComentario extends AbstractModel
{

    public function __construct(array $data = [])
    {
        $fields = [
            'id_relato',
            'id_usuario'
        ];
        parent::__construct($fields, $data);
    }
}