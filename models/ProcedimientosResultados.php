<?php
class ProcedimientosResultados
{
    private $conn;
    private $table_name = "PRCDMNTOS_RSLTDOS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoProcedimientoResultado($id_pcnte, $cnsctvo_pcnte)
    {
        $sql = 'SELECT 
                    pr.ID_PCNTE,   
                    pr.TPO_ID,   
                    pr.CNSCTVO_PCNTE,   
                    pr.NMRO_ORDEN,   
                    pr.ID_MDCO,   
                    pr.EQPO,   
                    pr.CDGO_PRCDMNTO,   
                    pr.ITEM,   
                    pr.RSLTDO,   
                    TO_CHAR(pr.FCHA, \'YYYY-MM-DD HH24:MI:SS\') AS FCHA,   
                    pr.EMPRSA,   
                    pr.CDGO_INDCION,   
                    pr.ORDEN,   
                    pr.DSCRPCION_PRCDMNTO  
                FROM "PRCDMNTOS_RSLTDOS" pr  
                WHERE pr.ID_PCNTE = :id 
                  AND pr.CNSCTVO_PCNTE = :cons';

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

        $resultados = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $resultados[] = [
                "id_pcnte" => $row['ID_PCNTE'],
                "tpo_id" => $row['TPO_ID'],
                "cnsctvo_pcnte" => $row['CNSCTVO_PCNTE'],
                "nmro_orden" => $row['NMRO_ORDEN'],
                "id_mdco" => $row['ID_MDCO'],
                "eqpo" => $row['EQPO'],
                "cdgo_prcdmnto" => $row['CDGO_PRCDMNTO'],
                "item" => $row['ITEM'],
                "rsltdo" => $row['RSLTDO'],
                "fcha" => $row['FCHA'],
                "emprsa" => $row['EMPRSA'],
                "cdgo_indcion" => $row['CDGO_INDCION'],
                "orden" => $row['ORDEN'],
                "dscrpcion_prcdmnto" => $row['DSCRPCION_PRCDMNTO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($resultados),
            "data" => $resultados
        ];
    }

    /**
     * Registra un nuevo resultado de procedimiento.
     * 
     * @param object $data Datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createProcedimientoResultado($data)
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

        // Generar consecutivo de ITEM si no se especifica
        $item = !empty($data->item) ? (int)$data->item : null;
        if (empty($item)) {
            $sql_cns = 'SELECT COALESCE(MAX("ITEM"), 0) + 1 AS MAX_CNS FROM "PRCDMNTOS_RSLTDOS" WHERE "ID_PCNTE" = :id AND "CNSCTVO_PCNTE" = :consec';
            $stid_cns = oci_parse($this->conn, $sql_cns);
            oci_bind_by_name($stid_cns, ":id", $id_pcnte);
            oci_bind_by_name($stid_cns, ":consec", $cnsctvo_pcnte);
            oci_execute($stid_cns);
            $row_cns = oci_fetch_array($stid_cns, OCI_ASSOC);
            $item = isset($row_cns['MAX_CNS']) ? (int)$row_cns['MAX_CNS'] : 1;
            oci_free_statement($stid_cns);
        }

        $sql = 'INSERT INTO "PRCDMNTOS_RSLTDOS" (
                    "ID_PCNTE", "TPO_ID", "CNSCTVO_PCNTE", "NMRO_ORDEN", "ID_MDCO",
                    "EQPO", "CDGO_PRCDMNTO", "ITEM", "RSLTDO", "FCHA",
                    "EMPRSA", "CDGO_INDCION", "ORDEN", "DSCRPCION_PRCDMNTO"
                ) VALUES (
                    :id_pcnte, :tpo_id, :cnsctvo_pcnte, :nmro_orden, :id_mdco,
                    :eqpo, :cdgo_prcdmnto, :item, :rsltdo, SYSDATE,
                    :emprsa, :cdgo_indcion, :orden, :dscrpcion_prcdmnto
                )';

        $stid = oci_parse($this->conn, $sql);

        $nmro_orden = !empty($data->nmro_orden) ? $data->nmro_orden : null;
        $id_mdco = !empty($data->id_mdco) ? $data->id_mdco : null;
        $eqpo = !empty($data->eqpo) ? $data->eqpo : null;
        $cdgo_prcdmnto = !empty($data->cdgo_prcdmnto) ? $data->cdgo_prcdmnto : null;
        $rsltdo = !empty($data->rsltdo) ? $data->rsltdo : null;
        $emprsa = !empty($data->emprsa) ? $data->emprsa : null;
        $cdgo_indcion = !empty($data->cdgo_indcion) ? $data->cdgo_indcion : null;
        $orden = isset($data->orden) ? $data->orden : null;
        $dscrpcion_prcdmnto = !empty($data->dscrpcion_prcdmnto) ? $data->dscrpcion_prcdmnto : null;

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":tpo_id", $tpo_id);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":nmro_orden", $nmro_orden);
        oci_bind_by_name($stid, ":id_mdco", $id_mdco);
        oci_bind_by_name($stid, ":eqpo", $eqpo);
        oci_bind_by_name($stid, ":cdgo_prcdmnto", $cdgo_prcdmnto);
        oci_bind_by_name($stid, ":item", $item);
        oci_bind_by_name($stid, ":rsltdo", $rsltdo);
        oci_bind_by_name($stid, ":emprsa", $emprsa);
        oci_bind_by_name($stid, ":cdgo_indcion", $cdgo_indcion);
        oci_bind_by_name($stid, ":orden", $orden);
        oci_bind_by_name($stid, ":dscrpcion_prcdmnto", $dscrpcion_prcdmnto);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Resultado de procedimiento registrado exitosamente.",
                "item" => $item
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar resultado de procedimiento: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza un resultado de procedimiento existente.
     * 
     * @param string $id_pcnte
     * @param int $cnsctvo_pcnte
     * @param int $item
     * @param object $data Datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateProcedimientoResultado($id_pcnte, $cnsctvo_pcnte, $item, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->nmro_orden)) {
            $fields_to_update[] = '"NMRO_ORDEN" = :nmro_orden';
            $params[":nmro_orden"] = $data->nmro_orden;
        }
        if (isset($data->id_mdco)) {
            $fields_to_update[] = '"ID_MDCO" = :id_mdco';
            $params[":id_mdco"] = $data->id_mdco;
        }
        if (isset($data->eqpo)) {
            $fields_to_update[] = '"EQPO" = :eqpo';
            $params[":eqpo"] = $data->eqpo;
        }
        if (isset($data->cdgo_prcdmnto)) {
            $fields_to_update[] = '"CDGO_PRCDMNTO" = :cdgo_prcdmnto';
            $params[":cdgo_prcdmnto"] = $data->cdgo_prcdmnto;
        }
        if (isset($data->rsltdo)) {
            $fields_to_update[] = '"RSLTDO" = :rsltdo';
            $params[":rsltdo"] = $data->rsltdo;
        }
        if (isset($data->emprsa)) {
            $fields_to_update[] = '"EMPRSA" = :emprsa';
            $params[":emprsa"] = $data->emprsa;
        }
        if (isset($data->cdgo_indcion)) {
            $fields_to_update[] = '"CDGO_INDCION" = :cdgo_indcion';
            $params[":cdgo_indcion"] = $data->cdgo_indcion;
        }
        if (isset($data->orden)) {
            $fields_to_update[] = '"ORDEN" = :orden';
            $params[":orden"] = $data->orden;
        }
        if (isset($data->dscrpcion_prcdmnto)) {
            $fields_to_update[] = '"DSCRPCION_PRCDMNTO" = :dscrpcion_prcdmnto';
            $params[":dscrpcion_prcdmnto"] = $data->dscrpcion_prcdmnto;
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se proporcionaron campos para actualizar."
            ];
        }

        $sql = 'UPDATE "PRCDMNTOS_RSLTDOS" SET ' . implode(", ", $fields_to_update) . ' 
                WHERE "ID_PCNTE" = :id_pcnte AND "CNSCTVO_PCNTE" = :cnsctvo_pcnte AND "ITEM" = :item';

        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":item", $item);

        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }
        unset($val);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Resultado de procedimiento actualizado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar resultado de procedimiento: " . $e['message']
            ];
        }
    }
}
?>
