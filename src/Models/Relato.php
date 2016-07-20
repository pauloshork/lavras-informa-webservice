<?php
namespace Models;

class Relato implements \JsonSerializable
{

    private $id;

    private $data;

    private $titulo;

    private $descricao;

    private $status;

    private $classificacao;

    private $foto;

    private $latitude;

    private $longitude;

    private $id_usuario;
    
    private $alterado;
    
    private $nome_usuario;
    
    public function isAlterado() {
        return $this->alterado;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setId($id) {
        $this->alterado = true;
        $this->id = $id;
    }
    
    public function getData() {
        return $this->data;
    }
    
    public function setData($data) {
        $this->data = $data;
    }

    public function getTitulo() {
        return $this->titulo;
    }
    
    public function setTitulo($titulo) {
        $this->alterado = true;
        $this->titulo = $titulo;
    }
    
    public function getDescricao() {
        return $this->descricao;
    }
    
    public function setDescricao($descricao) {
        $this->alterado = true;
        $this->descricao = $descricao;
    }

    public function getStatus() {
        return $this->status;
    }
    
    public function setStatus($status) {
        $this->alterado = true;
        $this->status = $status;
    }
    
    public function getClassificacao() {
        return $this->classificacao;
    }
    
    public function setClassificacao($classificacao) {
        $this->alterado = true;
        $this->classificacao = $classificacao;
    }

    public function getFoto() {
        return $this->foto;
    }
    
    public function setFoto($foto) {
        $this->alterado = true;
        $this->foto = $foto;
    }
    
    public function getLatitude() {
        return $this->latitude;
    }
    
    public function setLatitude($latitude) {
        $this->alterado = true;
        $this->latitude = $latitude;
    }
    
    public function getLongitude() {
        return $this->longitude;
    }
    
    public function setLongitude($longitude) {
        $this->alterado = true;
        $this->longitude = $longitude;
    }
    
    public function getIdUsuario() {
        return $this->id_usuario;
    }
    
    public function setIdUsuario($id_usuario) {
        $this->alterado = true;
        $this->id_usuario = $id_usuario;
    }
    
    public function getNomeUsuario() {
        return $this->nome_usuario;
    }
    
    public function setNomeUsuario($nome_usuario) {
        $this->nome_usuario = $nome_usuario;
    }

    /**
     * @return array
     */
    public function toArray() {
        $array = [
            'id' => $this->id,
            'data' => $this->data,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'status' => $this->status,
            'classificacao' => $this->classificacao,
            'foto' => $this->foto,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'id_usuario' => $this->id_usuario,
            'nome_usuario' => $this->nome_usuario
        ];
        return $array;
    }

    public static function fromArray(array $array) {
        $r = new Relato();
        
        $r->id = $array['id'];
        $r->data = $array['data'];
        $r->titulo = $array['titulo'];
        $r->descricao = $array['descricao'];
        $r->status = $array['status'];
        $r->classificacao = $array['classificacao'];
        $r->foto = $array['foto'];
        $r->latitude = $array['latitude'];
        $r->longitude = $array['longitude'];
        $r->id_usuario = $array['id_usuario'];
        $r->nome_usuario = $array['nome_usuario'];
        $r->alterado = false;
        
        return $r;
    }
    
    public function jsonSerialize() {
        return $this->toArray();
    }
}