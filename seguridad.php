<?php
require_once __DIR__ . '/conectar.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    if (!empty($_SERVER['HTTPS'])) { ini_set('session.cookie_secure', '1'); }
    session_start();
}

define('MAX_PRESTAMOS', 3);

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirigir($url) {
    header('Location: ' . $url);
    exit;
}

function flash($tipo, $texto) {
    $_SESSION['flash'] = array($tipo, $texto);
}

function logueado() {
    return !empty($_SESSION['usuario_id']);
}

function es_admin() {
    return logueado() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function es_lector() {
    return logueado() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'lector';
}

function es_bibliotecario() {
    return logueado() && isset($_SESSION['rol']) && $_SESSION['rol'] === 'bibliotecario';
}

function es_gestor_prestamos() {
    return es_admin() || es_bibliotecario();
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function campo_csrf() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function validar_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = isset($_POST['csrf']) ? $_POST['csrf'] : '';
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
            http_response_code(403);
            exit('El formulario venció. Vuelve atrás y recarga la página.');
        }
    }
}

function consulta($cn, $sql, $tipos = '', $params = array()) {
    $st = $cn->prepare($sql);
    if ($tipos !== '') {
        $args = array($tipos);
        foreach ($params as $k => $v) { $args[] = &$params[$k]; }
        call_user_func_array(array($st, 'bind_param'), $args);
    }
    $st->execute();
    $filas = array();
    $meta = $st->result_metadata();
    if ($meta) {
        if (method_exists($st, 'get_result')) {
            $r = $st->get_result();
            while ($f = $r->fetch_assoc()) { $filas[] = $f; }
        } else {
            $campos = array();
            $fila = array();
            while ($c = $meta->fetch_field()) { $campos[] = &$fila[$c->name]; }
            call_user_func_array(array($st, 'bind_result'), $campos);
            while ($st->fetch()) {
                $copia = array();
                foreach ($fila as $k => $v) { $copia[$k] = $v; }
                $filas[] = $copia;
            }
        }
        $meta->free();
    }
    $st->close();
    return $filas;
}

function fila($cn, $sql, $tipos = '', $params = array()) {
    $f = consulta($cn, $sql, $tipos, $params);
    return $f ? $f[0] : null;
}

function valor($cn, $sql, $tipos = '', $params = array()) {
    $f = fila($cn, $sql, $tipos, $params);
    if (!$f) { return null; }
    return reset($f);
}

function ejecutar($cn, $sql, $tipos = '', $params = array()) {
    $st = $cn->prepare($sql);
    if ($tipos !== '') {
        $args = array($tipos);
        foreach ($params as $k => $v) { $args[] = &$params[$k]; }
        call_user_func_array(array($st, 'bind_param'), $args);
    }
    $st->execute();
    $afectadas = $st->affected_rows;
    $st->close();
    return $afectadas;
}

function usuario_actual() {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (logueado()) {
            $u = fila(db(), 'SELECT id, nombre, apellido, rol, activo, foto, tema, cambiar_clave FROM usuarios WHERE id = ?', 'i', array((int)$_SESSION['usuario_id']));
        }
    }
    return $u;
}

function exigir_login() {
    if (!logueado()) {
        flash('error', 'Inicia sesión para continuar.');
        redirigir('login.php');
    }
    $u = usuario_actual();
    if (!$u || (int)$u['activo'] !== 1) {
        $_SESSION = array();
        session_regenerate_id(true);
        flash('error', 'Tu cuenta no está activa.');
        redirigir('login.php');
    }
    $_SESSION['rol'] = $u['rol'];
    $_SESSION['nombre'] = $u['nombre'];
}

function exigir_admin() {
    exigir_login();
    if (!es_admin()) {
        flash('error', 'No tienes permiso para entrar a esa página.');
        redirigir('index.php');
    }
}

function exigir_lector() {
    exigir_login();
    if (!es_lector()) {
        flash('error', 'Esa sección es solo para lectores.');
        redirigir('index.php');
    }
}

function exigir_gestor_prestamos() {
    exigir_login();
    if (!es_gestor_prestamos()) {
        flash('error', 'No tienes permiso para gestionar préstamos.');
        redirigir('index.php');
    }
}

function config_sitio() {
    static $c = null;
    if ($c === null) {
        $c = array('nombre_sitio' => 'Biblioteca Virtual', 'color_primario' => '#1f5f46', 'color_acento' => '#c8963e', 'color_fondo' => '#f6f1e6', 'color_fondo_oscuro' => '#101a16', 'css_extra' => '');
        try {
            foreach (consulta(db(), 'SELECT clave, valor FROM configuracion') as $f) { $c[$f['clave']] = (string)$f['valor']; }
        } catch (Throwable $ex) {
        }
    }
    return $c;
}

function color_valido($c) {
    return (bool)preg_match('/^#[0-9a-fA-F]{6}$/', $c);
}

function limpiar_css($css) {
    $css = str_replace(array('<', '>'), '', (string)$css);
    $css = preg_replace('/@import[^;]*;?/i', '', $css);
    $css = preg_replace('/(javascript:|expression\s*\(|behavior\s*:|-moz-binding)/i', '', $css);
    return mb_substr($css, 0, 8000);
}

function fecha_es($f) {
    if (!$f) { return '—'; }
    return date('d/m/Y', strtotime($f));
}

function fecha_hora_es($f) {
    if (!$f) { return '—'; }
    return date('d/m/Y H:i', strtotime($f));
}

function fecha_valida($f) {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $f, $m)) { return false; }
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

function edad_desde($fecha) {
    try {
        $n = new DateTime($fecha);
        return (int)$n->diff(new DateTime('today'))->y;
    } catch (Exception $ex) {
        return 0;
    }
}

function nacimiento_valido($f) {
    if (!fecha_valida($f)) { return false; }
    $edad = edad_desde($f);
    return $f <= date('Y-m-d') && $edad >= 5 && $edad <= 120;
}

function telefono_valido($t) {
    return (bool)preg_match('/^[0-9 +()-]{7,20}$/', $t);
}

function telefono_enlace($t) {
    return 'tel:' . preg_replace('/[^0-9+]/', '', $t);
}

function sql_estado_real() {
    return "CASE WHEN p.estado = 'Activo' AND p.fecha_limite < CURDATE() THEN 'Vencido' ELSE p.estado END";
}

function sql_disponibles($alias = 'l') {
    return '(' . $alias . ".ejemplares - (SELECT COUNT(*) FROM prestamos pd WHERE pd.libro_id = " . $alias . ".id AND pd.estado = 'Activo'))";
}

function sql_promedio($alias = 'l') {
    return '(SELECT ROUND(AVG(rs.puntuacion), 1) FROM resenas rs WHERE rs.libro_id = ' . $alias . '.id)';
}

function sql_votos($alias = 'l') {
    return '(SELECT COUNT(*) FROM resenas rv WHERE rv.libro_id = ' . $alias . '.id)';
}

function sql_leible($alias = 'l') {
    return '((SELECT COUNT(*) FROM capitulos cp WHERE cp.libro_id = ' . $alias . '.id) > 0 OR ' . $alias . '.archivo IS NOT NULL)';
}

function sql_extra_tarjeta() {
    return sql_promedio('l') . ' AS promedio, ' . sql_votos('l') . ' AS votos, ' . sql_leible('l') . ' AS leible';
}

function clase_estado($estado) {
    $mapa = array('Solicitado' => 'solicitado', 'Activo' => 'activo', 'Vencido' => 'vencido', 'Devuelto' => 'devuelto', 'Rechazado' => 'rechazado', 'Pendiente' => 'solicitado', 'Aprobada' => 'devuelto', 'Rechazada' => 'rechazado', 'Adquirida' => 'activo', 'Usada' => 'rechazado');
    return 'est est-' . (isset($mapa[$estado]) ? $mapa[$estado] : 'activo');
}

function contar_vencidos($cn) {
    return (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE estado = 'Activo' AND fecha_limite < CURDATE()");
}

function contar_solicitudes($cn) {
    return (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE estado = 'Solicitado'");
}

function asegurar_carpeta($ruta, $reglas) {
    if (!is_dir($ruta)) { @mkdir($ruta, 0755, true); }
    $ht = $ruta . '/.htaccess';
    if (is_dir($ruta) && !file_exists($ht)) { @file_put_contents($ht, $reglas); }
}

function ruta_uploads($sub) {
    return __DIR__ . '/uploads/' . $sub;
}

function guardar_avatar($archivo, $uid) {
    if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return array(false, 'No se pudo subir la imagen. Revisa que pese menos de 2 MB.');
    }
    if ($archivo['size'] > 2 * 1024 * 1024) { return array(false, 'La imagen debe pesar menos de 2 MB.'); }
    $info = @getimagesize($archivo['tmp_name']);
    if (!$info) { return array(false, 'El archivo no es una imagen válida.'); }
    $permitidos = array(IMAGETYPE_JPEG, IMAGETYPE_PNG);
    if (defined('IMAGETYPE_WEBP')) { $permitidos[] = IMAGETYPE_WEBP; }
    if (!in_array($info[2], $permitidos, true)) { return array(false, 'Usa una imagen JPG, PNG o WEBP.'); }
    $dir = ruta_uploads('avatares');
    asegurar_carpeta($dir, "<FilesMatch \"\\.(php|phtml|phar|pl|cgi)$\">\n    Require all denied\n</FilesMatch>\n");
    if (!is_dir($dir)) { return array(false, 'No se pudo crear la carpeta de fotos en el servidor.'); }
    $nombre = 'u' . (int)$uid . '_' . bin2hex(random_bytes(6)) . '.jpg';
    $destino = $dir . '/' . $nombre;
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $origen = @imagecreatefromstring(file_get_contents($archivo['tmp_name']));
        if (!$origen) { return array(false, 'No se pudo procesar la imagen.'); }
        $ancho = imagesx($origen);
        $alto = imagesy($origen);
        $lado = min($ancho, $alto);
        $x = (int)(($ancho - $lado) / 2);
        $y = (int)(($alto - $lado) / 2);
        $lienzo = imagecreatetruecolor(320, 320);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        imagecopyresampled($lienzo, $origen, 0, 0, $x, $y, 320, 320, $lado, $lado);
        $ok = imagejpeg($lienzo, $destino, 85);
        imagedestroy($origen);
        imagedestroy($lienzo);
        if (!$ok) { return array(false, 'No se pudo guardar la imagen.'); }
    } else {
        if ($info[2] !== IMAGETYPE_JPEG) { return array(false, 'Este servidor solo acepta fotos JPG.'); }
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) { return array(false, 'No se pudo guardar la imagen.'); }
    }
    return array(true, $nombre);
}

function borrar_avatar($nombre) {
    if ($nombre && preg_match('/^[A-Za-z0-9_]+\.jpg$/', $nombre)) {
        $ruta = ruta_uploads('avatares') . '/' . $nombre;
        if (is_file($ruta)) { @unlink($ruta); }
    }
}

function guardar_pdf($archivo, $libro_id) {
    if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return array(false, 'No se pudo subir el archivo. Revisa el límite de tamaño de tu servidor.');
    }
    if ($archivo['size'] > 25 * 1024 * 1024) { return array(false, 'El PDF debe pesar menos de 25 MB.'); }
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $cabecera = (string)@file_get_contents($archivo['tmp_name'], false, null, 0, 5);
    if ($ext !== 'pdf' || $cabecera !== '%PDF-') { return array(false, 'El archivo no es un PDF válido.'); }
    $dir = ruta_uploads('libros');
    asegurar_carpeta($dir, "Require all denied\n");
    if (!is_dir($dir)) { return array(false, 'No se pudo crear la carpeta de libros en el servidor.'); }
    $nombre = 'l' . (int)$libro_id . '_' . bin2hex(random_bytes(8)) . '.pdf';
    if (!move_uploaded_file($archivo['tmp_name'], $dir . '/' . $nombre)) { return array(false, 'No se pudo guardar el PDF.'); }
    return array(true, $nombre);
}

function borrar_pdf($nombre) {
    if ($nombre && preg_match('/^[A-Za-z0-9_]+\.pdf$/', $nombre)) {
        $ruta = ruta_uploads('libros') . '/' . $nombre;
        if (is_file($ruta)) { @unlink($ruta); }
    }
}

function avatar_html($u, $tam = 34) {
    $nombre = isset($u['nombre']) ? $u['nombre'] : '?';
    $apellido = isset($u['apellido']) ? $u['apellido'] : '';
    if (!empty($u['foto']) && preg_match('/^[A-Za-z0-9_]+\.jpg$/', $u['foto'])) {
        return '<img class="avatar" src="uploads/avatares/' . e($u['foto']) . '" width="' . (int)$tam . '" height="' . (int)$tam . '" alt="Foto de perfil">';
    }
    $ini = mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1));
    return '<span class="avatar avatar-ini" style="width:' . (int)$tam . 'px;height:' . (int)$tam . 'px;font-size:' . (int)($tam * 0.4) . 'px">' . e($ini) . '</span>';
}

function estrellas($promedio, $votos = null) {
    $p = (float)$promedio;
    $html = '<span class="estrellas" title="' . ($votos === 0 ? 'Sin calificaciones' : number_format($p, 1) . ' de 5') . '">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($p >= $i - 0.25 ? 'llena' : ($p >= $i - 0.75 ? 'media' : 'vacia')) . '">★</span>';
    }
    if ($votos !== null) { $html .= ' <small>' . ($votos > 0 ? number_format($p, 1) . ' (' . (int)$votos . ')' : 'Sin votos') . '</small>'; }
    return $html . '</span>';
}

function icono($nombre, $tam = 18) {
    $p = array(
        'libro' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'abierto' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'usuario' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'reloj' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'alerta' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'buscar' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'telefono' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'campana' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'inicio' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'grafico' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'etiqueta' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'pluma' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>',
        'prestamo' => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        'salir' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'mas' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'imprimir' => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'descargar' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'borrar' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        'ojo' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'menu' => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'luna' => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
        'paleta' => '<circle cx="13.5" cy="6.5" r="1"/><circle cx="17.5" cy="10.5" r="1"/><circle cx="8.5" cy="7.5" r="1"/><circle cx="6.5" cy="12.5" r="1"/><path d="M12 2a10 10 0 1 0 0 20c1.1 0 1.5-.9 1.5-1.6 0-.5-.2-.8-.5-1.2-.3-.4-.5-.8-.5-1.3 0-1 .8-1.9 1.9-1.9H17a5 5 0 0 0 5-5c0-4.4-4.5-8-10-8z"/>',
        'idea' => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.2 1 2V17h6v-.3c0-.8.4-1.5 1-2A7 7 0 0 0 12 2z"/>',
        'marcador' => '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>',
        'pincel' => '<path d="M12 19l7-7 3 3-7 7z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18z"/><path d="M2 2l7.586 7.586"/>',
        'config' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'
    );
    $trazo = isset($p[$nombre]) ? $p[$nombre] : $p['libro'];
    return '<svg class="ico" width="' . (int)$tam . '" height="' . (int)$tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $trazo . '</svg>';
}

function iconos_ejemplares($disp, $total) {
    $disp = max(0, (int)$disp);
    $total = max(0, (int)$total);
    $mostrar = min($total, 10);
    $html = '<span class="ejemplares-iconos" title="' . $disp . ' de ' . $total . ' ejemplares físicos disponibles">';
    for ($i = 1; $i <= $mostrar; $i++) {
        $html .= '<span class="ej ' . ($i <= $disp ? 'on' : 'off') . '">' . icono('libro', 16) . '</span>';
    }
    if ($total > 10) { $html .= '<span class="ej-mas">+' . ($total - 10) . '</span>'; }
    return $html . '</span>';
}

function tarjeta_libro($l) {
    $disp = (int)$l['disponibles'];
    $total = (int)$l['ejemplares'];
    $matiz = ((int)$l['categoria_id'] * 47) % 360;
    $url = 'libro.php?id=' . (int)$l['id'];
    $leible = !empty($l['leible']);
    $h = '<article class="tarjeta-libro">';
    $h .= '<a class="portada" href="' . $url . '" style="--h:' . $matiz . '">' . icono('libro', 30) . '<span>' . e(mb_substr($l['titulo'], 0, 44)) . '</span></a>';
    $h .= '<div class="datos"><span class="cat">' . e($l['categoria']) . '</span>';
    $h .= '<h3><a href="' . $url . '">' . e($l['titulo']) . '</a></h3>';
    $h .= '<p class="autor">' . e($l['autor']) . '</p>';
    $h .= '<div class="valoracion">' . estrellas(isset($l['promedio']) ? $l['promedio'] : 0, isset($l['votos']) ? (int)$l['votos'] : 0) . '</div>';
    if ($total > 0) {
        $h .= '<div class="stock ' . ($disp > 0 ? 'hay' : 'agotado') . '">' . iconos_ejemplares($disp, $total) . '<strong>' . $disp . '/' . $total . '</strong> <small>disponibles</small></div>';
    } else {
        $h .= '<div class="stock hay"><small>Solo lectura en línea</small></div>';
    }
    $h .= '<div class="acciones-tarjeta">';
    if ($leible && logueado()) {
        $h .= '<a class="btn btn-sm" href="leer.php?id=' . (int)$l['id'] . '">' . icono('abierto', 16) . ' Leer ahora</a>';
    } elseif ($leible) {
        $h .= '<a class="btn btn-sm" href="login.php">' . icono('abierto', 16) . ' Ingresa para leer</a>';
    }
    if (es_lector() && $disp > 0) {
        $h .= '<form method="post" action="solicitar.php">' . campo_csrf() . '<input type="hidden" name="libro_id" value="' . (int)$l['id'] . '"><button class="btn btn-sm btn-claro" type="submit">' . icono('prestamo', 16) . ' Pedir préstamo</button></form>';
    }
    $h .= '</div>';
    return $h . '</div></article>';
}
