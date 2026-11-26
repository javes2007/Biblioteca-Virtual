<?php
require_once 'seguridad.php';
exigir_login();
$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$l = fila(db(), 'SELECT archivo, titulo FROM libros WHERE id = ?', 'i', array($id));
if (!$l || !$l['archivo'] || !preg_match('/^[A-Za-z0-9_]+\.pdf$/', $l['archivo'])) {
    http_response_code(404);
    exit('Archivo no disponible.');
}
$ruta = ruta_uploads('libros') . '/' . $l['archivo'];
if (!is_file($ruta)) {
    http_response_code(404);
    exit('Archivo no disponible.');
}
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="libro-' . $id . '.pdf"');
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
readfile($ruta);
