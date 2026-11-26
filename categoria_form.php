<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$error = '';
$d = array('nombre' => '', 'descripcion' => '');
if ($id > 0) {
    $actual = fila($cn, 'SELECT nombre, descripcion FROM categorias WHERE id = ?', 'i', array($id));
    if (!$actual) { flash('error', 'La categoría no existe.'); redirigir('categorias.php'); }
    $d = array('nombre' => $actual['nombre'], 'descripcion' => (string)$actual['descripcion']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $d['nombre'] = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
    $d['descripcion'] = trim(isset($_POST['descripcion']) ? $_POST['descripcion'] : '');
    if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 60) {
        $error = 'Escribe el nombre de la categoría (máximo 60 letras).';
    } elseif (mb_strlen($d['descripcion']) > 200) {
        $error = 'La descripción admite máximo 200 caracteres.';
    } else {
        try {
            if ($id > 0) {
                ejecutar($cn, 'UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?', 'ssi', array($d['nombre'], $d['descripcion'], $id));
                flash('ok', 'Categoría actualizada con éxito.');
            } else {
                ejecutar($cn, 'INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)', 'ss', array($d['nombre'], $d['descripcion']));
                flash('ok', 'Categoría guardada con éxito.');
            }
            redirigir('categorias.php');
        } catch (mysqli_sql_exception $ex) {
            $error = ((int)$ex->getCode() === 1062) ? 'Ya existe una categoría con ese nombre.' : 'No se pudo guardar la categoría.';
        }
    }
}
$titulo = $id > 0 ? 'Editar categoría' : 'Nueva categoría';
require 'cabecera.php';
?>
<h1><?php echo e($titulo); ?></h1>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
<form class="formulario panel" method="post">
  <?php echo campo_csrf(); ?>
  <?php if ($id > 0): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
  <div><label for="nombre">Nombre</label><input id="nombre" type="text" name="nombre" required maxlength="60" value="<?php echo e($d['nombre']); ?>"></div>
  <div><label for="descripcion">Descripción (opcional)</label><input id="descripcion" type="text" name="descripcion" maxlength="200" value="<?php echo e($d['descripcion']); ?>"></div>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> <?php echo $id > 0 ? 'Actualizar' : 'Guardar'; ?></button><a class="btn btn-claro" href="categorias.php">Cancelar</a></div>
</form>
<?php require 'pie.php'; ?>
