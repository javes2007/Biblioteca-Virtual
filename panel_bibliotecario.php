<?php
require_once 'seguridad.php';
exigir_login();
if (!es_bibliotecario()) { redirigir(es_admin() ? 'panel.php' : 'index.php'); }
$cn = db();
$pendientes = contar_solicitudes($cn);
$vencidos = consulta($cn, "SELECT p.id, p.fecha_limite, DATEDIFF(CURDATE(), p.fecha_limite) AS dias, u.nombre, u.apellido, u.telefono, l.titulo FROM prestamos p JOIN usuarios u ON u.id = p.usuario_id JOIN libros l ON l.id = p.libro_id WHERE p.estado = 'Activo' AND p.fecha_limite < CURDATE() ORDER BY p.fecha_limite ASC");
$solicitudes = consulta($cn, "SELECT p.id, u.nombre, u.apellido, u.telefono, l.titulo, " . sql_disponibles('l') . " AS disponibles FROM prestamos p JOIN usuarios u ON u.id = p.usuario_id JOIN libros l ON l.id = p.libro_id WHERE p.estado = 'Solicitado' ORDER BY p.id ASC");
$activos = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE estado = 'Activo' AND fecha_limite >= CURDATE()");
$devueltos = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE estado = 'Devuelto'");
$titulo = 'Panel de bibliotecario';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Panel de bibliotecario</h1><a class="btn" href="prestamos.php">Ver todos los préstamos</a></div>
<p class="ayuda">Desde este espacio puedes atender solicitudes, registrar entregas, renovar préstamos y confirmar devoluciones. No tienes acceso a la administración de usuarios, libros, informes ni configuración.</p>
<section class="estadisticas">
  <div class="dato"><?php echo icono('campana', 28); ?><div><strong><?php echo $pendientes; ?></strong><small>Solicitudes pendientes</small></div></div>
  <div class="dato"><?php echo icono('prestamo', 28); ?><div><strong><?php echo $activos; ?></strong><small>Préstamos activos</small></div></div>
  <div class="dato alerta"><?php echo icono('alerta', 28); ?><div><strong><?php echo count($vencidos); ?></strong><small>Préstamos vencidos</small></div></div>
  <div class="dato"><?php echo icono('check', 28); ?><div><strong><?php echo $devueltos; ?></strong><small>Devoluciones registradas</small></div></div>
</section>
<section class="panel">
  <h2><?php echo icono('campana', 20); ?> Solicitudes por atender</h2>
  <?php if (!count($solicitudes)): ?><div class="vacio">No hay solicitudes pendientes.</div><?php else: ?>
  <div class="tabla-wrap"><table><thead><tr><th>Lector</th><th>Teléfono</th><th>Libro</th><th>Disponibles</th><th>Acciones</th></tr></thead><tbody>
  <?php foreach ($solicitudes as $s): ?><tr>
    <td><?php echo e($s['nombre'] . ' ' . $s['apellido']); ?></td><td><?php echo e($s['telefono']); ?></td><td><?php echo e($s['titulo']); ?></td><td><?php echo (int)$s['disponibles']; ?></td>
    <td><form method="post" action="prestamo_accion.php"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>"><input type="hidden" name="volver" value="panel_bibliotecario.php"><input type="number" name="dias" value="7" min="1" max="30" style="width:68px" aria-label="Días de préstamo"><button class="btn btn-sm" type="submit" name="accion" value="aprobar" <?php echo (int)$s['disponibles'] < 1 ? 'disabled' : ''; ?>>Aprobar</button><button class="btn btn-sm btn-peligro" type="submit" name="accion" value="rechazar" onclick="return confirm('¿Rechazar esta solicitud?');">Rechazar</button></form></td>
  </tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
<section class="panel">
  <h2><?php echo icono('alerta', 20); ?> Préstamos vencidos</h2>
  <?php if (!count($vencidos)): ?><div class="vacio">No hay préstamos vencidos.</div><?php else: ?>
  <div class="tabla-wrap"><table><thead><tr><th>Lector</th><th>Teléfono</th><th>Libro</th><th>Venció</th><th>Atraso</th><th>Acción</th></tr></thead><tbody>
  <?php foreach ($vencidos as $v): ?><tr><td><?php echo e($v['nombre'] . ' ' . $v['apellido']); ?></td><td><?php echo e($v['telefono']); ?></td><td><?php echo e($v['titulo']); ?></td><td><?php echo fecha_es($v['fecha_limite']); ?></td><td><?php echo (int)$v['dias']; ?> día(s)</td><td><form method="post" action="prestamo_accion.php" onsubmit="return confirm('¿Registrar la devolución?');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>"><input type="hidden" name="accion" value="devolver"><input type="hidden" name="volver" value="panel_bibliotecario.php"><button class="btn btn-sm" type="submit">Registrar devolución</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</section>
<?php require 'pie.php'; ?>
