<?php
require_once 'seguridad.php';
exigir_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirigir('prestamos.php'); }
validar_csrf();
$cn = db();
$yo = (int)$_SESSION['usuario_id'];
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
$volver = isset($_POST['volver']) ? $_POST['volver'] : 'prestamos.php';
if (!in_array($volver, array('prestamos.php', 'panel.php'), true)) { $volver = 'prestamos.php'; }
$p = fila($cn, 'SELECT id, libro_id, usuario_id, estado FROM prestamos WHERE id = ?', 'i', array($id));
if (!$p) {
    flash('error', 'El préstamo no existe.');
    redirigir($volver);
}
if ($accion === 'aprobar') {
    $dias = (int)(isset($_POST['dias']) ? $_POST['dias'] : 7);
    if ($dias < 1 || $dias > 30) { $dias = 7; }
    $l = fila($cn, 'SELECT ' . sql_disponibles('l') . ' AS disponibles FROM libros l WHERE l.id = ?', 'i', array((int)$p['libro_id']));
    if ($p['estado'] !== 'Solicitado') {
        flash('error', 'Esa solicitud ya fue atendida.');
    } elseif (!$l || (int)$l['disponibles'] < 1) {
        flash('error', 'No hay ejemplares disponibles para aprobar esta solicitud.');
    } else {
        ejecutar($cn, "UPDATE prestamos SET estado = 'Activo', fecha_prestamo = CURDATE(), fecha_limite = DATE_ADD(CURDATE(), INTERVAL ? DAY), registrado_por = ? WHERE id = ? AND estado = 'Solicitado'", 'iii', array($dias, $yo, $id));
        flash('ok', 'Solicitud aprobada. El libro quedó prestado por ' . $dias . ' día(s).');
    }
} elseif ($accion === 'rechazar') {
    $n = ejecutar($cn, "UPDATE prestamos SET estado = 'Rechazado', registrado_por = ? WHERE id = ? AND estado = 'Solicitado'", 'ii', array($yo, $id));
    flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Solicitud rechazada.' : 'Esa solicitud ya fue atendida.');
} elseif ($accion === 'devolver') {
    $n = ejecutar($cn, "UPDATE prestamos SET estado = 'Devuelto', fecha_devolucion = CURDATE() WHERE id = ? AND estado = 'Activo'", 'i', array($id));
    if ($n > 0) {
        flash('ok', 'Libro devuelto. Si quieres, ahora puedes prestarle otro libro a este usuario.');
        redirigir('prestamo_form.php?usuario_id=' . (int)$p['usuario_id']);
    }
    flash('error', 'Ese préstamo no está activo.');
} elseif ($accion === 'renovar') {
    $n = ejecutar($cn, "UPDATE prestamos SET fecha_limite = DATE_ADD(GREATEST(fecha_limite, CURDATE()), INTERVAL 7 DAY) WHERE id = ? AND estado = 'Activo'", 'i', array($id));
    flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Préstamo renovado por 7 días más.' : 'Ese préstamo no está activo.');
} elseif ($accion === 'eliminar') {
    $n = ejecutar($cn, 'DELETE FROM prestamos WHERE id = ?', 'i', array($id));
    flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Préstamo eliminado.' : 'No se pudo eliminar el préstamo.');
} else {
    flash('error', 'Acción no válida.');
}
redirigir($volver);
