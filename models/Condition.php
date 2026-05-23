<?php
class Condition
{
    private $conn;
    private $table_name = "ANTECEDENTES_PATOLOGICOS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoCondition($id_pcnte)
    {
        $sql = 'SELECT 
                    ap.ID_PCNTE,
                    ap.DESCRIPCION,
                    sa.CODE AS STATUS_CLINICO_CODE,
                    sa.DISPLAY AS STATUS_CLINICO_DISPLAY,
                    sb.CODE AS STATUS_VERIFICACION_CODE,
                    sb.DISPLAY AS STATUS_VERIFICACION_DISPLAY,
                    \'encounter-diagnosis\' AS CATEGORY_CODE,
                    \'Encounter Diagnosis\' AS CATEGORY_DISPLAY
                FROM "' . $this->table_name . '" ap
                INNER JOIN "STATUS" sa 
                    ON ap.ESTADO = sa.CODE 
                    AND sa.STATUS = \'ConditionClinicalStatusCodes\'
                INNER JOIN "STATUS" sb 
                    ON ap.ESTADO_VERIFICACION = sb.CODE 
                    AND sb.STATUS = \'verificationStatus\'
                WHERE ap.ID_PCNTE = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $conditions = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $conditions[] = $row;
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($conditions),
            "data" => $conditions
        ];
    }
}
?>