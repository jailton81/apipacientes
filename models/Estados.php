<?php
require_once __DIR__ . '/../utils/AuthMiddleware.php';

class Estados
{
    private $conn;
    private $table_name = "STATUS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene los campos code y display de la tabla status filtrado por el campo status.
     * Método tokenizado.
     * 
     * @param string $status El valor de filtrado para la columna STATUS.
     * @return string JSON con el status, count y la lista de registros (code y display).
     */
    public function getStatusData($status)
    {
        // Validar token de autorización JWT
        AuthMiddleware::checkToken();

        $sql = 'SELECT "CODE", "DISPLAY" FROM "' . $this->table_name . '" WHERE TRIM("STATUS") = :status';
        
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":status", $status);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return json_encode([
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ], JSON_PRETTY_PRINT);
        }

        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $results[] = [
                "code" => $row['CODE'],
                "display" => $row['DISPLAY']
            ];
        }
        oci_free_statement($stid);

        return json_encode([
            "status" => "success",
            "count" => count($results),
            "data" => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
?>
