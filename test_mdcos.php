<?php
require_once __DIR__ . '/config/Database.php';
$database = new Database();
$db = $database->getConnection();

$sql = "SELECT * FROM mdcos WHERE ROWNUM <= 1";
$stid = oci_parse($db, $sql);
oci_execute($stid);
$row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);

echo json_encode(array_keys((array)$row));
?>
