<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$error = '';
$d = array('orden' => '', 'titulo' => '', 'contenido' => '');
if ($id > 0) {
    $actual = fila($cn, 'SELECT libro_id, orden, titulo, contenido FROM capitulos WHERE id = ?', 'i', array($id));
    if (!$actual) { flash('error', 'El capítulo no existe.'); redirigir('libros.php'); }
    $libro_id = (int)$actual['libro_id'];
    $d = array('orden' => $actual['orden'], 'titulo' => $actual['titulo'], 'contenido' => $actual['contenido']);
} else {
    $libro_id = (int)(isset($_REQUEST['libro']) ? $_REQUEST['libro'] : 0);
    $d['orden'] = (int)valor($cn, 'SELECT COALESCE(MAX(orden), 0) + 1 FROM capitulos WHERE libro_id = ?', 'i', array($libro_id));
}
$libro = fila($cn, 'SELECT id, titulo FROM libros WHERE id = ?', 'i', array($libro_id));
if (!$libro) { flash('error', 'El libro no existe.'); redirigir('libros.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $d['orden'] = (int)(isset($_POST['orden']) ? $_POST['orden'] : 0);
    $d['titulo'] = trim(isset($_POST['titulo']) ? $_POST['titulo'] : '');
    $d['contenido'] = trim(str_replace("\r\n", "\n", isset($_POST['contenido']) ? $_POST['contenido'] : ''));
    if ($d['orden'] < 1 || $d['orden'] > 9999) {
        $error = 'El orden debe ser un número entre 1 y 9999.';
    } elseif ($d['titulo'] === '' || mb_strlen($d['titulo']) > 150) {
        $error = 'Escribe el título del capítulo (máximo 150 letras).';
    } elseif (mb_strlen($d['contenido']) < 10 || mb_strlen($d['contenido']) > 300000) {
        $error = 'El contenido debe tener entre 10 y 300.000 caracteres.';
    } else {
        try {
            if ($id > 0) {
                ejecutar($cn, 'UPDATE capitulos SET orden = ?, titulo = ?, contenido = ? WHERE id = ?', 'issi', array($d['orden'], $d['titulo'], $d['contenido'], $id));
                flash('ok', 'Capítulo actualizado con éxito.');
            } else {
                ejecutar($cn, 'INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES (?, ?, ?, ?)', 'iiss', array($libro_id, $d['orden'], $d['titulo'], $d['contenido']));
                flash('ok', 'Capítulo guardado con éxito.');
            }
            redirigir('capitulos.php?libro=' . $libro_id);
        } catch (mysqli_sql_exception $ex) {
            $error = ((int)$ex->getCode() === 1062) ? 'Ya existe un capítulo con ese orden en este libro.' : 'No se pudo guardar el capítulo.';
        }
    }
}
$titulo = $id > 0 ? 'Editar capítulo' : 'Nuevo capítulo';
require 'cabecera.php';
?>
<p><a href="capitulos.php?libro=<?php echo $libro_id; ?>">← Volver a los capítulos de <?php echo e($libro['titulo']); ?></a></p>
<h1><?php echo e($titulo); ?></h1>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
<form class="formulario panel" method="post" style="max-width:900px">
  <?php echo campo_csrf(); ?>
  <input type="hidden" name="libro" value="<?php echo $libro_id; ?>">
  <?php if ($id > 0): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
  <div class="doble">
    <div><label for="orden">Orden</label><input id="orden" type="number" name="orden" required min="1" max="9999" value="<?php echo e($d['orden']); ?>"></div>
    <div><label for="titulo_c">Título del capítulo</label><input id="titulo_c" type="text" name="titulo" required maxlength="150" value="<?php echo e($d['titulo']); ?>"></div>
  </div>
  <div><label for="contenido">Contenido</label><textarea id="contenido" name="contenido" required rows="18" style="font-family:Georgia,serif;font-size:1.05rem"><?php echo e($d['contenido']); ?></textarea><div class="ayuda">Deja una línea en blanco entre párrafos. Los saltos de línea simples se respetan (útil para poemas).</div></div>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> <?php echo $id > 0 ? 'Actualizar' : 'Guardar'; ?></button><a class="btn btn-claro" href="capitulos.php?libro=<?php echo $libro_id; ?>">Cancelar</a></div>
</form>
<?php require 'pie.php'; ?>
