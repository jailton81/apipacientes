<?php
class Paciente {
    private $conn;
    private $table_name = "pcntes";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getPacientesPorRangoTemporal($fechaInicio, $fechaFin, $page = 1, $limit = 10) {
        $page = (int)$page;
        $limit = (int)$limit;
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        $offset = ($page - 1) * $limit;

        // Obtener el total de registros para el paginado
        $count_sql = "SELECT COUNT(*) AS total FROM " . $this->table_name . " 
                      WHERE fcha_ingrso >= TO_DATE(:fcha_inicio, 'DD/MM/YYYY')
                        AND fcha_ingrso <= TO_DATE(:fcha_fin, 'DD/MM/YYYY')";
        $count_stid = oci_parse($this->conn, $count_sql);
        oci_bind_by_name($count_stid, ":fcha_inicio", $fechaInicio);
        oci_bind_by_name($count_stid, ":fcha_fin", $fechaFin);
        oci_execute($count_stid);
        $count_row = oci_fetch_array($count_stid, OCI_ASSOC);
        $total = isset($count_row['TOTAL']) ? (int)$count_row['TOTAL'] : 0;
        oci_free_statement($count_stid);

        // Sentencia SQL con paginado (Oracle 12c+ OFFSET/FETCH syntax)
        $max_row = $offset + $limit;
        $min_row = $offset;

        $sql = "SELECT * FROM (
                    SELECT a.*, ROWNUM rnum FROM (
                        SELECT idntfccion, prmer_aplldo, sgndo_aplldo, prmer_nmbre, 
                               sgndo_nmbre, tpo_usrio, tpo_afldo, sxo, drccion 
                        FROM " . $this->table_name . " 
                        WHERE fcha_ingrso >= TO_DATE(:fcha_inicio, 'DD/MM/YYYY')
                          AND fcha_ingrso <= TO_DATE(:fcha_fin, 'DD/MM/YYYY')
                        ORDER BY fcha_ingrso ASC
                    ) a WHERE ROWNUM <= :max_row
                ) WHERE rnum > :min_row";

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":fcha_inicio", $fechaInicio);
        oci_bind_by_name($stid, ":fcha_fin", $fechaFin);
        oci_bind_by_name($stid, ":max_row", $max_row);
        oci_bind_by_name($stid, ":min_row", $min_row);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $pacientes = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $pacientes[] = [
                "identificacion"   => $row['IDNTFCCION'],
                "primer_apellido"  => $row['PRMER_APLLDO'],
                "segundo_apellido" => $row['SGNDO_APLLDO'],
                "primer_nombre"    => $row['PRMER_NMBRE'],
                "segundo_nombre"   => $row['SGNDO_NMBRE'],
                "tipo_usuario"     => $row['TPO_USRIO'],
                "tipo_afiliado"    => $row['TPO_AFLDO'],
                "sexo"             => $row['SXO'],
                "direccion"        => $row['DRCCION']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "total" => $total,
            "page" => $page,
            "limit" => $limit,
            "pages" => ceil($total / $limit),
            "count" => count($pacientes),
            "data" => $pacientes
        ];
    }
}
?>
