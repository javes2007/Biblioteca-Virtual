<?php
date_default_timezone_set('America/Bogota');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$MOSTRAR_ERRORES_BD = false;

$servidor = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
if ($servidor === 'localhost' || $servidor === '127.0.0.1') {
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = '';
    $DB_NAME = 'biblioteca_virtual';
} else {
    $DB_HOST = 'sqlXXX.infinityfree.com';
    $DB_USER = 'ifXXXXXXXX';
    $DB_PASS = 'CLAVE_DE_LA_BASE';
    $DB_NAME = 'ifXXXXXXXX_biblioteca';
}

if ($MOSTRAR_ERRORES_BD) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

function db() {
    static $cn = null;
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    if ($cn === null) {
        $cn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        $cn->set_charset('utf8mb4');
        $cn->query("SET time_zone = '-05:00'");
    }
    return $cn;
}

set_exception_handler(function ($ex) {
    global $MOSTRAR_ERRORES_BD;
    if (!headers_sent()) { http_response_code(500); }
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ocurrió un problema</title></head><body style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:0 18px">';
    echo '<h1>Ocurrió un problema</h1><p>No se pudo completar la solicitud. Inténtalo de nuevo en unos minutos.</p>';
    if (!empty($MOSTRAR_ERRORES_BD)) { echo '<pre style="white-space:pre-wrap;background:#f6f6f6;padding:12px">' . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>'; }
    echo '<p><a href="index.php">Volver al inicio</a></p></body></html>';
});
