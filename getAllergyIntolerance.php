<?php
// 1. Configurar cabeceras para retornar JSON y manejo de caracteres
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Method: GET");

// 2. Incluir los archivos de clases requeridos
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/AllergyIntolerance.php';

// 3. Instanciar la base de datos y obtener su conexión
$database = new Database();
$db = $database->getConnection();

// 4. Leer parámetros de la petición GET
$idPcnte = isset($_GET['id_pcnte']) ? $_GET['id_pcnte'] : null;

if (!$idPcnte) {
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requiere el parámetro 'id_pcnte' en la URL."
    ]);
    $database->closeConnection();
    exit;
}

// 5. Instanciar el objeto AllergyIntolerance e invocar su método
$allergyIntolerance = new AllergyIntolerance($db);
$resultado = $allergyIntolerance->GetInfoAllergyIntolerance($idPcnte);

// 6. Retornar el JSON con los resultados
echo json_encode($resultado, JSON_PRETTY_PRINT);

// 7. Cerrar la conexión
$database->closeConnection();
?>
