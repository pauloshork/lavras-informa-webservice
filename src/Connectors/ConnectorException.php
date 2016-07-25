<?php
namespace Connectors;

class ConnectorException extends \Exception
{

    public static function fromPDOEx(\PDOException $e) {
        return new ConnectorException('PDOException: ' . $e->getMessage(), $e->getCode());
    }
    
    public static function fromStmt(\PDOStatement $stmt, $message) {
        return new ConnectorException($message . ': ' . $stmt->errorInfo()[2], $stmt->errorInfo()[1]);
    }
    
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct("{$message}", $code, $previous);
    }

    public function toArray() {
        $array = [
            'error' => [
                'code' => $this->getCode(),
                'message' => $this->getMessage()
            ]
        ];
        return $array;
    }
}