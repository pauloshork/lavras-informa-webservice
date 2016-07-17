<?php
require_once 'base.php';

$usuario = json_decode(file_get_contents('php://input'));

$response = Database\autenticar($usuario);
echo json_encode($response);
