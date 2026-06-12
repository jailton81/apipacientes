<?php
class Condition
{
    private $conn;
    private $table_name = "ANTECEDENTES_PATOLOGICOS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoCondition($id_pcnte)
    {
        $sql = 'SELECT 
                    ap.ID,
                    ap.ID_PCNTE,
                    ap.FHIR_ID,
                    ap.CODIGO,
                    ap.CIE_10,
                    ap.CIE_11,
                    ap.DESCRIPCION,
                    ap.ESTADO,
                    sa.DISPLAY AS STATUS_CLINICO_DISPLAY,
                    ap.ESTADO_VERIFICACION,
                    sb.DISPLAY AS STATUS_VERIFICACION_DISPLAY,
                    ap.EDAD_DIAGNOSTICO,
                    ap.OBSERVACIONES,
                    \'encounter-diagnosis\' AS CATEGORY_CODE,
                    \'Encounter Diagnosis\' AS CATEGORY_DISPLAY
                FROM "' . $this->table_name . '" ap
                INNER JOIN "STATUS" sa 
                    ON ap.ESTADO = sa.CODE 
                    AND sa.STATUS = \'ConditionClinicalStatusCodes\'
                INNER JOIN "STATUS" sb 
                    ON ap.ESTADO_VERIFICACION = sb.CODE 
                    AND sb.STATUS = \'verificationStatus\'
                WHERE ap.ID_PCNTE = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $conditions = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $conditions[] = [
                "id" => $row['ID'],
                "id_pcnte" => $row['ID_PCNTE'],
                "fhir_id" => $row['FHIR_ID'],
                "codigo" => $row['CODIGO'],
                "cie_10" => $row['CIE_10'],
                "cie_11" => $row['CIE_11'],
                "descripcion" => $row['DESCRIPCION'],
                "status_clinico_code" => $row['ESTADO'],
                "status_clinico_display" => $row['STATUS_CLINICO_DISPLAY'],
                "status_verificacion_code" => $row['ESTADO_VERIFICACION'],
                "status_verificacion_display" => $row['STATUS_VERIFICACION_DISPLAY'],
                "edad_diagnostico" => $row['EDAD_DIAGNOSTICO'],
                "observaciones" => $row['OBSERVACIONES'],
                "category_code" => $row['CATEGORY_CODE'],
                "category_display" => $row['CATEGORY_DISPLAY']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($conditions),
            "data" => $conditions
        ];
    }

    /**
     * Registra un nuevo antecedente patológico (Condition).
     * 
     * @param object $data Objeto con los datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createCondition($data)
    {
        $sql = 'INSERT INTO "' . $this->table_name . '" (
                    "ID", "ID_PCNTE", "FHIR_ID", "CODIGO", "CIE_10", "CIE_11", 
                    "DESCRIPCION", "ESTADO", "ESTADO_VERIFICACION", "EDAD_DIAGNOSTICO", 
                    "OBSERVACIONES", "FECHA_INGRESO", "USUARIO_INGRESO"
                ) VALUES (
                    (SELECT COALESCE(MAX("ID"), 0) + 1 FROM "' . $this->table_name . '"),
                    :id_pcnte, :fhir_id, :codigo, :cie_10, :cie_11, 
                    :descripcion, :estado, :estado_verificacion, :edad_diagnostico, 
                    :observaciones, SYSDATE, :usuario_ingreso
                )';

        $stid = oci_parse($this->conn, $sql);

        // Bind parameters
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        $fhir_id = !empty($data->fhir_id) ? $data->fhir_id : 'fhir-' . uniqid();
        $cie_10 = !empty($data->cie_10) ? $data->cie_10 : null;
        $cie_11 = !empty($data->cie_11) ? $data->cie_11 : null;
        $codigo = !empty($data->codigo) ? $data->codigo : (!empty($cie_10) ? $cie_10 : 'GENERIC');
        $descripcion = !empty($data->descripcion) ? $data->descripcion : null;
        $estado = !empty($data->estado) ? $data->estado : 'active';
        $estado_verificacion = !empty($data->estado_verificacion) ? $data->estado_verificacion : 'confirmed';
        $edad_diagnostico = isset($data->edad_diagnostico) ? $data->edad_diagnostico : null;
        $observaciones = !empty($data->observaciones) ? $data->observaciones : null;
        $usuario_ingreso = !empty($data->usuario_ingreso) ? $data->usuario_ingreso : 'API';

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":fhir_id", $fhir_id);
        oci_bind_by_name($stid, ":codigo", $codigo);
        oci_bind_by_name($stid, ":cie_10", $cie_10);
        oci_bind_by_name($stid, ":cie_11", $cie_11);
        oci_bind_by_name($stid, ":descripcion", $descripcion);
        oci_bind_by_name($stid, ":estado", $estado);
        oci_bind_by_name($stid, ":estado_verificacion", $estado_verificacion);
        oci_bind_by_name($stid, ":edad_diagnostico", $edad_diagnostico);
        oci_bind_by_name($stid, ":observaciones", $observaciones);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Antecedente patológico registrado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar antecedente patológico: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza un antecedente patológico existente por su ID.
     * 
     * @param int $id Identificador del antecedente.
     * @param object $data Objeto con los datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateCondition($id, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->codigo)) {
            $fields_to_update[] = '"CODIGO" = :codigo';
            $params[":codigo"] = $data->codigo;
        }
        if (isset($data->cie_10)) {
            $fields_to_update[] = '"CIE_10" = :cie_10';
            $params[":cie_10"] = $data->cie_10;
        }
        if (isset($data->cie_11)) {
            $fields_to_update[] = '"CIE_11" = :cie_11';
            $params[":cie_11"] = $data->cie_11;
        }
        if (isset($data->descripcion)) {
            $fields_to_update[] = '"DESCRIPCION" = :descripcion';
            $params[":descripcion"] = $data->descripcion;
        }
        if (isset($data->estado)) {
            $fields_to_update[] = '"ESTADO" = :estado';
            $params[":estado"] = $data->estado;
        }
        if (isset($data->estado_verificacion)) {
            $fields_to_update[] = '"ESTADO_VERIFICACION" = :estado_verificacion';
            $params[":estado_verificacion"] = $data->estado_verificacion;
        }
        if (isset($data->edad_diagnostico)) {
            $fields_to_update[] = '"EDAD_DIAGNOSTICO" = :edad_diagnostico';
            $params[":edad_diagnostico"] = $data->edad_diagnostico;
        }
        if (isset($data->observaciones)) {
            $fields_to_update[] = '"OBSERVACIONES" = :observaciones';
            $params[":observaciones"] = $data->observaciones;
        }
        if (isset($data->fhir_id)) {
            $fields_to_update[] = '"FHIR_ID" = :fhir_id';
            $params[":fhir_id"] = $data->fhir_id;
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se proporcionaron campos para actualizar."
            ];
        }

        $sql = 'UPDATE "' . $this->table_name . '" SET ' . implode(", ", $fields_to_update) . ' WHERE "ID" = :id';
        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":id", $id);

        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }
        unset($val);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Antecedente patológico actualizado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar antecedente patológico: " . $e['message']
            ];
        }
    }
}
?>