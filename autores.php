<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    try {
        $n = ejecutar($cn, 'DELETE FROM autores WHERE id = ?', 'i', array($id));
        flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Autor eliminado.' : 'El autor no existe.');
    } catch (mysqli_sql_exception $ex) {
        flash('error', ((int)$ex->getCode() === 1451) ? 'No se puede eliminar: el autor tiene libros.' : 'No se pudo eliminar el autor.');
    }
    redirigir('autores.php');
}
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
if ($q !== '') {
    $filas = consulta($cn, 'SELECT a.id, a.nombre, a.nacionalidad, COUNT(l.id) AS libros FROM autores a LEFT JOIN libros l ON l.autor_id = a.id WHERE a.nombre LIKE ? OR a.nacionalidad LIKE ? GROUP BY a.id, a.nombre, a.nacionalidad ORDER BY a.nombre', 'ss', array('%' . $q . '%', '%' . $q . '%'));
} else {
    $filas = consulta($cn, 'SELECT a.id, a.nombre, a.nacionalidad, COUNT(l.id) AS libros FROM autores a LEFT JOIN libros l ON l.autor_id = a.id GROUP BY a.id, a.nombre, a.nacionalidad ORDER BY a.nombre');
}
$titulo = 'Autores';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Autores</h1><a class="btn" href="autor_form.php"><?php echo icono('mas', 16); ?> Nuevo autor</a></div>
<form class="barra-acciones" method="get"><div class="buscar"><input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Buscar autor o nacionalidad"><button class="btn" type="submit"><?php echo icono('buscar', 16); ?></button></div><?php if ($q !== ''): ?><a href="autores.php">Limpiar</a><?php endif; ?></form>
<?php if (!count($filas)): ?><div class="vacio">No hay autores para mostrar.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>#</th><th>Nombre</th><th>Nacionalidad</th><th>Libros</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><?php echo (int)$f['id']; ?></td>
      <td><strong><?php echo e($f['nombre']); ?></strong></td>
      <td><?php echo e($f['nacionalidad']); ?></td>
      <td><?php echo (int)$f['libros']; ?></td>
      <td class="acciones">
        <a class="btn btn-sm btn-claro" href="autor_form.php?id=<?php echo (int)$f['id']; ?>">Editar</a>
        <form method="post" onsubmit="return confirm('¿Eliminar a <?php echo e(addslashes($f['nombre'])); ?>?');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><button class="btn btn-sm btn-peligro" type="submit"><?php echo icono('borrar', 15); ?> Eliminar</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
