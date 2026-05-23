<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Auth.php';
require_once __DIR__ . '/utils/JwtHelper.php';

$database = new Database();
$db = $database->getConnection();

$auth = new Auth($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    $result = $auth->loginInstitucion($data->email, $data->password);
    
    if ($result['status'] == 'success') {
        $user = $result['user'];
        $payload = [
            "id" => $user['id'],
            "nit_cntbldad" => $user['nit_cntbldad'],
            "name" => $user['name'],
            "email" => $user['email'],
            "institucion" => true,
            "iat" => time(),
            "exp" => time() + (3600 * 2) // 2 horas de expiración
        ];

        $token = JwtHelper::createToken($payload);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Inicio de sesión de institución exitoso.",
            "token" => $token,
            "user" => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email y password son requeridos."]);
}

$database->closeConnection();
?>
