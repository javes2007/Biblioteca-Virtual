<?php
require_once 'seguridad.php';
exigir_lector();
$cn = db();
$uid = (int)$_SESSION['usuario_id'];
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$error = '';
$d = array('titulo' => '', 'autor' => '', 'motivo' => '');
if ($id > 0) {
    $actual = fila($cn, "SELECT titulo, autor, motivo FROM sugerencias WHERE id = ? AND usuario_id = ? AND estado = 'Pendiente'", 'ii', array($id, $uid));
    if (!$actual) {
        flash('error', 'Solo puedes editar tus sugerencias pendientes.');
        redirigir('sugerencias.php');
    }
    $d = array('titulo' => $actual['titulo'], 'autor' => (string)$actual['autor'], 'motivo' => $actual['motivo']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $d['titulo'] = trim(isset($_POST['titulo']) ? $_POST['titulo'] : '');
    $d['autor'] = trim(isset($_POST['autor']) ? $_POST['autor'] : '');
    $d['motivo'] = trim(isset($_POST['motivo']) ? $_POST['motivo'] : '');
    if ($d['titulo'] === '' || mb_strlen($d['titulo']) > 150) {
        $error = 'Escribe el título del libro (máximo 150 letras).';
    } elseif (mb_strlen($d['autor']) > 100) {
        $error = 'El autor admite máximo 100 letras.';
    } elseif (mb_strlen($d['motivo']) < 10 || mb_strlen($d['motivo']) > 500) {
        $error = 'Cuéntanos el motivo (entre 10 y 500 letras).';
    } else {
        $autor = $d['autor'] === '' ? null : $d['autor'];
        if ($id > 0) {
            ejecutar($cn, "UPDATE sugerencias SET titulo = ?, autor = ?, motivo = ? WHERE id = ? AND usuario_id = ? AND estado = 'Pendiente'", 'sssii', array($d['titulo'], $autor, $d['motivo'], $id, $uid));
            flash('ok', 'Sugerencia actualizada.');
        } else {
            ejecutar($cn, 'INSERT INTO sugerencias (usuario_id, titulo, autor, motivo) VALUES (?, ?, ?, ?)', 'isss', array($uid, $d['titulo'], $autor, $d['motivo']));
            flash('ok', 'Gracias. Tu sugerencia fue enviada al administrador.');
        }
        redirigir('sugerencias.php');
    }
}
$titulo = $id > 0 ? 'Editar sugerencia' : 'Sugerir un libro';
require 'cabecera.php';
?>
<h1><?php echo e($titulo); ?></h1>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
<form class="formulario panel" method="post">
  <?php echo campo_csrf(); ?>
  <?php if ($id > 0): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
  <div><label for="titulo_s">Título del libro</label><input id="titulo_s" type="text" name="titulo" required maxlength="150" value="<?php echo e($d['titulo']); ?>"></div>
  <div><label for="autor">Autor (opcional)</label><input id="autor" type="text" name="autor" maxlength="100" value="<?php echo e($d['autor']); ?>"></div>
  <div><label for="motivo">¿Por qué te gustaría que esté en la biblioteca?</label><textarea id="motivo" name="motivo" required minlength="10" maxlength="500" rows="4"><?php echo e($d['motivo']); ?></textarea></div>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> <?php echo $id > 0 ? 'Actualizar' : 'Enviar sugerencia'; ?></button><a class="btn btn-claro" href="sugerencias.php">Cancelar</a></div>
</form>
<?php require 'pie.php'; ?>
