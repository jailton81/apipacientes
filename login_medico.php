<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Medico.php';
require_once __DIR__ . '/utils/JwtHelper.php';

$database = new Database();
$db = $database->getConnection();
$medicoModel = new Medico($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->idntfccion_mdcos) && !empty($data->password)) {
    $result = $medicoModel->loginMedico($data->idntfccion_mdcos, $data->password);

    if ($result['status'] == 'success') {
        $user = $result['user'];
        $payload = [
            "id" => $user['id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "medico" => true,
            "iat" => time(),
            "exp" => time() + (3600 * 2) // 2 horas de expiración
        ];

        $token = JwtHelper::createToken($payload);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Login exitoso",
            "token" => $token,
            "require_password_change" => $result['require_password_change'],
            "user" => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => $result['message']]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Identificación y contraseña son obligatorios."]);
}

$database->closeConnection();
?>
