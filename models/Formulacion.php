<?php
class Formulacion
{
    private $conn;
    private $table_name = "FORMULACION";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoFormula($id_pcnte, $nmro_evlcion)
    {
        $sql = 'SELECT 
                    f.CDGO_DRGA,
                    f.CNTDAD,
                    f.PLAN,
                    f.ID_PCNTE,
                    f.TPO_ID,
                    f.NMRO_EVLCION,
                    f.ID_MDCO,
                    f.CNSCTVO,
                    f.DSCRPCION,
                    f.PSLGIA,
                    f.FRMLA,
                    f.FCHA_FRMLA,
                    f.DOSIS,
                    f.VIA,
                    f.FRECUENCIA,
                    f.DURACION,
                    f.FORMA
                FROM "FORMULACION" f
                WHERE f.ID_PCNTE = :as_pcnte 
                  AND f.NMRO_EVLCION = :as_evlcion';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":as_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":as_evlcion", $nmro_evlcion);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $formulaciones = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $formulaciones[] = [
                "cdgo_drga" => $row['CDGO_DRGA'],
                "cntdad" => $row['CNTDAD'],
                "plan" => $row['PLAN'],
                "id_pcnte" => $row['ID_PCNTE'],
                "tpo_id" => $row['TPO_ID'],
                "nmro_evlcion" => $row['NMRO_EVLCION'],
                "id_mdco" => $row['ID_MDCO'],
                "cnsctvo" => $row['CNSCTVO'],
                "dscrpcion" => $row['DSCRPCION'],
                "pslgia" => $row['PSLGIA'],
                "frmla" => $row['FRMLA'],
                "fcha_frmla" => $row['FCHA_FRMLA'],
                "dosis" => $row['DOSIS'],
                "via" => $row['VIA'],
                "frecuencia" => $row['FRECUENCIA'],
                "duracion" => $row['DURACION'],
                "forma" => $row['FORMA']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($formulaciones),
            "data" => $formulaciones
        ];
    }

    /**
     * Registra una nueva formulación médica.
     * 
     * @param object $data Datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createFormulacion($data)
    {
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        $nmro_evlcion = !empty($data->nmro_evlcion) ? $data->nmro_evlcion : null;
        
        if (empty($id_pcnte) || empty($nmro_evlcion)) {
            return [
                "status" => "error",
                "message" => "Los campos 'id_pcnte' y 'nmro_evlcion' son requeridos."
            ];
        }

        // Obtener el TPO_ID del paciente en la tabla pcntes si no viene
        $tpo_id = !empty($data->tpo_id) ? $data->tpo_id : null;
        if (empty($tpo_id)) {
            $sql_tpo = "SELECT TPO_IDNTFCCION FROM PCNTES WHERE IDNTFCCION = :id";
            $stid_tpo = oci_parse($this->conn, $sql_tpo);
            oci_bind_by_name($stid_tpo, ":id", $id_pcnte);
            oci_execute($stid_tpo);
            $row_tpo = oci_fetch_array($stid_tpo, OCI_ASSOC);
            $tpo_id = isset($row_tpo['TPO_IDNTFCCION']) ? $row_tpo['TPO_IDNTFCCION'] : 'CC';
            oci_free_statement($stid_tpo);
        }

        // Generar el consecutivo de formulación (CNSCTVO) si no se especifica
        $cnsctvo = !empty($data->cnsctvo) ? (int)$data->cnsctvo : null;
        if (empty($cnsctvo)) {
            $sql_cns = 'SELECT COALESCE(MAX("CNSCTVO"), 0) + 1 AS MAX_CNS FROM "FORMULACION" WHERE "ID_PCNTE" = :id AND "NMRO_EVLCION" = :ev';
            $stid_cns = oci_parse($this->conn, $sql_cns);
            oci_bind_by_name($stid_cns, ":id", $id_pcnte);
            oci_bind_by_name($stid_cns, ":ev", $nmro_evlcion);
            oci_execute($stid_cns);
            $row_cns = oci_fetch_array($stid_cns, OCI_ASSOC);
            $cnsctvo = isset($row_cns['MAX_CNS']) ? (int)$row_cns['MAX_CNS'] : 1;
            oci_free_statement($stid_cns);
        }

        $sql = 'INSERT INTO "FORMULACION" (
                    "ID_PCNTE", "TPO_ID", "NMRO_EVLCION", "CNSCTVO", "CDGO_DRGA", "CNTDAD",
                    "PLAN", "ID_MDCO", "DSCRPCION", "PSLGIA", "FRMLA", "FCHA_FRMLA",
                    "DOSIS", "VIA", "FRECUENCIA", "DURACION", "FORMA"
                ) VALUES (
                    :id_pcnte, :tpo_id, :nmro_evlcion, :cnsctvo, :cdgo_drga, :cntdad,
                    :plan, :id_mdco, :dscrpcion, :pslgia, :frmla, SYSDATE,
                    :dosis, :via, :frecuencia, :duracion, :forma
                )';

        $stid = oci_parse($this->conn, $sql);

        $cdgo_drga = !empty($data->cdgo_drga) ? $data->cdgo_drga : null;
        $cntdad = isset($data->cntdad) ? $data->cntdad : null;
        $plan = !empty($data->plan) ? $data->plan : null;
        $id_mdco = !empty($data->id_mdco) ? $data->id_mdco : null;
        $dscrpcion = !empty($data->dscrpcion) ? $data->dscrpcion : null;
        $pslgia = !empty($data->pslgia) ? $data->pslgia : null;
        $frmla = !empty($data->frmla) ? $data->frmla : null;
        $dosis = !empty($data->dosis) ? $data->dosis : null;
        $via = !empty($data->via) ? $data->via : null;
        $frecuencia = !empty($data->frecuencia) ? $data->frecuencia : null;
        $duracion = !empty($data->duracion) ? $data->duracion : null;
        $forma = !empty($data->forma) ? $data->forma : null;

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":tpo_id", $tpo_id);
        oci_bind_by_name($stid, ":nmro_evlcion", $nmro_evlcion);
        oci_bind_by_name($stid, ":cnsctvo", $cnsctvo);
        oci_bind_by_name($stid, ":cdgo_drga", $cdgo_drga);
        oci_bind_by_name($stid, ":cntdad", $cntdad);
        oci_bind_by_name($stid, ":plan", $plan);
        oci_bind_by_name($stid, ":id_mdco", $id_mdco);
        oci_bind_by_name($stid, ":dscrpcion", $dscrpcion);
        oci_bind_by_name($stid, ":pslgia", $pslgia);
        oci_bind_by_name($stid, ":frmla", $frmla);
        oci_bind_by_name($stid, ":dosis", $dosis);
        oci_bind_by_name($stid, ":via", $via);
        oci_bind_by_name($stid, ":frecuencia", $frecuencia);
        oci_bind_by_name($stid, ":duracion", $duracion);
        oci_bind_by_name($stid, ":forma", $forma);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Formulación médica registrada exitosamente.",
                "cnsctvo" => $cnsctvo
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar formulación médica: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza una formulación médica existente.
     * 
     * @param string $id_pcnte
     * @param string $nmro_evlcion
     * @param int $cnsctvo
     * @param object $data Datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateFormulacion($id_pcnte, $nmro_evlcion, $cnsctvo, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->cdgo_drga)) {
            $fields_to_update[] = '"CDGO_DRGA" = :cdgo_drga';
            $params[":cdgo_drga"] = $data->cdgo_drga;
        }
        if (isset($data->cntdad)) {
            $fields_to_update[] = '"CNTDAD" = :cntdad';
            $params[":cntdad"] = $data->cntdad;
        }
        if (isset($data->plan)) {
            $fields_to_update[] = '"PLAN" = :plan';
            $params[":plan"] = $data->plan;
        }
        if (isset($data->id_mdco)) {
            $fields_to_update[] = '"ID_MDCO" = :id_mdco';
            $params[":id_mdco"] = $data->id_mdco;
        }
        if (isset($data->dscrpcion)) {
            $fields_to_update[] = '"DSCRPCION" = :dscrpcion';
            $params[":dscrpcion"] = $data->dscrpcion;
        }
        if (isset($data->pslgia)) {
            $fields_to_update[] = '"PSLGIA" = :pslgia';
            $params[":pslgia"] = $data->pslgia;
        }
        if (isset($data->frmla)) {
            $fields_to_update[] = '"FRMLA" = :frmla';
            $params[":frmla"] = $data->frmla;
        }
        if (isset($data->dosis)) {
            $fields_to_update[] = '"DOSIS" = :dosis';
            $params[":dosis"] = $data->dosis;
        }
        if (isset($data->via)) {
            $fields_to_update[] = '"VIA" = :via';
            $params[":via"] = $data->via;
        }
        if (isset($data->frecuencia)) {
            $fields_to_update[] = '"FRECUENCIA" = :frecuencia';
            $params[":frecuencia"] = $data->frecuencia;
        }
        if (isset($data->duracion)) {
            $fields_to_update[] = '"DURACION" = :duracion';
            $params[":duracion"] = $data->duracion;
        }
        if (isset($data->forma)) {
            $fields_to_update[] = '"FORMA" = :forma';
            $params[":forma"] = $data->forma;
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se proporcionaron campos para actualizar."
            ];
        }

        $sql = 'UPDATE "FORMULACION" SET ' . implode(", ", $fields_to_update) . ' 
                WHERE "ID_PCNTE" = :id_pcnte AND "NMRO_EVLCION" = :nmro_evlcion AND "CNSCTVO" = :cnsctvo';

        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":nmro_evlcion", $nmro_evlcion);
        oci_bind_by_name($stid, ":cnsctvo", $cnsctvo);

        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }
        unset($val);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Formulación médica actualizada exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar formulación médica: " . $e['message']
            ];
        }
    }
}
?>
