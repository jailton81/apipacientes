<?php
class IncapacidadesHC
{
    private $conn;
    private $table_name = "RMSION_INCPCDAD";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoIncapacidad($id_pcnte, $cnsctvo_pcnte)
    {
        $sql = 'SELECT 
                    i.ID_PCNTE,   
                    i.TPO_ID,   
                    i.CNSCTVO_PCNTE,   
                    i.NIT_EMPRSA,   
                    i.ID_MDCO,   
                    i.DSCRPCION_INCPCDAD,   
                    TO_CHAR(i.FCHA_RMSION, \'YYYY-MM-DD HH24:MI:SS\') AS FCHA_RMSION,   
                    i.CDGO_EXMEN,   
                    TO_CHAR(i.FCHA_INCIO, \'YYYY-MM-DD HH24:MI:SS\') AS FCHA_INCIO,   
                    TO_CHAR(i.FCHA_FNAL, \'YYYY-MM-DD HH24:MI:SS\') AS FCHA_FNAL,   
                    i.DGNSTCO,   
                    i.DRCION,   
                    i.GRPO_SRVCIO  
                FROM "RMSION_INCPCDAD" i  
                WHERE i.ID_PCNTE = :id 
                  AND i.CNSCTVO_PCNTE = :cons';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":id", $id_pcnte);
        oci_bind_by_name($stid, ":cons", $cnsctvo_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $incapacidades = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $incapacidades[] = [
                "id_pcnte" => $row['ID_PCNTE'],
                "tpo_id" => $row['TPO_ID'],
                "cnsctvo_pcnte" => $row['CNSCTVO_PCNTE'],
                "nit_emprsa" => $row['NIT_EMPRSA'],
                "id_mdco" => $row['ID_MDCO'],
                "dscrpcion_incpcdad" => $row['DSCRPCION_INCPCDAD'],
                "fcha_rmsion" => $row['FCHA_RMSION'],
                "cdgo_exmen" => $row['CDGO_EXMEN'],
                "fcha_incio" => $row['FCHA_INCIO'],
                "fcha_fnal" => $row['FCHA_FNAL'],
                "dgnstco" => $row['DGNSTCO'],
                "drcion" => $row['DRCION'],
                "grpo_srvcio" => $row['GRPO_SRVCIO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($incapacidades),
            "data" => $incapacidades
        ];
    }

    /**
     * Registra una nueva incapacidad.
     * 
     * @param object $data Datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createIncapacidad($data)
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

        $sql = 'INSERT INTO "RMSION_INCPCDAD" (
                    "ID_PCNTE", "TPO_ID", "CNSCTVO_PCNTE", "NIT_EMPRSA", "ID_MDCO",
                    "DSCRPCION_INCPCDAD", "FCHA_RMSION", "CDGO_EXMEN", "FCHA_INCIO",
                    "FCHA_FNAL", "DGNSTCO", "DRCION", "GRPO_SRVCIO"
                ) VALUES (
                    :id_pcnte, :tpo_id, :cnsctvo_pcnte, :nit_emprsa, :id_mdco,
                    :dscrpcion_incpcdad, SYSDATE, :cdgo_exmen, 
                    TO_DATE(:fcha_incio, \'YYYY-MM-DD\'), TO_DATE(:fcha_fnal, \'YYYY-MM-DD\'), 
                    :dgnstco, :drcion, :grpo_srvcio
                )';

        $stid = oci_parse($this->conn, $sql);

        $nit_emprsa = !empty($data->nit_emprsa) ? $data->nit_emprsa : null;
        $id_mdco = !empty($data->id_mdco) ? $data->id_mdco : null;
        $dscrpcion_incpcdad = !empty($data->dscrpcion_incpcdad) ? $data->dscrpcion_incpcdad : null;
        $cdgo_exmen = !empty($data->cdgo_exmen) ? $data->cdgo_exmen : null;
        $fcha_incio = !empty($data->fcha_incio) ? $data->fcha_incio : null;
        $fcha_fnal = !empty($data->fcha_fnal) ? $data->fcha_fnal : null;
        $dgnstco = !empty($data->dgnstco) ? $data->dgnstco : null;
        $drcion = isset($data->drcion) ? $data->drcion : null;
        $grpo_srvcio = !empty($data->grpo_srvcio) ? $data->grpo_srvcio : null;

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":tpo_id", $tpo_id);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":nit_emprsa", $nit_emprsa);
        oci_bind_by_name($stid, ":id_mdco", $id_mdco);
        oci_bind_by_name($stid, ":dscrpcion_incpcdad", $dscrpcion_incpcdad);
        oci_bind_by_name($stid, ":cdgo_exmen", $cdgo_exmen);
        oci_bind_by_name($stid, ":fcha_incio", $fcha_incio);
        oci_bind_by_name($stid, ":fcha_fnal", $fcha_fnal);
        oci_bind_by_name($stid, ":dgnstco", $dgnstco);
        oci_bind_by_name($stid, ":drcion", $drcion);
        oci_bind_by_name($stid, ":grpo_srvcio", $grpo_srvcio);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Incapacidad registrada exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar incapacidad: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza una incapacidad existente.
     * 
     * @param string $id_pcnte
     * @param int $cnsctvo_pcnte
     * @param object $data Datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateIncapacidad($id_pcnte, $cnsctvo_pcnte, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->nit_emprsa)) {
            $fields_to_update[] = '"NIT_EMPRSA" = :nit_emprsa';
            $params[":nit_emprsa"] = $data->nit_emprsa;
        }
        if (isset($data->id_mdco)) {
            $fields_to_update[] = '"ID_MDCO" = :id_mdco';
            $params[":id_mdco"] = $data->id_mdco;
        }
        if (isset($data->dscrpcion_incpcdad)) {
            $fields_to_update[] = '"DSCRPCION_INCPCDAD" = :dscrpcion_incpcdad';
            $params[":dscrpcion_incpcdad"] = $data->dscrpcion_incpcdad;
        }
        if (isset($data->cdgo_exmen)) {
            $fields_to_update[] = '"CDGO_EXMEN" = :cdgo_exmen';
            $params[":cdgo_exmen"] = $data->cdgo_exmen;
        }
        if (isset($data->fcha_incio)) {
            $fields_to_update[] = '"FCHA_INCIO" = TO_DATE(:fcha_incio, \'YYYY-MM-DD\')';
            $params[":fcha_incio"] = $data->fcha_incio;
        }
        if (isset($data->fcha_fnal)) {
            $fields_to_update[] = '"FCHA_FNAL" = TO_DATE(:fcha_fnal, \'YYYY-MM-DD\')';
            $params[":fcha_fnal"] = $data->fcha_fnal;
        }
        if (isset($data->dgnstco)) {
            $fields_to_update[] = '"DGNSTCO" = :dgnstco';
            $params[":dgnstco"] = $data->dgnstco;
        }
        if (isset($data->drcion)) {
            $fields_to_update[] = '"DRCION" = :drcion';
            $params[":drcion"] = $data->drcion;
        }
        if (isset($data->grpo_srvcio)) {
            $fields_to_update[] = '"GRPO_SRVCIO" = :grpo_srvcio';
            $params[":grpo_srvcio"] = $data->grpo_srvcio;
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se proporcionaron campos para actualizar."
            ];
        }

        $sql = 'UPDATE "RMSION_INCPCDAD" SET ' . implode(", ", $fields_to_update) . ' 
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
                "message" => "Incapacidad actualizada exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar incapacidad: " . $e['message']
            ];
        }
    }
}
?>
