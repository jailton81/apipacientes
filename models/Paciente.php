<?php
class Paciente {
    private $conn;
    private $table_name = "pcntes";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getPacientesPorRangoTemporal($fechaInicio, $fechaFin) {
        // Sentencia SQL simple sin límite de paginado
        $sql = "SELECT idntfccion, prmer_aplldo, sgndo_aplldo, prmer_nmbre, 
                       sgndo_nmbre, tpo_usrio, tpo_afldo, sxo, drccion 
                FROM " . $this->table_name . " 
                WHERE fcha_ingrso >= TO_DATE(:fcha_inicio, 'DD/MM/YYYY')
                  AND fcha_ingrso <= TO_DATE(:fcha_fin, 'DD/MM/YYYY')
                ORDER BY fcha_ingrso ASC";

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":fcha_inicio", $fechaInicio);
        oci_bind_by_name($stid, ":fcha_fin", $fechaFin);

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
            "count" => count($pacientes),
            "data" => $pacientes
        ];
    }
}
?>
