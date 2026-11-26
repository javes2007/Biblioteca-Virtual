<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$tot_titulos = (int)valor($cn, 'SELECT COUNT(*) FROM libros');
$tot_leibles = (int)valor($cn, 'SELECT COUNT(*) FROM libros l WHERE ' . sql_leible('l'));
$tot_ejemplares = (int)valor($cn, 'SELECT COALESCE(SUM(ejemplares),0) FROM libros');
$tot_lectores = (int)valor($cn, "SELECT COUNT(*) FROM usuarios WHERE rol = 'lector'");
$tot_activos = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE estado = 'Activo'");
$tot_leyendo = (int)valor($cn, 'SELECT COUNT(*) FROM lecturas WHERE terminado = 0');
$tot_terminados = (int)valor($cn, 'SELECT COUNT(*) FROM lecturas WHERE terminado = 1');
$pend_edad = (int)valor($cn, "SELECT COUNT(*) FROM cambios_edad WHERE estado = 'Pendiente'");
$pend_sug = (int)valor($cn, "SELECT COUNT(*) FROM sugerencias WHERE estado = 'Pendiente'");
$sin_contenido = (int)valor($cn, 'SELECT COUNT(*) FROM libros l WHERE NOT ' . sql_leible('l'));
$vencidos = consulta($cn, "SELECT p.id, p.fecha_limite, DATEDIFF(CURDATE(), p.fecha_limite) AS dias, u.nombre, u.apellido, u.telefono, l.titulo FROM prestamos p JOIN usuarios u ON u.id = p.usuario_id JOIN libros l ON l.id = p.libro_id WHERE p.estado = 'Activo' AND p.fecha_limite < CURDATE() ORDER BY p.fecha_limite ASC");
$solicitudes = consulta($cn, "SELECT p.id, p.creado_en, u.nombre, u.apellido, u.telefono, l.titulo, " . sql_disponibles('l') . " AS disponibles FROM prestamos p JOIN usuarios u ON u.id = p.usuario_id JOIN libros l ON l.id = p.libro_id WHERE p.estado = 'Solicitado' ORDER BY p.id ASC");
$mas_leidos = consulta($cn, 'SELECT l.titulo, COUNT(*) AS lectores, ROUND(AVG(ls.progreso)) AS promedio FROM lecturas ls JOIN libros l ON l.id = ls.libro_id GROUP BY l.id, l.titulo ORDER BY lectores DESC, l.titulo ASC LIMIT 5');
$titulo = 'Panel de administración';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Panel de administración</h1>
  <div class="acciones"><a class="btn" href="libro_form.php"><?php echo icono('libro', 16); ?> Nuevo libro</a><a class="btn btn-claro" href="prestamo_form.php"><?php echo icono('mas', 16); ?> Nuevo préstamo</a></div></div>

<section class="estadisticas">
  <div class="dato"><?php echo icono('libro', 30); ?><div><strong><?php echo $tot_titulos; ?></strong><small>Títulos</small></div></div>
  <div class="dato"><?php echo icono('abierto', 30); ?><div><strong><?php echo $tot_leibles; ?></strong><small>Para leer en línea</small></div></div>
  <div class="dato"><?php echo icono('libro', 30); ?><div><strong><?php echo $tot_ejemplares; ?></strong><small>Ejemplares físicos</small></div></div>
  <div class="dato"><?php echo icono('usuario', 30); ?><div><strong><?php echo $tot_lectores; ?></strong><small>Lectores</small></div></div>
  <div class="dato"><?php echo icono('abierto', 30); ?><div><strong><?php echo $tot_leyendo; ?></strong><small>Lecturas en curso</small></div></div>
  <div class="dato"><?php echo icono('check', 30); ?><div><strong><?php echo $tot_terminados; ?></strong><small>Libros terminados</small></div></div>
  <div class="dato"><?php echo icono('prestamo', 30); ?><div><strong><?php echo $tot_activos; ?></strong><small>Préstamos activos</small></div></div>
  <div class="dato alerta"><?php echo icono('alerta', 30); ?><div><strong><?php echo count($vencidos); ?></strong><small>Préstamos vencidos</small></div></div>
</section>

<?php if ($pend_edad > 0 || $pend_sug > 0 || count($solicitudes) > 0 || $sin_contenido > 0): ?>
<section class="panel">
  <h2><?php echo icono('campana', 22); ?> Pendiente por atender</h2>
  <ul class="lista-sencilla">
    <?php if (count($solicitudes) > 0): ?><li><a href="prestamos.php?estado=Solicitado"><strong><?php echo count($solicitudes); ?></strong> solicitud(es) de préstamo</a></li><?php endif; ?>
    <?php if ($pend_edad > 0): ?><li><a href="solicitudes_edad.php"><strong><?php echo $pend_edad; ?></strong> solicitud(es) de cambio de fecha de nacimiento</a></li><?php endif; ?>
    <?php if ($pend_sug > 0): ?><li><a href="sugerencias.php?estado=Pendiente"><strong><?php echo $pend_sug; ?></strong> sugerencia(s) de libros nuevos</a></li><?php endif; ?>
    <?php if ($sin_contenido > 0): ?><li><a href="libros.php"><strong><?php echo $sin_contenido; ?></strong> libro(s) todavía sin contenido para leer en línea</a></li><?php endif; ?>
  </ul>
</section>
<?php endif; ?>

<?php if (count($vencidos)): ?>
<section class="alerta-vencidos">
  <h2><?php echo icono('alerta', 22); ?> Préstamos vencidos: avisa a los lectores</h2>
  <div class="tabla-wrap"><table>
    <thead><tr><th>Lector</th><th>Teléfono</th><th>Libro</th><th>Venció</th><th>Atraso</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($vencidos as $v): ?>
      <tr>
        <td><strong><?php echo e($v['nombre'] . ' ' . $v['apellido']); ?></strong></td>
        <td><a class="tel" href="<?php echo e(telefono_enlace($v['telefono'])); ?>"><?php echo icono('telefono', 16); ?> <?php echo e($v['telefono']); ?></a></td>
        <td><?php echo e($v['titulo']); ?></td>
        <td><?php echo fecha_es($v['fecha_limite']); ?></td>
        <td><strong><?php echo (int)$v['dias']; ?> día(s)</strong></td>
        <td class="acciones">
          <a class="btn btn-sm btn-peligro" href="<?php echo e(telefono_enlace($v['telefono'])); ?>"><?php echo icono('telefono', 15); ?> Llamar</a>
          <form method="post" action="prestamo_accion.php" onsubmit="return confirm('¿Registrar la devolución de este libro?');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>"><input type="hidden" name="accion" value="devolver"><input type="hidden" name="volver" value="panel.php"><button class="btn btn-sm" type="submit"><?php echo icono('check', 15); ?> Devolver</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endif; ?>

<h2><?php echo icono('campana', 22); ?> Solicitudes de préstamo</h2>
<?php if (!count($solicitudes)): ?>
  <div class="vacio">No hay solicitudes pendientes.</div>
<?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>Lector</th><th>Teléfono</th><th>Libro</th><th>Disponibles</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($solicitudes as $s): ?>
    <tr>
      <td><strong><?php echo e($s['nombre'] . ' ' . $s['apellido']); ?></strong></td>
      <td><a class="tel" href="<?php echo e(telefono_enlace($s['telefono'])); ?>"><?php echo icono('telefono', 16); ?> <?php echo e($s['telefono']); ?></a></td>
      <td><?php echo e($s['titulo']); ?></td>
      <td><?php echo (int)$s['disponibles']; ?></td>
      <td class="acciones">
        <form method="post" action="prestamo_accion.php"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>"><input type="hidden" name="volver" value="panel.php">
          <input type="number" name="dias" value="7" min="1" max="30" style="width:70px" title="Días de préstamo">
          <button class="btn btn-sm" type="submit" name="accion" value="aprobar" <?php echo (int)$s['disponibles'] < 1 ? 'disabled' : ''; ?>><?php echo icono('check', 15); ?> Aprobar</button>
          <button class="btn btn-sm btn-peligro" type="submit" name="accion" value="rechazar" onclick="return confirm('¿Rechazar esta solicitud?');">Rechazar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>

<div class="seccion-titulo"><h2>Libros más leídos en línea</h2><a href="informes.php?tipo=lectura">Ver informe de lectura →</a></div>
<?php if (!count($mas_leidos)): ?><div class="vacio">Todavía nadie ha empezado a leer.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>Libro</th><th>Lectores</th><th>Avance promedio</th></tr></thead>
  <tbody><?php foreach ($mas_leidos as $m): ?><tr><td><?php echo e($m['titulo']); ?></td><td><?php echo (int)$m['lectores']; ?></td><td><div class="progreso" style="max-width:220px"><i style="width:<?php echo (int)$m['promedio']; ?>%"></i></div><?php echo (int)$m['promedio']; ?>%</td></tr><?php endforeach; ?></tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
