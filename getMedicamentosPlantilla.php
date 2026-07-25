<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Plantillas.php';

$database = new Database();
$db = $database->getConnection();

$plantillasModel = new Plantillas($db);

$cdgo_plntlla = isset($_GET['cdgo_plntlla']) ? $_GET['cdgo_plntlla'] : null;

if (!$cdgo_plntlla) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requiere el parámetro 'cdgo_plntlla' en la URL."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $database->closeConnection();
    exit;
}

// Obtener los datos desde el modelo (el método valida token internamente)
$response = $plantillasModel->GetMedicamentosPlantilla($cdgo_plntlla);

// Enviar la respuesta JSON
echo $response;

$database->closeConnection();
?>
