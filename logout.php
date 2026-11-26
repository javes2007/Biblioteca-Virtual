<?php
require_once 'seguridad.php';
$_SESSION = array();
session_regenerate_id(true);
flash('ok', 'Sesión cerrada correctamente.');
redirigir('login.php');
