<?php
namespace Models;

class Usuario extends AbstractModel
{

    private $local;

    private $facebook;

    public function __construct(array $data = [])
    {
        $fields = [
            'id',
            'admin',
            'email',
            'senha',
            'nome',
            'fb_user_id',
            'fb_email',
            'fb_nome'
        ];
        parent::__construct($fields, $data);
    }

    protected function init()
    {
        $this->local = $this->hasLocalOAuth();
        $this->facebook = $this->hasFacebookOAuth();
    }

    public function __get_admin($data)
    {
        if (isset($data['admin'])) {
            return boolval($data['admin']);
        } else {
            return null;
        }
    }

    public function __set_admin($admin)
    {
        return intval($admin);
    }

    public function __set_senha($senha)
    {
        if (is_null($senha)) {
            return null;
        } else {
            return password_hash($senha, \Config::config['security']['algo'], \Config::config['security']['options']);
        }
    }

    public function __get_preferred_email($data)
    {
        if ($this->hasFacebookOAuth()) {
            return $this->fb_email;
        } else {
            return $this->email;
        }
    }

    public function __get_preferred_nome($data)
    {
        if ($this->hasFacebookOAuth()) {
            return $this->fb_nome;
        } else {
            return $this->nome;
        }
    }

    /**
     * Converte o modelo em um array seguro para uso com o aplicativo.
     *
     * @return array Representação segura do modelo
     */
    public function toSafeArray()
    {
        $array = [
            'id' => $this->id,
            'email' => $this->preferred_email,
            'nome' => $this->preferred_nome,
            'admin' => $this->admin
        ];
        return $array;
    }

    public function getUsuariosArray($keys = null)
    {
        $array = $this->toArray([
            'id',
            'admin'
        ]);
        $this->select_key($array, $keys);
        return $array;
    }

    /**
     *
     * @return boolean Diz se o usuário tem dados locais.
     */
    public function hasLocalOAuth()
    {
        return isset($this->email) && ! is_null($this->email);
    }

    /**
     * Diz se o usuário tinha dados locais antes de ser alterado.
     */
    public function hadLocalOAuth()
    {
        return $this->local;
    }

    public function getLocalOAuthArray($keys = null)
    {
        if ($this->hasLocalOAuth()) {
            $array = $this->toArray([
                'id',
                'email',
                'senha',
                'nome'
            ]);
            $this->select_key($array, $keys);
            return $array;
        } else {
            return null;
        }
    }

    /**
     *
     * @return boolean Diz se o usuário tem dados do facebook.
     */
    public function hasFacebookOAuth()
    {
        return isset($this->fb_user_id) && ! is_null($this->fb_user_id);
    }

    /**
     *
     * @return boolean Diz se o usuário tinha dados do facebook antes de ser alterado.
     */
    public function hadFacebookOAuth()
    {
        return $this->facebook;
    }

    public function getFacebookOAuthArray($keys = null)
    {
        if ($this->hasFacebookOAuth()) {
            $array = $this->toArray([
                'id',
                'fb_user_id',
                'fb_email',
                'fb_nome'
            ]);
            $this->select_key($array, $keys);
            return $array;
        } else {
            return null;
        }
    }
}