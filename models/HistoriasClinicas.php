<?php
class HistoriasClinicas
{
    private $conn;
    private $table_name = "HISTORIAS_CLINICAS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoHistoriaClinica($id_pcnte)
    {
        $sql = 'SELECT "HISTORIAS_CLINICAS"."ID_PCNTE",                  
                       "HISTORIAS_CLINICAS"."CNSCTVO_PCNTE",
                       TO_CHAR("HISTORIAS_CLINICAS"."FCHA_APRTRA", \'YYYY-MM-DD HH24:MI:SS\') AS FCHA_APRTRA,   
                       "HISTORIAS_CLINICAS"."MTVO",   
                       "HISTORIAS_CLINICAS"."EVLCION",   
                       "HISTORIAS_CLINICAS"."EMPRSA",   
                       "HISTORIAS_CLINICAS"."NMRO_ORDEN",
                       "HISTORIAS_CLINICAS"."ID_MDCO",
                       "HISTORIAS_CLINICAS"."PSO",
                       "HISTORIAS_CLINICAS"."TLLA",
                       "HISTORIAS_CLINICAS"."INDCE_MSA_CRPRAL",
                       "HISTORIAS_CLINICAS"."TMPRTRA",
                       "HISTORIAS_CLINICAS"."PRSION_ARTRIAL",
                       "HISTORIAS_CLINICAS"."FRCNCIA_CRDCA",
                       "HISTORIAS_CLINICAS"."FRCNCIA_RSPRTRIA",
                       "HISTORIAS_CLINICAS"."ENFRMDAD",
                       "HISTORIAS_CLINICAS"."ESTDO_GNRAL",
                       "HISTORIAS_CLINICAS"."OBSRVCIONES",
                       "HISTORIAS_CLINICAS"."PLAN",
                       "HISTORIAS_CLINICAS"."PLAN_TRPTCO",
                       "HISTORIAS_CLINICAS"."DGNSTCO_DFNTVO",
                       "HISTORIAS_CLINICAS"."LBRTRIOS",
                       "HISTORIAS_CLINICAS"."ANLSIS"
                FROM "HISTORIAS_CLINICAS"  
                WHERE "HISTORIAS_CLINICAS"."ID_PCNTE" = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $records = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $records[] = [
                "id_pcnte" => $row['ID_PCNTE'],
                "cnsctvo_pcnte" => $row['CNSCTVO_PCNTE'],
                "fcha_aprtra" => $row['FCHA_APRTRA'],
                "mtvo" => $row['MTVO'],
                "evlcion" => $row['EVLCION'],
                "emprsa" => $row['EMPRSA'],
                "nmro_orden" => $row['NMRO_ORDEN'],
                "id_mdco" => $row['ID_MDCO'],
                "pso" => $row['PSO'],
                "tlla" => $row['TLLA'],
                "indce_msa_crpral" => $row['INDCE_MSA_CRPRAL'],
                "tmprtra" => $row['TMPRTRA'],
                "prsion_artrial" => $row['PRSION_ARTRIAL'],
                "frcncia_crdca" => $row['FRCNCIA_CRDCA'],
                "frcncia_rsprtria" => $row['FRCNCIA_RSPRTRIA'],
                "enfrmdad" => $row['ENFRMDAD'],
                "estdo_gnral" => $row['ESTDO_GNRAL'],
                "obsrvciones" => $row['OBSRVCIONES'],
                "observaciones" => $row['OBSRVCIONES'],
                "plan" => $row['PLAN'],
                "plan_trptco" => $row['PLAN_TRPTCO'],
                "dgnstco_dfntvo" => $row['DGNSTCO_DFNTVO'],
                "diagnostico_definitivo" => $row['DGNSTCO_DFNTVO'],
                "lbrtrios" => $row['LBRTRIOS'],
                "anlsis" => $row['ANLSIS']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($records),
            "data" => $records
        ];
    }

    /**
     * Registra una nueva historia clínica.
     * 
     * @param object $data Datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createHistoriaClinica($data)
    {
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        if (empty($id_pcnte)) {
            return [
                "status" => "error",
                "message" => "El campo 'id_pcnte' es requerido."
            ];
        }

        // Buscar TPO_ID (Tipo de identificación) del paciente en la tabla pcntes si no viene
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

        // Generar consecutivo del paciente (CNSCTVO_PCNTE) si no se especifica
        $cnsctvo_pcnte = !empty($data->cnsctvo_pcnte) ? (int)$data->cnsctvo_pcnte : null;
        if (empty($cnsctvo_pcnte)) {
            $sql_cns = 'SELECT COALESCE(MAX("CNSCTVO_PCNTE"), 0) + 1 AS MAX_CNS FROM "HISTORIAS_CLINICAS" WHERE "ID_PCNTE" = :id';
            $stid_cns = oci_parse($this->conn, $sql_cns);
            oci_bind_by_name($stid_cns, ":id", $id_pcnte);
            oci_execute($stid_cns);
            $row_cns = oci_fetch_array($stid_cns, OCI_ASSOC);
            $cnsctvo_pcnte = isset($row_cns['MAX_CNS']) ? (int)$row_cns['MAX_CNS'] : 1;
            oci_free_statement($stid_cns);
        }

        $sql = 'INSERT INTO "HISTORIAS_CLINICAS" (
                    "ID_PCNTE", "CNSCTVO_PCNTE", "TPO_ID", "FCHA_APRTRA", "MTVO", "EVLCION",
                    "EMPRSA", "NMRO_ORDEN", "ID_MDCO", "TPO_ID_MDCO", "OBSRVCIONES",
                    "DGNSTCO_DFNTVO", "FCHA_INGRSO", "USRIO_INGRSO",
                    "PSO", "TLLA", "INDCE_MSA_CRPRAL", "TMPRTRA", "PRSION_ARTRIAL",
                    "FRCNCIA_CRDCA", "FRCNCIA_RSPRTRIA", "PLSO",
                    "ENFRMDAD", "ESTDO_GNRAL", "PLAN", "PLAN_TRPTCO", "LBRTRIOS", "ANLSIS"
                ) VALUES (
                    :id_pcnte, :cnsctvo_pcnte, :tpo_id, SYSDATE, :mtvo, :evlcion,
                    :emprsa, :nmro_orden, :id_mdco, :tpo_id_mdco, :observaciones,
                    :diagnostico_definitivo, SYSDATE, :usuario_ingreso,
                    :pso, :tlla, :indce_msa_crpral, :tmprtra, :prsion_artrial,
                    :frcncia_crdca, :frcncia_rsprtria, :plso,
                    :enfrmdad, :estdo_gnral, :plan, :plan_trptco, :lbrtrios, :anlsis
                )';

        $stid = oci_parse($this->conn, $sql);

        $mtvo = !empty($data->mtvo) ? $data->mtvo : null;
        $evlcion = !empty($data->evlcion) ? $data->evlcion : null;
        $emprsa = !empty($data->emprsa) ? $data->emprsa : null;
        $nmro_orden = !empty($data->nmro_orden) ? $data->nmro_orden : null;
        $id_mdco = !empty($data->id_mdco) ? $data->id_mdco : null;
        $tpo_id_mdco = !empty($data->tpo_id_mdco) ? $data->tpo_id_mdco : null;
        $observaciones = !empty($data->obsrvciones) ? $data->obsrvciones : (!empty($data->observaciones) ? $data->observaciones : null);
        $diagnostico_definitivo = !empty($data->dgnstco_dfntvo) ? $data->dgnstco_dfntvo : (!empty($data->diagnostico_definitivo) ? $data->diagnostico_definitivo : null);
        $usuario_ingreso = !empty($data->usuario_ingreso) ? $data->usuario_ingreso : 'API';
        
        $pso = isset($data->pso) ? $data->pso : null;
        $tlla = isset($data->tlla) ? $data->tlla : null;
        $indce_msa_crpral = isset($data->indce_msa_crpral) ? $data->indce_msa_crpral : null;
        $tmprtra = isset($data->tmprtra) ? $data->tmprtra : null;
        $prsion_artrial = isset($data->prsion_artrial) ? $data->prsion_artrial : null;
        $frcncia_crdca = isset($data->frcncia_crdca) ? $data->frcncia_crdca : null;
        $frcncia_rsprtria = isset($data->frcncia_rsprtria) ? $data->frcncia_rsprtria : null;
        $plso = isset($data->plso) ? $data->plso : null;

        $enfrmdad = !empty($data->enfrmdad) ? $data->enfrmdad : null;
        $estdo_gnral = !empty($data->estdo_gnral) ? $data->estdo_gnral : null;
        $plan = !empty($data->plan) ? $data->plan : null;
        $plan_trptco = !empty($data->plan_trptco) ? $data->plan_trptco : null;
        $lbrtrios = !empty($data->lbrtrios) ? $data->lbrtrios : null;
        $anlsis = !empty($data->anlsis) ? $data->anlsis : null;

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":tpo_id", $tpo_id);
        oci_bind_by_name($stid, ":mtvo", $mtvo);
        oci_bind_by_name($stid, ":evlcion", $evlcion);
        oci_bind_by_name($stid, ":emprsa", $emprsa);
        oci_bind_by_name($stid, ":nmro_orden", $nmro_orden);
        oci_bind_by_name($stid, ":id_mdco", $id_mdco);
        oci_bind_by_name($stid, ":tpo_id_mdco", $tpo_id_mdco);
        oci_bind_by_name($stid, ":observaciones", $observaciones);
        oci_bind_by_name($stid, ":diagnostico_definitivo", $diagnostico_definitivo);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);
        
        oci_bind_by_name($stid, ":pso", $pso);
        oci_bind_by_name($stid, ":tlla", $tlla);
        oci_bind_by_name($stid, ":indce_msa_crpral", $indce_msa_crpral);
        oci_bind_by_name($stid, ":tmprtra", $tmprtra);
        oci_bind_by_name($stid, ":prsion_artrial", $prsion_artrial);
        oci_bind_by_name($stid, ":frcncia_crdca", $frcncia_crdca);
        oci_bind_by_name($stid, ":frcncia_rsprtria", $frcncia_rsprtria);
        oci_bind_by_name($stid, ":plso", $plso);

        oci_bind_by_name($stid, ":enfrmdad", $enfrmdad);
        oci_bind_by_name($stid, ":estdo_gnral", $estdo_gnral);
        oci_bind_by_name($stid, ":plan", $plan);
        oci_bind_by_name($stid, ":plan_trptco", $plan_trptco);
        oci_bind_by_name($stid, ":lbrtrios", $lbrtrios);
        oci_bind_by_name($stid, ":anlsis", $anlsis);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Historia clínica registrada exitosamente.",
                "cnsctvo_pcnte" => $cnsctvo_pcnte
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar historia clínica: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza una historia clínica existente identificada por id_pcnte y cnsctvo_pcnte.
     * 
     * @param string $id_pcnte Identificación del paciente.
     * @param int $cnsctvo_pcnte Consecutivo de la historia.
     * @param object $data Datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateHistoriaClinica($id_pcnte, $cnsctvo_pcnte, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->mtvo)) {
            $fields_to_update[] = '"MTVO" = :mtvo';
            $params[":mtvo"] = $data->mtvo;
        }
        if (isset($data->evlcion)) {
            $fields_to_update[] = '"EVLCION" = :evlcion';
            $params[":evlcion"] = $data->evlcion;
        }
        if (isset($data->emprsa)) {
            $fields_to_update[] = '"EMPRSA" = :emprsa';
            $params[":emprsa"] = $data->emprsa;
        }
        if (isset($data->nmro_orden)) {
            $fields_to_update[] = '"NMRO_ORDEN" = :nmro_orden';
            $params[":nmro_orden"] = $data->nmro_orden;
        }
        if (isset($data->id_mdco)) {
            $fields_to_update[] = '"ID_MDCO" = :id_mdco';
            $params[":id_mdco"] = $data->id_mdco;
        }
        if (isset($data->tpo_id_mdco)) {
            $fields_to_update[] = '"TPO_ID_MDCO" = :tpo_id_mdco';
            $params[":tpo_id_mdco"] = $data->tpo_id_mdco;
        }
        if (isset($data->obsrvciones) || isset($data->observaciones)) {
            $fields_to_update[] = '"OBSRVCIONES" = :observaciones';
            $params[":observaciones"] = isset($data->obsrvciones) ? $data->obsrvciones : $data->observaciones;
        }
        if (isset($data->dgnstco_dfntvo) || isset($data->diagnostico_definitivo)) {
            $fields_to_update[] = '"DGNSTCO_DFNTVO" = :diagnostico_definitivo';
            $params[":diagnostico_definitivo"] = isset($data->dgnstco_dfntvo) ? $data->dgnstco_dfntvo : $data->diagnostico_definitivo;
        }
        if (isset($data->enfrmdad)) {
            $fields_to_update[] = '"ENFRMDAD" = :enfrmdad';
            $params[":enfrmdad"] = $data->enfrmdad;
        }
        if (isset($data->estdo_gnral)) {
            $fields_to_update[] = '"ESTDO_GNRAL" = :estdo_gnral';
            $params[":estdo_gnral"] = $data->estdo_gnral;
        }
        if (isset($data->plan)) {
            $fields_to_update[] = '"PLAN" = :plan';
            $params[":plan"] = $data->plan;
        }
        if (isset($data->plan_trptco)) {
            $fields_to_update[] = '"PLAN_TRPTCO" = :plan_trptco';
            $params[":plan_trptco"] = $data->plan_trptco;
        }
        if (isset($data->lbrtrios)) {
            $fields_to_update[] = '"LBRTRIOS" = :lbrtrios';
            $params[":lbrtrios"] = $data->lbrtrios;
        }
        if (isset($data->anlsis)) {
            $fields_to_update[] = '"ANLSIS" = :anlsis';
            $params[":anlsis"] = $data->anlsis;
        }
        if (isset($data->pso)) {
            $fields_to_update[] = '"PSO" = :pso';
            $params[":pso"] = $data->pso;
        }
        if (isset($data->tlla)) {
            $fields_to_update[] = '"TLLA" = :tlla';
            $params[":tlla"] = $data->tlla;
        }
        if (isset($data->indce_msa_crpral)) {
            $fields_to_update[] = '"INDCE_MSA_CRPRAL" = :indce_msa_crpral';
            $params[":indce_msa_crpral"] = $data->indce_msa_crpral;
        }
        if (isset($data->tmprtra)) {
            $fields_to_update[] = '"TMPRTRA" = :tmprtra';
            $params[":tmprtra"] = $data->tmprtra;
        }
        if (isset($data->prsion_artrial)) {
            $fields_to_update[] = '"PRSION_ARTRIAL" = :prsion_artrial';
            $params[":prsion_artrial"] = $data->prsion_artrial;
        }
        if (isset($data->frcncia_crdca)) {
            $fields_to_update[] = '"FRCNCIA_CRDCA" = :frcncia_crdca';
            $params[":frcncia_crdca"] = $data->frcncia_crdca;
        }
        if (isset($data->frcncia_rsprtria)) {
            $fields_to_update[] = '"FRCNCIA_RSPRTRIA" = :frcncia_rsprtria';
            $params[":frcncia_rsprtria"] = $data->frcncia_rsprtria;
        }
        if (isset($data->plso)) {
            $fields_to_update[] = '"PLSO" = :plso';
            $params[":plso"] = $data->plso;
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se proporcionaron campos para actualizar."
            ];
        }

        // Registrar trazabilidad de modificación
        $fields_to_update[] = '"FCHA_MDFCCION" = SYSDATE';
        if (!empty($data->usuario_modificacion)) {
            $fields_to_update[] = '"USRIO_MDFCCION" = :usuario_modificacion';
            $params[":usuario_modificacion"] = $data->usuario_modificacion;
        }

        $sql = 'UPDATE "HISTORIAS_CLINICAS" SET ' . implode(", ", $fields_to_update) . ' 
                WHERE "ID_PCNTE" = :id_pcnte AND "CNSCTVO_PCNTE" = :cnsctvo_pcnte';

        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);

        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }
        unset($val);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Historia clínica actualizada exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar historia clínica: " . $e['message']
            ];
        }
    }
}
?>
