<?php
class IUM
{
    private $conn;
    private $table_name = "IUM";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene registros de la tabla IUM filtrando por código o descripción.
     * Devuelve una estructura con status, count y data.
     * 
     * @param string $search El texto a buscar en el código o descripción.
     * @return array Estructura de datos resultante.
     */
    public function GetInfoIUM($search)
    {
        $sql = 'SELECT * FROM (
                    SELECT 
                        i.CODIGO AS "code", 
                        i.NOMBRE AS "display",
                        i.DCI AS "dci",
                        i.INSTRUCCION AS "instruccion",
                        i.CODIGO_FORMA AS "codigo_forma",
                        i.DESCRIPCION_FORMA AS "descripcion_forma",
                        i.IUM_PRIMER_NIVEL AS "ium_primer_nivel",
                        i.UNIDAD_DISPENSACION AS "unidad_dispensacion",
                        i.DESC_UNIDAD_DISPENSACION AS "desc_unidad_dispensacion"
                    FROM "IUM" i
                    WHERE UPPER(i.CODIGO) LIKE UPPER(:search || \'%\') 
                       OR UPPER(i.NOMBRE) LIKE UPPER(\'%\' || :search || \'%\')
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
                "display" => $row['display'] ?? $row['NOMBRE'] ?? $row['DESCRIPCION'] ?? '',
                "dci" => $row['dci'] ?? $row['DCI'] ?? null,
                "instruccion" => $row['instruccion'] ?? $row['INSTRUCCION'] ?? null,
                "codigo_forma" => $row['codigo_forma'] ?? $row['CODIGO_FORMA'] ?? null,
                "descripcion_forma" => $row['descripcion_forma'] ?? $row['DESCRIPCION_FORMA'] ?? null,
                "ium_primer_nivel" => $row['ium_primer_nivel'] ?? $row['IUM_PRIMER_NIVEL'] ?? null,
                "unidad_dispensacion" => $row['unidad_dispensacion'] ?? $row['UNIDAD_DISPENSACION'] ?? null,
                "desc_unidad_dispensacion" => $row['desc_unidad_dispensacion'] ?? $row['DESC_UNIDAD_DISPENSACION'] ?? null
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
