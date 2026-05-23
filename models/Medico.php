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
                                    idntfccion_mdcos, tpo_idntfccion, nmbres, espcldad, estdo, email, password
                                 ) VALUES (
                                    :id, :tpo, :nmbres, :espcldad, :estdo, :email, :password
                                 )";
            $insert_stid = oci_parse($this->conn, $insert_mdcos_sql);
            oci_bind_by_name($insert_stid, ":id", $idntfccion);
            oci_bind_by_name($insert_stid, ":tpo", $tpo_idntfccion);
            oci_bind_by_name($insert_stid, ":nmbres", $nmbres);
            oci_bind_by_name($insert_stid, ":espcldad", $espcldad);
            oci_bind_by_name($insert_stid, ":estdo", $estdo);
            oci_bind_by_name($insert_stid, ":email", $email);
            oci_bind_by_name($insert_stid, ":password", $password_hash);
            
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
}
?>