<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Medico.php';

// Leer parámetros de la petición GET
$idntfccionMdcos = isset($_GET['idntfccion_mdcos']) ? $_GET['idntfccion_mdcos'] : null;

if (!$idntfccionMdcos) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requiere el parámetro 'idntfccion_mdcos' en la URL."
    ]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$medicoModel = new Medico($db);

$resultado = $medicoModel->GetInfoMedicoCompleto($idntfccionMdcos);

if ($resultado['status'] == 'success') {
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode($resultado, JSON_PRETTY_PRINT);

$database->closeConnection();
?>
