<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$error = '';
$d = array('codigo' => '', 'titulo' => '', 'autor_id' => 0, 'categoria_id' => 0, 'editorial' => '', 'anio' => '', 'ejemplares' => 0, 'descripcion' => '');
if ($id > 0) {
    $actual = fila($cn, 'SELECT codigo, titulo, autor_id, categoria_id, editorial, anio, ejemplares, descripcion FROM libros WHERE id = ?', 'i', array($id));
    if (!$actual) { flash('error', 'El libro no existe.'); redirigir('libros.php'); }
    foreach ($d as $k => $v) { $d[$k] = ($actual[$k] === null) ? '' : $actual[$k]; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $d['codigo'] = trim(isset($_POST['codigo']) ? $_POST['codigo'] : '');
    $d['titulo'] = trim(isset($_POST['titulo']) ? $_POST['titulo'] : '');
    $d['autor_id'] = (int)(isset($_POST['autor_id']) ? $_POST['autor_id'] : 0);
    $d['categoria_id'] = (int)(isset($_POST['categoria_id']) ? $_POST['categoria_id'] : 0);
    $d['editorial'] = trim(isset($_POST['editorial']) ? $_POST['editorial'] : '');
    $d['anio'] = trim(isset($_POST['anio']) ? $_POST['anio'] : '');
    $d['ejemplares'] = (int)(isset($_POST['ejemplares']) ? $_POST['ejemplares'] : 0);
    $d['descripcion'] = trim(isset($_POST['descripcion']) ? $_POST['descripcion'] : '');
    $prestados = $id > 0 ? (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND estado = 'Activo'", 'i', array($id)) : 0;
    $anio_max = (int)date('Y') + 1;
    if ($d['codigo'] === '' || mb_strlen($d['codigo']) > 30) {
        $error = 'Escribe el código del libro (máximo 30 caracteres).';
    } elseif ($d['titulo'] === '' || mb_strlen($d['titulo']) > 150) {
        $error = 'Escribe el título (máximo 150 letras).';
    } elseif ($d['autor_id'] < 1 || (int)valor($cn, 'SELECT COUNT(*) FROM autores WHERE id = ?', 'i', array($d['autor_id'])) === 0) {
        $error = 'Elige un autor de la lista.';
    } elseif ($d['categoria_id'] < 1 || (int)valor($cn, 'SELECT COUNT(*) FROM categorias WHERE id = ?', 'i', array($d['categoria_id'])) === 0) {
        $error = 'Elige una categoría de la lista.';
    } elseif ($d['anio'] !== '' && (!ctype_digit($d['anio']) || (int)$d['anio'] < 1000 || (int)$d['anio'] > $anio_max)) {
        $error = 'El año debe estar entre 1000 y ' . $anio_max . '.';
    } elseif ($d['ejemplares'] < 0 || $d['ejemplares'] > 999) {
        $error = 'La cantidad de ejemplares físicos debe estar entre 0 y 999 (0 si solo se lee en línea).';
    } elseif ($d['ejemplares'] < $prestados) {
        $error = 'No puedes dejar menos ejemplares que los ' . $prestados . ' que están prestados ahora.';
    } elseif (mb_strlen($d['editorial']) > 100) {
        $error = 'La editorial admite máximo 100 letras.';
    } else {
        $anio_guardar = $d['anio'] === '' ? null : (int)$d['anio'];
        try {
            if ($id > 0) {
                ejecutar($cn, 'UPDATE libros SET codigo = ?, titulo = ?, autor_id = ?, categoria_id = ?, editorial = ?, anio = ?, ejemplares = ?, descripcion = ? WHERE id = ?', 'ssiisiisi', array($d['codigo'], $d['titulo'], $d['autor_id'], $d['categoria_id'], $d['editorial'], $anio_guardar, $d['ejemplares'], $d['descripcion'], $id));
                flash('ok', 'Libro actualizado con éxito.');
            } else {
                ejecutar($cn, 'INSERT INTO libros (codigo, titulo, autor_id, categoria_id, editorial, anio, ejemplares, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', 'ssiisiis', array($d['codigo'], $d['titulo'], $d['autor_id'], $d['categoria_id'], $d['editorial'], $anio_guardar, $d['ejemplares'], $d['descripcion']));
                flash('ok', 'Libro guardado con éxito. Ahora puedes agregarle capítulos o un PDF para que se pueda leer en línea.');
                redirigir('capitulos.php?libro=' . (int)$cn->insert_id);
            }
            redirigir('libros.php');
        } catch (mysqli_sql_exception $ex) {
            $error = ((int)$ex->getCode() === 1062) ? 'Ya existe un libro con ese código.' : 'No se pudo guardar el libro.';
        }
    }
}
$autores = consulta($cn, 'SELECT id, nombre FROM autores ORDER BY nombre');
$cats = consulta($cn, 'SELECT id, nombre FROM categorias ORDER BY nombre');
$titulo = $id > 0 ? 'Editar libro' : 'Nuevo libro';
require 'cabecera.php';
?>
<h1><?php echo e($titulo); ?></h1>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
<?php if (!count($autores) || !count($cats)): ?><div class="mensaje mensaje-aviso">Antes de registrar libros crea al menos un <a href="autor_form.php">autor</a> y una <a href="categoria_form.php">categoría</a>.</div><?php endif; ?>
<form class="formulario panel" method="post">
  <?php echo campo_csrf(); ?>
  <?php if ($id > 0): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
  <div class="doble">
    <div><label for="codigo">Código</label><input id="codigo" type="text" name="codigo" required maxlength="30" value="<?php echo e($d['codigo']); ?>"></div>
    <div><label for="ejemplares">Ejemplares físicos (0 = solo en línea)</label><input id="ejemplares" type="number" name="ejemplares" required min="0" max="999" value="<?php echo e($d['ejemplares']); ?>"></div>
  </div>
  <div><label for="titulo_l">Título</label><input id="titulo_l" type="text" name="titulo" required maxlength="150" value="<?php echo e($d['titulo']); ?>"></div>
  <div class="doble">
    <div><label for="autor_id">Autor</label><select id="autor_id" name="autor_id" required><option value="">-- Elige un autor --</option><?php foreach ($autores as $a): ?><option value="<?php echo (int)$a['id']; ?>" <?php echo (int)$d['autor_id'] === (int)$a['id'] ? 'selected' : ''; ?>><?php echo e($a['nombre']); ?></option><?php endforeach; ?></select></div>
    <div><label for="categoria_id">Categoría</label><select id="categoria_id" name="categoria_id" required><option value="">-- Elige una categoría --</option><?php foreach ($cats as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo (int)$d['categoria_id'] === (int)$c['id'] ? 'selected' : ''; ?>><?php echo e($c['nombre']); ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="doble">
    <div><label for="editorial">Editorial (opcional)</label><input id="editorial" type="text" name="editorial" maxlength="100" value="<?php echo e($d['editorial']); ?>"></div>
    <div><label for="anio">Año (opcional)</label><input id="anio" type="number" name="anio" min="1000" max="<?php echo (int)date('Y') + 1; ?>" value="<?php echo e($d['anio']); ?>"></div>
  </div>
  <div><label for="descripcion">Descripción (opcional)</label><textarea id="descripcion" name="descripcion"><?php echo e($d['descripcion']); ?></textarea></div>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> <?php echo $id > 0 ? 'Actualizar' : 'Guardar'; ?></button><a class="btn btn-claro" href="libros.php">Cancelar</a></div>
</form>
<?php require 'pie.php'; ?>
