<?php
namespace Models;

class Busca
{

    private $titulo;

    private $autor;

    private $data;

    private $status;

    private $classificacao;

    private $foto;

    public function getTitulo()
    {
        return $this->titulo;
    }

    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
    }

    public function getAutor()
    {
        return $this->autor;
    }

    public function setAutor($autor)
    {
        $this->autor = $autor;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getstatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getClassificacao()
    {
        return $this->classificacao;
    }

    public function setClassificacao($classificacao)
    {
        $this->classificacao = $classificacao;
    }

    public function getFoto()
    {
        return $this->foto;
    }

    public function setFoto($foto)
    {
        $this->foto = $foto;
    }

    public function toArray()
    {
        $array = [];
        
        if (! is_null($this->titulo)) {
            $array['titulo'] = $this->titulo;
        }
        
        if (! is_null($this->autor)) {
            $array['autor'] = $this->autor;
        }
        
        if (! is_null($this->data)) {
            $array['data'] = $this->data;
        }
        
        if (! is_null($this->status)) {
            $array['status'] = $this->status;
        }
        
        if (! is_null($this->classificacao)) {
            $array['classificacao'] = $this->classificacao;
        }
        
        if (! is_null($this->foto)) {
            $array['foto'] = $this->foto;
        }
        
        return $array;
    }
}