<?php
class Medicamentos
{
    private $conn;
    private $table_name = "MEDICAMENTOS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene registros de la tabla MEDICAMENTOS filtrando por código o nombre.
     * Devuelve una estructura con status, count y data.
     * 
     * @param string $search El texto a buscar en el código o nombre.
     * @return array Estructura de datos resultante.
     */
    public function GetInfoMedicamento($search)
    {
        $sql = 'SELECT * FROM (
                    SELECT 
                        m.codigo AS "code", 
                        m.nombre AS "display",
                        m.descripcion,
                        m.habilitado,
                        m.ium,
                        m.ium_primer_nivel AS "ium_primer_nivel",
                        m.dci AS "dci",
                        m.instruccion AS "instruccion",
                        m.codigo_forma AS "codigo_forma",
                        m.descripcion_forma AS "descripcion_forma",
                        m.unidad_dispensacion AS "unidad_dispensacion",
                        m.desc_unidad_dispensacion AS "desc_unidad_dispensacion",
                        m.dosis,
                        m.um_dosis,
                        m.um_dosis_descripcion,
                        m.codigo_via,
                        m.via,
                        m.duracion,
                        m.um_duracion,
                        m.um_duracion_descripcion,
                        m.frecuencia,
                        m.um_frecuencia,
                        m.um_frecuencia_descripcion,
                        m.tipo_tecnologia,
                        m.tipo_tecnologia_descripcion
                    FROM "MEDICAMENTOS" m
                    WHERE UPPER(m.CODIGO) LIKE UPPER(:search || \'%\') 
                       OR UPPER(m.NOMBRE) LIKE UPPER(\'%\' || :search || \'%\')
                ) WHERE ROWNUM <= 50';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":search", $search);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $results[] = [
                "code" => $row['code'] ?? $row['CODE'] ?? $row['CODIGO'] ?? '',
                "display" => $row['display'] ?? $row['NOMBRE'] ?? '',
                "descripcion" => $row['DESCRIPCION'] ?? null,
                "habilitado" => $row['HABILITADO'] ?? null,
                "ium" => $row['IUM'] ?? null,
                "ium_primer_nivel" => $row['ium_primer_nivel'] ?? $row['IUM_PRIMER_NIVEL'] ?? null,
                "dci" => $row['dci'] ?? $row['DCI'] ?? null,
                "instruccion" => $row['instruccion'] ?? $row['INSTRUCCION'] ?? null,
                "codigo_forma" => $row['codigo_forma'] ?? $row['CODIGO_FORMA'] ?? null,
                "descripcion_forma" => $row['descripcion_forma'] ?? $row['DESCRIPCION_FORMA'] ?? null,
                "unidad_dispensacion" => $row['unidad_dispensacion'] ?? $row['UNIDAD_DISPENSACION'] ?? null,
                "desc_unidad_dispensacion" => $row['desc_unidad_dispensacion'] ?? $row['DESC_UNIDAD_DISPENSACION'] ?? null,
                "dosis" => $row['DOSIS'] ?? null,
                "um_dosis" => $row['UM_DOSIS'] ?? null,
                "um_dosis_descripcion" => $row['UM_DOSIS_DESCRIPCION'] ?? null,
                "codigo_via" => $row['CODIGO_VIA'] ?? null,
                "via" => $row['VIA'] ?? null,
                "duracion" => $row['DURACION'] ?? null,
                "um_duracion" => $row['UM_DURACION'] ?? null,
                "um_duracion_descripcion" => $row['UM_DURACION_DESCRIPCION'] ?? null,
                "frecuencia" => $row['FRECUENCIA'] ?? null,
                "um_frecuencia" => $row['UM_FRECUENCIA'] ?? null,
                "um_frecuencia_descripcion" => $row['UM_FRECUENCIA_DESCRIPCION'] ?? null,
                "tipo_tecnologia" => $row['TIPO_TECNOLOGIA'] ?? null,
                "tipo_tecnologia_descripcion" => $row['TIPO_TECNOLOGIA_DESCRIPCION'] ?? null
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($results),
            "data" => $results
        ];
    }
}
?>
