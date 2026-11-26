<?php
require_once 'seguridad.php';
exigir_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirigir('catalogo.php'); }
validar_csrf();
if (!es_lector()) {
    flash('error', 'Solo los lectores pueden solicitar préstamos.');
    redirigir('catalogo.php');
}
$cn = db();
$uid = (int)$_SESSION['usuario_id'];
$libro_id = (int)(isset($_POST['libro_id']) ? $_POST['libro_id'] : 0);
$l = fila($cn, 'SELECT l.id, l.titulo, ' . sql_disponibles('l') . ' AS disponibles FROM libros l WHERE l.id = ?', 'i', array($libro_id));
if (!$l) {
    flash('error', 'El libro no existe.');
    redirigir('catalogo.php');
}
$vencidos = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado = 'Activo' AND fecha_limite < CURDATE()", 'i', array($uid));
$abiertos = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado IN ('Solicitado','Activo')", 'i', array($uid));
$repetido = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND libro_id = ? AND estado IN ('Solicitado','Activo')", 'ii', array($uid, $libro_id));
if ((int)$l['disponibles'] < 1) {
    flash('error', 'Ese libro no tiene ejemplares disponibles.');
} elseif ($vencidos > 0) {
    flash('error', 'Tienes préstamos vencidos. Devuélvelos antes de pedir otro libro.');
} elseif ($repetido > 0) {
    flash('aviso', 'Ya tienes una solicitud o un préstamo de este libro.');
} elseif ($abiertos >= MAX_PRESTAMOS) {
    flash('aviso', 'Puedes tener máximo ' . MAX_PRESTAMOS . ' libros entre solicitudes y préstamos.');
} else {
    ejecutar($cn, "INSERT INTO prestamos (libro_id, usuario_id, estado) VALUES (?, ?, 'Solicitado')", 'ii', array($libro_id, $uid));
    flash('ok', 'Solicitaste "' . $l['titulo'] . '". La biblioteca la revisará pronto.');
    redirigir('mis_prestamos.php');
}
redirigir('libro.php?id=' . $libro_id);
