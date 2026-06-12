<?php
class MedicationStatement
{
    private $conn;
    private $table_name = "ANTECEDENTES_FARMACOLOGICOS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoMedicationStatement($id_pcnte)
    {
        $sql = 'SELECT 
                    af.ID,
                    af.ID_PCNTE,
                    af.FHIR_ID,
                    af.CODIGO,
                    af.DESCRIPCION,
                    af.ESTADO,
                    af.OBSERVACIONES
                FROM "' . $this->table_name . '" af
                WHERE af.ID_PCNTE = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $statements = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $statements[] = [
                "id" => $row['ID'],
                "id_pcnte" => $row['ID_PCNTE'],
                "fhir_id" => $row['FHIR_ID'],
                "codigo" => $row['CODIGO'],
                "descripcion" => $row['DESCRIPCION'],
                "estado" => $row['ESTADO'],
                "observaciones" => $row['OBSERVACIONES']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($statements),
            "data" => $statements
        ];
    }

    /**
     * Registra un nuevo antecedente farmacológico (MedicationStatement).
     * 
     * @param object $data Objeto con los datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createMedicationStatement($data)
    {
        $sql = 'INSERT INTO "' . $this->table_name . '" (
                    "ID", "ID_PCNTE", "FHIR_ID", "CODIGO", 
                    "DESCRIPCION", "ESTADO", "OBSERVACIONES", 
                    "FECHA_INGRESO", "USUARIO_INGRESO"
                ) VALUES (
                    (SELECT COALESCE(MAX("ID"), 0) + 1 FROM "' . $this->table_name . '"),
                    :id_pcnte, :fhir_id, :codigo, 
                    :descripcion, :estado, :observaciones, 
                    SYSDATE, :usuario_ingreso
                )';

        $stid = oci_parse($this->conn, $sql);

        // Bind parameters
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        $fhir_id = !empty($data->fhir_id) ? $data->fhir_id : 'fhir-' . uniqid();
        $codigo = !empty($data->codigo) ? $data->codigo : 'GENERIC';
        $descripcion = !empty($data->descripcion) ? $data->descripcion : null;
        $estado = !empty($data->estado) ? $data->estado : 'active';
        $observaciones = !empty($data->observaciones) ? $data->observaciones : null;
        $usuario_ingreso = !empty($data->usuario_ingreso) ? $data->usuario_ingreso : 'API';

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":fhir_id", $fhir_id);
        oci_bind_by_name($stid, ":codigo", $codigo);
        oci_bind_by_name($stid, ":descripcion", $descripcion);
        oci_bind_by_name($stid, ":estado", $estado);
        oci_bind_by_name($stid, ":observaciones", $observaciones);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Antecedente farmacológico registrado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar antecedente farmacológico: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza un antecedente farmacológico existente por su ID.
     * 
     * @param int $id Identificador del antecedente.
     * @param object $data Objeto con los datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateMedicationStatement($id, $data)
    {
        $fields_to_update = [];
        $params = [];

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
                "message" => "Antecedente farmacológico actualizado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar antecedente farmacológico: " . $e['message']
            ];
        }
    }
}
?>
