<?php
// 1. Configurar cabeceras para retornar JSON y manejo de caracteres
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Method: GET");

// 2. Incluir los archivos de clases requeridos
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/PacienteCita.php';

// 3. Instanciar la base de datos y obtener su conexión
$database = new Database();
$db = $database->getConnection();

// 4. Leer parámetros de la petición GET
$idMedico = isset($_GET['id_medico']) ? $_GET['id_medico'] : null;
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;

if (!$idMedico || !$fechaInicio || !$fechaFin) {
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requieren los parámetros 'id_medico', 'fecha_inicio' y 'fecha_fin' en la URL (formato DD/MM/YYYY)."
    ]);
    $database->closeConnection();
    exit;
}

// 5. Instanciar el objeto PacienteCita e invocar su método
$pacienteCita = new PacienteCita($db);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$resultado = $pacienteCita->GetInfoCitas($idMedico, $fechaInicio, $fechaFin, $page, $limit);

// 6. Retornar el JSON con los resultados mapeados
echo json_encode($resultado, JSON_PRETTY_PRINT);

// 7. Cerrar la conexión
$database->closeConnection();
?>
