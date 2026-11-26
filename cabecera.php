<?php
if (!isset($titulo)) { $titulo = 'Biblioteca Virtual'; }
$cab_cn = db();
$cab_cfg = config_sitio();
$cab_usuario = usuario_actual();
$cab_tema = ($cab_usuario && in_array($cab_usuario['tema'], array('auto', 'claro', 'oscuro'), true)) ? $cab_usuario['tema'] : 'auto';
$cab_pagina = basename($_SERVER['PHP_SELF']);
$cab_vencidos = 0;
$cab_solicitudes = 0;
$cab_edad = 0;
$cab_sugerencias = 0;
if (es_admin()) {
    $cab_vencidos = contar_vencidos($cab_cn);
    $cab_solicitudes = contar_solicitudes($cab_cn);
    $cab_edad = (int)valor($cab_cn, "SELECT COUNT(*) FROM cambios_edad WHERE estado = 'Pendiente'");
    $cab_sugerencias = (int)valor($cab_cn, "SELECT COUNT(*) FROM sugerencias WHERE estado = 'Pendiente'");
} elseif (es_lector()) {
    $cab_vencidos = (int)valor($cab_cn, "SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado = 'Activo' AND fecha_limite < CURDATE()", 'i', array((int)$_SESSION['usuario_id']));
}
$cab_menu = array();
if (es_admin()) {
    $cab_menu = array(
        array('panel.php', 'Panel', 'inicio', array('panel.php'), 0),
        array('libros.php', 'Libros', 'libro', array('libros.php', 'libro_form.php', 'capitulos.php', 'capitulo_form.php'), 0),
        array('categorias.php', 'Categorías', 'etiqueta', array('categorias.php', 'categoria_form.php'), 0),
        array('autores.php', 'Autores', 'pluma', array('autores.php', 'autor_form.php'), 0),
        array('usuarios.php', 'Usuarios', 'usuario', array('usuarios.php', 'usuario_form.php', 'solicitudes_edad.php'), $cab_edad),
        array('prestamos.php', 'Préstamos', 'prestamo', array('prestamos.php', 'prestamo_form.php'), $cab_vencidos + $cab_solicitudes),
        array('sugerencias.php', 'Sugerencias', 'idea', array('sugerencias.php'), $cab_sugerencias),
        array('informes.php', 'Informes', 'grafico', array('informes.php'), 0),
        array('apariencia.php', 'Apariencia', 'paleta', array('apariencia.php'), 0),
        array('catalogo.php', 'Catálogo', 'buscar', array('catalogo.php', 'libro.php', 'leer.php'), 0)
    );
} else {
    $cab_menu[] = array('index.php', 'Inicio', 'inicio', array('index.php'), 0);
    $cab_menu[] = array('catalogo.php', 'Catálogo', 'buscar', array('catalogo.php', 'libro.php'), 0);
    if (es_lector()) {
        $cab_menu[] = array('mi_biblioteca.php', 'Mi biblioteca', 'abierto', array('mi_biblioteca.php', 'leer.php'), 0);
        $cab_menu[] = array('mis_prestamos.php', 'Préstamos', 'prestamo', array('mis_prestamos.php'), $cab_vencidos);
        $cab_menu[] = array('sugerencias.php', 'Sugerencias', 'idea', array('sugerencias.php', 'sugerencia_form.php'), 0);
    }
}
$cab_estilo = ':root{';
if (color_valido($cab_cfg['color_primario'])) { $cab_estilo .= '--primario:' . $cab_cfg['color_primario'] . ';'; }
if (color_valido($cab_cfg['color_acento'])) { $cab_estilo .= '--acento:' . $cab_cfg['color_acento'] . ';'; }
if (color_valido($cab_cfg['color_fondo'])) { $cab_estilo .= '--fondo:' . $cab_cfg['color_fondo'] . ';'; }
if (color_valido($cab_cfg['color_fondo_oscuro'])) { $cab_estilo .= '--fondo-osc:' . $cab_cfg['color_fondo_oscuro'] . ';'; }
$cab_estilo .= '}' . limpiar_css($cab_cfg['css_extra']);
?>
<!DOCTYPE html>
<html lang="es" data-tema="<?php echo e($cab_tema); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<title><?php echo e($titulo); ?> · <?php echo e($cab_cfg['nombre_sitio']); ?></title>
<script>(function(){var t=<?php echo logueado() ? json_encode($cab_tema) : 'null'; ?>;if(!t){try{t=localStorage.getItem('bv_tema')||'auto';}catch(e){t='auto';}}document.documentElement.setAttribute('data-tema',t);})();</script>
<link rel="stylesheet" href="estilos.css">
<style id="estilo-sitio"><?php echo $cab_estilo; ?></style>
</head>
<body data-csrf="<?php echo e(csrf_token()); ?>" data-logueado="<?php echo logueado() ? '1' : '0'; ?>">
<header class="barra">
  <div class="barra-in">
    <a class="logo" href="<?php echo es_admin() ? 'panel.php' : 'index.php'; ?>"><?php echo icono('libro', 26); ?><span><?php echo e($cab_cfg['nombre_sitio']); ?></span></a>
    <button class="menu-boton" type="button" aria-label="Abrir menú" onclick="document.body.classList.toggle('menu-abierto')"><?php echo icono('menu', 24); ?></button>
    <nav class="menu">
      <?php foreach ($cab_menu as $cab_item): ?>
        <a class="<?php echo in_array($cab_pagina, $cab_item[3], true) ? 'activo' : ''; ?>" href="<?php echo e($cab_item[0]); ?>"><?php echo icono($cab_item[2], 16); ?> <?php echo e($cab_item[1]); ?><?php if ($cab_item[4] > 0): ?> <span class="globo"><?php echo (int)$cab_item[4]; ?></span><?php endif; ?></a>
      <?php endforeach; ?>
      <button class="enlace-menu" type="button" id="boton-tema" title="Cambiar entre modo automático, claro y oscuro" aria-label="Cambiar tema"><?php echo icono('luna', 16); ?> <span id="texto-tema">Tema</span></button>
      <?php if (logueado()): ?>
        <a class="<?php echo $cab_pagina === 'perfil.php' ? 'activo' : ''; ?>" href="perfil.php"><?php echo $cab_usuario ? avatar_html($cab_usuario, 26) : icono('usuario', 16); ?> <?php echo e($_SESSION['nombre']); ?></a>
        <a href="logout.php"><?php echo icono('salir', 16); ?> Salir</a>
      <?php else: ?>
        <a class="<?php echo $cab_pagina === 'login.php' ? 'activo' : ''; ?>" href="login.php">Ingresar</a>
        <a class="btn btn-sm" href="registro.php">Registrarse</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="contenedor">
<?php if (!empty($_SESSION['flash'])): ?>
  <div class="mensaje mensaje-<?php echo e($_SESSION['flash'][0]); ?>" role="status"><?php echo e($_SESSION['flash'][1]); ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
