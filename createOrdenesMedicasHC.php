<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/OrdenesMedicasHC.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
$payload = AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();
$model = new OrdenesMedicasHC($db);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->ordenes) && is_array($data->ordenes)) {
    // Procesa inserción masiva (bulk)
    $results = [];
    $success = true;
    
    // Obtener un número compartido para consecutivo_remision si no se especifica individualmente
    $firstOrder = $data->ordenes[0];
    $id_pcnte = !empty($firstOrder->id_pcnte) ? substr($firstOrder->id_pcnte, 0, 20) : (!empty($firstOrder->ID_PCNTE) ? substr($firstOrder->ID_PCNTE, 0, 20) : null);
    $cnsctvo_pcnte = !empty($firstOrder->cnsctvo_pcnte) ? (int)$firstOrder->cnsctvo_pcnte : (!empty($firstOrder->CNSCTVO_PCNTE) ? (int)$firstOrder->CNSCTVO_PCNTE : null);
    
    $consecutivo_remision_shared = null;
    if ($id_pcnte && $cnsctvo_pcnte) {
        $sql_rem = 'SELECT COALESCE(MAX(consecutivo_remision), 0) + 1 AS MAX_REM FROM ordenes_medicas_hc WHERE id_pcnte = :id AND cnsctvo_pcnte = :ev';
        $stid_rem = oci_parse($db, $sql_rem);
        oci_bind_by_name($stid_rem, ":id", $id_pcnte);
        oci_bind_by_name($stid_rem, ":ev", $cnsctvo_pcnte);
        oci_execute($stid_rem);
        $row_rem = oci_fetch_array($stid_rem, OCI_ASSOC);
        $consecutivo_remision_shared = isset($row_rem['MAX_REM']) ? (int)$row_rem['MAX_REM'] : 1;
        oci_free_statement($stid_rem);
    }
    
    foreach ($data->ordenes as $orderData) {
        // Enlazar datos comunes si están vacíos
        if (empty($orderData->id_mdco) && !empty($payload['id'])) {
            $orderData->id_mdco = substr($payload['id'], 0, 20);
        }
        if (empty($orderData->usuario_ingreso) && !empty($payload['username'])) {
            $orderData->usuario_ingreso = substr($payload['username'], 0, 30);
        }
        if (empty($orderData->consecutivo_remision) && empty($orderData->CONSECUTIVO_REMISION)) {
            $orderData->consecutivo_remision = $consecutivo_remision_shared;
        }
        
        $res = $model->createOrdenesMedicasHC($orderData);
        $results[] = $res;
        if ($res['status'] !== 'success') {
            $success = false;
        }
    }
    
    if ($success) {
        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "Todas las órdenes médicas registradas exitosamente.",
            "results" => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Ocurrió un error al registrar algunas de las órdenes médicas.",
            "results" => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} else {
    // Inserción unitaria
    if (empty($data->id_mdco) && !empty($payload['id'])) {
        $data->id_mdco = substr($payload['id'], 0, 20);
    }
    if (empty($data->usuario_ingreso) && !empty($payload['username'])) {
        $data->usuario_ingreso = substr($payload['username'], 0, 30);
    }

    $result = $model->createOrdenesMedicasHC($data);
    
    if ($result['status'] === 'success') {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$database->closeConnection();
?>
