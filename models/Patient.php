<?php
class Patient
{
    private $conn;
    private $table_name = "PCNTES";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoPatient($id)
    {
        $sql = 'SELECT 
                    p.TPO_IDNTFCCION as tipo_identificacion,   
                    p.IDNTFCCION as id_paciente,   
                    p.PRMER_NMBRE as primer_nombre,   
                    p.SGNDO_NMBRE as segundo_nombre,   
                    p.PRMER_APLLDO as primer_apellido,   
                    p.SGNDO_APLLDO as segundo_apellido,   
                    p.NCNLDAD as codigo_pais,   
                    pa.NMBRE_PAIS as nombre_pais,   
                    p.CDGO_DANE as codigo_dane,   
                    c.NMBRE_CDAD as nombre_ciudad,   
                    z.CODIGO_FHIR AS ZONA_FHIR,   
                    z.NOMBRE AS ZONA_NOMBRE,   
                    p.ETNIA,   
                    e.NOMBRE AS ETNIA_NOMBRE,   
                    p.CATEGORIADISCAPACIDAD,   
                    cd.NOMBRE AS DISCAPACIDAD_NOMBRE,   
                    s.CODIGO_FHIR AS SEXO_FHIR,   
                    s.NOMBRE AS SEXO_NOMBRE,   
                    ig.CODIGO AS GENERO_CODIGO,   
                    ig.NOMBRE AS GENERO_NOMBRE,   
                    ig.NOMBRE_FHIR AS GENERO_FHIR,   
                    TO_CHAR(p.FCHA_NCMNTO, \'YYYY-MM-DD\') AS FECHA_NACIMIENTO,
                    \'09:30:00\' AS HORA_NACIMIENTO,
                    \'false\' AS INDICADOR_FALLECIMIENTO
                FROM "' . $this->table_name . '" p
                INNER JOIN "CDDES" c                ON p.CDGO_DANE = c.CDGO_DANE
                INNER JOIN "ZONA_RESIDENCIA" z      ON p.ZNA_RSDNCIA = z.CODIGO
                INNER JOIN "PAISES" pa              ON p.NCNLDAD = pa.CDGO_PAIS
                INNER JOIN "ETNIA" e                ON p.ETNIA = e.CODIGO
                INNER JOIN "CATEGORIADISCAPACIDAD" cd ON p.CATEGORIADISCAPACIDAD = cd.CODIGO
                INNER JOIN "SEXO" s                 ON p.SXO = s.CODIGO
                INNER JOIN "IDENTIDADGENERO" ig     ON p.IDENTIDADGENERO = ig.CODIGO
                WHERE p.IDNTFCCION = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $pacientes = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $pacientes[] = $row;
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($pacientes),
            "data" => $pacientes
        ];
    }
}
?>