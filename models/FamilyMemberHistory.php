<?php
class FamilyMemberHistory
{
    private $conn;
    private $table_name = "ANTECEDENTES_FAMILIARES";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoFamilyMemberHistory($id_pcnte)
    {
        $sql = 'SELECT 
                    af.ID,
                    af.ID_PCNTE,
                    af.FHIR_ID,
                    af.PARENTESCO,
                    p.NOMBRE AS PARENTESCO_NOMBRE,
                    af.CODIGO,
                    af.CIE_10,
                    af.CIE_11,
                    af.DESCRIPCION,
                    af.ESTADO,
                    af.EDAD_DIAGNOSTICO,
                    af.OBSERVACIONES
                FROM "' . $this->table_name . '" af
                INNER JOIN "PARENTESCO" p 
                    ON af.PARENTESCO = p.CODIGO
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

        $history = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $history[] = [
                "id" => $row['ID'],
                "id_pcnte" => $row['ID_PCNTE'],
                "fhir_id" => $row['FHIR_ID'],
                "parentesco" => $row['PARENTESCO'],
                "parentesco_nombre" => $row['PARENTESCO_NOMBRE'],
                "codigo" => $row['CODIGO'],
                "cie_10" => $row['CIE_10'],
                "cie_11" => $row['CIE_11'],
                "descripcion" => $row['DESCRIPCION'],
                "estado" => $row['ESTADO'],
                "edad_diagnostico" => $row['EDAD_DIAGNOSTICO'],
                "observaciones" => $row['OBSERVACIONES']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($history),
            "data" => $history
        ];
    }

    /**
     * Registra un nuevo antecedente familiar (FamilyMemberHistory).
     * 
     * @param object $data Objeto con los datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createFamilyMemberHistory($data)
    {
        $sql = 'INSERT INTO "' . $this->table_name . '" (
                    "ID", "ID_PCNTE", "FHIR_ID", "PARENTESCO", "CODIGO", "CIE_10", "CIE_11", 
                    "DESCRIPCION", "ESTADO", "EDAD_DIAGNOSTICO", "OBSERVACIONES", 
                    "FECHA_INGRESO", "USUARIO_INGRESO"
                ) VALUES (
                    (SELECT COALESCE(MAX("ID"), 0) + 1 FROM "' . $this->table_name . '"),
                    :id_pcnte, :fhir_id, :parentesco, :codigo, :cie_10, :cie_11, 
                    :descripcion, :estado, :edad_diagnostico, :observaciones, 
                    SYSDATE, :usuario_ingreso
                )';

        $stid = oci_parse($this->conn, $sql);

        // Bind parameters
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        $fhir_id = !empty($data->fhir_id) ? $data->fhir_id : 'fhir-' . uniqid();
        $parentesco = !empty($data->parentesco) ? $data->parentesco : null;
        $cie_10 = !empty($data->cie_10) ? $data->cie_10 : null;
        $cie_11 = !empty($data->cie_11) ? $data->cie_11 : null;
        $codigo = !empty($data->codigo) ? $data->codigo : (!empty($cie_10) ? $cie_10 : 'GENERIC');
        $descripcion = !empty($data->descripcion) ? $data->descripcion : null;
        $estado = !empty($data->estado) ? $data->estado : 'completed';
        $edad_diagnostico = isset($data->edad_diagnostico) ? $data->edad_diagnostico : null;
        $observaciones = !empty($data->observaciones) ? $data->observaciones : null;
        $usuario_ingreso = !empty($data->usuario_ingreso) ? $data->usuario_ingreso : 'API';

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":fhir_id", $fhir_id);
        oci_bind_by_name($stid, ":parentesco", $parentesco);
        oci_bind_by_name($stid, ":codigo", $codigo);
        oci_bind_by_name($stid, ":cie_10", $cie_10);
        oci_bind_by_name($stid, ":cie_11", $cie_11);
        oci_bind_by_name($stid, ":descripcion", $descripcion);
        oci_bind_by_name($stid, ":estado", $estado);
        oci_bind_by_name($stid, ":edad_diagnostico", $edad_diagnostico);
        oci_bind_by_name($stid, ":observaciones", $observaciones);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Antecedente familiar registrado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar antecedente familiar: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza un antecedente familiar existente por su ID.
     * 
     * @param int $id Identificador del antecedente.
     * @param object $data Objeto con los datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateFamilyMemberHistory($id, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->parentesco)) {
            $fields_to_update[] = '"PARENTESCO" = :parentesco';
            $params[":parentesco"] = $data->parentesco;
        }
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
                "message" => "Antecedente familiar actualizado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar antecedente familiar: " . $e['message']
            ];
        }
    }
}
?>
