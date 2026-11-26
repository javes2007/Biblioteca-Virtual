<?php
require_once 'seguridad.php';
if (logueado()) { exigir_login(); }
$cn = db();
$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$l = fila($cn, 'SELECT l.*, c.nombre AS categoria, a.nombre AS autor, a.nacionalidad, ' . sql_disponibles('l') . ' AS disponibles, ' . sql_extra_tarjeta() . ' FROM libros l JOIN autores a ON a.id = l.autor_id JOIN categorias c ON c.id = l.categoria_id WHERE l.id = ?', 'i', array($id));
if (!$l) {
    flash('error', 'El libro no existe.');
    redirigir('catalogo.php');
}
$relacionados = consulta($cn, 'SELECT l.id, l.titulo, l.ejemplares, l.categoria_id, c.nombre AS categoria, a.nombre AS autor, ' . sql_disponibles('l') . ' AS disponibles, ' . sql_extra_tarjeta() . ' FROM libros l JOIN autores a ON a.id = l.autor_id JOIN categorias c ON c.id = l.categoria_id WHERE l.categoria_id = ? AND l.id <> ? ORDER BY l.titulo LIMIT 4', 'ii', array((int)$l['categoria_id'], $id));
$resenas = consulta($cn, 'SELECT r.id, r.usuario_id, r.puntuacion, r.comentario, r.creado_en, u.nombre, u.apellido, u.foto FROM resenas r JOIN usuarios u ON u.id = r.usuario_id WHERE r.libro_id = ? ORDER BY r.actualizado_en DESC', 'i', array($id));
$mia = null;
if (es_lector()) {
    foreach ($resenas as $r) { if ((int)$r['usuario_id'] === (int)$_SESSION['usuario_id']) { $mia = $r; } }
}
$capitulos = (int)valor($cn, 'SELECT COUNT(*) FROM capitulos WHERE libro_id = ?', 'i', array($id));
$lectura = es_lector() ? fila($cn, 'SELECT capitulo_orden, progreso, terminado FROM lecturas WHERE usuario_id = ? AND libro_id = ?', 'ii', array((int)$_SESSION['usuario_id'], $id)) : null;
$disp = (int)$l['disponibles'];
$total = (int)$l['ejemplares'];
$leible = !empty($l['leible']);
$matiz = ((int)$l['categoria_id'] * 47) % 360;
$titulo = $l['titulo'];
require 'cabecera.php';
?>
<p><a href="catalogo.php">← Volver al catálogo</a></p>
<div class="detalle">
  <div class="portada" style="--h:<?php echo $matiz; ?>"><?php echo icono('libro', 40); ?><span><?php echo e($l['titulo']); ?></span></div>
  <div>
    <span class="cat"><?php echo e($l['categoria']); ?></span>
    <h1><?php echo e($l['titulo']); ?></h1>
    <p class="autor">por <strong><?php echo e($l['autor']); ?></strong><?php echo $l['nacionalidad'] ? ' · ' . e($l['nacionalidad']) : ''; ?></p>
    <div><?php echo estrellas($l['promedio'], (int)$l['votos']); ?></div>
    <p><?php echo nl2br(e($l['descripcion'])); ?></p>
    <dl>
      <dt>Código</dt><dd><?php echo e($l['codigo']); ?></dd>
      <dt>Editorial</dt><dd><?php echo e($l['editorial'] ? $l['editorial'] : '—'); ?></dd>
      <dt>Año</dt><dd><?php echo e($l['anio'] ? $l['anio'] : '—'); ?></dd>
      <dt>Lectura en línea</dt><dd><?php echo $leible ? ($capitulos > 0 ? $capitulos . ' capítulo(s)' : 'PDF disponible') : 'No disponible todavía'; ?></dd>
      <dt>Ejemplares físicos</dt><dd><?php if ($total > 0): ?><div class="stock <?php echo $disp > 0 ? 'hay' : 'agotado'; ?>" style="margin:0"><?php echo iconos_ejemplares($disp, $total); ?><strong><?php echo $disp; ?></strong> de <?php echo $total; ?> disponibles</div><?php else: ?>Solo lectura en línea<?php endif; ?></dd>
      <?php if ($lectura): ?><dt>Tu progreso</dt><dd><?php echo (int)$lectura['progreso']; ?>%<?php echo (int)$lectura['terminado'] ? ' · Terminado' : ''; ?><div class="progreso"><i style="width:<?php echo (int)$lectura['progreso']; ?>%"></i></div></dd><?php endif; ?>
    </dl>
    <div class="acciones">
      <?php if ($leible && logueado()): ?>
        <a class="btn" href="leer.php?id=<?php echo (int)$l['id']; ?>"><?php echo icono('abierto', 18); ?> <?php echo $lectura ? 'Continuar leyendo' : 'Leer ahora'; ?></a>
      <?php elseif ($leible): ?>
        <a class="btn" href="login.php"><?php echo icono('abierto', 18); ?> Ingresa para leer</a>
      <?php endif; ?>
      <?php if (es_lector() && $disp > 0): ?>
        <form method="post" action="solicitar.php"><?php echo campo_csrf(); ?><input type="hidden" name="libro_id" value="<?php echo (int)$l['id']; ?>"><button class="btn btn-claro" type="submit"><?php echo icono('prestamo', 18); ?> Pedir préstamo</button></form>
      <?php endif; ?>
      <?php if (es_admin()): ?>
        <a class="btn btn-claro" href="libro_form.php?id=<?php echo (int)$l['id']; ?>">Editar libro</a>
        <a class="btn btn-claro" href="capitulos.php?libro=<?php echo (int)$l['id']; ?>">Capítulos y archivo</a>
      <?php endif; ?>
      <?php if (!logueado()): ?><a class="btn btn-claro" href="registro.php">Crear cuenta</a><?php endif; ?>
    </div>
  </div>
</div>

<div class="seccion-titulo"><h2>Calificaciones y comentarios</h2></div>
<?php if (es_lector()): ?>
<form class="panel formulario" method="post" action="resena.php">
  <?php echo campo_csrf(); ?>
  <input type="hidden" name="libro_id" value="<?php echo (int)$l['id']; ?>">
  <div>
    <label><?php echo $mia ? 'Tu calificación' : 'Califica este libro'; ?></label>
    <div class="selector-estrellas">
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <input type="radio" id="est<?php echo $i; ?>" name="puntuacion" value="<?php echo $i; ?>" <?php echo ($mia && (int)$mia['puntuacion'] === $i) ? 'checked' : ''; ?> required><label for="est<?php echo $i; ?>" title="<?php echo $i; ?> estrella(s)">★</label>
      <?php endfor; ?>
    </div>
  </div>
  <div><label for="comentario">Comentario (opcional)</label><textarea id="comentario" name="comentario" maxlength="600" rows="3"><?php echo e($mia ? $mia['comentario'] : ''); ?></textarea></div>
  <div class="acciones"><button class="btn" type="submit" name="accion" value="guardar"><?php echo icono('check', 16); ?> <?php echo $mia ? 'Actualizar mi reseña' : 'Publicar reseña'; ?></button>
  <?php if ($mia): ?><button class="btn btn-peligro" type="submit" name="accion" value="eliminar" onclick="return confirm('¿Eliminar tu reseña?');"><?php echo icono('borrar', 16); ?> Eliminar</button><?php endif; ?></div>
</form>
<?php elseif (!logueado()): ?>
<p><a href="login.php">Inicia sesión</a> para calificar y comentar este libro.</p>
<?php endif; ?>
<?php if (!count($resenas)): ?>
  <div class="vacio">Todavía no hay reseñas. ¡Sé la primera persona en opinar!</div>
<?php else: ?>
  <div class="panel">
  <?php foreach ($resenas as $r): ?>
    <div class="resena">
      <?php echo avatar_html($r, 40); ?>
      <div style="flex:1">
        <strong><?php echo e($r['nombre'] . ' ' . mb_substr($r['apellido'], 0, 1) . '.'); ?></strong> · <small><?php echo fecha_es($r['creado_en']); ?></small><br>
        <?php echo estrellas($r['puntuacion']); ?>
        <?php if ($r['comentario']): ?><p style="margin:6px 0 0"><?php echo nl2br(e($r['comentario'])); ?></p><?php endif; ?>
        <?php if (es_admin()): ?>
          <form method="post" action="resena.php" onsubmit="return confirm('¿Eliminar este comentario?');" style="margin-top:6px"><?php echo campo_csrf(); ?><input type="hidden" name="libro_id" value="<?php echo (int)$l['id']; ?>"><input type="hidden" name="resena_id" value="<?php echo (int)$r['id']; ?>"><input type="hidden" name="accion" value="moderar"><button class="btn btn-sm btn-peligro" type="submit"><?php echo icono('borrar', 14); ?> Eliminar comentario</button></form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (count($relacionados)): ?>
  <div class="seccion-titulo"><h2>Más de <?php echo e($l['categoria']); ?></h2></div>
  <div class="rejilla"><?php foreach ($relacionados as $r) { echo tarjeta_libro($r); } ?></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
