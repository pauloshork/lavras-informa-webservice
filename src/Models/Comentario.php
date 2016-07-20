<?php
namespace Models;

class Comentario implements \JsonSerializable
{

    private $id;

    private $texto;

    private $data;

    private $id_usuario;

    private $id_relato;

    private $alterado;

    private $nome_usuario;

    public function isAlterado()
    {
        return $this->alterado;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->alterado = true;
        $this->id = $id;
    }

    public function getTexto()
    {
        return $this->texto;
    }

    public function setTexto($texto)
    {
        $this->alterado = true;
        $this->texto = $texto;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->alterado = true;
        $this->data = $data;
    }

    public function getIdUsuario()
    {
        return $this->id_usuario;
    }

    public function setIdUsuario($id_usuario)
    {
        $this->alterado = true;
        $this->id_usuario = $id_usuario;
    }

    public function getIdRelato()
    {
        return $this->id_relato;
    }

    public function setIdRelato($id_relato)
    {
        $this->alterado = true;
        $this->id_relato = $id_relato;
    }

    public function getNomeUsuario()
    {
        return $this->nome_usuario;
    }

    public function setNomeUsuario($nome_usuario)
    {
        $this->nome_usuario = $nome_usuario;
    }

    public function toArray()
    {
        $array = [
            'id' => $this->id,
            'id_usuario' => $this->id_usuario,
            'id_relato' => $this->id_relato,
            'nome_usuario' => $this->nome_usuario,
            'data' => $this->data,
            'texto' => $this->texto
        ];
        return $array;
    }

    public static function fromArray(array $array)
    {
        $c = new Comentario();
        
        $c->id = $array['id'];
        $c->id_usuario = $array['id_usuario'];
        $c->id_relato = $array['id_relato'];
        $c->nome_usuario = $array['nome_usuario'];
        $c->data = $array['data'];
        $c->texto = $array['texto'];
        $c->alterado = false;
        
        return $c;
    }
    
    public function jsonSerialize() {
        return $this->toArray();
    }
}