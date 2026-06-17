<?php
class CIE10
{
    private $conn;
    private $table_name = "CIE10";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene registros de la tabla CIE10 filtrando por código o descripción.
     * Devuelve una estructura con status, count y data.
     * 
     * @param string $search El texto a buscar en el código o descripción.
     * @return array Estructura de datos resultante.
     */
    public function GetInfoCIE10($search)
    {
        $sql = 'SELECT * FROM (
                    SELECT 
                        c.CODIGO AS "code", 
                        c.NOMBRE AS "display"
                    FROM "CIE10" c
                    WHERE UPPER(c.CODIGO) LIKE UPPER(:search || \'%\') 
                       OR UPPER(c.NOMBRE) LIKE UPPER(\'%\' || :search || \'%\')
                ) WHERE ROWNUM <= 50';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":search", $search);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $results[] = [
                "code" => $row['code'] ?? $row['CODE'] ?? '',
                "display" => $row['display'] ?? $row['NOMBRE'] ?? $row['DESCRIPCION'] ?? ''
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($results),
            "data" => $results
        ];
    }
}
?>
