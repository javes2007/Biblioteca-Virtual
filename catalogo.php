<?php
require_once 'seguridad.php';
if (logueado()) { exigir_login(); }
$cn = db();
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$categoria = (int)(isset($_GET['categoria']) ? $_GET['categoria'] : 0);
$autor = (int)(isset($_GET['autor']) ? $_GET['autor'] : 0);
$letra = strtoupper(trim(isset($_GET['letra']) ? $_GET['letra'] : ''));
if (!preg_match('/^([A-Z]|0)$/', $letra)) { $letra = ''; }
$solo_disp = isset($_GET['disp']) ? 1 : 0;
$solo_leer = isset($_GET['leer']) ? 1 : 0;
$ordenes = array(
    'titulo_asc' => 'l.titulo ASC',
    'titulo_desc' => 'l.titulo DESC',
    'autor' => 'a.nombre ASC, l.titulo ASC',
    'anio_desc' => 'l.anio DESC, l.titulo ASC',
    'anio_asc' => 'l.anio ASC, l.titulo ASC',
    'disp_desc' => 'disponibles DESC, l.titulo ASC'
);
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'titulo_asc';
if (!isset($ordenes[$orden])) { $orden = 'titulo_asc'; }
$expr_disp = sql_disponibles('l');
$donde = ' WHERE 1=1';
$tipos = '';
$params = array();
if ($q !== '') {
    $donde .= ' AND (l.titulo LIKE ? OR a.nombre LIKE ? OR l.codigo LIKE ? OR l.editorial LIKE ?)';
    $like = '%' . $q . '%';
    $tipos .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if ($categoria > 0) { $donde .= ' AND l.categoria_id = ?'; $tipos .= 'i'; $params[] = $categoria; }
if ($autor > 0) { $donde .= ' AND l.autor_id = ?'; $tipos .= 'i'; $params[] = $autor; }
if ($letra === '0') {
    $donde .= " AND l.titulo REGEXP '^[0-9]'";
} elseif ($letra !== '') {
    $donde .= ' AND l.titulo LIKE ?';
    $tipos .= 's';
    $params[] = $letra . '%';
}
if ($solo_disp) { $donde .= ' AND ' . $expr_disp . ' > 0'; }
if ($solo_leer) { $donde .= ' AND ' . sql_leible('l'); }
$desde = ' FROM libros l JOIN autores a ON a.id = l.autor_id JOIN categorias c ON c.id = l.categoria_id';
$total = (int)valor($cn, 'SELECT COUNT(*)' . $desde . $donde, $tipos, $params);
$por_pagina = 12;
$paginas = max(1, (int)ceil($total / $por_pagina));
$pagina = max(1, min($paginas, (int)(isset($_GET['pagina']) ? $_GET['pagina'] : 1)));
$libros = consulta($cn, 'SELECT l.id, l.titulo, l.anio, l.ejemplares, l.categoria_id, c.nombre AS categoria, a.nombre AS autor, ' . $expr_disp . ' AS disponibles, ' . sql_extra_tarjeta() . $desde . $donde . ' ORDER BY ' . $ordenes[$orden] . ' LIMIT ' . $por_pagina . ' OFFSET ' . (($pagina - 1) * $por_pagina), $tipos, $params);
$cats = consulta($cn, 'SELECT c.id, c.nombre, COUNT(l.id) AS total FROM categorias c LEFT JOIN libros l ON l.categoria_id = c.id GROUP BY c.id, c.nombre ORDER BY c.nombre');
$autores = consulta($cn, 'SELECT id, nombre FROM autores ORDER BY nombre');
$base = $_GET;
unset($base['pagina']);
$enlace = function ($extra) use ($base) {
    return 'catalogo.php?' . http_build_query(array_merge($base, $extra));
};
$titulo = 'Catálogo';
require 'cabecera.php';
?>
<h1>Catálogo de libros</h1>
<form class="panel" method="get" action="catalogo.php">
  <div class="filtros">
    <div><label for="q">Buscar</label><input id="q" type="search" name="q" value="<?php echo e($q); ?>" placeholder="Título, autor, código o editorial"></div>
    <div><label for="categoria">Categoría</label>
      <select id="categoria" name="categoria"><option value="0">Todas</option>
        <?php foreach ($cats as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $categoria === (int)$c['id'] ? 'selected' : ''; ?>><?php echo e($c['nombre']); ?> (<?php echo (int)$c['total']; ?>)</option><?php endforeach; ?>
      </select></div>
    <div><label for="autor">Autor</label>
      <select id="autor" name="autor"><option value="0">Todos</option>
        <?php foreach ($autores as $a): ?><option value="<?php echo (int)$a['id']; ?>" <?php echo $autor === (int)$a['id'] ? 'selected' : ''; ?>><?php echo e($a['nombre']); ?></option><?php endforeach; ?>
      </select></div>
    <div><label for="orden">Ordenar por</label>
      <select id="orden" name="orden">
        <option value="titulo_asc" <?php echo $orden === 'titulo_asc' ? 'selected' : ''; ?>>Título (A-Z)</option>
        <option value="titulo_desc" <?php echo $orden === 'titulo_desc' ? 'selected' : ''; ?>>Título (Z-A)</option>
        <option value="autor" <?php echo $orden === 'autor' ? 'selected' : ''; ?>>Autor</option>
        <option value="anio_desc" <?php echo $orden === 'anio_desc' ? 'selected' : ''; ?>>Año (más recientes)</option>
        <option value="anio_asc" <?php echo $orden === 'anio_asc' ? 'selected' : ''; ?>>Año (más antiguos)</option>
        <option value="disp_desc" <?php echo $orden === 'disp_desc' ? 'selected' : ''; ?>>Más ejemplares disponibles</option>
      </select></div>
    <div><label><input type="checkbox" name="leer" value="1" <?php echo $solo_leer ? 'checked' : ''; ?>> Se puede leer en línea</label>
      <label><input type="checkbox" name="disp" value="1" <?php echo $solo_disp ? 'checked' : ''; ?>> Con ejemplares para préstamo</label>
      <button class="btn" type="submit" style="margin-top:6px"><?php echo icono('buscar', 16); ?> Buscar</button></div>
  </div>
  <?php if ($letra !== ''): ?><input type="hidden" name="letra" value="<?php echo e($letra); ?>"><?php endif; ?>
</form>

<div class="alfabeto" aria-label="Índice alfabético">
  <a class="<?php echo $letra === '' ? 'activo' : ''; ?>" href="<?php echo e($enlace(array('letra' => ''))); ?>">Todos</a>
  <a class="<?php echo $letra === '0' ? 'activo' : ''; ?>" href="<?php echo e($enlace(array('letra' => '0'))); ?>">0-9</a>
  <?php foreach (range('A', 'Z') as $L): ?><a class="<?php echo $letra === $L ? 'activo' : ''; ?>" href="<?php echo e($enlace(array('letra' => $L))); ?>"><?php echo $L; ?></a><?php endforeach; ?>
</div>

<p><strong><?php echo $total; ?></strong> libro(s) encontrado(s)<?php echo ($q !== '' || $categoria || $autor || $letra !== '' || $solo_disp || $solo_leer) ? ' · <a href="catalogo.php">Limpiar filtros</a>' : ''; ?></p>

<?php if (!count($libros)): ?>
  <div class="vacio">No se encontraron libros con esos criterios.</div>
<?php else: ?>
  <div class="rejilla">
    <?php foreach ($libros as $l) { echo tarjeta_libro($l); } ?>
  </div>
  <?php if ($paginas > 1): ?>
  <nav class="paginacion" aria-label="Paginación">
    <?php if ($pagina > 1): ?><a href="<?php echo e($enlace(array('pagina' => $pagina - 1))); ?>">‹ Anterior</a><?php endif; ?>
    <?php for ($i = 1; $i <= $paginas; $i++): ?>
      <?php if ($i === $pagina): ?><span class="actual"><?php echo $i; ?></span><?php else: ?><a href="<?php echo e($enlace(array('pagina' => $i))); ?>"><?php echo $i; ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($pagina < $paginas): ?><a href="<?php echo e($enlace(array('pagina' => $pagina + 1))); ?>">Siguiente ›</a><?php endif; ?>
  </nav>
  <?php endif; ?>
<?php endif; ?>
<?php require 'pie.php'; ?>
