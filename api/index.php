<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Content-Type");

if (isset($_GET['path'])) {
    $path = explode('/', $_GET['path']);
} else {

    $response = array(
        "message" => "Nenhum equipamento encontrado!"
    );

    echo $response;
    exit;

    echo 'caminho não existe';
    exit;
}

$api = $path[0];

if (isset($path[1])) {
    $acao = $path[1];
} else {
    $acao = '';
}

if (isset($path[2])) {
    $parametro = $path[2];
} else {
    $parametro = '';
}

$metodo = $_SERVER['REQUEST_METHOD'];



include_once './database/DB.php';
include_once './controllers/listaEscalas.php';
include_once './controllers/operadoresController.php';
include_once './controllers/equipamentosCotroller.php';
