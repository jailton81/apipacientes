<?php
class AllergyIntolerance
{
    private $conn;
    private $table_name = "ANTECEDENTES_ALERGICOS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoAllergyIntolerance($id_pcnte)
    {
        $sql = 'SELECT 
                    aa.ID_PCNTE,
                    aa.TIPOALERGIA,
                    ta.NOMBRE AS TIPO_ALERGIA_NOMBRE,
                    aa.DESCRIPCION,
                    aa.ESTADO,
                    sa.DISPLAY AS ESTADO_CLINICO_DISPLAY,
                    aa.ESTADO_VERIFICACION,
                    sb.DISPLAY AS VERIFICACION_DISPLAY
                FROM "' . $this->table_name . '" aa
                INNER JOIN "TIPOALERGIA" ta 
                    ON aa.TIPOALERGIA = ta.CODIGO
                INNER JOIN "STATUS" sa 
                    ON aa.ESTADO = sa.CODE 
                    AND sa.STATUS = \'AllergyIntoleranceClinicalStatusCodes\'
                INNER JOIN "STATUS" sb 
                    ON aa.ESTADO_VERIFICACION = sb.CODE 
                    AND sb.STATUS = \'verificationStatus\'
                WHERE aa.ID_PCNTE = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $allergies = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $allergies[] = $row;
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($allergies),
            "data" => $allergies
        ];
    }
}
?>
