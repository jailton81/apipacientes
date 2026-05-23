<?php
// 1. Configurar cabeceras para retornar JSON y manejo de caracteres
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Method: GET");

// 2. Incluir los archivos de clases requeridos
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Organization.php';

// 3. Instanciar la base de datos y obtener su conexión
$database = new Database();
$db = $database->getConnection();

// 4. Leer parámetros de la petición GET
$nitInsttcion = isset($_GET['nit_insttcion']) ? $_GET['nit_insttcion'] : null;

if (!$nitInsttcion) {
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requiere el parámetro 'nit_insttcion' en la URL."
    ]);
    $database->closeConnection();
    exit;
}

// 5. Instanciar el objeto Organization e invocar su método
$organization = new Organization($db);
$resultado = $organization->GetInfoOrganization($nitInsttcion);

// 6. Retornar el JSON con los resultados mapeados
echo json_encode($resultado, JSON_PRETTY_PRINT);

// 7. Cerrar la conexión
$database->closeConnection();
?>
