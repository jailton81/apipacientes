<?php
class PacienteCita {
    private $conn;
    private $table_name = "\"ORDNES_SRVCIOS\"";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function GetInfoCitas($sMdco, $dFchaI, $dFchaF, $page = 1, $limit = 10) {
        $page = (int)$page;
        $limit = (int)$limit;
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        $offset = ($page - 1) * $limit;

        // 1. Obtener el total de registros para el paginado
        $count_sql = "SELECT COUNT(*) AS total 
                      FROM " . $this->table_name . " o
                      INNER JOIN \"PCNTES\" p ON o.\"IDNTFCCION\" = p.\"IDNTFCCION\"
                      INNER JOIN \"EMPRSAS\" e ON o.\"NIT_EMPRSA\" = e.\"NIT_EMPRSA\"
                      INNER JOIN \"DTLLE_ORDEN\" d ON o.\"NMRO_ORDEN\" = d.\"NMRO_ORDEN\" AND o.\"NIT_EMPRSA\" = d.\"NIT_EMPRSA\"
                      INNER JOIN \"PRCDMNTOS\" pr ON d.\"CDGO_ITEM\" = pr.\"CDGO_PRCDMNTO\"
                      WHERE o.\"ID_MDCO\" = :s_mdco  
                        AND o.\"FCHA_ATNCION\" >= TO_DATE(:d_fcha_i, 'DD/MM/YYYY')
                        AND o.\"FCHA_ATNCION\" < TO_DATE(:d_fcha_f, 'DD/MM/YYYY')
                        AND o.\"ESTDO\" IN ('C', 'F')
                        AND d.\"TPO_ITEM\" = 'C'";

        $count_stid = oci_parse($this->conn, $count_sql);
        oci_bind_by_name($count_stid, ":s_mdco", $sMdco);
        oci_bind_by_name($count_stid, ":d_fcha_i", $dFchaI);
        oci_bind_by_name($count_stid, ":d_fcha_f", $dFchaF);
        
        oci_execute($count_stid);
        $count_row = oci_fetch_array($count_stid, OCI_ASSOC);
        $total = isset($count_row['TOTAL']) ? (int)$count_row['TOTAL'] : 0;
        oci_free_statement($count_stid);

        // 2. Sentencia SQL con paginado
        $max_row = $offset + $limit;
        $min_row = $offset;

        $sql = "SELECT * FROM (
                    SELECT a.*, ROWNUM rnum FROM (
                        SELECT 
                            p.\"IDNTFCCION\",   
                            p.\"PRMER_APLLDO\",   
                            p.\"SGNDO_APLLDO\",   
                            p.\"PRMER_NMBRE\",   
                            p.\"SGNDO_NMBRE\",   
                            TO_CHAR(o.\"FCHA_ATNCION\", 'YYYY-MM-DD HH24:MI:SS') AS \"FCHA_ATNCION\",   
                            o.\"NIT_EMPRSA\",   
                            e.\"DSCRPCION\" AS \"EMPRESA_DSCRPCION\",   
                            pr.\"DSCRPCION\" AS \"PROCEDIMIENTO_DSCRPCION\",   
                            o.\"ESTDO\",   
                            o.\"ESTDO_ATNCION\",   
                            o.\"NMRO_ORDEN\",   
                            o.\"ID_MDCO\",   
                            o.\"NIT_INSTTCION\",   
                            d.\"ITEM\",   
                            d.\"CDGO_ITEM\"  
                        FROM " . $this->table_name . " o
                        INNER JOIN \"PCNTES\" p ON o.\"IDNTFCCION\" = p.\"IDNTFCCION\"
                        INNER JOIN \"EMPRSAS\" e ON o.\"NIT_EMPRSA\" = e.\"NIT_EMPRSA\"
                        INNER JOIN \"DTLLE_ORDEN\" d ON o.\"NMRO_ORDEN\" = d.\"NMRO_ORDEN\" AND o.\"NIT_EMPRSA\" = d.\"NIT_EMPRSA\"
                        INNER JOIN \"PRCDMNTOS\" pr ON d.\"CDGO_ITEM\" = pr.\"CDGO_PRCDMNTO\"
                        WHERE o.\"ID_MDCO\" = :s_mdco  
                          AND o.\"FCHA_ATNCION\" >= TO_DATE(:d_fcha_i, 'DD/MM/YYYY')
                          AND o.\"FCHA_ATNCION\" < TO_DATE(:d_fcha_f, 'DD/MM/YYYY')
                          AND o.\"ESTDO\" IN ('C', 'F')
                          AND d.\"TPO_ITEM\" = 'C'
                        ORDER BY o.\"FCHA_ATNCION\" ASC
                    ) a WHERE ROWNUM <= :max_row
                ) WHERE rnum > :min_row";

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_mdco", $sMdco);
        oci_bind_by_name($stid, ":d_fcha_i", $dFchaI);
        oci_bind_by_name($stid, ":d_fcha_f", $dFchaF);
        oci_bind_by_name($stid, ":max_row", $max_row);
        oci_bind_by_name($stid, ":min_row", $min_row);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $citas = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $citas[] = [
                "identificacion" => $row['IDNTFCCION'],
                "primer_apellido" => $row['PRMER_APLLDO'],
                "segundo_apellido" => $row['SGNDO_APLLDO'],
                "primer_nombre" => $row['PRMER_NMBRE'],
                "segundo_nombre" => $row['SGNDO_NMBRE'],
                "fecha_atencion" => $row['FCHA_ATNCION'],
                "nit_empresa" => $row['NIT_EMPRSA'],
                "empresa_descripcion" => $row['EMPRESA_DSCRPCION'],
                "procedimiento_descripcion" => $row['PROCEDIMIENTO_DSCRPCION'],
                "estado" => $row['ESTDO'],
                "estado_atencion" => $row['ESTDO_ATNCION'],
                "numero_orden" => $row['NMRO_ORDEN'],
                "id_medico" => $row['ID_MDCO'],
                "nit_institucion" => $row['NIT_INSTTCION'],
                "item" => $row['ITEM'],
                "codigo_item" => $row['CDGO_ITEM']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "total" => $total,
            "page" => $page,
            "limit" => $limit,
            "pages" => ceil($total / $limit),
            "count" => count($citas),
            "data" => $citas
        ];
    }
}
?>
