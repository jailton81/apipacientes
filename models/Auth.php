<?php
class Auth {
    private $conn;
    private $table_name = "APP_USERS";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($name, $email, $password) {
        // Verificar si el email ya existe
        $check_sql = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $check_stid = oci_parse($this->conn, $check_sql);
        oci_bind_by_name($check_stid, ":email", $email);
        oci_execute($check_stid);
        
        if (oci_fetch_array($check_stid, OCI_ASSOC)) {
            return ["status" => "error", "message" => "El email ya se encuentra registrado."];
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO " . $this->table_name . " (name, email, password) VALUES (:name, :email, :password)";
        
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":name", $name);
        oci_bind_by_name($stid, ":email", $email);
        oci_bind_by_name($stid, ":password", $hashed_password);

        if (oci_execute($stid)) {
            return ["status" => "success", "message" => "Usuario registrado satisfactoriamente."];
        } else {
            $e = oci_error($stid);
            return ["status" => "error", "message" => "Error al registrar usuario: " . $e['message']];
        }
    }

    public function login($email, $password) {
        $sql = "SELECT id, name, email, password FROM " . $this->table_name . " WHERE email = :email";
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":email", $email);
        oci_execute($stid);

        $row = oci_fetch_array($stid, OCI_ASSOC);
        if ($row && password_verify($password, $row['PASSWORD'])) {
            return [
                "status" => "success",
                "user" => [
                    "id" => $row['ID'],
                    "name" => $row['NAME'],
                    "email" => $row['EMAIL']
                ]
            ];
        }

        return ["status" => "error", "message" => "Credenciales inválidas."];
    }

    public function loginInstitucion($email, $password) {
        $sql = "SELECT nit_insttcion, nit_cntbldad, dscrpcion, email, password FROM insttciones WHERE email = :email";
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":email", $email);
        oci_execute($stid);

        $row = oci_fetch_array($stid, OCI_ASSOC);
        if ($row && password_verify($password, $row['PASSWORD'])) {
            return [
                "status" => "success",
                "user" => [
                    "id" => $row['NIT_INSTTCION'],
                    "nit_cntbldad" => isset($row['NIT_CNTBLDAD']) ? $row['NIT_CNTBLDAD'] : null,
                    "name" => $row['DSCRPCION'],
                    "email" => $row['EMAIL']
                ]
            ];
        }

        return ["status" => "error", "message" => "Credenciales inválidas en instituciones."];
    }

    public function registerInstitucion($data) {
        $email = $data->email;
        $password = $data->password;
        $nit_insttcion = $data->nit_insttcion;
        $nit_cntbldad = $data->nit_cntbldad;
        
        $dscrpcion = !empty($data->dscrpcion) ? $data->dscrpcion : 'N/A';
        $entdad = !empty($data->entdad_admnstrdra) ? $data->entdad_admnstrdra : '000000';
        $cdgo_prstdor = !empty($data->cdgo_prstdor_srvcio) ? $data->cdgo_prstdor_srvcio : '000000000000';
        $drccion = !empty($data->drccion) ? $data->drccion : 'N/A';
        $tlfno = !empty($data->tlfno) ? $data->tlfno : '0000000';
        $fax = !empty($data->fax) ? $data->fax : '0000000';
        $nmro_lccia = !empty($data->nmro_lccia) ? $data->nmro_lccia : 'N/A';
        $usrio_ingso = 'API';

        // Check if exists
        $check_sql = "SELECT nit_insttcion FROM insttciones WHERE email = :email OR nit_insttcion = :nit";
        $check_stid = oci_parse($this->conn, $check_sql);
        oci_bind_by_name($check_stid, ":email", $email);
        oci_bind_by_name($check_stid, ":nit", $nit_insttcion);
        oci_execute($check_stid);
        
        if (oci_fetch_array($check_stid, OCI_ASSOC)) {
            return ["status" => "error", "message" => "El email o el NIT ya se encuentran registrados."];
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO insttciones (
                    nit_insttcion, dscrpcion, entdad_admnstrdra, cdgo_prstdor_srvcio, drccion, 
                    tlfno, fax, nmro_lccia, usrio_ingso, fcha_ingrso, 
                    email, password, nit_cntbldad
                ) VALUES (
                    :nit_insttcion, :dscrpcion, :entdad, :cdgo_prstdor, :drccion, 
                    :tlfno, :fax, :nmro_lccia, :usrio_ingso, SYSDATE, 
                    :email, :password, :nit_cntbldad
                )";
        
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":nit_insttcion", $nit_insttcion);
        oci_bind_by_name($stid, ":dscrpcion", $dscrpcion);
        oci_bind_by_name($stid, ":entdad", $entdad);
        oci_bind_by_name($stid, ":cdgo_prstdor", $cdgo_prstdor);
        oci_bind_by_name($stid, ":drccion", $drccion);
        oci_bind_by_name($stid, ":tlfno", $tlfno);
        oci_bind_by_name($stid, ":fax", $fax);
        oci_bind_by_name($stid, ":nmro_lccia", $nmro_lccia);
        oci_bind_by_name($stid, ":usrio_ingso", $usrio_ingso);
        oci_bind_by_name($stid, ":email", $email);
        oci_bind_by_name($stid, ":password", $hashed_password);
        oci_bind_by_name($stid, ":nit_cntbldad", $nit_cntbldad);

        if (oci_execute($stid)) {
            return ["status" => "success", "message" => "Institución registrada satisfactoriamente."];
        } else {
            $e = oci_error($stid);
            return ["status" => "error", "message" => "Error al registrar institución: " . $e['message']];
        }
    }

    public function changePasswordInstitucion($email, $old_password, $new_password) {
        $sql = "SELECT nit_insttcion, password FROM insttciones WHERE email = :email";
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":email", $email);
        oci_execute($stid);

        $row = oci_fetch_array($stid, OCI_ASSOC);
        if ($row && password_verify($old_password, $row['PASSWORD'])) {
            $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            $update_sql = "UPDATE insttciones SET password = :new_password WHERE email = :email";
            $update_stid = oci_parse($this->conn, $update_sql);
            oci_bind_by_name($update_stid, ":new_password", $hashed_new_password);
            oci_bind_by_name($update_stid, ":email", $email);
            
            if (oci_execute($update_stid)) {
                return ["status" => "success", "message" => "Contraseña actualizada exitosamente."];
            } else {
                return ["status" => "error", "message" => "Error al actualizar la base de datos."];
            }
        }

        return ["status" => "error", "message" => "La contraseña actual es incorrecta o la institución no existe."];
    }

    public function registerSoftware($nit_insttcion, $client_id, $client_secret, $scope, $subscription_key) {
        // Verificar si los datos (client_id, client_secret, subscription_key) ya existen en otros registros
        $check_sql = "SELECT nit_insttcion FROM insttciones 
                      WHERE client_id = :client_id 
                         OR client_secret = :client_secret 
                         OR subscription_key = :subscription_key";
        $check_stid = oci_parse($this->conn, $check_sql);
        oci_bind_by_name($check_stid, ":client_id", $client_id);
        oci_bind_by_name($check_stid, ":client_secret", $client_secret);
        oci_bind_by_name($check_stid, ":subscription_key", $subscription_key);
        oci_execute($check_stid);
        
        if (oci_fetch_array($check_stid, OCI_ASSOC)) {
            return ["status" => "error", "message" => "El client_id, client_secret o subscription_key ya se encuentran registrados y deben ser únicos."];
        }
        
        // Verificar que la institución exista
        $inst_sql = "SELECT nit_insttcion FROM insttciones WHERE nit_insttcion = :nit";
        $inst_stid = oci_parse($this->conn, $inst_sql);
        oci_bind_by_name($inst_stid, ":nit", $nit_insttcion);
        oci_execute($inst_stid);
        
        if (!oci_fetch_array($inst_stid, OCI_ASSOC)) {
             return ["status" => "error", "message" => "La institución no existe. NIT inválido."];
        }

        // Actualizar la institución con las credenciales del software
        $update_sql = "UPDATE insttciones 
                       SET client_id = :client_id, 
                           client_secret = :client_secret, 
                           scope = :scope, 
                           subscription_key = :subscription_key 
                       WHERE nit_insttcion = :nit";
        
        $update_stid = oci_parse($this->conn, $update_sql);
        oci_bind_by_name($update_stid, ":client_id", $client_id);
        oci_bind_by_name($update_stid, ":client_secret", $client_secret);
        oci_bind_by_name($update_stid, ":scope", $scope);
        oci_bind_by_name($update_stid, ":subscription_key", $subscription_key);
        oci_bind_by_name($update_stid, ":nit", $nit_insttcion);
        
        if (oci_execute($update_stid)) {
            return ["status" => "success", "message" => "Software de la institución registrado exitosamente."];
        } else {
            $e = oci_error($update_stid);
            return ["status" => "error", "message" => "Error al registrar el software: " . $e['message']];
        }
    }

    public function updateSoftware($nit_insttcion, $client_id, $client_secret, $scope, $subscription_key) {
        // Verificar que la institución exista
        $inst_sql = "SELECT nit_insttcion FROM insttciones WHERE nit_insttcion = :nit";
        $inst_stid = oci_parse($this->conn, $inst_sql);
        oci_bind_by_name($inst_stid, ":nit", $nit_insttcion);
        oci_execute($inst_stid);
        
        if (!oci_fetch_array($inst_stid, OCI_ASSOC)) {
             return ["status" => "error", "message" => "La institución no existe. NIT inválido."];
        }

        // Verificar si los datos (client_id, client_secret, subscription_key) ya existen en OTROS registros
        $check_sql = "SELECT nit_insttcion FROM insttciones 
                      WHERE (client_id = :client_id 
                         OR client_secret = :client_secret 
                         OR subscription_key = :subscription_key) 
                        AND nit_insttcion != :nit";
        $check_stid = oci_parse($this->conn, $check_sql);
        oci_bind_by_name($check_stid, ":client_id", $client_id);
        oci_bind_by_name($check_stid, ":client_secret", $client_secret);
        oci_bind_by_name($check_stid, ":subscription_key", $subscription_key);
        oci_bind_by_name($check_stid, ":nit", $nit_insttcion);
        oci_execute($check_stid);
        
        if (oci_fetch_array($check_stid, OCI_ASSOC)) {
            return ["status" => "error", "message" => "El client_id, client_secret o subscription_key proporcionados ya están siendo utilizados por otra institución."];
        }

        // Actualizar la institución con las credenciales del software
        $update_sql = "UPDATE insttciones 
                       SET client_id = :client_id, 
                           client_secret = :client_secret, 
                           scope = :scope, 
                           subscription_key = :subscription_key 
                       WHERE nit_insttcion = :nit";
        
        $update_stid = oci_parse($this->conn, $update_sql);
        oci_bind_by_name($update_stid, ":client_id", $client_id);
        oci_bind_by_name($update_stid, ":client_secret", $client_secret);
        oci_bind_by_name($update_stid, ":scope", $scope);
        oci_bind_by_name($update_stid, ":subscription_key", $subscription_key);
        oci_bind_by_name($update_stid, ":nit", $nit_insttcion);
        
        if (oci_execute($update_stid)) {
            return ["status" => "success", "message" => "Datos de software actualizados exitosamente."];
        } else {
            $e = oci_error($update_stid);
            return ["status" => "error", "message" => "Error al actualizar los datos del software: " . $e['message']];
        }
    }
}
?>
