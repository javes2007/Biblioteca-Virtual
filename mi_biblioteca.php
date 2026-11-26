<?php
require_once 'seguridad.php';
exigir_login();
if (es_admin()) { redirigir('panel.php'); }
$cn = db();
$uid = (int)$_SESSION['usuario_id'];
$leyendo = consulta($cn, 'SELECT l.id, l.titulo, a.nombre AS autor, ls.capitulo_orden, ls.progreso, ls.actualizado_en FROM lecturas ls JOIN libros l ON l.id = ls.libro_id JOIN autores a ON a.id = l.autor_id WHERE ls.usuario_id = ? AND ls.terminado = 0 ORDER BY ls.actualizado_en DESC', 'i', array($uid));
$terminados = consulta($cn, 'SELECT l.id, l.titulo, a.nombre AS autor, ls.actualizado_en FROM lecturas ls JOIN libros l ON l.id = ls.libro_id JOIN autores a ON a.id = l.autor_id WHERE ls.usuario_id = ? AND ls.terminado = 1 ORDER BY ls.actualizado_en DESC', 'i', array($uid));
$marcadores = consulta($cn, 'SELECT m.id, m.creado_en, c.orden, c.titulo AS capitulo, l.id AS libro_id, l.titulo FROM marcadores m JOIN capitulos c ON c.id = m.capitulo_id JOIN libros l ON l.id = c.libro_id WHERE m.usuario_id = ? ORDER BY m.creado_en DESC LIMIT 30', 'i', array($uid));
$resaltados = consulta($cn, 'SELECT r.id, r.texto, r.color, r.creado_en, c.orden, c.titulo AS capitulo, l.id AS libro_id, l.titulo FROM resaltados r JOIN capitulos c ON c.id = r.capitulo_id JOIN libros l ON l.id = c.libro_id WHERE r.usuario_id = ? ORDER BY r.creado_en DESC LIMIT 30', 'i', array($uid));
$tot_res = (int)valor($cn, 'SELECT COUNT(*) FROM resaltados WHERE usuario_id = ?', 'i', array($uid));
$titulo = 'Mi biblioteca';
require 'cabecera.php';
?>
<h1>Mi biblioteca</h1>
<section class="estadisticas">
  <div class="dato aviso"><?php echo icono('abierto', 28); ?><div><strong><?php echo count($leyendo); ?></strong><small>Leyendo ahora</small></div></div>
  <div class="dato"><?php echo icono('check', 28); ?><div><strong><?php echo count($terminados); ?></strong><small>Terminados</small></div></div>
  <div class="dato"><?php echo icono('marcador', 28); ?><div><strong><?php echo count($marcadores); ?></strong><small>Marcadores</small></div></div>
  <div class="dato"><?php echo icono('pincel', 28); ?><div><strong><?php echo $tot_res; ?></strong><small>Resaltados</small></div></div>
</section>

<h2>Continuar leyendo</h2>
<?php if (!count($leyendo)): ?>
  <div class="vacio">Todavía no has empezado ningún libro. <a href="catalogo.php?leer=1">Explora los libros para leer en línea</a></div>
<?php else: ?>
  <div class="rejilla">
  <?php foreach ($leyendo as $l): ?>
    <div class="panel" style="margin:0">
      <h3><a href="libro.php?id=<?php echo (int)$l['id']; ?>"><?php echo e($l['titulo']); ?></a></h3>
      <p class="autor"><?php echo e($l['autor']); ?></p>
      <div class="progreso"><i style="width:<?php echo (int)$l['progreso']; ?>%"></i></div>
      <small><?php echo (int)$l['progreso']; ?>% · última lectura <?php echo fecha_es($l['actualizado_en']); ?></small>
      <p><a class="btn btn-sm" href="leer.php?id=<?php echo (int)$l['id']; ?>&cap=<?php echo (int)$l['capitulo_orden']; ?>"><?php echo icono('abierto', 16); ?> Continuar</a></p>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (count($terminados)): ?>
<h2 style="margin-top:30px">Libros terminados</h2>
<div class="panel"><ul class="lista-sencilla">
  <?php foreach ($terminados as $l): ?><li><?php echo icono('check', 16); ?> <a href="libro.php?id=<?php echo (int)$l['id']; ?>"><strong><?php echo e($l['titulo']); ?></strong></a> · <?php echo e($l['autor']); ?> <small>(<?php echo fecha_es($l['actualizado_en']); ?>)</small></li><?php endforeach; ?>
</ul></div>
<?php endif; ?>

<div class="dos-columnas" style="margin-top:30px">
  <div>
    <h2>Mis marcadores</h2>
    <?php if (!count($marcadores)): ?><div class="vacio">Marca capítulos desde el lector para volver a ellos.</div><?php else: ?>
    <div class="panel"><ul class="lista-sencilla">
      <?php foreach ($marcadores as $m): ?><li><?php echo icono('marcador', 16); ?> <a href="leer.php?id=<?php echo (int)$m['libro_id']; ?>&cap=<?php echo (int)$m['orden']; ?>"><strong><?php echo e($m['titulo']); ?></strong> — <?php echo e($m['capitulo']); ?></a></li><?php endforeach; ?>
    </ul></div><?php endif; ?>
  </div>
  <div>
    <h2>Mis resaltados</h2>
    <?php if (!count($resaltados)): ?><div class="vacio">Selecciona texto en el lector para resaltarlo.</div><?php else: ?>
    <div class="panel">
      <?php foreach ($resaltados as $r): ?>
        <div class="cita"><mark class="res res-<?php echo e($r['color']); ?>"><?php echo e($r['texto']); ?></mark><br><small><a href="leer.php?id=<?php echo (int)$r['libro_id']; ?>&cap=<?php echo (int)$r['orden']; ?>"><?php echo e($r['titulo']); ?> · <?php echo e($r['capitulo']); ?></a></small></div>
      <?php endforeach; ?>
    </div><?php endif; ?>
  </div>
</div>
<?php require 'pie.php'; ?>
