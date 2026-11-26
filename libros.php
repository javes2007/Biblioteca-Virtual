<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $libro = fila($cn, 'SELECT archivo FROM libros WHERE id = ?', 'i', array($id));
    $abiertos = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND estado IN ('Solicitado','Activo')", 'i', array($id));
    if (!$libro) {
        flash('error', 'El libro no existe.');
    } elseif ($abiertos > 0) {
        flash('error', 'No se puede eliminar: el libro tiene préstamos o solicitudes en curso.');
    } else {
        ejecutar($cn, 'DELETE FROM libros WHERE id = ?', 'i', array($id));
        borrar_pdf($libro['archivo']);
        flash('ok', 'Libro eliminado junto con sus capítulos, reseñas y registros de lectura.');
    }
    redirigir('libros.php');
}
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$categoria = (int)(isset($_GET['categoria']) ? $_GET['categoria'] : 0);
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
$filas = consulta($cn, 'SELECT l.id, l.codigo, l.titulo, l.anio, l.ejemplares, l.archivo, c.nombre AS categoria, a.nombre AS autor, ' . sql_disponibles('l') . ' AS disponibles, (SELECT COUNT(*) FROM capitulos cp WHERE cp.libro_id = l.id) AS caps, ' . sql_promedio('l') . ' AS promedio, ' . sql_votos('l') . ' AS votos FROM libros l JOIN autores a ON a.id = l.autor_id JOIN categorias c ON c.id = l.categoria_id' . $donde . ' ORDER BY l.titulo ASC', $tipos, $params);
$cats = consulta($cn, 'SELECT id, nombre FROM categorias ORDER BY nombre');
$titulo = 'Libros';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Libros</h1><a class="btn" href="libro_form.php"><?php echo icono('mas', 16); ?> Nuevo libro</a></div>
<form class="barra-acciones" method="get">
  <div class="buscar"><input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Buscar por título, autor, código o editorial"><button class="btn" type="submit"><?php echo icono('buscar', 16); ?></button></div>
  <select name="categoria" style="width:auto" onchange="this.form.submit()"><option value="0">Todas las categorías</option><?php foreach ($cats as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $categoria === (int)$c['id'] ? 'selected' : ''; ?>><?php echo e($c['nombre']); ?></option><?php endforeach; ?></select>
  <?php if ($q !== '' || $categoria): ?><a href="libros.php">Limpiar</a><?php endif; ?>
</form>
<?php if (!count($filas)): ?><div class="vacio">No hay libros para mostrar.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>Código</th><th>Título</th><th>Autor</th><th>Categoría</th><th>Lectura en línea</th><th>Ejemplares</th><th>Nota</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><?php echo e($f['codigo']); ?></td>
      <td><strong><?php echo e($f['titulo']); ?></strong></td>
      <td><?php echo e($f['autor']); ?></td>
      <td><?php echo e($f['categoria']); ?></td>
      <td><?php echo (int)$f['caps']; ?> cap.<?php echo $f['archivo'] ? ' + PDF' : ''; ?><?php if ((int)$f['caps'] === 0 && !$f['archivo']): ?> <span class="est est-rechazado">Sin contenido</span><?php endif; ?></td>
      <td><?php if ((int)$f['ejemplares'] > 0): ?><div class="stock <?php echo (int)$f['disponibles'] > 0 ? 'hay' : 'agotado'; ?>" style="margin:0"><?php echo iconos_ejemplares((int)$f['disponibles'], (int)$f['ejemplares']); ?><strong><?php echo (int)$f['disponibles']; ?>/<?php echo (int)$f['ejemplares']; ?></strong></div><?php else: ?>Solo en línea<?php endif; ?></td>
      <td><?php echo estrellas($f['promedio'], (int)$f['votos']); ?></td>
      <td class="acciones">
        <a class="btn btn-sm btn-oro" href="capitulos.php?libro=<?php echo (int)$f['id']; ?>">Capítulos / PDF</a>
        <a class="btn btn-sm btn-claro" href="libro_form.php?id=<?php echo (int)$f['id']; ?>">Editar</a>
        <form method="post" onsubmit="return confirm('¿Eliminar el libro <?php echo e(addslashes($f['titulo'])); ?>? También se borrarán sus capítulos, reseñas y registros de lectura.');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><button class="btn btn-sm btn-peligro" type="submit"><?php echo icono('borrar', 15); ?> Eliminar</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
