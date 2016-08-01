<?php
namespace Models;

class Relato extends AbstractModel
{

    public function __construct(array $data = [])
    {
        $fields = [
            'id',
            'data',
            'titulo',
            'descricao',
            'status',
            'classificacao',
            'foto',
            'latitude',
            'longitude',
            'id_usuario',
            'nome_usuario'
        ];
        parent::__construct($fields, $data);
    }
    
    protected function __get_foto(array $data) {
        return isset($data['foto']) && boolval($data['foto']);
    }
    
    protected function __set_foto($foto) {
        return intval($foto);
    }
}