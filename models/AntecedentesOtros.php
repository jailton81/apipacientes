<?php
class AntecedentesOtros
{
    private $conn;
    private $table_name = "ANTECEDENTES_OTROS";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetInfoAntecedenteOtro($id_pcnte)
    {
        $sql = 'SELECT 
                    ao.ID,   
                    ao.ID_PCNTE,   
                    ao.QUIRURGICOS,   
                    ao.TRANSFUSIONES,   
                    ao.TRAUMATICOS,   
                    ao.TOXICOS,   
                    ao.ETS,   
                    ao.GINECOOBSTETRAS,   
                    ao.FECHA_INGRESO,   
                    ao.USUARIO_INGRESO  
                FROM "ANTECEDENTES_OTROS" ao  
                WHERE ao.ID_PCNTE = :s_id';

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":s_id", $id_pcnte);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error extrayendo datos: " . $e['message']
            ];
        }

        $antecedentes = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $antecedentes[] = [
                "id" => $row['ID'],
                "id_pcnte" => $row['ID_PCNTE'],
                "quirurgicos" => $row['QUIRURGICOS'],
                "transfusiones" => $row['TRANSFUSIONES'],
                "traumaticos" => $row['TRAUMATICOS'],
                "toxicos" => $row['TOXICOS'],
                "ets" => $row['ETS'],
                "ginecoobstetras" => $row['GINECOOBSTETRAS'],
                "fecha_ingreso" => $row['FECHA_INGRESO'],
                "usuario_ingreso" => $row['USUARIO_INGRESO']
            ];
        }
        oci_free_statement($stid);

        return [
            "status" => "success",
            "count" => count($antecedentes),
            "data" => $antecedentes
        ];
    }

    public function saveAntecedenteOtro($data)
    {
        $sql = 'MERGE INTO "' . $this->table_name . '" dest
                USING (SELECT :id_pcnte AS ID_PCNTE FROM dual) src
                ON (dest.ID_PCNTE = src.ID_PCNTE)
                WHEN MATCHED THEN
                    UPDATE SET 
                        dest.QUIRURGICOS = :quirurgicos,
                        dest.TRANSFUSIONES = :transfusiones,
                        dest.TRAUMATICOS = :traumaticos,
                        dest.TOXICOS = :toxicos,
                        dest.ETS = :ets,
                        dest.GINECOOBSTETRAS = :ginecoobstetras,
                        dest.FECHA_INGRESO = SYSDATE,
                        dest.USUARIO_INGRESO = :usuario_ingreso
                WHEN NOT MATCHED THEN
                    INSERT (
                        "ID", "ID_PCNTE", "QUIRURGICOS", "TRANSFUSIONES", "TRAUMATICOS", 
                        "TOXICOS", "ETS", "GINECOOBSTETRAS", "FECHA_INGRESO", "USUARIO_INGRESO"
                    ) VALUES (
                        (SELECT COALESCE(MAX("ID"), 0) + 1 FROM "' . $this->table_name . '"),
                        :id_pcnte, :quirurgicos, :transfusiones, :traumaticos, 
                        :toxicos, :ets, :ginecoobstetras, SYSDATE, :usuario_ingreso
                    )';

        $stid = oci_parse($this->conn, $sql);

        // Bind parameters
        $id_pcnte = !empty($data->id_pcnte) ? $data->id_pcnte : null;
        $quirurgicos = isset($data->quirurgicos) ? $data->quirurgicos : null;
        $transfusiones = isset($data->transfusiones) ? $data->transfusiones : null;
        $traumaticos = isset($data->traumaticos) ? $data->traumaticos : null;
        $toxicos = isset($data->toxicos) ? $data->toxicos : null;
        $ets = isset($data->ets) ? $data->ets : null;
        $ginecoobstetras = isset($data->ginecoobstetras) ? $data->ginecoobstetras : null;
        $usuario_ingreso = !empty($data->usuario_ingreso) ? $data->usuario_ingreso : 'SYSTEM';

        oci_bind_by_name($stid, ":id_pcnte", $id_pcnte);
        oci_bind_by_name($stid, ":quirurgicos", $quirurgicos);
        oci_bind_by_name($stid, ":transfusiones", $transfusiones);
        oci_bind_by_name($stid, ":traumaticos", $traumaticos);
        oci_bind_by_name($stid, ":toxicos", $toxicos);
        oci_bind_by_name($stid, ":ets", $ets);
        oci_bind_by_name($stid, ":ginecoobstetras", $ginecoobstetras);
        oci_bind_by_name($stid, ":usuario_ingreso", $usuario_ingreso);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            return [
                "status" => "error",
                "message" => "Error al guardar antecedentes: " . $e['message']
            ];
        }

        oci_free_statement($stid);

        return [
            "status" => "success",
            "message" => "Otros antecedentes guardados correctamente."
        ];
    }
}
?>
