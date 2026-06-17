<?php
class AllergyIntolerance
{
    private $conn;
    private $table_name = "ANTECEDENTES_ALERGICOS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoAllergyIntolerance($id_pcnte)
    {
        $sql = 'SELECT 
                    aa.ID,
                    aa.ID_PCNTE,
                    aa.FHIR_ID,
                    aa.TIPOALERGIA,
                    ta.NOMBRE AS TIPO_ALERGIA_NOMBRE,
                    aa.CODIGO,
                    aa.DESCRIPCION,
                    aa.ESTADO,
                    sa.DISPLAY AS ESTADO_CLINICO_DISPLAY,
                    aa.ESTADO_VERIFICACION,
                    sb.DISPLAY AS VERIFICACION_DISPLAY,
                    aa.OBSERVACIONES,
                    aa.CRITICIDAD
                FROM "' . $this->table_name . '" aa
                INNER JOIN "TIPOALERGIA" ta 
                    ON aa.TIPOALERGIA = ta.CODIGO
                INNER JOIN "STATUS" sa 
                    ON aa.ESTADO = sa.CODE 
                    AND sa.STATUS = \'AllergyIntoleranceClinicalStatusCodes\'
                INNER JOIN "STATUS" sb 
                    ON aa.ESTADO_VERIFICACION = sb.CODE 
                    AND sb.STATUS = \'verificationStatus\'
                WHERE aa.ID_PCNTE = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $allergies = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $allergies[] = [
                "id" => $row['ID'],
                "id_pcnte" => $row['ID_PCNTE'],
                "fhir_id" => $row['FHIR_ID'],
                "tipoalergia" => $row['TIPOALERGIA'],
                "tipo_alergia_nombre" => $row['TIPO_ALERGIA_NOMBRE'],
                "codigo" => $row['CODIGO'],
                "descripcion" => $row['DESCRIPCION'],
                "estado" => $row['ESTADO'],
                "estado_clinico_display" => $row['ESTADO_CLINICO_DISPLAY'],
                "estado_verificacion" => $row['ESTADO_VERIFICACION'],
                "verificacion_display" => $row['VERIFICACION_DISPLAY'],
                "observaciones" => $row['OBSERVACIONES'],
                "criticidad" => $row['CRITICIDAD']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($allergies),
            "data" => $allergies
        ];
    }

    /**
     * Registra un nuevo antecedente alérgico.
     * 
     * @param object $data Objeto con los datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createAllergyIntolerance($data)
    {
        $sql = 'INSERT INTO "' . $this->table_name . '" (
                    "ID", "ID_PCNTE", "FHIR_ID", "TIPOALERGIA", "CODIGO", 
                    "DESCRIPCION", "ESTADO", "ESTADO_VERIFICACION", "CRITICIDAD", "OBSERVACIONES", 
                    "FECHA_INGRESO", "USUARIO_INGRESO"
                ) VALUES (
                    (SELECT COALESCE(MAX("ID"), 0) + 1 FROM "' . $this->table_name . '"),
                    :id_pcnte, :fhir_id, :tipoalergia, :codigo, 
                    :descripcion, :estado, :estado_verificacion, :criticidad, :observaciones, 
                    SYSDATE, :usuario_ingreso
                )';

        $stid = oci_parse($this->conn, $sql);

        // Bind parameters
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        $fhir_id = !empty($data->fhir_id) ? $data->fhir_id : 'fhir-' . uniqid();
        $tipoalergia = !empty($data->tipoalergia) ? $data->tipoalergia : null;
        $codigo = !empty($data->codigo) ? $data->codigo : (!empty($tipoalergia) ? $tipoalergia : 'GENERIC');
        $descripcion = !empty($data->descripcion) ? $data->descripcion : null;
        $estado = !empty($data->estado) ? $data->estado : 'active';
        $estado_verificacion = !empty($data->estado_verificacion) ? $data->estado_verificacion : 'confirmed';
        $criticidad = !empty($data->criticidad) ? $data->criticidad : 'high';
        $observaciones = !empty($data->observaciones) ? $data->observaciones : null;
        $usuario_ingreso = !empty($data->usuario_ingreso) ? $data->usuario_ingreso : 'API';

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":fhir_id", $fhir_id);
        oci_bind_by_name($stid, ":tipoalergia", $tipoalergia);
        oci_bind_by_name($stid, ":codigo", $codigo);
        oci_bind_by_name($stid, ":descripcion", $descripcion);
        oci_bind_by_name($stid, ":estado", $estado);
        oci_bind_by_name($stid, ":estado_verificacion", $estado_verificacion);
        oci_bind_by_name($stid, ":criticidad", $criticidad);
        oci_bind_by_name($stid, ":observaciones", $observaciones);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Antecedente alérgico registrado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar antecedente alérgico: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza un antecedente alérgico existente por su ID.
     * 
     * @param int $id Identificador del antecedente.
     * @param object $data Objeto con los datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateAllergyIntolerance($id, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->tipoalergia)) {
            $fields_to_update[] = '"TIPOALERGIA" = :tipoalergia';
            $params[":tipoalergia"] = $data->tipoalergia;
        }
        if (isset($data->codigo)) {
            $fields_to_update[] = '"CODIGO" = :codigo';
            $params[":codigo"] = $data->codigo;
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
        if (isset($data->criticidad)) {
            $fields_to_update[] = '"CRITICIDAD" = :criticidad';
            $params[":criticidad"] = $data->criticidad;
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
                "message" => "Antecedente alérgico actualizado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar antecedente alérgico: " . $e['message']
            ];
        }
    }
}
?>
