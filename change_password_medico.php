<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Medico.php';
// Nota: Se elimina la verificación de token para permitir que los médicos
// cambien su contraseña por defecto inicial (ya que no pueden loguearse aún).

$database = new Database();
$db = $database->getConnection();
$medicoModel = new Medico($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->idntfccion_mdcos) && !empty($data->old_password) && !empty($data->new_password)) {
    
    $result = $medicoModel->changePassword($data->idntfccion_mdcos, $data->old_password, $data->new_password);
    
    if ($result['status'] == 'success') {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Los campos idntfccion_mdcos, old_password y new_password son obligatorios."]);
}

$database->closeConnection();
?>
