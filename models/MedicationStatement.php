<?php
class MedicationStatement
{
    private $conn;
    private $table_name = "ANTECEDENTES_FARMACOLOGICOS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoMedicationStatement($id_pcnte)
    {
        $sql = 'SELECT 
                    af.ID_PCNTE,
                    af.CODIGO,
                    af.DESCRIPCION,
                    af.ESTADO
                FROM "' . $this->table_name . '" af
                WHERE af.ID_PCNTE = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $statements = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $statements[] = $row;
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($statements),
            "data" => $statements
        ];
    }
}
?>
