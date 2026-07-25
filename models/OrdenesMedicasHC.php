<?php
class OrdenesMedicasHC
{
    private $conn;
    private $table_name = "ordenes_medicas_hc";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Get medical orders for a specific patient and encounter consecutive.
     * @param string $id_pcnte
     * @param int $cnsctvo_pcnte
     * @return array
     */
    public function GetInfoOrdenesMedicasHC($id_pcnte, $cnsctvo_pcnte)
    {
        $sql = "SELECT om.id,   
                       om.id_pcnte,   
                       om.cnsctvo_pcnte,   
                       om.id_mdco,   
                       TO_CHAR(om.fecha_prescripcion, 'YYYY-MM-DD HH24:MI:SS') AS fecha_prescripcion,   
                       om.categoria_fhir,   
                       om.tipo_tecnologia,   
                       om.tipo_tecnologia_descripcion,   
                       om.tipo_examen,   
                       om.codigo,   
                       om.nombre,   
                       om.usuario_ingreso,   
                       TO_CHAR(om.fecha_ingreso, 'YYYY-MM-DD HH24:MI:SS') AS fecha_ingreso,   
                       om.cantidad,   
                       om.observaciones,
                       om.id_encuentro,
                       om.estado
                  FROM ordenes_medicas_hc om  
                 WHERE om.id_pcnte = :s_id 
                   AND om.cnsctvo_pcnte = :l_cons";

        $stid = oci_parse($this->conn, $sql);
        
        $id_pcnte = substr($id_pcnte, 0, 20);
        $cnsctvo_pcnte = (int)$cnsctvo_pcnte;

        oci_bind_by_name($stid, ":s_id", $id_pcnte);
        oci_bind_by_name($stid, ":l_cons", $cnsctvo_pcnte);

        if (oci_execute($stid)) {
            $results = [];
            while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $results[] = [
                    "id" => isset($row['ID']) ? (int)$row['ID'] : null,
                    "id_pcnte" => $row['ID_PCNTE'],
                    "cnsctvo_pcnte" => (int)$row['CNSCTVO_PCNTE'],
                    "id_mdco" => $row['ID_MDCO'],
                    "fecha_prescripcion" => $row['FECHA_PRESCRIPCION'],
                    "categoria_fhir" => $row['CATEGORIA_FHIR'],
                    "tipo_tecnologia" => $row['TIPO_TECNOLOGIA'],
                    "tipo_tecnologia_descripcion" => $row['TIPO_TECNOLOGIA_DESCRIPCION'],
                    "tipo_examen" => $row['TIPO_EXAMEN'],
                    "codigo" => $row['CODIGO'],
                    "nombre" => $row['NOMBRE'],
                    "usuario_ingreso" => $row['USUARIO_INGRESO'],
                    "fecha_ingreso" => $row['FECHA_INGRESO'],
                    "cantidad" => isset($row['CANTIDAD']) ? (int)$row['CANTIDAD'] : null,
                    "observaciones" => $row['OBSERVACIONES'],
                    "id_encuentro" => $row['ID_ENCUENTRO'] ?? null,
                    "estado" => $row['ESTADO'] ?? null
                ];
            }
            oci_free_statement($stid);
            return [
                "status" => "success",
                "data" => $results
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al ejecutar consulta: " . $e['message']
            ];
        }
    }

    /**
     * Create a new OrdenesMedicasHC record.
     * @param object|array $data
     * @return array
     */
    public function createOrdenesMedicasHC($data)
    {
        $id_pcnte = !empty($data->id_pcnte) ? substr($data->id_pcnte, 0, 20) : (!empty($data->ID_PCNTE) ? substr($data->ID_PCNTE, 0, 20) : null);
        $cnsctvo_pcnte = !empty($data->cnsctvo_pcnte) ? (int)$data->cnsctvo_pcnte : (!empty($data->CNSCTVO_PCNTE) ? (int)$data->CNSCTVO_PCNTE : null);

        if (empty($id_pcnte) || empty($cnsctvo_pcnte)) {
            return [
                "status" => "error",
                "message" => "Los campos 'id_pcnte' y 'cnsctvo_pcnte' son requeridos."
            ];
        }

        // Generar ID único incremental si no se especifica
        $id = !empty($data->id) ? (int)$data->id : (!empty($data->ID) ? (int)$data->ID : null);
        if (empty($id)) {
            $sql_id = 'SELECT COALESCE(MAX(id), 0) + 1 AS MAX_ID FROM ordenes_medicas_hc';
            $stid_id = oci_parse($this->conn, $sql_id);
            oci_execute($stid_id);
            $row_id = oci_fetch_array($stid_id, OCI_ASSOC);
            $id = isset($row_id['MAX_ID']) ? (int)$row_id['MAX_ID'] : 1;
            oci_free_statement($stid_id);
        }

        $sql = "INSERT INTO ordenes_medicas_hc (
                    id, id_pcnte, cnsctvo_pcnte, id_mdco, fecha_prescripcion,
                    categoria_fhir, tipo_tecnologia, tipo_tecnologia_descripcion,
                    tipo_examen, codigo, nombre,
                    usuario_ingreso, fecha_ingreso, cantidad, observaciones,
                    id_encuentro, estado
                ) VALUES (
                    :id, :id_pcnte, :cnsctvo_pcnte, :id_mdco,
                    TO_DATE(:fecha_prescripcion, 'YYYY-MM-DD HH24:MI:SS'),
                    :categoria_fhir, :tipo_tecnologia, :tipo_tecnologia_descripcion,
                    :tipo_examen, :codigo, :nombre,
                    :usuario_ingreso, SYSDATE, :cantidad, :observaciones,
                    :id_encuentro, :estado
                )";

        $stid = oci_parse($this->conn, $sql);

        $id_mdco = !empty($data->id_mdco) ? substr($data->id_mdco, 0, 20) : (!empty($data->ID_MDCO) ? substr($data->ID_MDCO, 0, 20) : null);
        $fecha_prescripcion = !empty($data->fecha_prescripcion) ? $data->fecha_prescripcion : (!empty($data->FECHA_PRESCRIPCION) ? $data->FECHA_PRESCRIPCION : date('Y-m-d H:i:s'));
        $categoria_fhir = !empty($data->categoria_fhir) ? substr($data->categoria_fhir, 0, 10) : (!empty($data->CATEGORIA_FHIR) ? substr($data->CATEGORIA_FHIR, 0, 10) : null);
        $tipo_tecnologia = !empty($data->tipo_tecnologia) ? substr($data->tipo_tecnologia, 0, 5) : (!empty($data->TIPO_TECNOLOGIA) ? substr($data->TIPO_TECNOLOGIA, 0, 5) : null);
        $tipo_tecnologia_descripcion = !empty($data->tipo_tecnologia_descripcion) ? substr($data->tipo_tecnologia_descripcion, 0, 50) : (!empty($data->TIPO_TECNOLOGIA_DESCRIPCION) ? substr($data->TIPO_TECNOLOGIA_DESCRIPCION, 0, 50) : null);
        $tipo_examen = !empty($data->tipo_examen) ? substr($data->tipo_examen, 0, 30) : (!empty($data->TIPO_EXAMEN) ? substr($data->TIPO_EXAMEN, 0, 30) : null);
        $codigo = !empty($data->codigo) ? substr($data->codigo, 0, 30) : (!empty($data->CODIGO) ? substr($data->CODIGO, 0, 30) : null);
        $nombre = !empty($data->nombre) ? substr($data->nombre, 0, 100) : (!empty($data->NOMBRE) ? substr($data->NOMBRE, 0, 100) : null);
        $cantidad = isset($data->cantidad) ? (int)$data->cantidad : (isset($data->CANTIDAD) ? (int)$data->CANTIDAD : null);
        $observaciones = !empty($data->observaciones) ? substr($data->observaciones, 0, 100) : (!empty($data->OBSERVACIONES) ? substr($data->OBSERVACIONES, 0, 100) : null);
        $id_encuentro = !empty($data->id_encuentro) ? substr($data->id_encuentro, 0, 50) : (!empty($data->ID_ENCUENTRO) ? substr($data->ID_ENCUENTRO, 0, 50) : null);
        $estado = !empty($data->estado) ? substr($data->estado, 0, 20) : (!empty($data->ESTADO) ? substr($data->ESTADO, 0, 20) : 'active');
        
        $usuario_ingreso = !empty($data->usuario_ingreso) ? substr($data->usuario_ingreso, 0, 30) : (!empty($data->USUARIO_INGRESO) ? substr($data->USUARIO_INGRESO, 0, 30) : null);
        if (empty($usuario_ingreso)) {
            $usuario_ingreso = 'ADMIN';
        }

        oci_bind_by_name($stid, ":id", $id);
        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":id_mdco", $id_mdco);
        oci_bind_by_name($stid, ":fecha_prescripcion", $fecha_prescripcion);
        oci_bind_by_name($stid, ":categoria_fhir", $categoria_fhir);
        oci_bind_by_name($stid, ":tipo_tecnologia", $tipo_tecnologia);
        oci_bind_by_name($stid, ":tipo_tecnologia_descripcion", $tipo_tecnologia_descripcion);
        oci_bind_by_name($stid, ":tipo_examen", $tipo_examen);
        oci_bind_by_name($stid, ":codigo", $codigo);
        oci_bind_by_name($stid, ":nombre", $nombre);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);
        oci_bind_by_name($stid, ":cantidad", $cantidad);
        oci_bind_by_name($stid, ":observaciones", $observaciones);
        oci_bind_by_name($stid, ":id_encuentro", $id_encuentro);
        oci_bind_by_name($stid, ":estado", $estado);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "OrdenMedicaHC creada exitosamente.",
                "id" => $id
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al insertar en la base de datos: " . $e['message']
            ];
        }
    }

    /**
     * Update an existing OrdenesMedicasHC record.
     * @param object|array $data
     * @return array
     */
    public function updateOrdenesMedicasHC($data)
    {
        $id = !empty($data->id) ? (int)$data->id : (!empty($data->ID) ? (int)$data->ID : null);
        if (empty($id)) {
            return [
                "status" => "error",
                "message" => "El campo 'id' es requerido para actualizar."
            ];
        }

        $fields_to_update = [];
        $params = [];

        if (isset($data->id_pcnte) || isset($data->ID_PCNTE)) {
            $val = isset($data->id_pcnte) ? $data->id_pcnte : $data->ID_PCNTE;
            $fields_to_update[] = "id_pcnte = :id_pcnte";
            $params[":id_pcnte"] = substr($val, 0, 20);
        }
        if (isset($data->cnsctvo_pcnte) || isset($data->CNSCTVO_PCNTE)) {
            $val = isset($data->cnsctvo_pcnte) ? $data->cnsctvo_pcnte : $data->CNSCTVO_PCNTE;
            $fields_to_update[] = "cnsctvo_pcnte = :cnsctvo_pcnte";
            $params[":cnsctvo_pcnte"] = (int)$val;
        }
        if (isset($data->id_mdco) || isset($data->ID_MDCO)) {
            $val = isset($data->id_mdco) ? $data->id_mdco : $data->ID_MDCO;
            $fields_to_update[] = "id_mdco = :id_mdco";
            $params[":id_mdco"] = substr($val, 0, 20);
        }
        if (isset($data->fecha_prescripcion) || isset($data->FECHA_PRESCRIPCION)) {
            $val = isset($data->fecha_prescripcion) ? $data->fecha_prescripcion : $data->FECHA_PRESCRIPCION;
            $fields_to_update[] = "fecha_prescripcion = TO_DATE(:fecha_prescripcion, 'YYYY-MM-DD HH24:MI:SS')";
            $params[":fecha_prescripcion"] = $val;
        }
        if (isset($data->categoria_fhir) || isset($data->CATEGORIA_FHIR)) {
            $val = isset($data->categoria_fhir) ? $data->categoria_fhir : $data->CATEGORIA_FHIR;
            $fields_to_update[] = "categoria_fhir = :categoria_fhir";
            $params[":categoria_fhir"] = substr($val, 0, 10);
        }
        if (isset($data->tipo_tecnologia) || isset($data->TIPO_TECNOLOGIA)) {
            $val = isset($data->tipo_tecnologia) ? $data->tipo_tecnologia : $data->TIPO_TECNOLOGIA;
            $fields_to_update[] = "tipo_tecnologia = :tipo_tecnologia";
            $params[":tipo_tecnologia"] = substr($val, 0, 5);
        }
        if (isset($data->tipo_tecnologia_descripcion) || isset($data->TIPO_TECNOLOGIA_DESCRIPCION)) {
            $val = isset($data->tipo_tecnologia_descripcion) ? $data->tipo_tecnologia_descripcion : $data->TIPO_TECNOLOGIA_DESCRIPCION;
            $fields_to_update[] = "tipo_tecnologia_descripcion = :tipo_tecnologia_descripcion";
            $params[":tipo_tecnologia_descripcion"] = substr($val, 0, 50);
        }
        if (isset($data->tipo_examen) || isset($data->TIPO_EXAMEN)) {
            $val = isset($data->tipo_examen) ? $data->tipo_examen : $data->TIPO_EXAMEN;
            $fields_to_update[] = "tipo_examen = :tipo_examen";
            $params[":tipo_examen"] = substr($val, 0, 30);
        }
        if (isset($data->codigo) || isset($data->CODIGO)) {
            $val = isset($data->codigo) ? $data->codigo : $data->CODIGO;
            $fields_to_update[] = "codigo = :codigo";
            $params[":codigo"] = substr($val, 0, 30);
        }
        if (isset($data->nombre) || isset($data->NOMBRE)) {
            $val = isset($data->nombre) ? $data->nombre : $data->NOMBRE;
            $fields_to_update[] = "nombre = :nombre";
            $params[":nombre"] = substr($val, 0, 100);
        }

        if (isset($data->cantidad) || isset($data->CANTIDAD)) {
            $val = isset($data->cantidad) ? $data->cantidad : $data->CANTIDAD;
            $fields_to_update[] = "cantidad = :cantidad";
            $params[":cantidad"] = (int)$val;
        }
        if (isset($data->observaciones) || isset($data->OBSERVACIONES)) {
            $val = isset($data->observaciones) ? $data->observaciones : $data->OBSERVACIONES;
            $fields_to_update[] = "observaciones = :observaciones";
            $params[":observaciones"] = substr($val, 0, 100);
        }
        if (isset($data->id_encuentro) || isset($data->ID_ENCUENTRO)) {
            $val = isset($data->id_encuentro) ? $data->id_encuentro : $data->ID_ENCUENTRO;
            $fields_to_update[] = "id_encuentro = :id_encuentro";
            $params[":id_encuentro"] = substr($val, 0, 50);
        }
        if (isset($data->estado) || isset($data->ESTADO)) {
            $val = isset($data->estado) ? $data->estado : $data->ESTADO;
            $fields_to_update[] = "estado = :estado";
            $params[":estado"] = substr($val, 0, 20);
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se enviaron campos válidos para actualizar."
            ];
        }

        $sql = "UPDATE ordenes_medicas_hc SET " . implode(", ", $fields_to_update) . " WHERE id = :id";
        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":id", $id);
        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "OrdenMedicaHC actualizada exitosamente."
            ];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al actualizar la base de datos: " . $e['message']
            ];
        }
    }
}
?>
