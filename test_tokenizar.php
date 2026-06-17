<?php
header("Content-Type: text/plain; charset=UTF-8");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Medico.php';
require_once __DIR__ . '/utils/JwtHelper.php';

$database = new Database();
$db = $database->getConnection();
$medicoModel = new Medico($db);

// Datos del médico a probar
$medicoId = "1001778460";
$password = "234567";

echo "========================================================\n";
echo "1. AUTENTICANDO AL MÉDICO (PHP DIRECTO)\n";
echo "========================================================\n";

$loginResult = $medicoModel->loginMedico($medicoId, $password);

if ($loginResult['status'] === 'success') {
    $user = $loginResult['user'];
    
    // Generar el token JWT de login
    $payload = [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "medico" => true,
        "iat" => time(),
        "exp" => time() + (3600 * 2)
    ];
    $token = JwtHelper::createToken($payload);
    echo "[+] Login Exitoso. Token JWT generado:\n$token\n\n";

    echo "========================================================\n";
    echo "2. OBTENIENDO DATOS PARA TOKENIZAR (PHP DIRECTO VIA MÉTODO DEL MODELO)\n";
    echo "========================================================\n";

    $tokenizarResult = $medicoModel->getDatosTokenizar($medicoId, $token);
    echo json_encode($tokenizarResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    echo "========================================================\n";
    echo "3. CONSUMIENDO ENDPOINT API HTTP (POST) CON TOKEN BEARER\n";
    echo "========================================================\n";
    
    $baseUrl = "http://localhost:8082/APIPacientes";
    $endpointUrl = "$baseUrl/get_datos_tokenizar.php";
    $postData = json_encode([
        "idntfccion_mdcos" => $medicoId
    ]);
    
    echo "Enviando POST a: $endpointUrl\n";
    echo "Body JSON: $postData\n";
    echo "Header: Authorization: Bearer $token\n\n";
    
    $ch = curl_init($endpointUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status Code: $httpCode\n";
    echo "JSON Response:\n$response\n";

    echo "========================================================\n";
    echo "4. PROBANDO RECHAZO DE MÉTODO GET (405 METHOD NOT ALLOWED)\n";
    echo "========================================================\n";
    
    $ch = curl_init($endpointUrl . "?idntfccion_mdcos=" . $medicoId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    
    $responseGet = curl_exec($ch);
    $httpCodeGet = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status Code (debe ser 405): $httpCodeGet\n";
    echo "JSON Response:\n$responseGet\n";

} else {
    echo "[-] Login fallido para $medicoId. Verifique las credenciales en la base de datos.\n";
}

$database->closeConnection();
?>
