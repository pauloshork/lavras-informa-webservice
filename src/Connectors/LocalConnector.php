<?php
namespace Connectors;

use OAuth2\Storage\UserCredentialsInterface;

class LocalConnector extends BaseConnector
{

    /**
     * Verifica as credenciais do usuário.
     *
     * {@inheritdoc}
     *
     * @see \OAuth2\Storage\UserCredentialsInterface::checkUserCredentials()
     */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($password, $user);
        }
        
        return false;
    }

    /**
     * Realiza a comparação de hashes de senha do usuário e, se necessário,
     * atualiza o hash.
     *
     * @param Usuario $user
     *            Usuário do sistema para se verificar a senha
     * @param string $password
     *            Senha para ser validada
     * @return boolean Se a senha fornecida é a mesma que está armazenada
     */
    protected function checkPassword($password, $user)
    {
        if (! password_verify($password, $user->senha)) {
            return false;
        } else {
            if (password_needs_rehash($user->senha, $this->config['security']['algo'], $this->config['security']['options'])) {
                $user->setSenha(password_hash($password, $this->config['security']['algo'], $this->config['security']['options']));
                return setUser($user);
            } else {
                return true;
            }
        }
    }

    /**
     * Busca um usuário pelo email do sistema de oauth local.
     *
     * @param string $username
     *            Email do usuário
     * @return boolean|Usuario O usuário do sistema ou false se não existe
     */
    public function getUser($username)
    {
        if ($user = $this->getUserOn('l.email', $username)) {
            return $user;
        }
        return false;
    }
}
