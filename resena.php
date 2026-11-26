<?php
require_once 'seguridad.php';
exigir_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirigir('catalogo.php'); }
validar_csrf();
$cn = db();
$libro_id = (int)(isset($_POST['libro_id']) ? $_POST['libro_id'] : 0);
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$existe = (int)valor($cn, 'SELECT COUNT(*) FROM libros WHERE id = ?', 'i', array($libro_id));
if (!$existe) {
    flash('error', 'El libro no existe.');
    redirigir('catalogo.php');
}
if ($accion === 'moderar' && es_admin()) {
    $rid = (int)(isset($_POST['resena_id']) ? $_POST['resena_id'] : 0);
    ejecutar($cn, 'DELETE FROM resenas WHERE id = ? AND libro_id = ?', 'ii', array($rid, $libro_id));
    flash('ok', 'Comentario eliminado.');
} elseif (!es_lector()) {
    flash('error', 'Solo los lectores pueden calificar libros.');
} elseif ($accion === 'guardar') {
    $p = (int)(isset($_POST['puntuacion']) ? $_POST['puntuacion'] : 0);
    $c = trim(isset($_POST['comentario']) ? $_POST['comentario'] : '');
    if ($p < 1 || $p > 5) {
        flash('error', 'Elige de 1 a 5 estrellas.');
    } elseif (mb_strlen($c) > 600) {
        flash('error', 'El comentario admite máximo 600 caracteres.');
    } else {
        ejecutar($cn, 'INSERT INTO resenas (libro_id, usuario_id, puntuacion, comentario) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE puntuacion = VALUES(puntuacion), comentario = VALUES(comentario)', 'iiis', array($libro_id, (int)$_SESSION['usuario_id'], $p, $c === '' ? null : $c));
        flash('ok', 'Tu reseña fue guardada. ¡Gracias por opinar!');
    }
} elseif ($accion === 'eliminar') {
    ejecutar($cn, 'DELETE FROM resenas WHERE libro_id = ? AND usuario_id = ?', 'ii', array($libro_id, (int)$_SESSION['usuario_id']));
    flash('ok', 'Tu reseña fue eliminada.');
}
redirigir('libro.php?id=' . $libro_id);
