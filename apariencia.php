<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$error = '';
$claves = array('nombre_sitio', 'color_primario', 'color_acento', 'color_fondo', 'color_fondo_oscuro', 'css_extra');
function guardar_config($cn, $clave, $valor) {
    ejecutar($cn, 'INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', 'ss', array($clave, $valor));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    if ($accion === 'restablecer') {
        guardar_config($cn, 'nombre_sitio', 'Biblioteca Virtual');
        guardar_config($cn, 'color_primario', '#1f5f46');
        guardar_config($cn, 'color_acento', '#c8963e');
        guardar_config($cn, 'color_fondo', '#f6f1e6');
        guardar_config($cn, 'color_fondo_oscuro', '#101a16');
        guardar_config($cn, 'css_extra', '');
        flash('ok', 'La apariencia volvió a los valores originales.');
        redirigir('apariencia.php');
    }
    $nombre = trim(isset($_POST['nombre_sitio']) ? $_POST['nombre_sitio'] : '');
    $datos = array();
    foreach (array('color_primario', 'color_acento', 'color_fondo', 'color_fondo_oscuro') as $k) { $datos[$k] = isset($_POST[$k]) ? $_POST[$k] : ''; }
    $css = isset($_POST['css_extra']) ? $_POST['css_extra'] : '';
    if ($nombre === '' || mb_strlen($nombre) > 60) {
        $error = 'Escribe el nombre del sitio (máximo 60 letras).';
    } elseif (!color_valido($datos['color_primario']) || !color_valido($datos['color_acento']) || !color_valido($datos['color_fondo']) || !color_valido($datos['color_fondo_oscuro'])) {
        $error = 'Alguno de los colores no es válido.';
    } elseif (mb_strlen($css) > 8000) {
        $error = 'El CSS personalizado admite máximo 8000 caracteres.';
    } else {
        guardar_config($cn, 'nombre_sitio', $nombre);
        foreach ($datos as $k => $v) { guardar_config($cn, $k, $v); }
        guardar_config($cn, 'css_extra', $css);
        flash('ok', 'La apariencia del sitio fue actualizada.');
        redirigir('apariencia.php');
    }
}
$c = config_sitio();
$titulo = 'Apariencia del sitio';
require 'cabecera.php';
?>
<h1>Apariencia del sitio</h1>
<p>Cambia los colores y el nombre de la biblioteca. Los colores de marca se aplican en todo el sitio. Los fondos se adaptan al tema claro u oscuro de cada lector. Mueve los controles para previsualizar los cambios.</p>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
<form class="panel formulario" method="post" style="max-width:860px">
  <?php echo campo_csrf(); ?>
  <div><label for="nombre_sitio">Nombre del sitio</label><input id="nombre_sitio" type="text" name="nombre_sitio" required maxlength="60" value="<?php echo e($c['nombre_sitio']); ?>"></div>
  <div class="muestra-color">
    <div><label for="color_primario">Color principal</label><input id="color_primario" type="color" name="color_primario" value="<?php echo e($c['color_primario']); ?>" data-var="--primario"></div>
    <div><label for="color_acento">Color de acento</label><input id="color_acento" type="color" name="color_acento" value="<?php echo e($c['color_acento']); ?>" data-var="--acento"></div>
    <div><label for="color_fondo">Fondo (modo claro)</label><input id="color_fondo" type="color" name="color_fondo" value="<?php echo e($c['color_fondo']); ?>" data-var="--fondo-personalizado"></div>
    <div><label for="color_fondo_oscuro">Fondo (modo oscuro)</label><input id="color_fondo_oscuro" type="color" name="color_fondo_oscuro" value="<?php echo e($c['color_fondo_oscuro']); ?>" data-var="--fondo-osc-personalizado"></div>
  </div>
  <div><label for="css_extra">CSS personalizado (avanzado)</label><textarea id="css_extra" name="css_extra" rows="8" maxlength="8000" spellcheck="false" style="font-family:Consolas,monospace" placeholder=".hero { background: #222; }"><?php echo e($c['css_extra']); ?></textarea><div class="ayuda">Escribe reglas CSS para cambiar cualquier parte del diseño. Se aplican a todas las páginas.</div></div>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> Guardar apariencia</button></div>
</form>
<form method="post" onsubmit="return confirm('¿Volver a los colores y al nombre originales?');"><?php echo campo_csrf(); ?><input type="hidden" name="accion" value="restablecer"><button class="btn btn-claro" type="submit">Restablecer valores originales</button></form>
<script>
(function () {
  var campos = document.querySelectorAll('input[type=color][data-var]');
  for (var i = 0; i < campos.length; i++) {
    campos[i].addEventListener('input', function () { document.documentElement.style.setProperty(this.getAttribute('data-var'), this.value); });
  }
})();
</script>
<?php require 'pie.php'; ?>
