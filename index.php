<?php
require_once 'seguridad.php';
if (logueado()) { exigir_login(); }
if (es_admin()) { redirigir('panel.php'); }
$cn = db();
$tot_libros = (int)valor($cn, 'SELECT COUNT(*) FROM libros');
$tot_ejemplares = (int)valor($cn, 'SELECT COALESCE(SUM(ejemplares),0) FROM libros');
$tot_prestados = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE estado = 'Activo'");
$tot_categorias = (int)valor($cn, 'SELECT COUNT(*) FROM categorias');
$tot_leibles = (int)valor($cn, 'SELECT COUNT(*) FROM libros l WHERE ' . sql_leible('l'));
$categorias = consulta($cn, 'SELECT c.id, c.nombre, COUNT(l.id) AS total FROM categorias c LEFT JOIN libros l ON l.categoria_id = c.id GROUP BY c.id, c.nombre ORDER BY c.nombre');
$novedades = consulta($cn, 'SELECT l.id, l.titulo, l.ejemplares, l.categoria_id, c.nombre AS categoria, a.nombre AS autor, ' . sql_disponibles('l') . ' AS disponibles, ' . sql_extra_tarjeta() . ' FROM libros l JOIN autores a ON a.id = l.autor_id JOIN categorias c ON c.id = l.categoria_id ORDER BY l.creado_en DESC, l.id DESC LIMIT 8');
$titulo = 'Inicio';
require 'cabecera.php';
?>
<section class="hero">
  <h1>Tu biblioteca, abierta las 24 horas</h1>
  <p>Busca en el catálogo, lee libros completos en línea, resalta tus frases favoritas y pide préstamos desde cualquier dispositivo.</p>
  <form class="buscador-hero" method="get" action="catalogo.php">
    <input type="search" name="q" placeholder="Busca por título, autor, código o editorial" aria-label="Buscar libros">
    <button class="btn btn-oro" type="submit"><?php echo icono('buscar', 18); ?> Buscar</button>
  </form>
</section>

<section class="estadisticas">
  <div class="dato"><?php echo icono('libro', 30); ?><div><strong><?php echo $tot_libros; ?></strong><small>Títulos</small></div></div>
  <div class="dato"><?php echo icono('abierto', 30); ?><div><strong><?php echo $tot_leibles; ?></strong><small>Para leer en línea</small></div></div>
  <div class="dato"><?php echo icono('libro', 30); ?><div><strong><?php echo $tot_ejemplares; ?></strong><small>Ejemplares físicos</small></div></div>
  <div class="dato aviso"><?php echo icono('prestamo', 30); ?><div><strong><?php echo $tot_prestados; ?></strong><small>Prestados ahora</small></div></div>
  <div class="dato"><?php echo icono('etiqueta', 30); ?><div><strong><?php echo $tot_categorias; ?></strong><small>Categorías</small></div></div>
</section>

<div class="seccion-titulo"><h2>Explora por categoría</h2><a href="catalogo.php">Ver todo el catálogo →</a></div>
<div class="chips">
  <?php foreach ($categorias as $c): ?>
    <a class="chip" href="catalogo.php?categoria=<?php echo (int)$c['id']; ?>"><?php echo icono('etiqueta', 15); ?> <?php echo e($c['nombre']); ?> <small>(<?php echo (int)$c['total']; ?>)</small></a>
  <?php endforeach; ?>
</div>

<div class="seccion-titulo"><h2>Últimas novedades</h2><a href="catalogo.php?orden=titulo_asc">Orden alfabético →</a></div>
<?php if (!count($novedades)): ?>
  <div class="vacio">Aún no hay libros registrados.</div>
<?php else: ?>
  <div class="rejilla">
    <?php foreach ($novedades as $l) { echo tarjeta_libro($l); } ?>
  </div>
<?php endif; ?>

<?php if (!logueado()): ?>
<div class="panel" style="margin-top:34px;text-align:center">
  <h2>¿Quieres pedir libros prestados?</h2>
  <p>Crea tu cuenta gratis y solicita préstamos en línea.</p>
  <a class="btn" href="registro.php">Crear cuenta</a> <a class="btn btn-claro" href="login.php">Ya tengo cuenta</a>
</div>
<?php endif; ?>
<?php require 'pie.php'; ?>
