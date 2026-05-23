<?php
require_once __DIR__ . '/config/Database.php';

$database = new Database();
$db = $database->getConnection();

$email = "test@institucion.com";
$password_plain = "secret123";
$password_hash = password_hash($password_plain, PASSWORD_BCRYPT);

// Actualizar cualquier registro de insttciones (LIMIT 1 en oracle es con rownum)
$sql = "UPDATE insttciones SET email = :email, password = :password WHERE rownum = 1";
$stid = oci_parse($db, $sql);
oci_bind_by_name($stid, ":email", $email);
oci_bind_by_name($stid, ":password", $password_hash);

if (oci_execute($stid)) {
    echo "Registro actualizado en insttciones.\n";
} else {
    echo "Error al actualizar registro.\n";
}
oci_commit($db);
$database->closeConnection();

// Ahora intentamos hacer el login
$url = "http://localhost:8082/APIPacientes/login_institucion.php";
$data = ['email' => $email, 'password' => $password_plain];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];
$context  = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error en la petición de login.\n";
} else {
    echo "Respuesta del login:\n";
    echo $result . "\n";
}
?>
