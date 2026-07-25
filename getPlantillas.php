<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Plantillas.php';

$database = new Database();
$db = $database->getConnection();

$plantillasModel = new Plantillas($db);

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : (isset($_GET['s_tpo']) ? $_GET['s_tpo'] : null);

// Obtener los datos desde el modelo
if ($tipo === 'MEDICAMENTO') {
    $response = $plantillasModel->GetInfoPlantillaMedicamentos();
} else {
    $response = $plantillasModel->GetInfoPlantilla($tipo);
}

// Enviar la respuesta JSON
echo $response;

$database->closeConnection();
?>
