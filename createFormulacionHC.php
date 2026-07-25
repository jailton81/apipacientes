<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/utils/AuthMiddleware.php';

// Validar token de seguridad JWT
$payload = AuthMiddleware::checkToken();

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Extract patient details to stamp/propagate if needed
$first_id_pcnte = null;
$first_cnsctvo_pcnte = null;

if (!empty($data->formulas) && is_array($data->formulas) && count($data->formulas) > 0) {
    $first_id_pcnte = !empty($data->formulas[0]->id_pcnte) ? $data->formulas[0]->id_pcnte : null;
    $first_cnsctvo_pcnte = !empty($data->formulas[0]->cnsctvo_pcnte) ? (int)$data->formulas[0]->cnsctvo_pcnte : null;
} elseif (!empty($data->ordenesMedicas) && is_array($data->ordenesMedicas) && count($data->ordenesMedicas) > 0) {
    $first_id_pcnte = !empty($data->ordenesMedicas[0]->id_pcnte) ? $data->ordenesMedicas[0]->id_pcnte : (!empty($data->ordenesMedicas[0]->ID_PCNTE) ? $data->ordenesMedicas[0]->ID_PCNTE : null);
    $first_cnsctvo_pcnte = !empty($data->ordenesMedicas[0]->cnsctvo_pcnte) ? (int)$data->ordenesMedicas[0]->cnsctvo_pcnte : (!empty($data->ordenesMedicas[0]->CNSCTVO_PCNTE) ? (int)$data->ordenesMedicas[0]->CNSCTVO_PCNTE : null);
} elseif (!empty($data->incapacidades) && is_array($data->incapacidades) && count($data->incapacidades) > 0) {
    $first_id_pcnte = !empty($data->incapacidades[0]->id_pcnte) ? $data->incapacidades[0]->id_pcnte : null;
    $first_cnsctvo_pcnte = !empty($data->incapacidades[0]->cnsctvo_pcnte) ? (int)$data->incapacidades[0]->cnsctvo_pcnte : null;
}

if (!$first_id_pcnte || !$first_cnsctvo_pcnte) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Los campos 'id_pcnte' y 'cnsctvo_pcnte' son obligatorios para firmar el plan."
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $database->closeConnection();
    exit;
}

// Log clinical plan data received
error_log("Receiving clinical plan for patient " . $first_id_pcnte . " consecutivo " . $first_cnsctvo_pcnte);

try {
    // Start sharing logic for formula ID
    $formula_shared_id = null;
    $sql_frm = 'SELECT COALESCE(MAX(formula), 0) + 1 AS MAX_FRM FROM formulacion_hc WHERE id_pcnte = :id AND cnsctvo_pcnte = :ev';
    $stid_frm = oci_parse($db, $sql_frm);
    oci_bind_by_name($stid_frm, ":id", $first_id_pcnte);
    oci_bind_by_name($stid_frm, ":ev", $first_cnsctvo_pcnte);
    oci_execute($stid_frm);
    $row_frm = oci_fetch_array($stid_frm, OCI_ASSOC);
    $formula_shared_id = isset($row_frm['MAX_FRM']) ? (int)$row_frm['MAX_FRM'] : 1;
    oci_free_statement($stid_frm);



    // 1. Persist Formulas
    if (!empty($data->formulas) && is_array($data->formulas)) {
        $sql_ins_frm = "INSERT INTO formulacion_hc (
                            id_pcnte, cnsctvo_pcnte, formula, cnsctvo_formula, id_mdco,
                            fecha_prescripcion, codigo_medicamento, descripcion_medicamento,
                            codigo_dci, descripcion_dci, dosis, um_dosis,
                            um_dosis_descripcion, codigo_via, via, duracion,
                            um_duracion, um_duracion_descripcion, frecuencia, um_frecuencia,
                            um_frecuencia_descripcion, tipo_tecnologia, tipo_tecnologia_descripcion,
                            posologia, usuario_ingreso, fecha_ingreso,
                            instruccion, codigo_instruccion_mipres, instruccion_mipres,
                            codigo_forma, descripcion_forma, ium_primer_nivel, cantidad_ciclos,
                            unidad_dispensacion, desc_unidad_dispensacion, cantidad_total, ium
                        ) VALUES (
                            :id_pcnte, :cnsctvo_pcnte, :formula, :cnsctvo_formula, :id_mdco,
                            TO_DATE(:fecha_prescripcion, 'YYYY-MM-DD HH24:MI:SS'), :codigo_medicamento, :descripcion_medicamento,
                            :codigo_dci, :descripcion_dci, :dosis, :um_dosis,
                            :um_dosis_descripcion, :codigo_via, :via, :duracion,
                            :um_duracion, :um_duracion_descripcion, :frecuencia, :um_frecuencia,
                            :um_frecuencia_descripcion, :tipo_tecnologia, :tipo_tecnologia_descripcion,
                            :posologia, :usuario_ingreso, SYSDATE,
                            :instruccion, :codigo_instruccion_mipres, :instruccion_mipres,
                            :codigo_forma, :descripcion_forma, :ium_primer_nivel, :cantidad_ciclos,
                            :unidad_dispensacion, :desc_dispensacion, :cantidad_total, :ium
                        )";

        foreach ($data->formulas as $index => $frm) {
            $stid = oci_parse($db, $sql_ins_frm);

            $id_p = $first_id_pcnte;
            $cns_p = $first_cnsctvo_pcnte;
            $f_shared = $formula_shared_id;
            $c_f = $index + 1;
            $id_m = !empty($frm->id_mdco) ? substr($frm->id_mdco, 0, 20) : (!empty($payload['id']) ? substr($payload['id'], 0, 20) : null);
            $fecha_p = !empty($frm->fecha_prescripcion) ? $frm->fecha_prescripcion : date('Y-m-d H:i:s');
            $cod_m = !empty($frm->codigo_medicamento) ? substr($frm->codigo_medicamento, 0, 30) : null;
            $desc_m = !empty($frm->descripcion_medicamento) ? substr($frm->descripcion_medicamento, 0, 1000) : null;
            $cod_dci = !empty($frm->codigo_dci) ? substr($frm->codigo_dci, 0, 20) : null;
            $desc_dci = !empty($frm->descripcion_dci) ? substr($frm->descripcion_dci, 0, 300) : null;
            $dosis = isset($frm->dosis) ? (float)$frm->dosis : 0.0;
            $um_dos = !empty($frm->um_dosis) ? substr($frm->um_dosis, 0, 5) : null;
            $um_dos_desc = !empty($frm->um_dosis_descripcion) ? substr($frm->um_dosis_descripcion, 0, 50) : null;
            $cod_via = !empty($frm->codigo_via) ? substr($frm->codigo_via, 0, 5) : null;
            $via = !empty($frm->via) ? substr($frm->via, 0, 50) : null;
            $duracion = isset($frm->duracion) ? (int)$frm->duracion : 0;
            $um_dur = !empty($frm->um_duracion) ? substr($frm->um_duracion, 0, 5) : null;
            $um_dur_desc = !empty($frm->um_duracion_descripcion) ? substr($frm->um_duracion_descripcion, 0, 50) : null;
            $freq = isset($frm->frecuencia) ? (int)$frm->frecuencia : 0;
            $um_freq = !empty($frm->um_frecuencia) ? substr($frm->um_frecuencia, 0, 5) : null;
            $um_freq_desc = !empty($frm->um_frecuencia_descripcion) ? substr($frm->um_frecuencia_descripcion, 0, 50) : null;
            $t_tec = !empty($frm->tipo_tecnologia) ? substr($frm->tipo_tecnologia, 0, 5) : null;
            $t_tec_desc = !empty($frm->tipo_tecnologia_descripcion) ? substr($frm->tipo_tecnologia_descripcion, 0, 50) : null;
            $posol = !empty($frm->posologia) ? substr($frm->posologia, 0, 100) : null;
            $usr_ing = !empty($frm->usuario_ingreso) ? substr($frm->usuario_ingreso, 0, 30) : (!empty($payload['id']) ? substr($payload['id'], 0, 30) : (!empty($payload['email']) ? substr($payload['email'], 0, 30) : 'ADMIN'));

            $inst = !empty($frm->instruccion) ? substr($frm->instruccion, 0, 500) : null;
            $cod_ins_mipres = !empty($frm->codigo_instruccion_mipres) ? substr($frm->codigo_instruccion_mipres, 0, 10) : null;
            $ins_mipres = !empty($frm->instruccion_mipres) ? substr($frm->instruccion_mipres, 0, 500) : null;
            $cod_forma = !empty($frm->codigo_forma) ? substr($frm->codigo_forma, 0, 10) : null;
            $desc_forma = !empty($frm->descripcion_forma) ? substr($frm->descripcion_forma, 0, 100) : null;
            $ium_prim_niv = !empty($frm->ium_primer_nivel) ? substr($frm->ium_primer_nivel, 0, 30) : null;
            $ium = !empty($frm->ium) ? substr($frm->ium, 0, 30) : null;
            $cantidad_ciclos = isset($frm->cantidad_ciclos) && $frm->cantidad_ciclos !== '' ? (int)$frm->cantidad_ciclos : null;
            $unidad_dispensacion = !empty($frm->unidad_dispensacion) ? substr($frm->unidad_dispensacion, 0, 10) : null;
            $descripcion_unidad_dispensacion = !empty($frm->desc_unidad_dispensacion) ? substr($frm->desc_unidad_dispensacion, 0, 100) : (!empty($frm->decripcion_unidad_dispensacion) ? substr($frm->decripcion_unidad_dispensacion, 0, 100) : null);
            $cantidad_total = isset($frm->cantidad_total) && $frm->cantidad_total !== '' ? (float)$frm->cantidad_total : null;

            oci_bind_by_name($stid, ":id_pcnte", $id_p);
            oci_bind_by_name($stid, ":cnsctvo_pcnte", $cns_p);
            oci_bind_by_name($stid, ":formula", $f_shared);
            oci_bind_by_name($stid, ":cnsctvo_formula", $c_f);
            oci_bind_by_name($stid, ":id_mdco", $id_m);
            oci_bind_by_name($stid, ":fecha_prescripcion", $fecha_p);
            oci_bind_by_name($stid, ":codigo_medicamento", $cod_m);
            oci_bind_by_name($stid, ":descripcion_medicamento", $desc_m);
            oci_bind_by_name($stid, ":codigo_dci", $cod_dci);
            oci_bind_by_name($stid, ":descripcion_dci", $desc_dci);
            oci_bind_by_name($stid, ":dosis", $dosis);
            oci_bind_by_name($stid, ":um_dosis", $um_dos);
            oci_bind_by_name($stid, ":um_dosis_descripcion", $um_dos_desc);
            oci_bind_by_name($stid, ":codigo_via", $cod_via);
            oci_bind_by_name($stid, ":via", $via);
            oci_bind_by_name($stid, ":duracion", $duracion);
            oci_bind_by_name($stid, ":um_duracion", $um_dur);
            oci_bind_by_name($stid, ":um_duracion_descripcion", $um_dur_desc);
            oci_bind_by_name($stid, ":frecuencia", $freq);
            oci_bind_by_name($stid, ":um_frecuencia", $um_freq);
            oci_bind_by_name($stid, ":um_frecuencia_descripcion", $um_freq_desc);
            oci_bind_by_name($stid, ":tipo_tecnologia", $t_tec);
            oci_bind_by_name($stid, ":tipo_tecnologia_descripcion", $t_tec_desc);
            oci_bind_by_name($stid, ":posologia", $posol);
            oci_bind_by_name($stid, ":usuario_ingreso", $usr_ing);
            oci_bind_by_name($stid, ":instruccion", $inst);
            oci_bind_by_name($stid, ":codigo_instruccion_mipres", $cod_ins_mipres);
            oci_bind_by_name($stid, ":instruccion_mipres", $ins_mipres);
            oci_bind_by_name($stid, ":codigo_forma", $cod_forma);
            oci_bind_by_name($stid, ":descripcion_forma", $desc_forma);
            oci_bind_by_name($stid, ":ium_primer_nivel", $ium_prim_niv);
            oci_bind_by_name($stid, ":cantidad_ciclos", $cantidad_ciclos);
            oci_bind_by_name($stid, ":unidad_dispensacion", $unidad_dispensacion);
            oci_bind_by_name($stid, ":desc_dispensacion", $descripcion_unidad_dispensacion);
            oci_bind_by_name($stid, ":cantidad_total", $cantidad_total);
            oci_bind_by_name($stid, ":ium", $ium);

            if (!oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
                $e = oci_error($stid);
                oci_free_statement($stid);
                throw new Exception("Error insertando formulación: " . $e['message']);
            }
            oci_free_statement($stid);
        }
    }

    // 2. Persist Orders (OrdenesMedicasHC)
    if (!empty($data->ordenesMedicas) && is_array($data->ordenesMedicas)) {
        $sql_ins_ord = "INSERT INTO ordenes_medicas_hc (
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

        foreach ($data->ordenesMedicas as $idx => $ord) {
            // Get max incremental ID for this new row
            $sql_id = 'SELECT COALESCE(MAX(id), 0) + 1 AS MAX_ID FROM ordenes_medicas_hc';
            $stid_id = oci_parse($db, $sql_id);
            oci_execute($stid_id);
            $row_id = oci_fetch_array($stid_id, OCI_ASSOC);
            $next_id = isset($row_id['MAX_ID']) ? (int)$row_id['MAX_ID'] : 1;
            oci_free_statement($stid_id);

            $stid = oci_parse($db, $sql_ins_ord);

            $id_p = $first_id_pcnte;
            $cns_p = $first_cnsctvo_pcnte;
            $id_m = !empty($ord->id_mdco) ? substr($ord->id_mdco, 0, 20) : (!empty($payload['id']) ? substr($payload['id'], 0, 20) : null);
            $fecha_p = !empty($ord->fecha_prescripcion) ? $ord->fecha_prescripcion : date('Y-m-d H:i:s');

            // Consultar tabla tecnologias_salud para garantizar consistencia de categoria_fhir y tipo_examen (grupo_filtro)
            $ref_categoria_fhir = null;
            $ref_grupo_filtro = null;
            $ref_tipo_tecnologia = null;
            if (!empty($ord->codigo)) {
                $sql_ref = "SELECT categoria_fhir, grupo_filtro, tipo_tecnologia FROM tecnologias_salud WHERE codigo = :cod";
                $stid_ref = oci_parse($db, $sql_ref);
                oci_bind_by_name($stid_ref, ":cod", $ord->codigo);
                oci_execute($stid_ref);
                $row_ref = oci_fetch_array($stid_ref, OCI_ASSOC);
                if ($row_ref) {
                    $rawCat = strtolower($row_ref['CATEGORIA_FHIR'] ?? '');
                    $ref_categoria_fhir = ($rawCat === 'procedimientos' || $rawCat === 'procedimiento' || $rawCat === 'consulta' || $rawCat === 'consultas' || $rawCat === 'procedure') ? 'procedure' : 'technology';
                    $ref_grupo_filtro = $row_ref['GRUPO_FILTRO'] ?? null;
                    $ref_tipo_tecnologia = $row_ref['TIPO_TECNOLOGIA'] ?? null;
                }
                oci_free_statement($stid_ref);
            }

            $cat_f = $ref_categoria_fhir ? $ref_categoria_fhir : (!empty($ord->categoria_fhir) ? $ord->categoria_fhir : null);
            $t_ex = $ref_grupo_filtro ? $ref_grupo_filtro : (!empty($ord->tipo_examen) ? $ord->tipo_examen : null);
            $t_tec = $ref_tipo_tecnologia ? $ref_tipo_tecnologia : (!empty($ord->tipo_tecnologia) ? $ord->tipo_tecnologia : null);

            $cat_f = $cat_f ? substr($cat_f, 0, 10) : null;
            $t_tec = $t_tec ? substr($t_tec, 0, 5) : null;
            $t_tec_desc = !empty($ord->tipo_tecnologia_descripcion) ? substr($ord->tipo_tecnologia_descripcion, 0, 50) : null;
            $t_ex = $t_ex ? substr($t_ex, 0, 30) : null;

            $cod = !empty($ord->codigo) ? substr($ord->codigo, 0, 30) : null;
            $nom = !empty($ord->nombre) ? substr($ord->nombre, 0, 100) : null;
            $usr_ing = !empty($ord->usuario_ingreso) ? substr($ord->usuario_ingreso, 0, 30) : (!empty($payload['id']) ? substr($payload['id'], 0, 30) : (!empty($payload['email']) ? substr($payload['email'], 0, 30) : 'ADMIN'));
            $cant = isset($ord->cantidad) ? (int)$ord->cantidad : null;
            $obs = !empty($ord->observaciones) ? substr($ord->observaciones, 0, 100) : null;
            $id_enc = !empty($ord->id_encuentro) ? substr($ord->id_encuentro, 0, 50) : null;
            $est = !empty($ord->estado) ? substr($ord->estado, 0, 20) : 'active';

            oci_bind_by_name($stid, ":id", $next_id);
            oci_bind_by_name($stid, ":id_pcnte", $id_p);
            oci_bind_by_name($stid, ":cnsctvo_pcnte", $cns_p);
            oci_bind_by_name($stid, ":id_mdco", $id_m);
            oci_bind_by_name($stid, ":fecha_prescripcion", $fecha_p);
            oci_bind_by_name($stid, ":categoria_fhir", $cat_f);
            oci_bind_by_name($stid, ":tipo_tecnologia", $t_tec);
            oci_bind_by_name($stid, ":tipo_tecnologia_descripcion", $t_tec_desc);
            oci_bind_by_name($stid, ":tipo_examen", $t_ex);
            oci_bind_by_name($stid, ":codigo", $cod);
            oci_bind_by_name($stid, ":nombre", $nom);
            oci_bind_by_name($stid, ":usuario_ingreso", $usr_ing);
            oci_bind_by_name($stid, ":cantidad", $cant);
            oci_bind_by_name($stid, ":observaciones", $obs);
            oci_bind_by_name($stid, ":id_encuentro", $id_enc);
            oci_bind_by_name($stid, ":estado", $est);

            if (!oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
                $e = oci_error($stid);
                oci_free_statement($stid);
                throw new Exception("Error insertando orden médica: " . $e['message']);
            }
            oci_free_statement($stid);
        }
    }

    // 3. Persist Incapacidades
    if (!empty($data->incapacidades) && is_array($data->incapacidades)) {
        $sql_ins_inc = 'INSERT INTO "RMSION_INCPCDAD" (
                            "ID_PCNTE", "TPO_ID", "CNSCTVO_PCNTE", "NIT_EMPRSA", "ID_MDCO",
                            "DSCRPCION_INCPCDAD", "FCHA_RMSION", "CDGO_EXMEN", "FCHA_INCIO",
                            "FCHA_FNAL", "DGNSTCO", "DRCION", "GRPO_SRVCIO", "ES_PRORROGA",
                            "TIPO_CONTINGENCIA", "MODALIDAD", "ORIGEN_INCAPACIDAD"
                        ) VALUES (
                            :id_pcnte, :tpo_id, :cnsctvo_pcnte, :nit_emprsa, :id_mdco,
                            :dscrpcion_incpcdad, SYSDATE, :cdgo_exmen, 
                            TO_DATE(:fcha_incio, \'YYYY-MM-DD\'), TO_DATE(:fcha_fnal, \'YYYY-MM-DD\'), 
                            :dgnstco, :drcion, :grpo_srvcio, :es_prorroga,
                            :tipo_contingencia, :modalidad, :origen_incapacidad
                        )';

        foreach ($data->incapacidades as $inc) {
            $tpo_id = 'CC';
            $sql_tpo = "SELECT TPO_IDNTFCCION FROM PCNTES WHERE IDNTFCCION = :id";
            $stid_tpo = oci_parse($db, $sql_tpo);
            oci_bind_by_name($stid_tpo, ":id", $first_id_pcnte);
            oci_execute($stid_tpo);
            $row_tpo = oci_fetch_array($stid_tpo, OCI_ASSOC);
            if (isset($row_tpo['TPO_IDNTFCCION'])) {
                $tpo_id = $row_tpo['TPO_IDNTFCCION'];
            }
            oci_free_statement($stid_tpo);

            $stid = oci_parse($db, $sql_ins_inc);

            $id_p = $first_id_pcnte;
            $cns_p = $first_cnsctvo_pcnte;
            $nit = !empty($inc->nit_emprsa) ? $inc->nit_emprsa : null;
            $id_m = !empty($inc->id_mdco) ? $inc->id_mdco : (!empty($payload['id']) ? $payload['id'] : null);
            $desc = !empty($inc->dscrpcion_incpcdad) ? $inc->dscrpcion_incpcdad : (!empty($inc->motivo) ? $inc->motivo : null);
            $cdgo_ex = !empty($inc->cdgo_exmen) ? $inc->cdgo_exmen : null;
            $f_ini = !empty($inc->fcha_incio) ? $inc->fcha_incio : (!empty($inc->inicio) ? $inc->inicio : null);
            $f_fin = !empty($inc->fcha_fnal) ? $inc->fcha_fnal : (!empty($inc->fin) ? $inc->fin : null);
            $diag = !empty($inc->dgnstco) ? $inc->dgnstco : null;
            $drc = isset($inc->drcion) ? $inc->drcion : (!empty($inc->dias) ? (int)$inc->dias : 1);
            $grp_srv = !empty($inc->grpo_srvcio) ? $inc->grpo_srvcio : null;
            $es_prorroga = isset($inc->es_prorroga) ? $inc->es_prorroga : null;
            $tipo_contingencia = !empty($inc->tipo_contingencia) ? $inc->tipo_contingencia : null;
            $modalidad = !empty($inc->modalidad) ? $inc->modalidad : null;
            $origen_incapacidad = !empty($inc->origen_incapacidad) ? $inc->origen_incapacidad : null;

            oci_bind_by_name($stid, ":id_pcnte", $id_p);
            oci_bind_by_name($stid, ":tpo_id", $tpo_id);
            oci_bind_by_name($stid, ":cnsctvo_pcnte", $cns_p);
            oci_bind_by_name($stid, ":nit_emprsa", $nit);
            oci_bind_by_name($stid, ":id_mdco", $id_m);
            oci_bind_by_name($stid, ":dscrpcion_incpcdad", $desc);
            oci_bind_by_name($stid, ":cdgo_exmen", $cdgo_ex);
            oci_bind_by_name($stid, ":fcha_incio", $f_ini);
            oci_bind_by_name($stid, ":fcha_fnal", $f_fin);
            oci_bind_by_name($stid, ":dgnstco", $diag);
            oci_bind_by_name($stid, ":drcion", $drc);
            oci_bind_by_name($stid, ":grpo_srvcio", $grp_srv);
            oci_bind_by_name($stid, ":es_prorroga", $es_prorroga);
            oci_bind_by_name($stid, ":tipo_contingencia", $tipo_contingencia);
            oci_bind_by_name($stid, ":modalidad", $modalidad);
            oci_bind_by_name($stid, ":origen_incapacidad", $origen_incapacidad);

            if (!oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
                $e = oci_error($stid);
                oci_free_statement($stid);
                throw new Exception("Error insertando incapacidad: " . $e['message']);
            }
            oci_free_statement($stid);
        }
    }


    // Commit transaction if all inserts succeed
    oci_commit($db);
    
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Plan de manejo (fórmulas, órdenes e incapacidades) guardado exitosamente.",
        "formula" => $formula_shared_id
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    oci_rollback($db);
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Fallo de persistencia transaccional: " . $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} finally {
    $database->closeConnection();
}
?>
