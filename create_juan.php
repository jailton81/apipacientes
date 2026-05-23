<?php
$url = "http://localhost:8082/APIPacientes/register_medico.php";
$data = [
    'idntfccion_mdcos' => '987654321', // ID de ejemplo
    'tpo_idntfccion' => 'CC',
    'nmbres' => 'Juan Perez',
    'espcldad' => 'MED',
    'estdo' => 'A',
    'email' => 'juan.perez@example.com',
    'password' => '1234*'
];

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6IjkwMDU1NTQ0NC0xIiwibml0X2NudGJsZGFkIjoiOTAwNTU1NDQ0IiwibmFtZSI6Ikhvc3BpdGFsIGRlIE5vcnRlIGRlIFBydWViYSIsImVtYWlsIjoidGVzdEBpbnN0aXR1Y2lvbi5jb20iLCJpbnN0aXR1Y2lvbiI6dHJ1ZSwiaWF0IjoxNzc5NTU5NTUxLCJleHAiOjE3Nzk1NjY3NTF9.46zTBBhHgLdgxVePJ_0MX_e2fMUVXGw5DIw21AIp9Qk";

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nAuthorization: Bearer $token\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Respuesta del registro:\n";
echo $result . "\n";
?>
