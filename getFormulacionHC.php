<?php
// 1. Configurar cabeceras para retornar JSON y manejo de caracteres
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// 2. Incluir los archivos de clases requeridos y middleware
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/FormulacionHC.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
AuthMiddleware::checkToken();

// 3. Instanciar la base de datos y obtener su conexión
$database = new Database();
$db = $database->getConnection();

// 4. Leer parámetros de la petición GET
$idPcnte = isset($_GET['id_pcnte']) ? $_GET['id_pcnte'] : null;
$cnsctvoPcnte = isset($_GET['cnsctvo_pcnte']) ? $_GET['cnsctvo_pcnte'] : (isset($_GET['cnsctvo']) ? $_GET['cnsctvo'] : null);

if (!$idPcnte || !$cnsctvoPcnte) {
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error. Se requieren los parámetros 'id_pcnte' y 'cnsctvo_pcnte' (o 'cnsctvo') en la URL."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $database->closeConnection();
    exit;
}

// 5. Instanciar el objeto FormulacionHC e invocar su método
$formulacionHC = new FormulacionHC($db);
$resultado = $formulacionHC->GetInfoFormulacionHC($idPcnte, $cnsctvoPcnte);

// 6. Retornar el JSON con los resultados
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// 7. Cerrar la conexión
$database->closeConnection();
?>
