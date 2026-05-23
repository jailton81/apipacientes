<?php
// Configurar cabeceras para retornar JSON y manejo de caracteres
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Method: GET");

// Incluir los archivos de clases requeridos
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Patient.php';

// Instanciar la base de datos y obtener su conexión
$database = new Database();
$db = $database->getConnection();

// Leer parámetros de la petición GET
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requiere el parámetro 'id' en la URL."
    ]);
    $database->closeConnection();
    exit;
}

// Instanciar el objeto Patient e invocar su método
$patient = new Patient($db);
$resultado = $patient->GetInfoPatient($id);

// Retornar el JSON con los resultados mapeados
echo json_encode($resultado, JSON_PRETTY_PRINT);

// Cerrar la conexión
$database->closeConnection();
?>
