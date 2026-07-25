<?php
class FormulacionHC
{
    private $conn;
    private $table_name = "formulacion_hc";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Get formulations for a specific patient and encounter.
     * @param string $id_pcnte
     * @param int $cnsctvo_pcnte
     * @return array
     */
    public function GetInfoFormulacionHC($id_pcnte, $cnsctvo_pcnte)
    {
        $sql = "SELECT f.id_pcnte,
                       f.cnsctvo_pcnte,
                       f.formula,
                       f.cnsctvo_formula,
                       f.id_mdco,
                       TO_CHAR(f.fecha_prescripcion, 'YYYY-MM-DD HH24:MI:SS') AS fecha_prescripcion,
                       f.codigo_medicamento,
                       f.descripcion_medicamento,
                       f.codigo_dci,
                       f.descripcion_dci,
                       f.dosis,
                       f.um_dosis,
                       f.um_dosis_descripcion,
                       f.codigo_via,
                       f.via,
                       f.duracion,
                       f.um_duracion,
                       f.um_duracion_descripcion,
                       f.frecuencia,
                       f.um_frecuencia,
                       f.um_frecuencia_descripcion,
                       f.tipo_tecnologia,
                       f.tipo_tecnologia_descripcion,
                       f.posologia,
                       f.usuario_ingreso,
                       TO_CHAR(f.fecha_ingreso, 'YYYY-MM-DD HH24:MI:SS') AS fecha_ingreso,
                       f.instruccion,
                       f.codigo_instruccion_mipres,
                       f.instruccion_mipres,
                       f.codigo_forma,
                       f.descripcion_forma,
                       f.ium_primer_nivel,
                       f.unidad_dispensacion,
                       f.desc_unidad_dispensacion,
                       f.cantidad_total,
                       f.cantidad_ciclos,
                       f.ium
                  FROM formulacion_hc f
                 WHERE f.id_pcnte = :s_id 
                   AND f.cnsctvo_pcnte = :l_cons";

        $stid = oci_parse($this->conn, $sql);
        
        $id_pcnte = substr($id_pcnte, 0, 20);
        $cnsctvo_pcnte = (int)$cnsctvo_pcnte;

        oci_bind_by_name($stid, ":s_id", $id_pcnte);
        oci_bind_by_name($stid, ":l_cons", $cnsctvo_pcnte);

        if (oci_execute($stid)) {
            $results = [];
            while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $results[] = [
                    "id_pcnte" => $row['ID_PCNTE'],
                    "cnsctvo_pcnte" => (int)$row['CNSCTVO_PCNTE'],
                    "formula" => (int)$row['FORMULA'],
                    "cnsctvo_formula" => (int)$row['CNSCTVO_FORMULA'],
                    "id_mdco" => $row['ID_MDCO'],
                    "fecha_prescripcion" => $row['FECHA_PRESCRIPCION'],
                    "codigo_medicamento" => $row['CODIGO_MEDICAMENTO'],
                    "descripcion_medicamento" => $row['DESCRIPCION_MEDICAMENTO'],
                    "codigo_dci" => $row['CODIGO_DCI'],
                    "descripcion_dci" => $row['DESCRIPCION_DCI'],
                    "dosis" => (float)$row['DOSIS'],
                    "um_dosis" => $row['UM_DOSIS'],
                    "um_dosis_descripcion" => $row['UM_DOSIS_DESCRIPCION'],
                    "codigo_via" => $row['CODIGO_VIA'],
                    "via" => $row['VIA'],
                    "duracion" => (int)$row['DURACION'],
                    "um_duracion" => $row['UM_DURACION'],
                    "um_duracion_descripcion" => $row['UM_DURACION_DESCRIPCION'],
                    "frecuencia" => (int)$row['FRECUENCIA'],
                    "um_frecuencia" => $row['UM_FRECUENCIA'],
                    "um_frecuencia_descripcion" => $row['UM_FRECUENCIA_DESCRIPCION'],
                    "tipo_tecnologia" => $row['TIPO_TECNOLOGIA'],
                    "tipo_tecnologia_descripcion" => $row['TIPO_TECNOLOGIA_DESCRIPCION'],
                    "posologia" => $row['POSOLOGIA'],
                    "usuario_ingreso" => $row['USUARIO_INGRESO'],
                    "fecha_ingreso" => $row['FECHA_INGRESO'],
                    "instruccion" => $row['INSTRUCCION'],
                    "codigo_instruccion_mipres" => $row['CODIGO_INSTRUCCION_MIPRES'],
                    "instruccion_mipres" => $row['INSTRUCCION_MIPRES'],
                    "codigo_forma" => $row['CODIGO_FORMA'],
                    "descripcion_forma" => $row['DESCRIPCION_FORMA'],
                    "ium_primer_nivel" => $row['IUM_PRIMER_NIVEL'] ?? null,
                    "unidad_dispensacion" => $row['UNIDAD_DISPENSACION'] ?? null,
                    "desc_unidad_dispensacion" => $row['DESC_UNIDAD_DISPENSACION'] ?? null,
                    "cantidad_total" => isset($row['CANTIDAD_TOTAL']) ? (float)$row['CANTIDAD_TOTAL'] : null,
                    "cantidad_ciclos" => isset($row['CANTIDAD_CICLOS']) ? (int)$row['CANTIDAD_CICLOS'] : null,
                    "ium" => $row['IUM'] ?? null
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
     * Create a new FormulationHC record.
     * @param object|array $data
     * @return array
     */
    public function createFormulacionHC($data)
    {
        $id_pcnte = !empty($data->id_pcnte) ? substr($data->id_pcnte, 0, 20) : null;
        $cnsctvo_pcnte = !empty($data->cnsctvo_pcnte) ? (int)$data->cnsctvo_pcnte : null;
        
        if (empty($id_pcnte) || empty($cnsctvo_pcnte)) {
            return [
                "status" => "error",
                "message" => "Los campos 'id_pcnte' y 'cnsctvo_pcnte' son requeridos."
            ];
        }

        // Generar formula si no viene especificada
        $formula = !empty($data->formula) ? (int)$data->formula : null;
        if (empty($formula)) {
            $sql_frm = 'SELECT COALESCE(MAX(formula), 0) + 1 AS MAX_FRM FROM formulacion_hc WHERE id_pcnte = :id AND cnsctvo_pcnte = :ev';
            $stid_frm = oci_parse($this->conn, $sql_frm);
            oci_bind_by_name($stid_frm, ":id", $id_pcnte);
            oci_bind_by_name($stid_frm, ":ev", $cnsctvo_pcnte);
            oci_execute($stid_frm);
            $row_frm = oci_fetch_array($stid_frm, OCI_ASSOC);
            $formula = isset($row_frm['MAX_FRM']) ? (int)$row_frm['MAX_FRM'] : 1;
            oci_free_statement($stid_frm);
        }

        // Generar cnsctvo_formula si no se especifica
        $cnsctvo_formula = !empty($data->cnsctvo_formula) ? (int)$data->cnsctvo_formula : null;
        if (empty($cnsctvo_formula)) {
            $sql_cns = 'SELECT COALESCE(MAX(cnsctvo_formula), 0) + 1 AS MAX_CNS FROM formulacion_hc WHERE id_pcnte = :id AND cnsctvo_pcnte = :ev AND formula = :frm';
            $stid_cns = oci_parse($this->conn, $sql_cns);
            oci_bind_by_name($stid_cns, ":id", $id_pcnte);
            oci_bind_by_name($stid_cns, ":ev", $cnsctvo_pcnte);
            oci_bind_by_name($stid_cns, ":frm", $formula);
            oci_execute($stid_cns);
            $row_cns = oci_fetch_array($stid_cns, OCI_ASSOC);
            $cnsctvo_formula = isset($row_cns['MAX_CNS']) ? (int)$row_cns['MAX_CNS'] : 1;
            oci_free_statement($stid_cns);
        }

        $sql = "INSERT INTO formulacion_hc (
                    id_pcnte, cnsctvo_pcnte, formula, cnsctvo_formula, id_mdco,
                    fecha_prescripcion, codigo_medicamento, descripcion_medicamento,
                    codigo_dci, descripcion_dci, dosis, um_dosis,
                    um_dosis_descripcion, codigo_via, via, duracion,
                    um_duracion, um_duracion_descripcion, frecuencia, um_frecuencia,
                    um_frecuencia_descripcion, tipo_tecnologia, tipo_tecnologia_descripcion,
                    posologia, usuario_ingreso, fecha_ingreso,
                    instruccion, codigo_instruccion_mipres, instruccion_mipres,
                    codigo_forma, descripcion_forma, ium_primer_nivel,
                    unidad_dispensacion, desc_unidad_dispensacion, cantidad_total,
                    cantidad_ciclos, ium
                ) VALUES (
                    :id_pcnte, :cnsctvo_pcnte, :formula, :cnsctvo_formula, :id_mdco,
                    TO_DATE(:fecha_prescripcion, 'YYYY-MM-DD HH24:MI:SS'), :codigo_medicamento, :descripcion_medicamento,
                    :codigo_dci, :descripcion_dci, :dosis, :um_dosis,
                    :um_dosis_descripcion, :codigo_via, :via, :duracion,
                    :um_duracion, :um_duracion_descripcion, :frecuencia, :um_frecuencia,
                    :um_frecuencia_descripcion, :tipo_tecnologia, :tipo_tecnologia_descripcion,
                    :posologia, :usuario_ingreso, SYSDATE,
                    :instruccion, :codigo_instruccion_mipres, :instruccion_mipres,
                    :codigo_forma, :descripcion_forma, :ium_primer_nivel,
                    :unidad_dispensacion, :desc_dispensacion, :cantidad_total,
                    :cantidad_ciclos, :ium
                )";

        $stid = oci_parse($this->conn, $sql);

        $id_mdco = !empty($data->id_mdco) ? substr($data->id_mdco, 0, 20) : null;
        $fecha_prescripcion = !empty($data->fecha_prescripcion) ? $data->fecha_prescripcion : date('Y-m-d H:i:s');
        $codigo_medicamento = !empty($data->codigo_medicamento) ? substr($data->codigo_medicamento, 0, 30) : null;
        $descripcion_medicamento = !empty($data->descripcion_medicamento) ? substr($data->descripcion_medicamento, 0, 1000) : null;
        $codigo_dci = !empty($data->codigo_dci) ? substr($data->codigo_dci, 0, 20) : null;
        $descripcion_dci = !empty($data->descripcion_dci) ? substr($data->descripcion_dci, 0, 300) : null;
        $dosis = !empty($data->dosis) ? (float)$data->dosis : 0.0;
        $um_dosis = !empty($data->um_dosis) ? substr($data->um_dosis, 0, 5) : null;
        $um_dosis_descripcion = !empty($data->um_dosis_descripcion) ? substr($data->um_dosis_descripcion, 0, 50) : null;
        $codigo_via = !empty($data->codigo_via) ? substr($data->codigo_via, 0, 5) : null;
        $via = !empty($data->via) ? substr($data->via, 0, 50) : null;
        $duracion = !empty($data->duracion) ? (int)$data->duracion : 0;
        $um_duracion = !empty($data->um_duracion) ? substr($data->um_duracion, 0, 5) : null;
        $um_duracion_descripcion = !empty($data->um_duracion_descripcion) ? substr($data->um_duracion_descripcion, 0, 50) : null;
        $frecuencia = !empty($data->frecuencia) ? (int)$data->frecuencia : 0;
        $um_frecuencia = !empty($data->um_frecuencia) ? substr($data->um_frecuencia, 0, 5) : null;
        $um_frecuencia_descripcion = !empty($data->um_frecuencia_descripcion) ? substr($data->um_frecuencia_descripcion, 0, 50) : null;
        $tipo_tecnologia = !empty($data->tipo_tecnologia) ? substr($data->tipo_tecnologia, 0, 5) : null;
        $tipo_tecnologia_descripcion = !empty($data->tipo_tecnologia_descripcion) ? substr($data->tipo_tecnologia_descripcion, 0, 50) : null;
        $posologia = !empty($data->posologia) ? substr($data->posologia, 0, 100) : null;
        $usuario_ingreso = !empty($data->usuario_ingreso) ? substr($data->usuario_ingreso, 0, 30) : null;
        if (empty($usuario_ingreso) && !empty($data->USUARIO_INGRESO)) {
            $usuario_ingreso = substr($data->USUARIO_INGRESO, 0, 30);
        }
        if (empty($usuario_ingreso)) {
            $usuario_ingreso = 'ADMIN';
        }

        $instruccion = !empty($data->instruccion) ? substr($data->instruccion, 0, 500) : null;
        $codigo_instruccion_mipres = !empty($data->codigo_instruccion_mipres) ? substr($data->codigo_instruccion_mipres, 0, 10) : null;
        $instruccion_mipres = !empty($data->instruccion_mipres) ? substr($data->instruccion_mipres, 0, 500) : null;
        $codigo_forma = !empty($data->codigo_forma) ? substr($data->codigo_forma, 0, 10) : null;
        $descripcion_forma = !empty($data->descripcion_forma) ? substr($data->descripcion_forma, 0, 100) : null;
        $ium_primer_nivel = !empty($data->ium_primer_nivel) ? substr($data->ium_primer_nivel, 0, 30) : null;
        $ium = !empty($data->ium) ? substr($data->ium, 0, 30) : null;
        
        $unidad_dispensacion = !empty($data->unidad_dispensacion) ? substr($data->unidad_dispensacion, 0, 10) : null;
        $descripcion_unidad_dispensacion = !empty($data->descripcion_unidad_dispensacion) ? substr($data->descripcion_unidad_dispensacion, 0, 100) : null;
        if (empty($descripcion_unidad_dispensacion) && !empty($data->desc_unidad_dispensacion)) {
            $descripcion_unidad_dispensacion = substr($data->desc_unidad_dispensacion, 0, 100);
        }
        $cantidad_total = isset($data->cantidad_total) && $data->cantidad_total !== '' ? (float)$data->cantidad_total : null;
        $cantidad_ciclos = isset($data->cantidad_ciclos) && $data->cantidad_ciclos !== '' ? (int)$data->cantidad_ciclos : null;

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":formula", $formula);
        oci_bind_by_name($stid, ":cnsctvo_formula", $cnsctvo_formula);
        oci_bind_by_name($stid, ":id_mdco", $id_mdco);
        oci_bind_by_name($stid, ":fecha_prescripcion", $fecha_prescripcion);
        oci_bind_by_name($stid, ":codigo_medicamento", $codigo_medicamento);
        oci_bind_by_name($stid, ":descripcion_medicamento", $descripcion_medicamento);
        oci_bind_by_name($stid, ":codigo_dci", $codigo_dci);
        oci_bind_by_name($stid, ":descripcion_dci", $descripcion_dci);
        oci_bind_by_name($stid, ":dosis", $dosis);
        oci_bind_by_name($stid, ":um_dosis", $um_dosis);
        oci_bind_by_name($stid, ":um_dosis_descripcion", $um_dosis_descripcion);
        oci_bind_by_name($stid, ":codigo_via", $codigo_via);
        oci_bind_by_name($stid, ":via", $via);
        oci_bind_by_name($stid, ":duracion", $duracion);
        oci_bind_by_name($stid, ":um_duracion", $um_duracion);
        oci_bind_by_name($stid, ":um_duracion_descripcion", $um_duracion_descripcion);
        oci_bind_by_name($stid, ":frecuencia", $frecuencia);
        oci_bind_by_name($stid, ":um_frecuencia", $um_frecuencia);
        oci_bind_by_name($stid, ":um_frecuencia_descripcion", $um_frecuencia_descripcion);
        oci_bind_by_name($stid, ":tipo_tecnologia", $tipo_tecnologia);
        oci_bind_by_name($stid, ":tipo_tecnologia_descripcion", $tipo_tecnologia_descripcion);
        oci_bind_by_name($stid, ":posologia", $posologia);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);
        oci_bind_by_name($stid, ":instruccion", $instruccion);
        oci_bind_by_name($stid, ":codigo_instruccion_mipres", $codigo_instruccion_mipres);
        oci_bind_by_name($stid, ":instruccion_mipres", $instruccion_mipres);
        oci_bind_by_name($stid, ":codigo_forma", $codigo_forma);
        oci_bind_by_name($stid, ":descripcion_forma", $descripcion_forma);
        oci_bind_by_name($stid, ":ium_primer_nivel", $ium_primer_nivel);
        oci_bind_by_name($stid, ":unidad_dispensacion", $unidad_dispensacion);
        oci_bind_by_name($stid, ":desc_dispensacion", $descripcion_unidad_dispensacion);
        oci_bind_by_name($stid, ":cantidad_total", $cantidad_total);
        oci_bind_by_name($stid, ":cantidad_ciclos", $cantidad_ciclos);
        oci_bind_by_name($stid, ":ium", $ium);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "FormulacionHC creada exitosamente.",
                "formula" => $formula,
                "cnsctvo_formula" => $cnsctvo_formula
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
     * Update an existing FormulationHC record.
     * @param object|array|string $id_pcnte_or_data
     * @param int|null $cnsctvo_pcnte
     * @param int|null $cnsctvo_formula
     * @param object|array|null $data
     * @return array
     */
    public function updateFormulacionHC($id_pcnte_or_data, $cnsctvo_pcnte = null, $cnsctvo_formula = null, $data = null)
    {
        if (is_object($id_pcnte_or_data) || is_array($id_pcnte_or_data)) {
            $data_payload = (object)$id_pcnte_or_data;
            $id_pcnte = !empty($data_payload->id_pcnte) ? substr($data_payload->id_pcnte, 0, 20) : null;
            $cnsctvo_pcnte = !empty($data_payload->cnsctvo_pcnte) ? (int)$data_payload->cnsctvo_pcnte : null;
            $formula = !empty($data_payload->formula) ? (int)$data_payload->formula : null;
            $cnsctvo_formula = !empty($data_payload->cnsctvo_formula) ? (int)$data_payload->cnsctvo_formula : null;
        } else {
            $id_pcnte = substr($id_pcnte_or_data, 0, 20);
            $cnsctvo_pcnte = (int)$cnsctvo_pcnte;
            $cnsctvo_formula = (int)$cnsctvo_formula;
            $data_payload = (object)$data;
            $formula = !empty($data_payload->formula) ? (int)$data_payload->formula : null;
        }

        if (empty($id_pcnte) || empty($cnsctvo_pcnte) || empty($cnsctvo_formula) || (empty($formula) && empty($data_payload->formula))) {
            if (empty($formula) && !empty($data_payload->formula)) {
                $formula = (int)$data_payload->formula;
            }
        }

        if (empty($id_pcnte) || empty($cnsctvo_pcnte) || empty($formula) || empty($cnsctvo_formula)) {
            return [
                "status" => "error",
                "message" => "Los campos 'id_pcnte', 'cnsctvo_pcnte', 'formula' y 'cnsctvo_formula' son requeridos."
            ];
        }

        $fields_to_update = [];
        $params = [];

        if (isset($data_payload->codigo_medicamento)) {
            $fields_to_update[] = "codigo_medicamento = :codigo_medicamento";
            $params[":codigo_medicamento"] = substr($data_payload->codigo_medicamento, 0, 30);
        }
        if (isset($data_payload->descripcion_medicamento)) {
            $fields_to_update[] = "descripcion_medicamento = :descripcion_medicamento";
            $params[":descripcion_medicamento"] = substr($data_payload->descripcion_medicamento, 0, 1000);
        }
        if (isset($data_payload->codigo_dci)) {
            $fields_to_update[] = "codigo_dci = :codigo_dci";
            $params[":codigo_dci"] = substr($data_payload->codigo_dci, 0, 20);
        }
        if (isset($data_payload->descripcion_dci)) {
            $fields_to_update[] = "descripcion_dci = :descripcion_dci";
            $params[":descripcion_dci"] = substr($data_payload->descripcion_dci, 0, 300);
        }
        if (isset($data_payload->dosis)) {
            $fields_to_update[] = "dosis = :dosis";
            $params[":dosis"] = (float)$data_payload->dosis;
        }
        if (isset($data_payload->um_dosis)) {
            $fields_to_update[] = "um_dosis = :um_dosis";
            $params[":um_dosis"] = substr($data_payload->um_dosis, 0, 5);
        }
        if (isset($data_payload->um_dosis_descripcion)) {
            $fields_to_update[] = "um_dosis_descripcion = :um_dosis_descripcion";
            $params[":um_dosis_descripcion"] = substr($data_payload->um_dosis_descripcion, 0, 50);
        }
        if (isset($data_payload->codigo_via)) {
            $fields_to_update[] = "codigo_via = :codigo_via";
            $params[":codigo_via"] = substr($data_payload->codigo_via, 0, 5);
        }
        if (isset($data_payload->via)) {
            $fields_to_update[] = "via = :via";
            $params[":via"] = substr($data_payload->via, 0, 50);
        }
        if (isset($data_payload->duracion)) {
            $fields_to_update[] = "duracion = :duracion";
            $params[":duracion"] = (int)$data_payload->duracion;
        }
        if (isset($data_payload->um_duracion)) {
            $fields_to_update[] = "um_duracion = :um_duracion";
            $params[":um_duracion"] = substr($data_payload->um_duracion, 0, 5);
        }
        if (isset($data_payload->um_duracion_descripcion)) {
            $fields_to_update[] = "um_duracion_descripcion = :um_duracion_descripcion";
            $params[":um_duracion_descripcion"] = substr($data_payload->um_duracion_descripcion, 0, 50);
        }
        if (isset($data_payload->frecuencia)) {
            $fields_to_update[] = "frecuencia = :frecuencia";
            $params[":frecuencia"] = (int)$data_payload->frecuencia;
        }
        if (isset($data_payload->um_frecuencia)) {
            $fields_to_update[] = "um_frecuencia = :um_frecuencia";
            $params[":um_frecuencia"] = substr($data_payload->um_frecuencia, 0, 5);
        }
        if (isset($data_payload->um_frecuencia_descripcion)) {
            $fields_to_update[] = "um_frecuencia_descripcion = :um_frecuencia_descripcion";
            $params[":um_frecuencia_descripcion"] = substr($data_payload->um_frecuencia_descripcion, 0, 50);
        }
        if (isset($data_payload->tipo_tecnologia)) {
            $fields_to_update[] = "tipo_tecnologia = :tipo_tecnologia";
            $params[":tipo_tecnologia"] = substr($data_payload->tipo_tecnologia, 0, 5);
        }
        if (isset($data_payload->tipo_tecnologia_descripcion)) {
            $fields_to_update[] = "tipo_tecnologia_descripcion = :tipo_tecnologia_descripcion";
            $params[":tipo_tecnologia_descripcion"] = substr($data_payload->tipo_tecnologia_descripcion, 0, 50);
        }
        if (isset($data_payload->posologia)) {
            $fields_to_update[] = "posologia = :posologia";
            $params[":posologia"] = substr($data_payload->posologia, 0, 100);
        }
        if (isset($data_payload->instruccion)) {
            $fields_to_update[] = "instruccion = :instruccion";
            $params[":instruccion"] = substr($data_payload->instruccion, 0, 500);
        }
        if (isset($data_payload->codigo_instruccion_mipres)) {
            $fields_to_update[] = "codigo_instruccion_mipres = :codigo_instruccion_mipres";
            $params[":codigo_instruccion_mipres"] = substr($data_payload->codigo_instruccion_mipres, 0, 10);
        }
        if (isset($data_payload->instruccion_mipres)) {
            $fields_to_update[] = "instruccion_mipres = :instruccion_mipres";
            $params[":instruccion_mipres"] = substr($data_payload->instruccion_mipres, 0, 500);
        }
        if (isset($data_payload->codigo_forma)) {
            $fields_to_update[] = "codigo_forma = :codigo_forma";
            $params[":codigo_forma"] = substr($data_payload->codigo_forma, 0, 10);
        }
        if (isset($data_payload->descripcion_forma)) {
            $fields_to_update[] = "descripcion_forma = :descripcion_forma";
            $params[":descripcion_forma"] = substr($data_payload->descripcion_forma, 0, 100);
        }
        if (isset($data_payload->ium_primer_nivel)) {
            $fields_to_update[] = "ium_primer_nivel = :ium_primer_nivel";
            $params[":ium_primer_nivel"] = substr($data_payload->ium_primer_nivel, 0, 30);
        }
        if (isset($data_payload->ium)) {
            $fields_to_update[] = "ium = :ium";
            $params[":ium"] = substr($data_payload->ium, 0, 30);
        }
        if (isset($data_payload->unidad_dispensacion)) {
            $fields_to_update[] = "unidad_dispensacion = :unidad_dispensacion";
            $params[":unidad_dispensacion"] = substr($data_payload->unidad_dispensacion, 0, 10);
        }
        if (isset($data_payload->descripcion_unidad_dispensacion)) {
            $fields_to_update[] = "desc_unidad_dispensacion = :desc_dispensacion";
            $params[":desc_dispensacion"] = substr($data_payload->descripcion_unidad_dispensacion, 0, 100);
        } elseif (isset($data_payload->desc_unidad_dispensacion)) {
            $fields_to_update[] = "desc_unidad_dispensacion = :desc_dispensacion";
            $params[":desc_dispensacion"] = substr($data_payload->desc_unidad_dispensacion, 0, 100);
        }
        if (isset($data_payload->cantidad_total)) {
            $fields_to_update[] = "cantidad_total = :cantidad_total";
            $params[":cantidad_total"] = (float)$data_payload->cantidad_total;
        }
        if (isset($data_payload->cantidad_ciclos)) {
            $fields_to_update[] = "cantidad_ciclos = :cantidad_ciclos";
            $params[":cantidad_ciclos"] = (int)$data_payload->cantidad_ciclos;
        }

        if (empty($fields_to_update)) {
            return [
                "status" => "error",
                "message" => "No se enviaron campos válidos para actualizar."
            ];
        }

        $sql = "UPDATE formulacion_hc SET " . implode(", ", $fields_to_update) . " 
                 WHERE id_pcnte = :id_pcnte 
                   AND cnsctvo_pcnte = :cnsctvo_pcnte 
                   AND formula = :formula 
                   AND cnsctvo_formula = :cnsctvo_formula";

        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":cnsctvo_pcnte", $cnsctvo_pcnte);
        oci_bind_by_name($stid, ":formula", $formula);
        oci_bind_by_name($stid, ":cnsctvo_formula", $cnsctvo_formula);

        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return [
                "status" => "success",
                "message" => "FormulacionHC actualizada exitosamente."
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
