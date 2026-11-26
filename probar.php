<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$titulo = 'Prueba del sistema';
require 'cabecera.php';
$tablas = array('usuarios', 'configuracion', 'categorias', 'autores', 'libros', 'capitulos', 'prestamos', 'lecturas', 'resaltados', 'marcadores', 'resenas', 'sugerencias', 'cambios_edad');
?>
<h1>Prueba del sistema</h1>
<div class="panel">
  <p>PHP versión: <strong><?php echo e(phpversion()); ?></strong></p>
  <p class="mensaje">Conexión correcta a la base de datos.</p>
  <?php foreach ($tablas as $t): ?>
    <?php
    try {
        $n = (int)valor($cn, 'SELECT COUNT(*) FROM ' . $t);
        echo '<p>' . icono('check', 16) . ' La tabla <strong>' . e($t) . '</strong> tiene ' . $n . ' registro(s).</p>';
    } catch (mysqli_sql_exception $ex) {
        echo '<p class="mensaje mensaje-error">La tabla ' . e($t) . ' no existe. Importa biblioteca_virtual.sql.</p>';
    }
    ?>
  <?php endforeach; ?>
</div>
<div class="panel">
  <h2>Funciones del servidor</h2>
  <p><?php echo function_exists('imagecreatefromstring') ? icono('check', 16) . ' GD disponible: las fotos de perfil se recortan y optimizan.' : 'GD no disponible: las fotos de perfil solo aceptarán JPG.'; ?></p>
  <p><?php echo class_exists('ZipArchive') ? icono('check', 16) . ' ZipArchive disponible: los informes se descargan como Excel (.xlsx).' : 'ZipArchive no disponible: los informes se descargan como Excel (.xls).'; ?></p>
  <p><?php echo ini_get('file_uploads') ? icono('check', 16) . ' Subida de archivos activada.' : 'La subida de archivos está desactivada.'; ?> Límite por archivo: <strong><?php echo e(ini_get('upload_max_filesize')); ?></strong> · Límite por envío: <strong><?php echo e(ini_get('post_max_size')); ?></strong></p>
  <p><?php echo (is_writable(__DIR__) || is_dir(ruta_uploads('avatares'))) ? icono('check', 16) . ' La carpeta uploads se puede crear o ya existe.' : 'No se puede crear la carpeta uploads. Créala a mano con permisos de escritura.'; ?></p>
</div>
<?php require 'pie.php'; ?>
