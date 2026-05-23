<?php
require_once __DIR__ . '/config/Database.php';

$database = new Database();
$conn = $database->getConnection();

$queries = [
    "CREATE TABLE APP_USERS (
        ID NUMBER,
        NAME VARCHAR2(255) NOT NULL,
        EMAIL VARCHAR2(255) NOT NULL,
        PASSWORD VARCHAR2(255) NOT NULL,
        CONSTRAINT pk_app_users PRIMARY KEY (ID),
        CONSTRAINT uq_app_users_email UNIQUE (EMAIL)
    )",
    "CREATE SEQUENCE seq_app_users START WITH 1 INCREMENT BY 1",
    "CREATE OR REPLACE TRIGGER trg_app_users
    BEFORE INSERT ON APP_USERS
    FOR EACH ROW
    BEGIN
        SELECT seq_app_users.NEXTVAL INTO :new.ID FROM dual;
    END;"
];

foreach ($queries as $sql) {
    $stid = oci_parse($conn, $sql);
    if (oci_execute($stid)) {
        echo "Successfully executed query.\n";
    } else {
        $e = oci_error($stid);
        echo "Error: " . $e['message'] . "\n";
    }
    oci_free_statement($stid);
}

$database->closeConnection();
?>
