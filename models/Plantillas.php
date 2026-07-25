<?php
require_once __DIR__ . '/../utils/AuthMiddleware.php';

class Plantillas
{
    private $conn;
    private $table_name = "plntllas_rsltdos_prcdmntos";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene las plantillas filtradas por tipo de plantilla.
     * Método tokenizado.
     * 
     * @param string $tipo_plantilla El valor de filtrado para tipo_plantilla.
     * @return string JSON con el status, count y la lista de registros.
     */
    public function GetInfoPlantilla($tipo_plantilla)
    {
        // Validar token de autorización JWT
        AuthMiddleware::checkToken();

        $sql = "SELECT cdgo_plntlla AS codigo_plantilla,
                       dscrpcion AS descripcion,
                       tpo_plntlla AS tipo_plantilla
                FROM " . $this->table_name . "
                WHERE (:tipo = 'TODOS' OR :tipo IS NULL OR tpo_plntlla = :tipo)";
        
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":tipo", $tipo_plantilla);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return json_encode([
                "status" => "error",
                "message" => "Error extrayendo plantillas: " . $e['message']
            ], JSON_PRETTY_PRINT);
        }

        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $results[] = [
                "codigo_plantilla" => $row['CODIGO_PLANTILLA'] ?? $row['codigo_plantilla'] ?? null,
                "descripcion" => $row['DESCRIPCION'] ?? $row['descripcion'] ?? null,
                "tipo_plantilla" => $row['TIPO_PLANTILLA'] ?? $row['tipo_plantilla'] ?? null
            ];
        }
        oci_free_statement($stid);

        return json_encode([
            "status" => "success",
            "count" => count($results),
            "data" => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Obtiene las plantillas de medicamentos (donde tpo_plntlla = 'D').
     * Método tokenizado.
     * 
     * @return string JSON con el status, count y la lista de plantillas.
     */
    public function GetInfoPlantillaMedicamentos()
    {
        // Validar token de autorización JWT
        AuthMiddleware::checkToken();

        $sql = "SELECT cdgo_plntlla AS codigo_plantilla,
                       dscrpcion AS descripcion,
                       tpo_plntlla AS tipo_plantilla
                FROM " . $this->table_name . "
                WHERE tpo_plntlla = 'D'";
        
        $stid = oci_parse($this->conn, $sql);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return json_encode([
                "status" => "error",
                "message" => "Error extrayendo plantillas de medicamentos: " . $e['message']
            ], JSON_PRETTY_PRINT);
        }

        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $results[] = [
                "codigo_plantilla" => $row['CODIGO_PLANTILLA'] ?? $row['codigo_plantilla'] ?? null,
                "descripcion" => $row['DESCRIPCION'] ?? $row['descripcion'] ?? null,
                "tipo_plantilla" => $row['TIPO_PLANTILLA'] ?? $row['tipo_plantilla'] ?? null
            ];
        }
        oci_free_statement($stid);

        return json_encode([
            "status" => "success",
            "count" => count($results),
            "data" => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Obtiene las tecnologías de salud de una plantilla específica.
     * Método tokenizado.
     * 
     * @param string $cdgo_plntlla El código de la plantilla (s_codigo).
     * @return string JSON con el status, count y la lista de tecnologías.
     */
    public function GetTecnologiasPlantilla($cdgo_plntlla)
    {
        // Validar token de autorización JWT
        AuthMiddleware::checkToken();

        $sql = "SELECT ts.codigo AS codigo,   
                       ts.nombre AS nombre,   
                       ts.grupo_filtro AS grupo_filtro,   
                       ts.categoria_fhir AS categoria_fhir,   
                       ts.tipo_tecnologia AS tipo_tecnologia,
                       ts.observaciones AS observaciones
                  FROM plntllas_rsltdos_prcdmntos pr
                  JOIN tpo_exmen_rmsion_plntlla terp 
                    ON pr.cdgo_plntlla = terp.cdgo_plntlla
                  JOIN tecnologias_salud ts 
                    ON terp.cdgo_exmen_rmsion = ts.codigo
                 WHERE pr.cdgo_plntlla = :s_codigo 
                   AND ts.estado = 'Activo'";
        
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_codigo", $cdgo_plntlla);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return json_encode([
                "status" => "error",
                "message" => "Error extrayendo tecnologías de plantilla: " . $e['message']
            ], JSON_PRETTY_PRINT);
        }

        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $rawCat = strtolower($row['CATEGORIA_FHIR'] ?? $row['categoria_fhir'] ?? '');
            $catFhir = ($rawCat === 'procedimientos' || $rawCat === 'procedimiento' || $rawCat === 'consulta' || $rawCat === 'consultas' || $rawCat === 'procedure') ? 'procedure' : 'technology';

            $results[] = [
                "codigo" => $row['CODIGO'] ?? $row['codigo'] ?? null,
                "nombre" => $row['NOMBRE'] ?? $row['nombre'] ?? null,
                "grupo_filtro" => $row['GRUPO_FILTRO'] ?? $row['grupo_filtro'] ?? null,
                "categoria_fhir" => $catFhir,
                "tipo_tecnologia" => $row['TIPO_TECNOLOGIA'] ?? $row['tipo_tecnologia'] ?? null,
                "observaciones" => $row['OBSERVACIONES'] ?? $row['observaciones'] ?? null
            ];
        }
        oci_free_statement($stid);

        return json_encode([
            "status" => "success",
            "count" => count($results),
            "data" => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Obtiene los medicamentos de una plantilla específica.
     * Método tokenizado.
     * 
     * @param string $cdgo_plntlla El código de la plantilla.
     * @return string JSON con el status, count y la lista de medicamentos.
     */
    public function GetMedicamentosPlantilla($cdgo_plntlla)
    {
        // Validar token de autorización JWT
        AuthMiddleware::checkToken();

        $sql = 'SELECT 
                    m.codigo,
                    m.nombre,
                    m.descripcion,
                    m.habilitado,
                    m.ium,
                    m.ium_primer_nivel,
                    m.dci,
                    m.instruccion,
                    m.codigo_forma,
                    m.descripcion_forma,
                    m.unidad_dispensacion,
                    m.desc_unidad_dispensacion,
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
                INNER JOIN "TPO_EXMEN_RMSION_PLNTLLA" t 
                    ON m.codigo = t.cdgo_exmen_rmsion
                WHERE t.cdgo_plntlla = :s_plantilla
                  AND m.habilitado = \'SI\'';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_plantilla", $cdgo_plntlla);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return json_encode([
                "status" => "error",
                "message" => "Error extrayendo medicamentos de plantilla: " . $e['message']
            ], JSON_PRETTY_PRINT);
        }

        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $results[] = [
                "codigo" => $row['CODIGO'] ?? $row['codigo'] ?? null,
                "nombre" => $row['NOMBRE'] ?? $row['nombre'] ?? null,
                "descripcion" => $row['DESCRIPCION'] ?? $row['descripcion'] ?? null,
                "habilitado" => $row['HABILITADO'] ?? $row['habilitado'] ?? null,
                "ium" => $row['IUM'] ?? $row['ium'] ?? null,
                "ium_primer_nivel" => $row['IUM_PRIMER_NIVEL'] ?? $row['ium_primer_nivel'] ?? null,
                "dci" => $row['DCI'] ?? $row['dci'] ?? null,
                "instruccion" => $row['INSTRUCCION'] ?? $row['instruccion'] ?? null,
                "codigo_forma" => $row['CODIGO_FORMA'] ?? $row['codigo_forma'] ?? null,
                "descripcion_forma" => $row['DESCRIPCION_FORMA'] ?? $row['descripcion_forma'] ?? null,
                "unidad_dispensacion" => $row['UNIDAD_DISPENSACION'] ?? $row['unidad_dispensacion'] ?? null,
                "desc_unidad_dispensacion" => $row['DESC_UNIDAD_DISPENSACION'] ?? $row['desc_unidad_dispensacion'] ?? null,
                "dosis" => isset($row['DOSIS']) ? (float)$row['DOSIS'] : null,
                "um_dosis" => $row['UM_DOSIS'] ?? $row['um_dosis'] ?? null,
                "um_dosis_descripcion" => $row['UM_DOSIS_DESCRIPCION'] ?? $row['um_dosis_descripcion'] ?? null,
                "codigo_via" => $row['CODIGO_VIA'] ?? $row['codigo_via'] ?? null,
                "via" => $row['VIA'] ?? $row['via'] ?? null,
                "duracion" => isset($row['DURACION']) ? (int)$row['DURACION'] : null,
                "um_duracion" => $row['UM_DURACION'] ?? $row['um_duracion'] ?? null,
                "um_duracion_descripcion" => $row['UM_DURACION_DESCRIPCION'] ?? $row['um_duracion_descripcion'] ?? null,
                "frecuencia" => isset($row['FRECUENCIA']) ? (int)$row['FRECUENCIA'] : null,
                "um_frecuencia" => $row['UM_FRECUENCIA'] ?? $row['um_frecuencia'] ?? null,
                "um_frecuencia_descripcion" => $row['UM_FRECUENCIA_DESCRIPCION'] ?? $row['um_frecuencia_descripcion'] ?? null,
                "tipo_tecnologia" => $row['TIPO_TECNOLOGIA'] ?? $row['tipo_tecnologia'] ?? null,
                "tipo_tecnologia_descripcion" => $row['TIPO_TECNOLOGIA_DESCRIPCION'] ?? $row['tipo_tecnologia_descripcion'] ?? null
            ];
        }
        oci_free_statement($stid);

        return json_encode([
            "status" => "success",
            "count" => count($results),
            "data" => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
?>
