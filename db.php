<?php
// db.php
declare(strict_types=1);

$DB_HOST = 'mysql.seguridadappumg.space';
$DB_NAME = 'usuarios_seguridad';
$DB_USER = 'adminhic';    // usuario con permisos mínimos (SELECT, INSERT, UPDATE)
$DB_PASS = '6TFWbckg&N';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // En producción no devolver $e->getMessage()
    error_log($e->getMessage());
    http_response_code(500);
    exit('Error interno');
}
