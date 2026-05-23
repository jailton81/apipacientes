<?php
class Organization {
    private $conn;
    private $table_name = "insttciones";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function GetInfoOrganization($nit_insttcion) {
        // Sentencia SQL
        $sql = "SELECT substr(cdgo_prstdor_srvcio,1,10) as CDGO_PRSTDOR_SRVCIO, nit_cntbldad 
                FROM " . $this->table_name . " 
                WHERE nit_insttcion = :nit_insttcion";

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":nit_insttcion", $nit_insttcion);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $organizations = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $organizations[] = [
                "codigo_prestador_servicio" => $row['CDGO_PRSTDOR_SRVCIO'],
                "nit_contabilidad"          => $row['NIT_CNTBLDAD']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($organizations),
            "data" => $organizations
        ];
    }
}
?>
