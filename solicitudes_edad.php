<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    $respuesta = mb_substr(trim(isset($_POST['respuesta']) ? $_POST['respuesta'] : ''), 0, 300);
    $s = fila($cn, "SELECT usuario_id FROM cambios_edad WHERE id = ? AND estado = 'Pendiente'", 'i', array($id));
    if (!$s) {
        flash('error', 'Esa solicitud ya fue atendida.');
    } elseif ($accion === 'aprobar') {
        ejecutar($cn, "UPDATE cambios_edad SET estado = 'Aprobada', respuesta = ?, resuelto_en = NOW() WHERE id = ?", 'si', array($respuesta === '' ? null : $respuesta, $id));
        ejecutar($cn, 'UPDATE usuarios SET edad_editable = 1 WHERE id = ?', 'i', array((int)$s['usuario_id']));
        flash('ok', 'Aprobaste la solicitud. El lector podrá corregir su fecha una sola vez.');
    } elseif ($accion === 'rechazar') {
        if ($respuesta === '') {
            flash('error', 'Escribe el motivo del rechazo para que el lector lo entienda.');
        } else {
            ejecutar($cn, "UPDATE cambios_edad SET estado = 'Rechazada', respuesta = ?, resuelto_en = NOW() WHERE id = ?", 'si', array($respuesta, $id));
            flash('ok', 'Solicitud rechazada.');
        }
    }
    redirigir('solicitudes_edad.php');
}
$pendientes = consulta($cn, "SELECT c.id, c.motivo, c.creado_en, u.nombre, u.apellido FROM cambios_edad c JOIN usuarios u ON u.id = c.usuario_id WHERE c.estado = 'Pendiente' ORDER BY c.id ASC");
$historial = consulta($cn, "SELECT c.id, c.motivo, c.estado, c.respuesta, c.creado_en, c.resuelto_en, u.nombre, u.apellido FROM cambios_edad c JOIN usuarios u ON u.id = c.usuario_id WHERE c.estado <> 'Pendiente' ORDER BY c.id DESC LIMIT 30");
$titulo = 'Solicitudes de cambio de fecha';
require 'cabecera.php';
?>
<p><a href="usuarios.php">← Volver a los usuarios</a></p>
<h1>Solicitudes de cambio de fecha de nacimiento</h1>
<p class="ayuda">Los lectores no pueden modificar su fecha de nacimiento. Si necesitan corregirla, piden permiso y explican el motivo. Al aprobar, podrán cambiarla una sola vez.</p>
<?php if (!count($pendientes)): ?><div class="vacio">No hay solicitudes pendientes.</div><?php else: ?>
  <?php foreach ($pendientes as $s): ?>
  <form class="panel formulario" method="post" style="max-width:none">
    <?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
    <p><strong><?php echo e($s['nombre'] . ' ' . $s['apellido']); ?></strong> · <small><?php echo fecha_hora_es($s['creado_en']); ?></small></p>
    <div class="cita"><?php echo nl2br(e($s['motivo'])); ?></div>
    <div><label>Respuesta para el lector (obligatoria si rechazas)</label><input type="text" name="respuesta" maxlength="300"></div>
    <div class="acciones"><button class="btn" type="submit" name="accion" value="aprobar"><?php echo icono('check', 16); ?> Aprobar</button><button class="btn btn-peligro" type="submit" name="accion" value="rechazar">Rechazar</button></div>
  </form>
  <?php endforeach; ?>
<?php endif; ?>
<h2 style="margin-top:30px">Historial</h2>
<?php if (!count($historial)): ?><div class="vacio">Todavía no hay solicitudes atendidas.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>Lector</th><th>Motivo</th><th>Estado</th><th>Respuesta</th><th>Atendida</th></tr></thead>
  <tbody>
  <?php foreach ($historial as $h): ?>
    <tr><td><?php echo e($h['nombre'] . ' ' . $h['apellido']); ?></td><td><?php echo e($h['motivo']); ?></td><td><span class="<?php echo e(clase_estado($h['estado'])); ?>"><?php echo e($h['estado']); ?></span></td><td><?php echo e($h['respuesta']); ?></td><td><?php echo fecha_hora_es($h['resuelto_en']); ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
