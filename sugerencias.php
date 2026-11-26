<?php
require_once 'seguridad.php';
exigir_login();
$cn = db();
$uid = (int)$_SESSION['usuario_id'];
$estados = array('Pendiente', 'Aprobada', 'Rechazada', 'Adquirida');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    if (es_admin() && $accion === 'estado') {
        $estado = isset($_POST['estado']) ? $_POST['estado'] : '';
        $respuesta = mb_substr(trim(isset($_POST['respuesta']) ? $_POST['respuesta'] : ''), 0, 300);
        if (!in_array($estado, $estados, true)) {
            flash('error', 'Estado no válido.');
        } else {
            ejecutar($cn, 'UPDATE sugerencias SET estado = ?, respuesta = ? WHERE id = ?', 'ssi', array($estado, $respuesta === '' ? null : $respuesta, $id));
            flash('ok', 'Sugerencia actualizada.');
        }
    } elseif (es_admin() && $accion === 'eliminar') {
        $n = ejecutar($cn, 'DELETE FROM sugerencias WHERE id = ?', 'i', array($id));
        flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Sugerencia eliminada.' : 'La sugerencia no existe.');
    } elseif (es_lector() && $accion === 'eliminar') {
        $n = ejecutar($cn, "DELETE FROM sugerencias WHERE id = ? AND usuario_id = ? AND estado = 'Pendiente'", 'ii', array($id, $uid));
        flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Sugerencia eliminada.' : 'Solo puedes eliminar sugerencias pendientes.');
    }
    redirigir('sugerencias.php');
}
$filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
if (!in_array($filtro, $estados, true)) { $filtro = ''; }
if (es_admin()) {
    if ($filtro !== '') {
        $filas = consulta($cn, 'SELECT s.id, s.titulo, s.autor, s.motivo, s.estado, s.respuesta, s.creado_en, u.nombre, u.apellido FROM sugerencias s JOIN usuarios u ON u.id = s.usuario_id WHERE s.estado = ? ORDER BY s.id DESC', 's', array($filtro));
    } else {
        $filas = consulta($cn, 'SELECT s.id, s.titulo, s.autor, s.motivo, s.estado, s.respuesta, s.creado_en, u.nombre, u.apellido FROM sugerencias s JOIN usuarios u ON u.id = s.usuario_id ORDER BY s.id DESC');
    }
} else {
    $filas = consulta($cn, 'SELECT id, titulo, autor, motivo, estado, respuesta, creado_en FROM sugerencias WHERE usuario_id = ? ORDER BY id DESC', 'i', array($uid));
}
$titulo = 'Sugerencias de libros';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Sugerencias de libros</h1>
  <div class="acciones">
    <?php if (es_lector()): ?><a class="btn" href="sugerencia_form.php"><?php echo icono('mas', 16); ?> Sugerir un libro</a><?php endif; ?>
    <?php if (es_admin()): ?><a class="btn btn-claro" href="informes.php?tipo=sugerencias"><?php echo icono('descargar', 16); ?> Informe de sugerencias</a><?php endif; ?>
  </div></div>
<?php if (es_lector()): ?><p>¿No encuentras un libro? Cuéntanos cuál quieres leer y por qué. El administrador revisará tu sugerencia.</p><?php endif; ?>
<?php if (es_admin()): ?>
<form class="barra-acciones" method="get"><select name="estado" style="width:auto" onchange="this.form.submit()"><option value="">Todos los estados</option><?php foreach ($estados as $es): ?><option value="<?php echo e($es); ?>" <?php echo $filtro === $es ? 'selected' : ''; ?>><?php echo e($es); ?></option><?php endforeach; ?></select><?php if ($filtro !== ''): ?><a href="sugerencias.php">Limpiar</a><?php endif; ?></form>
<?php endif; ?>
<?php if (!count($filas)): ?><div class="vacio">No hay sugerencias para mostrar.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><?php if (es_admin()): ?><th>Lector</th><?php endif; ?><th>Libro sugerido</th><th>Motivo</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <?php if (es_admin()): ?><td><?php echo e($f['nombre'] . ' ' . $f['apellido']); ?></td><?php endif; ?>
      <td><strong><?php echo e($f['titulo']); ?></strong><br><small><?php echo e($f['autor']); ?></small></td>
      <td style="min-width:220px"><?php echo e($f['motivo']); ?><?php if ($f['respuesta'] && !es_admin()): ?><div class="cita">Respuesta: <?php echo e($f['respuesta']); ?></div><?php endif; ?></td>
      <td><?php echo fecha_es($f['creado_en']); ?></td>
      <td><span class="<?php echo e(clase_estado($f['estado'])); ?>"><?php echo e($f['estado']); ?></span></td>
      <td class="acciones">
        <?php if (es_admin()): ?>
          <form method="post" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>">
            <select name="estado" style="width:auto"><?php foreach ($estados as $es): ?><option value="<?php echo e($es); ?>" <?php echo $f['estado'] === $es ? 'selected' : ''; ?>><?php echo e($es); ?></option><?php endforeach; ?></select>
            <input type="text" name="respuesta" maxlength="300" placeholder="Respuesta (opcional)" value="<?php echo e($f['respuesta']); ?>" style="width:190px">
            <button class="btn btn-sm" type="submit" name="accion" value="estado">Guardar</button>
            <button class="btn btn-sm btn-peligro" type="submit" name="accion" value="eliminar" onclick="return confirm('¿Eliminar esta sugerencia?');"><?php echo icono('borrar', 15); ?></button>
          </form>
        <?php elseif ($f['estado'] === 'Pendiente'): ?>
          <a class="btn btn-sm btn-claro" href="sugerencia_form.php?id=<?php echo (int)$f['id']; ?>">Editar</a>
          <form method="post" onsubmit="return confirm('¿Eliminar esta sugerencia?');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><input type="hidden" name="accion" value="eliminar"><button class="btn btn-sm btn-peligro" type="submit"><?php echo icono('borrar', 15); ?> Eliminar</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
