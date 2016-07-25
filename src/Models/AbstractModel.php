<?php
namespace Models;

use OAuth2\RequestInterface;

abstract class AbstractModel implements \JsonSerializable
{

    private $fields;

    private $data;

    private $dirty;

    public function __construct(array $fields, array $data = [], array $dirty = [])
    {
        $this->fields = $fields;
        $this->select_key($data, $fields);
        $this->data = $data;
        $this->select($dirty, $fields);
        $this->dirty = $dirty;
        $this->init();
    }

    /**
     * Callback para inicialização de campos auxiliares.
     * É executado ao fim da inicialização dos campos primitivos do modelo.
     */
    protected function init()
    {
        // vazio
    }

    /**
     * Remove itens não selecionados do array.
     * 
     * @param array $all
     *            Array com campos indesejados.
     * @param array $filter
     *            Array com campos desejados.
     */
    protected function select(array &$all, array $filter)
    {
        if (! is_null($filter)) {
            $all = array_intersect($all, $filter);
        }
    }

    /**
     * Remove itens não selecionados do array pela chave.
     * 
     * @param array $all
     *            Array com chaves indesejadas.
     * @param array $filter
     *            Array com as chaves desejadas.
     */
    protected function select_key(array &$all, array $filter = null)
    {
        if (! is_null($filter)) {
            $select = [];
            foreach ($filter as $k) {
                $select[$k] = true;
            }
            $all = array_intersect_key($all, $select);
        }
    }

    public function __get($name)
    {
        $getter = $this->get_getter($name);
        if (method_exists($this, $getter)) {
            return $this->$getter($this->data);
        } else {
            if ($this->hasPrimitiveField($name) && !isset($this->data[$name])) {
                return null;
            } else {
                return $this->data[$name];
            }
        }
    }

    public function __set($name, $value)
    {
        $setter = $this->get_setter($name);
        
        if ($this->hasField($name)) {
            $this->setDirty($name);
            if (method_exists($this, $setter)) {
                $value = $this->$setter($value);
            }
            $this->data[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return $this->hasField($name);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    private function get_getter($name)
    {
        return "__get_{$name}";
    }

    private function get_setter($name)
    {
        return "__set_{$name}";
    }

    public function getPrimitiveFields()
    {
        return $this->fields;
    }

    protected function hasField($name)
    {
        $getter = $this->get_getter($name);
        return method_exists($this, $getter) || $this->hasPrimitiveField($name);
    }
    
    protected function hasPrimitiveField($name) {
        return in_array($name, $this->fields);
    }

    protected function isDirty($name)
    {
        return in_array($name, $this->dirty);
    }

    private function setDirty($name)
    {
        if (! $this->isDirty($name)) {
            $this->dirty[] = $name;
        }
    }

    public function clearDirty()
    {
        $this->dirty = [];
    }

    public function toArray(array $filter = null)
    {
        $array = $this->data;
        $this->select_key($array, $filter);
        return $array;
    }

    public function initFromRequest(RequestInterface $request)
    {
        $this->data = [];
        foreach ($this->fields as $f) {
            $this->__set($f, $request->request($f));
        }
        $this->dirty = [];
        
        $this->init();
        
        return $this;
    }
    
    public function jsonSerialize() {
        return $this->toArray();
    }
}