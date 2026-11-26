<?php
require_once 'seguridad.php';
header('Content-Type: application/json; charset=utf-8');
if (!es_lector() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => false, 'mensaje' => 'Solo los lectores pueden usar esta función.'));
    exit;
}
validar_csrf();
$cn = db();
$uid = (int)$_SESSION['usuario_id'];
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
if ($accion === 'resaltar') {
    $cap = (int)(isset($_POST['capitulo_id']) ? $_POST['capitulo_id'] : 0);
    $inicio = (int)(isset($_POST['inicio']) ? $_POST['inicio'] : -1);
    $fin = (int)(isset($_POST['fin']) ? $_POST['fin'] : -1);
    $texto = mb_substr(trim(isset($_POST['texto']) ? $_POST['texto'] : ''), 0, 300);
    $color = isset($_POST['color']) ? $_POST['color'] : '';
    if (!in_array($color, array('amarillo', 'verde', 'rosa', 'azul'), true) || $inicio < 0 || $fin <= $inicio || ($fin - $inicio) > 2000 || $texto === '') {
        echo json_encode(array('ok' => false, 'mensaje' => 'Selección no válida.'));
        exit;
    }
    if ((int)valor($cn, 'SELECT COUNT(*) FROM capitulos WHERE id = ?', 'i', array($cap)) === 0) {
        echo json_encode(array('ok' => false, 'mensaje' => 'El capítulo no existe.'));
        exit;
    }
    if ((int)valor($cn, 'SELECT COUNT(*) FROM resaltados WHERE usuario_id = ? AND capitulo_id = ?', 'ii', array($uid, $cap)) >= 200) {
        echo json_encode(array('ok' => false, 'mensaje' => 'Llegaste al máximo de resaltados en este capítulo.'));
        exit;
    }
    ejecutar($cn, 'INSERT INTO resaltados (usuario_id, capitulo_id, inicio, fin, texto, color) VALUES (?, ?, ?, ?, ?, ?)', 'iiiiss', array($uid, $cap, $inicio, $fin, $texto, $color));
    echo json_encode(array('ok' => true, 'id' => (int)$cn->insert_id));
} elseif ($accion === 'borrar_resaltado') {
    $rid = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $n = ejecutar($cn, 'DELETE FROM resaltados WHERE id = ? AND usuario_id = ?', 'ii', array($rid, $uid));
    echo json_encode(array('ok' => $n > 0));
} elseif ($accion === 'marcador') {
    $cap = (int)(isset($_POST['capitulo_id']) ? $_POST['capitulo_id'] : 0);
    if ((int)valor($cn, 'SELECT COUNT(*) FROM capitulos WHERE id = ?', 'i', array($cap)) === 0) {
        echo json_encode(array('ok' => false));
        exit;
    }
    $n = ejecutar($cn, 'DELETE FROM marcadores WHERE usuario_id = ? AND capitulo_id = ?', 'ii', array($uid, $cap));
    if ($n > 0) {
        echo json_encode(array('ok' => true, 'activo' => false));
    } else {
        ejecutar($cn, 'INSERT INTO marcadores (usuario_id, capitulo_id) VALUES (?, ?)', 'ii', array($uid, $cap));
        echo json_encode(array('ok' => true, 'activo' => true));
    }
} elseif ($accion === 'terminar') {
    $lid = (int)(isset($_POST['libro_id']) ? $_POST['libro_id'] : 0);
    if ((int)valor($cn, 'SELECT COUNT(*) FROM libros WHERE id = ?', 'i', array($lid)) === 0) {
        echo json_encode(array('ok' => false));
        exit;
    }
    ejecutar($cn, 'INSERT INTO lecturas (usuario_id, libro_id, capitulo_orden, progreso, terminado) VALUES (?, ?, 1, 100, 1) ON DUPLICATE KEY UPDATE progreso = 100, terminado = 1, actualizado_en = NOW()', 'ii', array($uid, $lid));
    echo json_encode(array('ok' => true));
} else {
    echo json_encode(array('ok' => false, 'mensaje' => 'Acción no válida.'));
}
