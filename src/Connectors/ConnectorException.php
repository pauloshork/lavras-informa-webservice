<?php
namespace Connectors;

class ConnectorException extends \Exception
{

    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function toJson()
    {
        $array = [
            'error' => [
                'code' => $this->getCode(),
                'message' => $this->getMessage()
            ]
        ];
        return json_encode($array);
    }
}