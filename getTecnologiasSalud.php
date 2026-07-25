<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/TecnologiasSalud.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
$payload = AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$model = new TecnologiasSalud($db);

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : null;

if ($codigo !== null && $codigo !== '') {
    // 1. Endpoint de verificación (GET por ID/Código)
    $result = $model->obtenerPorCodigo($codigo);
    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(404);
    }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    // 2. Endpoint de autocompletado (Búsqueda rápida con filtros q y grupo)
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $grupo = isset($_GET['grupo']) ? trim($_GET['grupo']) : null;

    $result = $model->buscar($q, $grupo);
    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(500);
    }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
