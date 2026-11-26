<?php
require_once 'seguridad.php';
header('Content-Type: application/json; charset=utf-8');
if (!logueado() || $_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(array('ok' => false)); exit; }
validar_csrf();
$tema = isset($_POST['tema']) ? $_POST['tema'] : '';
if (!in_array($tema, array('auto', 'claro', 'oscuro'), true)) { echo json_encode(array('ok' => false)); exit; }
ejecutar(db(), 'UPDATE usuarios SET tema = ? WHERE id = ?', 'si', array($tema, (int)$_SESSION['usuario_id']));
echo json_encode(array('ok' => true));
