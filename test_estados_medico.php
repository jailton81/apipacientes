<?php
/**
 * Ejemplo de Consumo de la APIPacientes
 * Flujo:
 * 1. Loguear un médico en login_medico.php para obtener su token JWT de sesión.
 * 2. Consultar el nuevo endpoint getEstados.php usando el token JWT obtenido.
 */

// Configuración de la URL base del servidor local
$baseUrl = "http://localhost:8082/APIPacientes";

// Datos de prueba para el médico (ID y contraseña por defecto)
$medicoId = "987654321";
$password = "987654321"; // El password inicial suele ser la misma identificación si no se especificó otro en el registro

echo "========================================================\n";
echo "1. INICIANDO SESIÓN DEL MÉDICO ($medicoId)...\n";
echo "========================================================\n";

$loginUrl = "$baseUrl/login_medico.php";
$loginPayload = [
    "idntfccion_mdcos" => $medicoId,
    "password" => $password
];

$loginOptions = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n",
        "content" => json_encode($loginPayload),
        "ignore_errors" => true
    ]
];

$loginContext = stream_context_create($loginOptions);
$loginResponse = file_get_contents($loginUrl, false, $loginContext);

echo "Respuesta del Login:\n$loginResponse\n\n";

$loginData = json_decode($loginResponse, true);

if (!$loginData || $loginData['status'] !== 'success') {
    echo "[-] Error: No se pudo autenticar al médico. Asegúrese de que el médico esté registrado en la base de datos y activo.\n";
    exit;
}

$token = $loginData['token'];
echo "[+] Token JWT obtenido con éxito:\n$token\n\n";

echo "========================================================\n";
echo "2. OBTENIENDO ESTADOS CON EL TOKEN DEL MÉDICO...\n";
echo "========================================================\n";

// Queremos obtener los estados del tipo AllergyIntoleranceClinicalStatusCodes
$statusGroup = "AllergyIntoleranceClinicalStatusCodes";
$getEstadosUrl = "$baseUrl/getEstados.php?status=" . urlencode($statusGroup);

$getOptions = [
    "http" => [
        "method" => "GET",
        "header" => "Content-Type: application/json\r\n" .
                    "Authorization: Bearer $token\r\n",
        "ignore_errors" => true
    ]
];

$getContext = stream_context_create($getOptions);
$getResponse = file_get_contents($getEstadosUrl, false, $getContext);

echo "Respuesta del Endpoint getEstados.php:\n$getResponse\n";
echo "========================================================\n";
?>
