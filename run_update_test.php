<?php
require_once __DIR__ . '/config/Database.php';

// 1. Iniciar sesión para obtener el token JWT fresco
echo "Iniciando sesión del operador...\n";
$login_url = "http://localhost:8082/APIPacientes/login_institucion.php";
$login_data = [
    'email' => 'test@institucion.com',
    'password' => 'secret123'
];

$login_options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($login_data),
        'ignore_errors' => true
    ]
];
$login_context  = stream_context_create($login_options);
$login_response = file_get_contents($login_url, false, $login_context);
$login_result = json_decode($login_response);

if (!$login_result || $login_result->status !== 'success') {
    die("Error en el login: " . $login_response);
}

$token = $login_result->token;
echo "Token obtenido con éxito.\n\n";

// 2. Asegurar que el médico 987654321 esté registrado y vinculado
echo "Registrando/Vinculando médico 987654321...\n";
$reg_url = "http://localhost:8082/APIPacientes/register_medico.php";
$reg_data = [
    'idntfccion_mdcos' => '987654321',
    'tpo_idntfccion' => 'CC',
    'nmbres' => 'Juan Perez',
    'espcldad' => 'MED',
    'estdo' => 'A',
    'email' => 'juan.perez@example.com'
];

$reg_options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nAuthorization: Bearer $token\r\n",
        'method'  => 'POST',
        'content' => json_encode($reg_data),
        'ignore_errors' => true
    ]
];
$reg_context  = stream_context_create($reg_options);
$reg_response = file_get_contents($reg_url, false, $reg_context);
echo "Respuesta del registro: " . $reg_response . "\n\n";

// 3. Ejecutar la actualización del médico via update_medico.php
echo "Actualizando médico 987654321...\n";
$update_url = "http://localhost:8082/APIPacientes/update_medico.php";
$update_data = [
    'idntfccion_mdcos' => '987654321',
    'nmbres' => 'Juan Perez Actualizado',
    'espcldad' => 'CARDI',
    'email' => 'juan.perez.updated@example.com',
    'estdo' => 'A'
];

$update_options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nAuthorization: Bearer $token\r\n",
        'method'  => 'POST',
        'content' => json_encode($update_data),
        'ignore_errors' => true
    ]
];
$update_context  = stream_context_create($update_options);
$update_response = file_get_contents($update_url, false, $update_context);
echo "Respuesta de la actualización: " . $update_response . "\n\n";

// 4. Consultar de nuevo el médico para comprobar que cambió
echo "Verificando en base de datos...\n";
$database = new Database();
$db = $database->getConnection();
$stid = oci_parse($db, "SELECT idntfccion_mdcos, nmbres, espcldad, email FROM mdcos WHERE idntfccion_mdcos = '987654321'");
oci_execute($stid);
$row = oci_fetch_array($stid, OCI_ASSOC);
print_r($row);
$database->closeConnection();
?>
