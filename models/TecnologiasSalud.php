<?php
class TecnologiasSalud
{
    private $conn;
    private $table_name = "tecnologias_salud";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Busca tecnologías de salud activas por coincidencia parcial en código o nombre.
     * Opcionalmente filtra por grupo de filtro y limita los resultados a 15 filas.
     *
     * @param string $query Texto a buscar en código o nombre.
     * @param string|null $grupo Filtro por grupo (CONSULTA, PROCEDIMIENTO, IMAGEN, LABORATORIO, TECNOLOGIA).
     * @return array
     */
    public function buscar($query, $grupo = null)
    {
        $sql = "SELECT codigo, nombre, grupo_filtro, categoria_fhir, requiere_cantidad, requiere_frecuencia, estado
                FROM tecnologias_salud
                WHERE LOWER(estado) = 'activo'
                  AND (LOWER(nombre) LIKE LOWER(:query || '%') OR codigo LIKE :query || '%')
                  AND (:grupo IS NULL OR :grupo = 'TODOS' OR grupo_filtro = :grupo)
                FETCH FIRST 15 ROWS ONLY";
        
        // Use ROWNUM fallback inside query structure for 11g compatibility
        $sql = "SELECT codigo, nombre, grupo_filtro, categoria_fhir, tipo_tecnologia, requiere_cantidad, requiere_frecuencia, estado, observaciones
                FROM tecnologias_salud
                WHERE LOWER(estado) = 'activo'
                  AND (LOWER(nombre) LIKE '%' || LOWER(:query) || '%' OR codigo LIKE :query || '%')
                  AND (:grupo IS NULL OR :grupo = 'TODOS' OR grupo_filtro = :grupo)
                  AND ROWNUM <= 15";

        $stid = oci_parse($this->conn, $sql);

        oci_bind_by_name($stid, ":query", $query);
        oci_bind_by_name($stid, ":grupo", $grupo);

        if (oci_execute($stid)) {
            $results = [];
            while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
                $rawCat = strtolower($row['CATEGORIA_FHIR'] ?? '');
                $catFhir = ($rawCat === 'procedimientos' || $rawCat === 'procedimiento' || $rawCat === 'consulta' || $rawCat === 'consultas' || $rawCat === 'procedure') ? 'procedure' : 'technology';

                $results[] = [
                    "codigo" => $row['CODIGO'],
                    "nombre" => $row['NOMBRE'],
                    "grupo_filtro" => $row['GRUPO_FILTRO'],
                    "categoria_fhir" => $catFhir,
                    "tipo_tecnologia" => $row['TIPO_TECNOLOGIA'] ?? null,
                    "requiere_cantidad" => (isset($row['REQUIERE_CANTIDAD']) && (int)$row['REQUIERE_CANTIDAD'] === 1),
                    "requiere_frecuencia" => (isset($row['REQUIERE_FRECUENCIA']) && (int)$row['REQUIERE_FRECUENCIA'] === 1),
                    "estado" => $row['ESTADO'],
                    "observaciones" => $row['OBSERVACIONES'] ?? null
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
                "message" => "Error al buscar tecnologías de salud: " . $e['message']
            ];
        }
    }

    /**
     * Obtiene una tecnología de salud por su código de clave primaria única.
     *
     * @param string $codigo Clave primaria del registro.
     * @return array
     */
    public function obtenerPorCodigo($codigo)
    {
        $sql = "SELECT codigo, nombre, grupo_filtro, categoria_fhir, tipo_tecnologia, requiere_cantidad, requiere_frecuencia, estado, observaciones
                FROM tecnologias_salud
                WHERE codigo = :codigo";

        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ":codigo", $codigo);

        if (oci_execute($stid)) {
            $row = oci_fetch_array($stid, OCI_ASSOC);
            oci_free_statement($stid);

            if ($row) {
                $rawCat = strtolower($row['CATEGORIA_FHIR'] ?? '');
                $catFhir = ($rawCat === 'procedimientos' || $rawCat === 'procedimiento' || $rawCat === 'consulta' || $rawCat === 'consultas' || $rawCat === 'procedure') ? 'procedure' : 'technology';

                return [
                    "status" => "success",
                    "data" => [
                        "codigo" => $row['CODIGO'],
                        "nombre" => $row['NOMBRE'],
                        "grupo_filtro" => $row['GRUPO_FILTRO'],
                        "categoria_fhir" => $catFhir,
                        "tipo_tecnologia" => $row['TIPO_TECNOLOGIA'] ?? null,
                        "requiere_cantidad" => (isset($row['REQUIERE_CANTIDAD']) && (int)$row['REQUIERE_CANTIDAD'] === 1),
                        "requiere_frecuencia" => (isset($row['REQUIERE_FRECUENCIA']) && (int)$row['REQUIERE_FRECUENCIA'] === 1),
                        "estado" => $row['ESTADO'],
                        "observaciones" => $row['OBSERVACIONES'] ?? null
                    ]
                ];
            } else {
                return [
                    "status" => "error",
                    "message" => "Tecnología de salud no encontrada para el código especificado."
                ];
            }
        } else {
            $e = oci_error($stid);
            oci_free_statement($stid);
            return [
                "status" => "error",
                "message" => "Error al consultar la tecnología de salud: " . $e['message']
            ];
        }
    }
}
?>
