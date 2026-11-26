<?php
require_once 'seguridad.php';
exigir_login();
if (es_admin()) { redirigir('prestamos.php'); }
$cn = db();
$uid = (int)$_SESSION['usuario_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $n = ejecutar($cn, "DELETE FROM prestamos WHERE id = ? AND usuario_id = ? AND estado = 'Solicitado'", 'ii', array($id, $uid));
    flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Solicitud cancelada.' : 'No se pudo cancelar la solicitud.');
    redirigir('mis_prestamos.php');
}
$filas = consulta($cn, 'SELECT p.id, p.estado, p.fecha_prestamo, p.fecha_limite, p.fecha_devolucion, p.creado_en, ' . sql_estado_real() . ' AS estado_real, l.id AS libro_id, l.titulo, a.nombre AS autor FROM prestamos p JOIN libros l ON l.id = p.libro_id JOIN autores a ON a.id = l.autor_id WHERE p.usuario_id = ? ORDER BY p.id DESC', 'i', array($uid));
$vencidos = array();
foreach ($filas as $f) { if ($f['estado_real'] === 'Vencido') { $vencidos[] = $f; } }
$titulo = 'Mis préstamos';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Mis préstamos</h1><button class="btn btn-claro no-imprimir" type="button" onclick="window.print()"><?php echo icono('imprimir', 16); ?> Imprimir</button></div>
<?php if (count($vencidos)): ?>
  <div class="alerta-vencidos">
    <h2><?php echo icono('alerta', 22); ?> Tienes préstamos vencidos</h2>
    <p>Devuelve estos libros lo antes posible a la biblioteca:</p>
    <ul><?php foreach ($vencidos as $v): ?><li><strong><?php echo e($v['titulo']); ?></strong> — venció el <?php echo fecha_es($v['fecha_limite']); ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>
<?php if (!count($filas)): ?>
  <div class="vacio">Todavía no tienes préstamos. <a href="catalogo.php">Explora el catálogo</a></div>
<?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>Libro</th><th>Autor</th><th>Estado</th><th>Préstamo</th><th>Fecha límite</th><th>Devolución</th><th class="no-imprimir"></th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><a href="libro.php?id=<?php echo (int)$f['libro_id']; ?>"><strong><?php echo e($f['titulo']); ?></strong></a></td>
      <td><?php echo e($f['autor']); ?></td>
      <td><span class="<?php echo e(clase_estado($f['estado_real'])); ?>"><?php echo e($f['estado_real']); ?></span></td>
      <td><?php echo fecha_es($f['fecha_prestamo']); ?></td>
      <td><?php echo fecha_es($f['fecha_limite']); ?></td>
      <td><?php echo fecha_es($f['fecha_devolucion']); ?></td>
      <td class="acciones no-imprimir">
        <?php if ($f['estado'] === 'Solicitado'): ?>
          <form method="post" onsubmit="return confirm('¿Cancelar esta solicitud?');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><button class="btn btn-sm btn-peligro" type="submit">Cancelar</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
