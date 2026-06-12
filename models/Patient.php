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
                    oc.DSCRPCION AS ocupacion,
                    TO_CHAR(p.FCHA_NCMNTO, \'YYYY-MM-DD\') AS FECHA_NACIMIENTO,
                    \'09:30:00\' AS HORA_NACIMIENTO,
                    \'false\' AS INDICADOR_FALLECIMIENTO
                FROM "' . $this->table_name . '" p
                LEFT JOIN "CDDES" c                ON p.CDGO_DANE = c.CDGO_DANE
                LEFT JOIN "ZONA_RESIDENCIA" z      ON p.ZNA_RSDNCIA = z.CODIGO
                LEFT JOIN "PAISES" pa              ON p.NCNLDAD = pa.CDGO_PAIS
                LEFT JOIN "ETNIA" e                ON p.ETNIA = e.CODIGO
                LEFT JOIN "CATEGORIADISCAPACIDAD" cd ON p.CATEGORIADISCAPACIDAD = cd.CODIGO
                LEFT JOIN "SEXO" s                 ON p.SXO = s.CODIGO
                LEFT JOIN "IDENTIDADGENERO" ig     ON p.IDENTIDADGENERO = ig.CODIGO
                LEFT JOIN "OCPCIONES" oc            ON p.OCPCION = oc.CDGO_OCPCION
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
            $pacientes[] = [
                "tipo_identificacion" => $row['TIPO_IDENTIFICACION'],
                "id_paciente" => $row['ID_PACIENTE'],
                "primer_nombre" => $row['PRIMER_NOMBRE'],
                "segundo_nombre" => $row['SEGUNDO_NOMBRE'],
                "primer_apellido" => $row['PRIMER_APELLIDO'],
                "segundo_apellido" => $row['SEGUNDO_APELLIDO'],
                "codigo_pais" => $row['CODIGO_PAIS'],
                "nombre_pais" => $row['NOMBRE_PAIS'],
                "codigo_dane" => $row['CODIGO_DANE'],
                "nombre_ciudad" => $row['NOMBRE_CIUDAD'],
                "zona_fhir" => $row['ZONA_FHIR'],
                "zona_nombre" => $row['ZONA_NOMBRE'],
                "etnia" => $row['ETNIA'],
                "etnia_nombre" => $row['ETNIA_NOMBRE'],
                "categoriadiscapacidad" => $row['CATEGORIADISCAPACIDAD'],
                "discapacidad_nombre" => $row['DISCAPACIDAD_NOMBRE'],
                "sexo_fhir" => $row['SEXO_FHIR'],
                "sexo_nombre" => $row['SEXO_NOMBRE'],
                "genero_codigo" => $row['GENERO_CODIGO'],
                "genero_nombre" => $row['GENERO_NOMBRE'],
                "genero_fhir" => $row['GENERO_FHIR'],
                "ocupacion" => $row['OCUPACION'],
                "fecha_nacimiento" => $row['FECHA_NACIMIENTO'],
                "hora_nacimiento" => $row['HORA_NACIMIENTO'],
                "indicador_fallecimiento" => $row['INDICADOR_FALLECIMIENTO']
            ];
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