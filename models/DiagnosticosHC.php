<?php
class DiagnosticosHC
{
    private $conn;
    private $table_name = "DIAGNOSTICOS_HC";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoDiagnosticoHC($id_pcnte, $cnsctvo_pcnte)
    {
        $sql = 'SELECT 
                    d.ID_PCNTE,   
                    d.CNSCTVO_PCNTE,   
                    d.FHIR_ID,   
                    d.CODIGO,   
                    d.CIE_10,   
                    d.CIE_11,   
                    d.DESCRIPCION,   
                    d.ESTADO,   
                    d.ESTADO_VERIFICACION,   
                    d.OBSERVACIONES,   
                    d.INDICADOR_DIAGNOSTICO,
                    d.TIPO_DIAGNOSTICO,
                    TO_CHAR(d.FECHA_INGRESO, \'YYYY-MM-DD HH24:MI:SS\') AS FECHA_INGRESO,   
                    d.USUARIO_INGRESO  
                FROM "DIAGNOSTICOS_HC" d  
                WHERE d.ID_PCNTE = :id 
                  AND d.CNSCTVO_PCNTE = :cons';

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

        $diagnosticos = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $diagnosticos[] = [
                "id_pcnte" => $row['ID_PCNTE'],
                "cnsctvo_pcnte" => $row['CNSCTVO_PCNTE'],
                "fhir_id" => $row['FHIR_ID'],
                "codigo" => $row['CODIGO'],
                "cie_10" => $row['CIE_10'],
                "cie_11" => $row['CIE_11'],
                "descripcion" => $row['DESCRIPCION'],
                "estado" => $row['ESTADO'],
                "estado_verificacion" => $row['ESTADO_VERIFICACION'],
                "observaciones" => $row['OBSERVACIONES'],
                "indicador_diagnostico" => $row['INDICADOR_DIAGNOSTICO'],
                "tipo_diagnostico" => $row['TIPO_DIAGNOSTICO'],
                "fecha_ingreso" => $row['FECHA_INGRESO'],
                "usuario_ingreso" => $row['USUARIO_INGRESO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($diagnosticos),
            "data" => $diagnosticos
        ];
    }

    public function GetInfoDiagnosticosPaciente($id_pcnte)
    {
        $sql = 'SELECT 
                    d.ID_PCNTE,   
                    d.CNSCTVO_PCNTE,   
                    d.FHIR_ID,   
                    d.CODIGO,   
                    d.CIE_10,   
                    d.CIE_11,   
                    d.DESCRIPCION,   
                    d.ESTADO,   
                    d.ESTADO_VERIFICACION,   
                    d.OBSERVACIONES,   
                    d.INDICADOR_DIAGNOSTICO,
                    d.TIPO_DIAGNOSTICO,
                    TO_CHAR(d.FECHA_INGRESO, \'YYYY-MM-DD HH24:MI:SS\') AS FECHA_INGRESO,   
                    d.USUARIO_INGRESO  
                FROM "DIAGNOSTICOS_HC" d  
                WHERE d.ID_PCNTE = :id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $diagnosticos = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $diagnosticos[] = [
                "id_pcnte" => $row['ID_PCNTE'],
                "cnsctvo_pcnte" => $row['CNSCTVO_PCNTE'],
                "fhir_id" => $row['FHIR_ID'],
                "codigo" => $row['CODIGO'],
                "cie_10" => $row['CIE_10'],
                "cie_11" => $row['CIE_11'],
                "descripcion" => $row['DESCRIPCION'],
                "estado" => $row['ESTADO'],
                "estado_verificacion" => $row['ESTADO_VERIFICACION'],
                "observaciones" => $row['OBSERVACIONES'],
                "indicador_diagnostico" => $row['INDICADOR_DIAGNOSTICO'],
                "tipo_diagnostico" => $row['TIPO_DIAGNOSTICO'],
                "fecha_ingreso" => $row['FECHA_INGRESO'],
                "usuario_ingreso" => $row['USUARIO_INGRESO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($diagnosticos),
            "data" => $diagnosticos
        ];
    }

    /**
     * Registra un nuevo diagnóstico de historia clínica.
     * 
     * @param object $data Datos de entrada.
     * @return array Resultado de la operación.
     */
    public function createDiagnosticoHC($data)
    {
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        $cnsctvo_pcnte = !empty($data->cnsctvo_pcnte) ? (int)$data->cnsctvo_pcnte : null;
        $codigo = !empty($data->codigo) ? $data->codigo : null;

        if (empty($id_pcnte) || empty($cnsctvo_pcnte) || empty($codigo)) {
            return [
                "status" => "error",
                "message" => "Los campos 'id_pcnte', 'cnsctvo_pcnte' y 'codigo' son requeridos."
            ];
        }

        $sql = 'INSERT INTO "DIAGNOSTICOS_HC" (
                    "ID_PCNTE", "CNSCTVO_PCNTE", "FHIR_ID", "CODIGO", "CIE_10", "CIE_11",
                    "DESCRIPCION", "ESTADO", "ESTADO_VERIFICACION", "OBSERVACIONES",
                    "INDICADOR_DIAGNOSTICO", "TIPO_DIAGNOSTICO",
                    "FECHA_INGRESO", "USUARIO_INGRESO"
                ) VALUES (
                    :id_pcnte, :cnsctvo_pcnte, :fhir_id, :codigo, :cie_10, :cie_11,
                    :descripcion, :estado, :estado_verificacion, :observaciones,
                    :indicador_diagnostico, :tipo_diagnostico,
                    SYSDATE, :usuario_ingreso
                )';

        $stid = oci_parse($this->conn, $sql);

        $fhir_id = !empty($data->fhir_id) ? $data->fhir_id : 'fhir-' . uniqid();
        $cie_10 = !empty($data->cie_10) ? $data->cie_10 : null;
        $cie_11 = !empty($data->cie_11) ? $data->cie_11 : null;
        $descripcion = !empty($data->descripcion) ? $data->descripcion : null;
        $estado = !empty($data->estado) ? $data->estado : 'active';
        $estado_verificacion = !empty($data->estado_verificacion) ? $data->estado_verificacion : 'confirmed';
        $observaciones = !empty($data->observaciones) ? $data->observaciones : null;
        $indicador_diagnostico = !empty($data->indicador_diagnostico) ? $data->indicador_diagnostico : null;
        $tipo_diagnostico = !empty($data->tipo_diagnostico) ? $data->tipo_diagnostico : null;
        $usuario_ingreso = !empty($data->usuario_ingreso) ? $data->usuario_ingreso : 'API';

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":fhir_id", $fhir_id);
        oci_bind_by_name($stid, ":codigo", $codigo);
        oci_bind_by_name($stid, ":cie_10", $cie_10);
        oci_bind_by_name($stid, ":cie_11", $cie_11);
        oci_bind_by_name($stid, ":descripcion", $descripcion);
        oci_bind_by_name($stid, ":estado", $estado);
        oci_bind_by_name($stid, ":estado_verificacion", $estado_verificacion);
        oci_bind_by_name($stid, ":observaciones", $observaciones);
        oci_bind_by_name($stid, ":indicador_diagnostico", $indicador_diagnostico);
        oci_bind_by_name($stid, ":tipo_diagnostico", $tipo_diagnostico);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Diagnóstico registrado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al registrar diagnóstico: " . $e['message']
            ];
        }
    }

    /**
     * Actualiza un diagnóstico existente.
     * 
     * @param string $id_pcnte
     * @param int $cnsctvo_pcnte
     * @param string $codigo
     * @param object $data Datos a modificar.
     * @return array Resultado de la operación.
     */
    public function updateDiagnosticoHC($id_pcnte, $cnsctvo_pcnte, $codigo, $data)
    {
        $fields_to_update = [];
        $params = [];

        if (isset($data->fhir_id)) {
            $fields_to_update[] = '"FHIR_ID" = :fhir_id';
            $params[":fhir_id"] = $data->fhir_id;
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
        if (isset($data->observaciones)) {
            $fields_to_update[] = '"OBSERVACIONES" = :observaciones';
            $params[":observaciones"] = $data->observaciones;
        }
        if (isset($data->indicador_diagnostico)) {
            $fields_to_update[] = '"INDICADOR_DIAGNOSTICO" = :indicador_diagnostico';
            $params[":indicador_diagnostico"] = $data->indicador_diagnostico;
        }
        if (isset($data->tipo_diagnostico)) {
            $fields_to_update[] = '"TIPO_DIAGNOSTICO" = :tipo_diagnostico';
            $params[":tipo_diagnostico"] = $data->tipo_diagnostico;
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se proporcionaron campos para actualizar."
            ];
        }

        $sql = 'UPDATE "DIAGNOSTICOS_HC" SET ' . implode(", ", $fields_to_update) . ' 
                WHERE "ID_PCNTE" = :id_pcnte AND "CNSCTVO_PCNTE" = :cnsctvo_pcnte AND "CODIGO" = :codigo';

        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":codigo", $codigo);

        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }
        unset($val);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "Diagnóstico actualizado exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar diagnóstico: " . $e['message']
            ];
        }
    }
}
?>
