<?php
// 1. Configurar cabeceras para retornar JSON y manejo de caracteres
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Method: GET");

// 2. Incluir los archivos de clases requeridos
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Paciente.php';

// 3. Instanciar la base de datos y obtener su conexión
$database = new Database();
$db = $database->getConnection();

// 4. Leer parámetros de la petición GET
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;

if (!$fechaInicio || !$fechaFin) {
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requieren los parámetros 'fecha_inicio' y 'fecha_fin' en la URL (formato DD/MM/YYYY)."
    ]);
    $database->closeConnection();
    exit;
}

// 5. Instanciar el objeto Paciente e invocar su método
$paciente = new Paciente($db);
$resultado = $paciente->getPacientesPorRangoTemporal($fechaInicio, $fechaFin);

// 6. Retornar el JSON con los resultados mapeados
echo json_encode($resultado, JSON_PRETTY_PRINT);

// 7. Cerrar la conexión
$database->closeConnection();
?>