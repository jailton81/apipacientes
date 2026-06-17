<?php
// Configurar cabeceras para retornar JSON y manejo de caracteres
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Validar que el método de petición sea estrictamente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Método no permitido. Solo se acepta la petición vía POST."
    ]);
    exit;
}

// Incluir los archivos de clases requeridos y middleware
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Medico.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT Bearer (retornará 401 si no es válido o no está presente)
$payload = AuthMiddleware::checkToken();

// Obtener el token de los encabezados para pasarlo al modelo
$headers = apache_request_headers();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

// Instanciar la base de datos y obtener su conexión
$database = new Database();
$db = $database->getConnection();

// Leer parámetros de la petición POST (JSON)
$data = json_decode(file_get_contents("php://input"));
$idntfccionMdcos = isset($data->idntfccion_mdcos) ? $data->idntfccion_mdcos : null;

if (!$idntfccionMdcos) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Se requiere el parámetro 'idntfccion_mdcos' en el cuerpo JSON de la petición."
    ]);
    $database->closeConnection();
    exit;
}

// Instanciar el objeto Medico e invocar el método
$medico = new Medico($db);
$resultado = $medico->getDatosTokenizar($idntfccionMdcos, $token);

if ($resultado['status'] === 'success') {
    http_response_code(200);
} else {
    http_response_code(404);
}

// Retornar el JSON con los resultados
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Cerrar la conexión
$database->closeConnection();
?>
