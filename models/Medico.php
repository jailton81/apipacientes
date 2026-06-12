<?php
class Medico
{
    private $conn;
    private $table_name = "mdcos";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoMedico($idntfccion_mdcos)
    {
        // Sentencia SQL
        $sql = "SELECT tpo_idntfccion, id_mdco as idntfccion_mdcos, prmer_nmbre, sgndo_nmbre, prmer_aplldo, sgndo_aplldo 
                FROM " . $this->table_name . " 
                WHERE id_mdco = :id_mdco";

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":id_mdco", $idntfccion_mdcos);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $medicos = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $medicos[] = [
                "tipo_identificacion" => $row['TPO_IDNTFCCION'],
                "id_medico" => $row['IDNTFCCION_MDCOS'],
                "primer_nombre" => $row['PRMER_NMBRE'],
                "segundo_nombre" => $row['SGNDO_NMBRE'],
                "primer_apellido" => $row['PRMER_APLLDO'],
                "segundo_apellido" => $row['SGNDO_APLLDO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($medicos),
            "data" => $medicos
        ];
    }

    public function registerMedico($id_institucion, $data) {
        $idntfccion = $data->idntfccion_mdcos;
        $tpo_idntfccion = !empty($data->tpo_idntfccion) ? $data->tpo_idntfccion : 'CC';
        $nmbres = !empty($data->nmbres) ? $data->nmbres : 'N/A';
        $espcldad = !empty($data->espcldad) ? $data->espcldad : 'N/A';
        $estdo = !empty($data->estdo) ? $data->estdo : 'A';
        $email = !empty($data->email) ? $data->email : null;
        $password = !empty($data->password) ? $data->password : $idntfccion;
        
        $primer_nombre = !empty($data->primer_nombre) ? $data->primer_nombre : null;
        $segundo_nombre = !empty($data->segundo_nombre) ? $data->segundo_nombre : null;
        $primer_apellido = !empty($data->primer_apellido) ? $data->primer_apellido : null;
        $segundo_apellido = !empty($data->segundo_apellido) ? $data->segundo_apellido : null;
        $registro_medico = !empty($data->registro_medico) ? $data->registro_medico : null;
        $firma_medico = !empty($data->firma_medico) ? $data->firma_medico : null;
        $id_medico = !empty($data->id_medico) ? $data->id_medico : null;

        // Si nmbres es N/A o vacío pero se proveen nombres individuales, construirlos
        if ($nmbres === 'N/A' || empty($nmbres)) {
            $names_arr = array_filter([$primer_nombre, $segundo_nombre, $primer_apellido, $segundo_apellido]);
            if (!empty($names_arr)) {
                $nmbres = implode(' ', $names_arr);
            }
        }

        // El password inicial será el proporcionado o su identificación (cifrado)
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // 1. Verificar si el médico ya existe en la tabla mdcos
        $check_sql = "SELECT idntfccion_mdcos FROM mdcos WHERE idntfccion_mdcos = :id";
        $check_stid = oci_parse($this->conn, $check_sql);
        oci_bind_by_name($check_stid, ":id", $idntfccion);
        oci_execute($check_stid);
        
        $exists_in_mdcos = false;
        if (oci_fetch_array($check_stid, OCI_ASSOC)) {
            $exists_in_mdcos = true;
        } else {
            // No existe, lo insertamos
            $insert_mdcos_sql = "INSERT INTO mdcos (
                                    idntfccion_mdcos, tpo_idntfccion, nmbres, espcldad, estdo, email, password, pwd_changed,
                                    prmer_nmbre, sgndo_nmbre, prmer_aplldo, sgndo_aplldo, rgstro_mdco, frma_mdco, id_mdco
                                 ) VALUES (
                                    :id, :tpo, :nmbres, :espcldad, :estdo, :email, :password, 0,
                                    :primer_nombre, :segundo_nombre, :primer_apellido, :segundo_apellido, :registro_medico, :firma_medico, :id_medico
                                 )";
            $insert_stid = oci_parse($this->conn, $insert_mdcos_sql);
            oci_bind_by_name($insert_stid, ":id", $idntfccion);
            oci_bind_by_name($insert_stid, ":tpo", $tpo_idntfccion);
            oci_bind_by_name($insert_stid, ":nmbres", $nmbres);
            oci_bind_by_name($insert_stid, ":espcldad", $espcldad);
            oci_bind_by_name($insert_stid, ":estdo", $estdo);
            oci_bind_by_name($insert_stid, ":email", $email);
            oci_bind_by_name($insert_stid, ":password", $password_hash);
            
            oci_bind_by_name($insert_stid, ":primer_nombre", $primer_nombre);
            oci_bind_by_name($insert_stid, ":segundo_nombre", $segundo_nombre);
            oci_bind_by_name($insert_stid, ":primer_apellido", $primer_apellido);
            oci_bind_by_name($insert_stid, ":segundo_apellido", $segundo_apellido);
            oci_bind_by_name($insert_stid, ":registro_medico", $registro_medico);
            oci_bind_by_name($insert_stid, ":firma_medico", $firma_medico);
            oci_bind_by_name($insert_stid, ":id_medico", $id_medico);
            
            if (!oci_execute($insert_stid)) {
                $e = oci_error($insert_stid);
                return ["status" => "error", "message" => "Error al registrar médico en mdcos: " . $e['message']];
            }
        }

        // 2. Llenar la tabla intermedia institucion_medico
        // Primero verificamos si ya están vinculados
        $check_rel_sql = "SELECT id_medico FROM institucion_medico WHERE id_medico = :id_medico AND id_institucion = :id_institucion";
        $check_rel_stid = oci_parse($this->conn, $check_rel_sql);
        oci_bind_by_name($check_rel_stid, ":id_medico", $idntfccion);
        oci_bind_by_name($check_rel_stid, ":id_institucion", $id_institucion);
        oci_execute($check_rel_stid);
        
        if (oci_fetch_array($check_rel_stid, OCI_ASSOC)) {
            return ["status" => "error", "message" => "El médico ya se encuentra vinculado a esta institución."];
        }

        $insert_rel_sql = "INSERT INTO institucion_medico (id_medico, id_institucion) VALUES (:id_medico, :id_institucion)";
        $rel_stid = oci_parse($this->conn, $insert_rel_sql);
        oci_bind_by_name($rel_stid, ":id_medico", $idntfccion);
        oci_bind_by_name($rel_stid, ":id_institucion", $id_institucion);
        
        if (oci_execute($rel_stid)) {
            return ["status" => "success", "message" => "Médico registrado y vinculado a la institución exitosamente."];
        } else {
            $e = oci_error($rel_stid);
            return ["status" => "error", "message" => "Error al vincular el médico con la institución: " . $e['message']];
        }
    }

    public function updateMedico($id_institucion, $idntfccion_mdcos, $data) {
        // Verificar que el médico esté vinculado a la institución
        $check_rel_sql = "SELECT id_medico FROM institucion_medico WHERE id_medico = :id_medico AND id_institucion = :id_institucion";
        $check_rel_stid = oci_parse($this->conn, $check_rel_sql);
        oci_bind_by_name($check_rel_stid, ":id_medico", $idntfccion_mdcos);
        oci_bind_by_name($check_rel_stid, ":id_institucion", $id_institucion);
        oci_execute($check_rel_stid);
        
        if (!oci_fetch_array($check_rel_stid, OCI_ASSOC)) {
            oci_free_statement($check_rel_stid);
            return ["status" => "error", "message" => "El médico no está vinculado a su institución o no tiene permisos para modificarlo."];
        }
        oci_free_statement($check_rel_stid);

        // Construir la consulta de actualización de forma dinámica
        $fields_to_update = [];
        $params = [];

        if (isset($data->tpo_idntfccion)) {
            $fields_to_update[] = "tpo_idntfccion = :tpo_idntfccion";
            $params[":tpo_idntfccion"] = $data->tpo_idntfccion;
        }
        if (isset($data->nmbres)) {
            $fields_to_update[] = "nmbres = :nmbres";
            $params[":nmbres"] = $data->nmbres;
        }
        if (isset($data->espcldad)) {
            $fields_to_update[] = "espcldad = :espcldad";
            $params[":espcldad"] = $data->espcldad;
        }
        if (isset($data->estdo)) {
            $fields_to_update[] = "estdo = :estdo";
            $params[":estdo"] = $data->estdo;
        }
        if (isset($data->email)) {
            $fields_to_update[] = "email = :email";
            $params[":email"] = $data->email;
        }

        // Soporte adicional para campos de nombres separados si se proveen
        if (isset($data->primer_nombre)) {
            $fields_to_update[] = "prmer_nmbre = :primer_nombre";
            $params[":primer_nombre"] = $data->primer_nombre;
        }
        if (isset($data->segundo_nombre)) {
            $fields_to_update[] = "sgndo_nmbre = :segundo_nombre";
            $params[":segundo_nombre"] = $data->segundo_nombre;
        }
        if (isset($data->primer_apellido)) {
            $fields_to_update[] = "prmer_aplldo = :primer_apellido";
            $params[":primer_apellido"] = $data->primer_apellido;
        }
        if (isset($data->segundo_apellido)) {
            $fields_to_update[] = "sgndo_aplldo = :segundo_apellido";
            $params[":segundo_apellido"] = $data->segundo_apellido;
        }
        if (isset($data->registro_medico)) {
            $fields_to_update[] = "rgstro_mdco = :registro_medico";
            $params[":registro_medico"] = $data->registro_medico;
        }
        if (isset($data->firma_medico)) {
            $fields_to_update[] = "frma_mdco = :firma_medico";
            $params[":firma_medico"] = $data->firma_medico;
        }
        if (isset($data->id_medico)) {
            $fields_to_update[] = "id_mdco = :id_medico";
            $params[":id_medico"] = $data->id_medico;
        }

        if (empty($fields_to_update)) {
            return ["status" => "error", "message" => "No se proporcionaron campos para actualizar."];
        }

        $sql = "UPDATE " . $this->table_name . " SET " . implode(", ", $fields_to_update) . " WHERE idntfccion_mdcos = :idntfccion_mdcos";
        $stid = oci_parse($this->conn, $sql);

        // Enlazar el identificador
        oci_bind_by_name($stid, ":idntfccion_mdcos", $idntfccion_mdcos);

        // Enlazar los parámetros dinámicos por referencia
        foreach ($params as $key => &$val) {
            oci_bind_by_name($stid, $key, $val);
        }
        unset($val);

        if (oci_execute($stid)) {
            oci_commit($this->conn);
            oci_free_statement($stid);
            return ["status" => "success", "message" => "Información del médico actualizada exitosamente."];
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return ["status" => "error", "message" => "Error al actualizar médico: " . $e['message']];
        }
    }

    public function changePassword($idntfccion, $old_password, $new_password) {
        $sql = "SELECT password FROM mdcos WHERE idntfccion_mdcos = :id";
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":id", $idntfccion);
        oci_execute($stid);

        $row = oci_fetch_array($stid, OCI_ASSOC);
        
        if (!$row) {
            return ["status" => "error", "message" => "Médico no encontrado."];
        }

        if (password_verify($old_password, $row['PASSWORD'])) {
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            $update_sql = "UPDATE mdcos SET password = :password, pwd_changed = 1 WHERE idntfccion_mdcos = :id";
            $update_stid = oci_parse($this->conn, $update_sql);
            oci_bind_by_name($update_stid, ":password", $new_password_hash);
            oci_bind_by_name($update_stid, ":id", $idntfccion);
            
            if (oci_execute($update_stid)) {
                return ["status" => "success", "message" => "Contraseña del médico actualizada correctamente."];
            } else {
                $e = oci_error($update_stid);
                return ["status" => "error", "message" => "Error al actualizar la contraseña: " . $e['message']];
            }
        } else {
            return ["status" => "error", "message" => "La contraseña actual es incorrecta."];
        }
    }

    public function loginMedico($idntfccion, $password) {
        $sql = "SELECT idntfccion_mdcos, nmbres, email, password, pwd_changed FROM mdcos WHERE idntfccion_mdcos = :id AND estdo = 'A'";
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":id", $idntfccion);
        oci_execute($stid);

        $row = oci_fetch_array($stid, OCI_ASSOC);
        
        if ($row && password_verify($password, $row['PASSWORD'])) {
            $require_change = (!isset($row['PWD_CHANGED']) || $row['PWD_CHANGED'] == 0);
            
            if ($require_change) {
                return [
                    "status" => "success",
                    "require_password_change" => true,
                    "user" => [
                        "id" => $row['IDNTFCCION_MDCOS'],
                        "name" => $row['NMBRES'],
                        "email" => $row['EMAIL']
                    ]
                ];
            }

            return [
                "status" => "success",
                "require_password_change" => false,
                "user" => [
                    "id" => $row['IDNTFCCION_MDCOS'],
                    "name" => $row['NMBRES'],
                    "email" => $row['EMAIL']
                ]
            ];
        }

        return ["status" => "error", "message" => "Identificación o contraseña incorrectos."];
    }

    public function getAllMedicos($page = 1, $limit = 10) {
        $page = (int)$page;
        $limit = (int)$limit;
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        $offset = ($page - 1) * $limit;

        // Obtener el total de registros para el paginado
        $count_sql = "SELECT COUNT(*) AS total FROM " . $this->table_name;
        $count_stid = oci_parse($this->conn, $count_sql);
        oci_execute($count_stid);
        $count_row = oci_fetch_array($count_stid, OCI_ASSOC);
        $total = isset($count_row['TOTAL']) ? (int)$count_row['TOTAL'] : 0;
        oci_free_statement($count_stid);

        $max_row = $offset + $limit;
        $min_row = $offset;

        $sql = "SELECT * FROM (
                    SELECT a.*, ROWNUM rnum FROM (
                        SELECT idntfccion_mdcos, tpo_idntfccion, nmbres, espcldad, estdo, email, pwd_changed, id_mdco, 
                                prmer_nmbre, sgndo_nmbre, prmer_aplldo, sgndo_aplldo, rgstro_mdco, frma_mdco 
                        FROM " . $this->table_name . "
                        ORDER BY idntfccion_mdcos ASC
                    ) a WHERE ROWNUM <= :max_row
                ) WHERE rnum > :min_row";
        
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":max_row", $max_row);
        oci_bind_by_name($stid, ":min_row", $min_row);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error al extraer los médicos: " . $e['message']
            ];
        }

        $medicos = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $medicos[] = [
                "idntfccion_mdcos" => $row['IDNTFCCION_MDCOS'],
                "tpo_idntfccion" => $row['TPO_IDNTFCCION'],
                "nmbres" => $row['NMBRES'],
                "espcldad" => $row['ESPCLDAD'],
                "estdo" => $row['ESTDO'],
                "email" => $row['EMAIL'],
                "pwd_changed" => isset($row['PWD_CHANGED']) ? (int)$row['PWD_CHANGED'] : 0,
                "id_mdco" => $row['ID_MDCO'],
                "primer_nombre" => $row['PRMER_NMBRE'],
                "segundo_nombre" => $row['SGNDO_NMBRE'],
                "primer_apellido" => $row['PRMER_APLLDO'],
                "segundo_apellido" => $row['SGNDO_APLLDO'],
                "registro_medico" => $row['RGSTRO_MDCO'],
                "firma_medico" => $row['FRMA_MDCO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "total" => $total,
            "page" => $page,
            "limit" => $limit,
            "pages" => ceil($total / $limit),
            "count" => count($medicos),
            "data" => $medicos
        ];
    }

    public function getAllMedicosCompleto() {
        $sql = "SELECT idntfccion_mdcos, tpo_idntfccion, nmbres, espcldad, estdo, email, pwd_changed, id_mdco, 
                       prmer_nmbre, sgndo_nmbre, prmer_aplldo, sgndo_aplldo, rgstro_mdco, frma_mdco 
                FROM " . $this->table_name . "
                ORDER BY idntfccion_mdcos ASC";
        
        $stid = oci_parse($this->conn, $sql);
        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error al extraer los médicos: " . $e['message']
            ];
        }

        $medicos = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $medicos[] = [
                "idntfccion_mdcos" => $row['IDNTFCCION_MDCOS'],
                "tpo_idntfccion" => $row['TPO_IDNTFCCION'],
                "nmbres" => $row['NMBRES'],
                "espcldad" => $row['ESPCLDAD'],
                "estdo" => $row['ESTDO'],
                "email" => $row['EMAIL'],
                "pwd_changed" => isset($row['PWD_CHANGED']) ? (int)$row['PWD_CHANGED'] : 0,
                "id_mdco" => $row['ID_MDCO'],
                "primer_nombre" => $row['PRMER_NMBRE'],
                "segundo_nombre" => $row['SGNDO_NMBRE'],
                "primer_apellido" => $row['PRMER_APLLDO'],
                "segundo_apellido" => $row['SGNDO_APLLDO'],
                "registro_medico" => $row['RGSTRO_MDCO'],
                "firma_medico" => $row['FRMA_MDCO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($medicos),
            "data" => $medicos
        ];
    }

    public function GetInfoMedicoCompleto($idntfccion_mdcos)
    {
        $sql = "SELECT m.idntfccion_mdcos, m.tpo_idntfccion, m.nmbres, m.espcldad, e.dscrpcion AS desc_espcldad, m.estdo, m.email, m.pwd_changed, m.id_mdco, 
                       m.prmer_nmbre, m.sgndo_nmbre, m.prmer_aplldo, m.sgndo_aplldo, m.rgstro_mdco, m.frma_mdco 
                FROM " . $this->table_name . " m 
                LEFT JOIN espclddes e ON m.espcldad = e.cdgo_espcldad 
                WHERE m.idntfccion_mdcos = :id OR m.id_mdco = :id_mdco";

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":id", $idntfccion_mdcos);
        oci_bind_by_name($stid, ":id_mdco", $idntfccion_mdcos);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $medicos = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $medicos[] = [
                "idntfccion_mdcos" => $row['IDNTFCCION_MDCOS'],
                "tpo_idntfccion" => $row['TPO_IDNTFCCION'],
                "nmbres" => $row['NMBRES'],
                "espcldad" => $row['ESPCLDAD'],
                "desc_espcldad" => $row['DESC_ESPCLDAD'],
                "estdo" => $row['ESTDO'],
                "email" => $row['EMAIL'],
                "pwd_changed" => isset($row['PWD_CHANGED']) ? (int)$row['PWD_CHANGED'] : 0,
                "id_mdco" => $row['ID_MDCO'],
                "primer_nombre" => $row['PRMER_NMBRE'],
                "segundo_nombre" => $row['SGNDO_NMBRE'],
                "primer_apellido" => $row['PRMER_APLLDO'],
                "segundo_apellido" => $row['SGNDO_APLLDO'],
                "registro_medico" => $row['RGSTRO_MDCO'],
                "firma_medico" => $row['FRMA_MDCO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($medicos),
            "data" => $medicos
        ];
    }
}
?>