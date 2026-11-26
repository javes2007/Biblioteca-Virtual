<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$error = '';
$d = array('nombre' => '', 'nacionalidad' => '');
if ($id > 0) {
    $actual = fila($cn, 'SELECT nombre, nacionalidad FROM autores WHERE id = ?', 'i', array($id));
    if (!$actual) { flash('error', 'El autor no existe.'); redirigir('autores.php'); }
    $d = array('nombre' => $actual['nombre'], 'nacionalidad' => (string)$actual['nacionalidad']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $d['nombre'] = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
    $d['nacionalidad'] = trim(isset($_POST['nacionalidad']) ? $_POST['nacionalidad'] : '');
    if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 100) {
        $error = 'Escribe el nombre del autor (máximo 100 letras).';
    } elseif (mb_strlen($d['nacionalidad']) > 60) {
        $error = 'La nacionalidad admite máximo 60 letras.';
    } else {
        try {
            if ($id > 0) {
                ejecutar($cn, 'UPDATE autores SET nombre = ?, nacionalidad = ? WHERE id = ?', 'ssi', array($d['nombre'], $d['nacionalidad'], $id));
                flash('ok', 'Autor actualizado con éxito.');
            } else {
                ejecutar($cn, 'INSERT INTO autores (nombre, nacionalidad) VALUES (?, ?)', 'ss', array($d['nombre'], $d['nacionalidad']));
                flash('ok', 'Autor guardado con éxito.');
            }
            redirigir('autores.php');
        } catch (mysqli_sql_exception $ex) {
            $error = ((int)$ex->getCode() === 1062) ? 'Ya existe un autor con ese nombre.' : 'No se pudo guardar el autor.';
        }
    }
}
$titulo = $id > 0 ? 'Editar autor' : 'Nuevo autor';
require 'cabecera.php';
?>
<h1><?php echo e($titulo); ?></h1>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
<form class="formulario panel" method="post">
  <?php echo campo_csrf(); ?>
  <?php if ($id > 0): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
  <div><label for="nombre">Nombre</label><input id="nombre" type="text" name="nombre" required maxlength="100" value="<?php echo e($d['nombre']); ?>"></div>
  <div><label for="nacionalidad">Nacionalidad (opcional)</label><input id="nacionalidad" type="text" name="nacionalidad" maxlength="60" value="<?php echo e($d['nacionalidad']); ?>"></div>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> <?php echo $id > 0 ? 'Actualizar' : 'Guardar'; ?></button><a class="btn btn-claro" href="autores.php">Cancelar</a></div>
</form>
<?php require 'pie.php'; ?>
