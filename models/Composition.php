<?php
class Composition
{
    private $conn;
    private $table_name = "HISTORIAS_CLINICAS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoComposition($id_pcnte, $cnsctvo_pcnte)
    {
        $sql = 'SELECT \'final\' as status,
                    hc.TPO_ID, 
                    hc.ID_PCNTE as subject, 
                    \'N\' as confidentiality,
                    TO_CHAR(hc.FCHA_APRTRA, \'YYYY-MM-DD"T"HH24:MI:SS"-05:00"\') as "date",       
                    \'CC\' as tpo_id_mdco,
                    m.ID_MDCO as author,   
                    substr(i.CDGO_PRSTDOR_SRVCIO,1,10) as attester,
                    substr(i.CDGO_PRSTDOR_SRVCIO,1,10) as custodian,  
                    \'01\' as codigo_modalidad,
                    \'Intramural\' as desc_modalidad,
                    \'01\' as grupo_servicio,
                    \'Consulta externa\' as desc_grupo_servicio,   
                    TO_CHAR(hc.FCHA_APRTRA, \'YYYY-MM-DD"T"HH24:MI:SS"-05:00"\') as periodstart,   
                    TO_CHAR(hc.FCHA_INGRSO, \'YYYY-MM-DD"T"HH24:MI:SS"-05:00"\') as periodend,
                    i.NIT_CNTBLDAD AS NIT
                FROM "' . $this->table_name . '" hc
                INNER JOIN "INSTTCIONES" i 
                    ON hc.NIT_INSTTCION = i.NIT_INSTTCION
                INNER JOIN "MDCOS" m 
                    ON hc.ID_MDCO = m.IDNTFCCION_MDCOS
                WHERE hc.ID_PCNTE = :id_pcnte 
                  AND hc.CNSCTVO_PCNTE = :cnsctvo_pcnte';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $compositions = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $compositions[] = $row;
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($compositions),
            "data" => $compositions
        ];
    }
}
?>