<?php
class Remisiones
{
    private $conn;
    private $table_name = "RMSION_CNSLTAS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoRemision($idntfccion_mdcos, $cnsctvo_pcnte)
    {
        $sql = 'SELECT 
                    r.ID_PCNTE,
                    r.TPO_ID,
                    r.CNSCTVO_PCNTE,
                    r.CNDCTA,
                    r.TPO_CNSLTA,
                    r.NIT_EMPRSA,
                    r.OBSRVCIONES,
                    r.PLAN,
                    r.RSLTDO_EXMEN,
                    r.ID_MDCO,
                    r.DSCRPCION_CNSLTA,
                    r.CNSCTVO_RMSION,
                    TO_CHAR(r.FCHA_RMSION, \'YYYY-MM-DD HH24:MI:SS\') AS FCHA_RMSION,
                    r.CDGO_EXMEN,
                    r.JSTFCCION,
                    r.FRMLA
                FROM "RMSION_CNSLTAS" r
                WHERE r.ID_PCNTE = :s_id 
                  AND r.CNSCTVO_PCNTE = :l_consec';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $idntfccion_mdcos);
        oci_bind_by_name($stid, ":l_consec", $cnsctvo_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $remisiones = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $remisiones[] = [
                "id_pcnte" => $row['ID_PCNTE'],
                "tpo_id" => $row['TPO_ID'],
                "cnsctvo_pcnte" => $row['CNSCTVO_PCNTE'],
                "cndcta" => $row['CNDCTA'],
                "tpo_cnslta" => $row['TPO_CNSLTA'],
                "nit_emprsa" => $row['NIT_EMPRSA'],
                "obsrvciones" => $row['OBSRVCIONES'],
                "plan" => $row['PLAN'],
                "rsltdo_exmen" => $row['RSLTDO_EXMEN'],
                "id_mdco" => $row['ID_MDCO'],
                "dscrpcion_cnslta" => $row['DSCRPCION_CNSLTA'],
                "cnsctvo_rmsion" => $row['CNSCTVO_RMSION'],
                "fcha_rmsion" => $row['FCHA_RMSION'],
                "cdgo_exmen" => $row['CDGO_EXMEN'],
                "jstfccion" => $row['JSTFCCION'],
                "frmla" => $row['FRMLA']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($remisiones),
            "data" => $remisiones
        ];
    }

    /**
     * Registra una nueva remisión.
     * 
     * @param object $data Datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createRemision($data)
    {
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        $cnsctvo_pcnte = !empty($data->cnsctvo_pcnte) ? (int)$data->cnsctvo_pcnte : null;

        if (empty($id_pcnte) || empty($cnsctvo_pcnte)) {
            return [
                "status" => "error",
                "message" => "Los campos 'id_pcnte' y 'cnsctvo_pcnte' son requeridos."
            ];
        }

        // Obtener el TPO_ID (Tipo de identificación) del paciente en la tabla pcntes si no viene
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

        // Generar consecutivo de remisión (CNSCTVO_RMSION) si no se especifica
        $cnsctvo_rmsion = !empty($data->cnsctvo_rmsion) ? (int)$data->cnsctvo_rmsion : null;
        if (empty($cnsctvo_rmsion)) {
            $sql_cns = 'SELECT COALESCE(MAX("CNSCTVO_RMSION"), 0) + 1 AS MAX_CNS FROM "RMSION_CNSLTAS" WHERE "ID_PCNTE" = :id AND "CNSCTVO_PCNTE" = :consec';
            $stid_cns = oci_parse($this->conn, $sql_cns);
            oci_bind_by_name($stid_cns, ":id", $id_pcnte);
            oci_bind_by_name($stid_cns, ":consec", $cnsctvo_pcnte);
            oci_execute($stid_cns);
            $row_cns = oci_fetch_array($stid_cns, OCI_ASSOC);
            $cnsctvo_rmsion = isset($row_cns['MAX_CNS']) ? (int)$row_cns['MAX_CNS'] : 1;
            oci_free_statement($stid_cns);
        }

        $sql = 'INSERT INTO "RMSION_CNSLTAS" (
                    "ID_PCNTE", "TPO_ID", "CNSCTVO_PCNTE", "CNDCTA", "TPO_CNSLTA",
                    "NIT_EMPRSA", "OBSRVCIONES", "PLAN", "RSLTDO_EXMEN", "ID_MDCO",
                    "DSCRPCION_CNSLTA", "CNSCTVO_RMSION", "FCHA_RMSION", "CDGO_EXMEN",
                    "JSTFCCION", "FRMLA"
                ) VALUES (
                    :id_pcnte, :tpo_id, :cnsctvo_pcnte, :cndcta, :tpo_cnslta,
                    :nit_emprsa, :obsrvciones, :plan, :rsltdo_exmen, :id_mdco,
                    :dscrpcion_cnslta, :cnsctvo_rmsion, SYSDATE, :cdgo_exmen,
                    :jstfccion, :frmla
                )';

        $stid = oci_parse($this->conn, $sql);

        $cndcta = !empty($data->cndcta) ? $data->cndcta : null;
        $tpo_cnslta = !empty($data->tpo_cnslta) ? $data->tpo_cnslta : null;
        $nit_emprsa = !empty($data->nit_emprsa) ? $data->nit_emprsa : null;
        $obsrvciones = !empty($data->obsrvciones) ? $data->obsrvciones : null;
        $plan = !empty($data->plan) ? $data->plan : null;
        $rsltdo_exmen = !empty($data->rsltdo_exmen) ? $data->rsltdo_exmen : null;
        $id_mdco = !empty($data->id_mdco) ? $data->id_mdco : null;
        $dscrpcion_cnslta = !empty($data->dscrpcion_cnslta) ? $data->dscrpcion_cnslta : null;
        $cdgo_exmen = !empty($data->cdgo_exmen) ? $data->cdgo_exmen : null;
        $jstfccion = !empty($data->jstfccion) ? $data->jstfccion : null;
        $frmla = !empty($data->frmla) ? $data->frmla : null;

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":tpo_id", $tpo_id);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":cndcta", $cndcta);
        oci_bind_by_name($stid, ":tpo_cnslta", $tpo_cnslta);
        oci_bind_by_name($stid, ":nit_emprsa", $nit_emprsa);
        oci_bind_by_name($stid, ":obsrvciones", $obsrvciones);
        oci_bind_by_name($stid, ":plan", $plan);
        oci_bind_by_name($stid, ":rsltdo_exmen", $rsltdo_exmen);
        oci_bind_by_name($stid, ":id_mdco", $id_mdco);
        oci_bind_by_name($stid, ":dscrpcion_cnslta", $dscrpcion_cnslta);
        oci_bind_by_name($stid, ":cnsctvo_rmsion", $cnsctvo_rmsion);
        oci_bind_by_name($stid, ":cdgo_exmen", $cdgo_exmen);
        oci_bind_by_name($stid, ":jstfccion", $jstfccion);
        oci_bind_by_name($stid, ":frmla", $frmla);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Remisión registrada exitosamente.",
                "cnsctvo_rmsion" => $cnsctvo_rmsion
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar remisión: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza una remisión existente.
     * 
     * @param string $id_pcnte
     * @param int $cnsctvo_pcnte
     * @param int $cnsctvo_rmsion
     * @param object $data Datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateRemision($id_pcnte, $cnsctvo_pcnte, $cnsctvo_rmsion, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->cndcta)) {
            $fields_to_update[] = '"CNDCTA" = :cndcta';
            $params[":cndcta"] = $data->cndcta;
        }
        if (isset($data->tpo_cnslta)) {
            $fields_to_update[] = '"TPO_CNSLTA" = :tpo_cnslta';
            $params[":tpo_cnslta"] = $data->tpo_cnslta;
        }
        if (isset($data->nit_emprsa)) {
            $fields_to_update[] = '"NIT_EMPRSA" = :nit_emprsa';
            $params[":nit_emprsa"] = $data->nit_emprsa;
        }
        if (isset($data->obsrvciones)) {
            $fields_to_update[] = '"OBSRVCIONES" = :obsrvciones';
            $params[":obsrvciones"] = $data->obsrvciones;
        }
        if (isset($data->plan)) {
            $fields_to_update[] = '"PLAN" = :plan';
            $params[":plan"] = $data->plan;
        }
        if (isset($data->rsltdo_exmen)) {
            $fields_to_update[] = '"RSLTDO_EXMEN" = :rsltdo_exmen';
            $params[":rsltdo_exmen"] = $data->rsltdo_exmen;
        }
        if (isset($data->id_mdco)) {
            $fields_to_update[] = '"ID_MDCO" = :id_mdco';
            $params[":id_mdco"] = $data->id_mdco;
        }
        if (isset($data->dscrpcion_cnslta)) {
            $fields_to_update[] = '"DSCRPCION_CNSLTA" = :dscrpcion_cnslta';
            $params[":dscrpcion_cnslta"] = $data->dscrpcion_cnslta;
        }
        if (isset($data->cdgo_exmen)) {
            $fields_to_update[] = '"CDGO_EXMEN" = :cdgo_exmen';
            $params[":cdgo_exmen"] = $data->cdgo_exmen;
        }
        if (isset($data->jstfccion)) {
            $fields_to_update[] = '"JSTFCCION" = :jstfccion';
            $params[":jstfccion"] = $data->jstfccion;
        }
        if (isset($data->frmla)) {
            $fields_to_update[] = '"FRMLA" = :frmla';
            $params[":frmla"] = $data->frmla;
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se proporcionaron campos para actualizar."
            ];
        }

        $sql = 'UPDATE "RMSION_CNSLTAS" SET ' . implode(", ", $fields_to_update) . ' 
                WHERE "ID_PCNTE" = :id_pcnte AND "CNSCTVO_PCNTE" = :cnsctvo_pcnte AND "CNSCTVO_RMSION" = :cnsctvo_rmsion';

        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_rmsion", $cnsctvo_rmsion);

        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }
        unset($val);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Remisión actualizada exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar remisión: " . $e['message']
            ];
        }
    }
}
?>
