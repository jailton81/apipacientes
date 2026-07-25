<?php
// 1. Configurar cabeceras para retornar JSON y permitir peticiones GET
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// 2. Incluir los archivos de clases requeridos y middleware
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/IUM.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
AuthMiddleware::checkToken();

// 3. Instanciar la base de datos y obtener su conexión
$database = new Database();
$db = $database->getConnection();

// 4. Leer parámetro de búsqueda 'search' desde la petición GET
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 5. Instanciar el objeto IUM e invocar su método
$iumModel = new IUM($db);
$resultado = $iumModel->GetInfoIUM($search);

// 6. Retornar el JSON con los resultados
if (isset($resultado['status']) && $resultado['status'] === 'error') {
    http_response_code(500);
} else {
    http_response_code(200);
}
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// 7. Cerrar la conexión
$database->closeConnection();
?>
