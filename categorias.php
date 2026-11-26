<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    try {
        $n = ejecutar($cn, 'DELETE FROM categorias WHERE id = ?', 'i', array($id));
        flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Categoría eliminada.' : 'La categoría no existe.');
    } catch (mysqli_sql_exception $ex) {
        flash('error', ((int)$ex->getCode() === 1451) ? 'No se puede eliminar: la categoría tiene libros.' : 'No se pudo eliminar la categoría.');
    }
    redirigir('categorias.php');
}
$filas = consulta($cn, 'SELECT c.id, c.nombre, c.descripcion, COUNT(l.id) AS libros FROM categorias c LEFT JOIN libros l ON l.categoria_id = c.id GROUP BY c.id, c.nombre, c.descripcion ORDER BY c.nombre');
$titulo = 'Categorías';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Categorías</h1><a class="btn" href="categoria_form.php"><?php echo icono('mas', 16); ?> Nueva categoría</a></div>
<?php if (!count($filas)): ?><div class="vacio">No hay categorías registradas.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Libros</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><?php echo (int)$f['id']; ?></td>
      <td><strong><?php echo e($f['nombre']); ?></strong></td>
      <td><?php echo e($f['descripcion']); ?></td>
      <td><?php echo (int)$f['libros']; ?></td>
      <td class="acciones">
        <a class="btn btn-sm btn-claro" href="categoria_form.php?id=<?php echo (int)$f['id']; ?>">Editar</a>
        <form method="post" onsubmit="return confirm('¿Eliminar la categoría <?php echo e(addslashes($f['nombre'])); ?>?');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><button class="btn btn-sm btn-peligro" type="submit"><?php echo icono('borrar', 15); ?> Eliminar</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
