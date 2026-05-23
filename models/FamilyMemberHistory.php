<?php
class FamilyMemberHistory
{
    private $conn;
    private $table_name = "ANTECEDENTES_FAMILIARES";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoFamilyMemberHistory($id_pcnte)
    {
        $sql = 'SELECT 
                    af.ID_PCNTE,
                    af.PARENTESCO,
                    p.NOMBRE AS PARENTESCO_NOMBRE,
                    af.CIE_10,
                    af.DESCRIPCION,
                    af.ESTADO
                FROM "' . $this->table_name . '" af
                INNER JOIN "PARENTESCO" p 
                    ON af.PARENTESCO = p.CODIGO
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

        $history = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $history[] = $row;
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($history),
            "data" => $history
        ];
    }
}
?>
