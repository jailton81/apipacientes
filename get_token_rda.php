<?php
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

// Incluir middleware para validar token de seguridad JWT Bearer
require_once __DIR__ . '/utils/AuthMiddleware.php';
try {
    $payload = AuthMiddleware::checkToken();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "No autorizado: " . $e->getMessage()
    ]);
    exit;
}

// Leer parámetros de la petición POST (JSON)
$data = json_decode(file_get_contents("php://input"));

$tokenUrl = isset($data->token_url) ? $data->token_url : null;
$clientId = isset($data->client_id) ? $data->client_id : null;
$clientSecret = isset($data->client_secret) ? $data->client_secret : null;
$scope = isset($data->scope) ? $data->scope : null;

if (!$tokenUrl || !$clientId || !$clientSecret || !$scope) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan parámetros requeridos (token_url, client_id, client_secret, scope)."
    ]);
    exit;
}

// Preparar los campos de la petición en formato application/x-www-form-urlencoded para OAuth2
$postFields = http_build_query([
    'grant_type' => 'client_credentials',
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'scope' => $scope
]);

// Inicializar cURL
$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/x-www-form-urlencoded"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evitar problemas con certificados autofirmados en local

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error de cURL al conectar con el servidor RDA: " . $curlError
    ]);
} else {
    http_response_code($httpCode);
    // Retornar la respuesta (que ya es JSON)
    echo $response;
}
?>
