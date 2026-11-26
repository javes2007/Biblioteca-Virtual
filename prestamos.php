<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$validos = array('Solicitado', 'Activo', 'Vencido', 'Devuelto', 'Rechazado');
if (!in_array($estado, $validos, true)) { $estado = ''; }
$donde = ' WHERE 1=1';
$tipos = '';
$params = array();
if ($q !== '') {
    $donde .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.telefono LIKE ? OR l.titulo LIKE ? OR l.codigo LIKE ?)';
    $like = '%' . $q . '%';
    $tipos .= 'sssss';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($estado === 'Vencido') {
    $donde .= " AND p.estado = 'Activo' AND p.fecha_limite < CURDATE()";
} elseif ($estado === 'Activo') {
    $donde .= " AND p.estado = 'Activo' AND p.fecha_limite >= CURDATE()";
} elseif ($estado !== '') {
    $donde .= ' AND p.estado = ?';
    $tipos .= 's';
    $params[] = $estado;
}
$filas = consulta($cn, 'SELECT p.id, p.estado, p.fecha_prestamo, p.fecha_limite, p.fecha_devolucion, ' . sql_estado_real() . ' AS estado_real, u.id AS usuario_id, u.nombre, u.apellido, u.telefono, l.titulo, l.codigo, ' . sql_disponibles('l') . ' AS disponibles FROM prestamos p JOIN usuarios u ON u.id = p.usuario_id JOIN libros l ON l.id = p.libro_id' . $donde . ' ORDER BY p.id DESC', $tipos, $params);
$titulo = 'Préstamos';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Préstamos</h1><a class="btn" href="prestamo_form.php"><?php echo icono('mas', 16); ?> Nuevo préstamo</a></div>
<form class="barra-acciones" method="get">
  <div class="buscar"><input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Buscar por lector, teléfono, libro o código"><button class="btn" type="submit"><?php echo icono('buscar', 16); ?></button></div>
  <select name="estado" style="width:auto" onchange="this.form.submit()"><option value="">Todos los estados</option><?php foreach ($validos as $v): ?><option value="<?php echo e($v); ?>" <?php echo $estado === $v ? 'selected' : ''; ?>><?php echo e($v); ?></option><?php endforeach; ?></select>
  <?php if ($q !== '' || $estado !== ''): ?><a href="prestamos.php">Limpiar</a><?php endif; ?>
</form>
<?php if (!count($filas)): ?><div class="vacio">No hay préstamos para mostrar.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>#</th><th>Lector</th><th>Teléfono</th><th>Libro</th><th>Estado</th><th>Préstamo</th><th>Límite</th><th>Devolución</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><?php echo (int)$f['id']; ?></td>
      <td><strong><?php echo e($f['nombre'] . ' ' . $f['apellido']); ?></strong></td>
      <td><a class="tel" href="<?php echo e(telefono_enlace($f['telefono'])); ?>"><?php echo icono('telefono', 15); ?> <?php echo e($f['telefono']); ?></a></td>
      <td><?php echo e($f['titulo']); ?><br><small><?php echo e($f['codigo']); ?></small></td>
      <td><span class="<?php echo e(clase_estado($f['estado_real'])); ?>"><?php echo e($f['estado_real']); ?></span></td>
      <td><?php echo fecha_es($f['fecha_prestamo']); ?></td>
      <td><?php echo fecha_es($f['fecha_limite']); ?></td>
      <td><?php echo fecha_es($f['fecha_devolucion']); ?></td>
      <td class="acciones">
        <?php if ($f['estado'] === 'Solicitado'): ?>
          <form method="post" action="prestamo_accion.php"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><input type="number" name="dias" value="7" min="1" max="30" style="width:64px" title="Días de préstamo"><button class="btn btn-sm" type="submit" name="accion" value="aprobar" <?php echo (int)$f['disponibles'] < 1 ? 'disabled' : ''; ?>>Aprobar</button><button class="btn btn-sm btn-peligro" type="submit" name="accion" value="rechazar" onclick="return confirm('¿Rechazar esta solicitud?');">Rechazar</button></form>
        <?php elseif ($f['estado'] === 'Activo'): ?>
          <form method="post" action="prestamo_accion.php" onsubmit="return confirm('¿Registrar la devolución de este libro?');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><input type="hidden" name="accion" value="devolver"><button class="btn btn-sm" type="submit">Devolver</button></form>
          <form method="post" action="prestamo_accion.php"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><input type="hidden" name="accion" value="renovar"><button class="btn btn-sm btn-oro" type="submit">+7 días</button></form>
          <a class="btn btn-sm btn-claro" href="prestamo_form.php?id=<?php echo (int)$f['id']; ?>">Editar</a>
        <?php endif; ?>
        <form method="post" action="prestamo_accion.php" onsubmit="return confirm('¿Eliminar este registro de préstamo?');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><input type="hidden" name="accion" value="eliminar"><button class="btn btn-sm btn-peligro" type="submit"><?php echo icono('borrar', 15); ?></button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
