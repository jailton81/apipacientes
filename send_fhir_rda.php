<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Método no permitido. Solo se acepta POST."
    ]);
    exit;
}

// Validar token de seguridad JWT Bearer (sesión del médico)
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

// Leer parámetros de la petición POST
$data = json_decode(file_get_contents("php://input"));

$sendUrl = isset($data->send_url) ? $data->send_url : null;
$token = isset($data->token) ? $data->token : null;
$fhirData = isset($data->fhir_data) ? $data->fhir_data : null;

if (!$sendUrl || !$token || !$fhirData) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan parámetros requeridos (send_url, token, fhir_data)."
    ]);
    exit;
}

// Enviar al Webservice de MinSalud/Ihcecol RDA
$body = json_encode($fhirData, JSON_UNESCAPED_UNICODE);

$ch = curl_init($sendUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $token,
    "Content-Type: application/json; charset=utf-8",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evitar problemas con certificados SSL locales o de pruebas

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error de cURL al enviar el JSON FHIR al webservice: " . $curlError
    ]);
} else {
    // Retornar siempre código de éxito 200 del proxy para que el JS no lance error Ajax y pueda renderizar la respuesta
    http_response_code(200);
    $decodedResponse = json_decode($response);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode([
            "status" => $httpCode,
            "response" => $decodedResponse
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "status" => $httpCode,
            "response" => $response
        ]);
    }
}
?>
