<?php
namespace Models;

class Usuario implements \JsonSerializable
{

    private $id;

    private $admin;

    private $email;

    private $senha;

    private $nome;

    private $fb_user_id;

    private $fb_email;

    private $fb_nome;

    private $local;

    private $facebook;

    private $alterado;

    /**
     *
     * @return boolean
     */
    public function isAlterado()
    {
        return $this->alterado;
    }

    /**
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return bool|null
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     *
     * @return string|null
     */
    public function getFbEmail()
    {
        return $this->fb_email;
    }

    /**
     *
     * @return string|null
     */
    public function getNome()
    {
        return $this->nome;
    }

    /**
     *
     * @return string|null
     */
    public function getFbNome()
    {
        return $this->fb_nome;
    }

    /**
     *
     * @return string|null
     */
    public function getSenha()
    {
        return $this->senha;
    }

    /**
     *
     * @return string|null
     */
    public function getFbUserId()
    {
        return $this->fb_user_id;
    }

    public function setId($id)
    {
        $this->alterado = true;
        $this->id = $id;
    }

    public function setAdmin($admin)
    {
        $this->alterado = true;
        $this->admin = $admin;
    }

    public function setEmail($email)
    {
        $this->alterado = true;
        $this->email = $email;
    }

    public function setFbEmail($fb_email)
    {
        $this->alterado = true;
        $this->fb_email = $fb_email;
    }

    public function setNome($nome)
    {
        $this->alterado = true;
        $this->nome = $nome;
    }

    public function setFbNome($fb_nome)
    {
        $this->alterado = true;
        $this->fb_nome = $fb_nome;
    }

    public function setSenha($senha)
    {
        $this->alterado = true;
        if (is_null($senha)) {
            $this->senha = null;
        } else {
            $this->senha = password_hash($senha, \Config::$config['security']['algo'], \Config::$config['security']['options']);
        }
    }

    public function setFbUserId($fb_user_id)
    {
        $this->alterado = true;
        $this->fb_user_id = $fb_user_id;
    }

    public function getPreferredEmail()
    {
        if (is_null($this->getFbEmail())) {
            return $this->getEmail();
        } else {
            return $this->getFbEmail();
        }
    }

    public function getPreferredNome()
    {
        if (is_null($this->getFbNome())) {
            return $this->getNome();
        } else {
            return $this->getFbNome();
        }
    }

    /**
     * Converte o modelo em um objeto JSON seguro para uso com o aplicativo.
     *
     * @return string JSON representando o modelo
     */
    public function jsonSerialize()
    {
        $array = [
            'id' => $this->getId(),
            'email' => $this->getPreferredEmail(),
            'nome' => $this->getPreferredNome(),
            'admin' => $this->isAdmin()
        ];
        return $array;
    }

    public static function fromArray(array $usuario)
    {
        $u = new Usuario();
        $u->id = $usuario['id'];
        $u->admin = $usuario['admin'];
        
        $u->email = $usuario['email'];
        $u->senha = $usuario['senha'];
        $u->nome = $usuario['nome'];
        
        $u->fb_user_id = $usuario['fb_user_id'];
        $u->fb_email = $usuario['fb_nome'];
        $u->fb_nome = $usuario['fb_nome'];
        
        $u->local = $u->hasLocalOAuth();
        $u->facebook = $u->hasFacebookOAuth();
        $u->alterado = false;
        return $u;
    }

    public function getUsuariosArray()
    {
        $array = [
            'id' => $this->id,
            'admin' => $this->admin || false
        ];
        return $array;
    }

    /**
     *
     * @return boolean Diz se o usuário tem dados locais.
     */
    public function hasLocalOAuth()
    {
        return ! is_null($this->email);
    }

    /**
     * Diz se o usuário tinha dados locais antes de ser alterado.
     */
    public function hadLocalOAuth()
    {
        return $this->local;
    }

    public function getLocalOAuthArray()
    {
        if ($this->hasLocalOAuth()) {
            $array = [
                'id_usuario' => $this->id,
                'email' => $this->email,
                'senha' => $this->senha,
                'nome' => $this->nome
            ];
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
        return ! is_null($this->fb_user_id);
    }

    /**
     *
     * @return boolean Diz se o usuário tinha dados do facebook antes de ser alterado.
     */
    public function hadFacebookOAuth()
    {
        return $this->facebook;
    }

    public function getFacebookOAuthArray()
    {
        if ($this->hasFacebookOAuth()) {
            $array = [
                'id_usuario' => $this->id,
                'fb_user_id' => $this->fb_user_id,
                'fb_email' => $this->fb_email,
                'fb_nome' => $this->fb_nome
            ];
            return $array;
        } else {
            return null;
        }
    }
}