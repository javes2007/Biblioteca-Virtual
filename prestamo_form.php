<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$yo = (int)$_SESSION['usuario_id'];
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$error = '';
$usuario_id = (int)(isset($_REQUEST['usuario_id']) ? $_REQUEST['usuario_id'] : 0);
$libro_id = 0;
$dias = 7;
$fecha_limite = '';
$p = null;
if ($id > 0) {
    $p = fila($cn, 'SELECT p.id, p.estado, p.fecha_prestamo, p.fecha_limite, u.nombre, u.apellido, l.titulo FROM prestamos p JOIN usuarios u ON u.id = p.usuario_id JOIN libros l ON l.id = p.libro_id WHERE p.id = ?', 'i', array($id));
    if (!$p || $p['estado'] !== 'Activo') {
        flash('error', 'Solo se pueden editar préstamos activos.');
        redirigir('prestamos.php');
    }
    $fecha_limite = $p['fecha_limite'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    if ($id > 0) {
        $fecha_limite = trim(isset($_POST['fecha_limite']) ? $_POST['fecha_limite'] : '');
        if (!fecha_valida($fecha_limite)) {
            $error = 'Escribe una fecha límite válida.';
        } elseif ($fecha_limite < $p['fecha_prestamo']) {
            $error = 'La fecha límite no puede ser anterior a la fecha del préstamo.';
        } else {
            ejecutar($cn, "UPDATE prestamos SET fecha_limite = ? WHERE id = ? AND estado = 'Activo'", 'si', array($fecha_limite, $id));
            flash('ok', 'Préstamo actualizado con éxito.');
            redirigir('prestamos.php');
        }
    } else {
        $libro_id = (int)(isset($_POST['libro_id']) ? $_POST['libro_id'] : 0);
        $dias = (int)(isset($_POST['dias']) ? $_POST['dias'] : 7);
        $u = fila($cn, "SELECT id, nombre FROM usuarios WHERE id = ? AND rol = 'lector' AND activo = 1", 'i', array($usuario_id));
        $l = fila($cn, 'SELECT l.id, l.titulo, ' . sql_disponibles('l') . ' AS disponibles FROM libros l WHERE l.id = ?', 'i', array($libro_id));
        $abiertos = $u ? (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado IN ('Solicitado','Activo')", 'i', array($usuario_id)) : 0;
        $repetido = $u ? (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND libro_id = ? AND estado IN ('Solicitado','Activo')", 'ii', array($usuario_id, $libro_id)) : 0;
        $vencidos = $u ? (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado = 'Activo' AND fecha_limite < CURDATE()", 'i', array($usuario_id)) : 0;
        if (!$u) {
            $error = 'Elige un lector activo de la lista.';
        } elseif (!$l) {
            $error = 'Elige un libro de la lista.';
        } elseif ($dias < 1 || $dias > 30) {
            $error = 'Los días de préstamo deben estar entre 1 y 30.';
        } elseif ((int)$l['disponibles'] < 1) {
            $error = 'Ese libro ya no tiene ejemplares disponibles.';
        } elseif ($repetido > 0) {
            $error = 'Ese lector ya tiene una solicitud o un préstamo de este libro.';
        } elseif ($vencidos > 0) {
            $error = 'Ese lector tiene préstamos vencidos. Debe devolverlos primero.';
        } elseif ($abiertos >= MAX_PRESTAMOS) {
            $error = 'Ese lector ya tiene el máximo de ' . MAX_PRESTAMOS . ' préstamos o solicitudes.';
        } else {
            ejecutar($cn, "INSERT INTO prestamos (libro_id, usuario_id, estado, fecha_prestamo, fecha_limite, registrado_por) VALUES (?, ?, 'Activo', CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?)", 'iiii', array($libro_id, $usuario_id, $dias, $yo));
            flash('ok', 'Préstamo registrado: "' . $l['titulo'] . '" para ' . $u['nombre'] . '.');
            redirigir('prestamos.php');
        }
    }
}
$lectores = $id > 0 ? array() : consulta($cn, "SELECT id, nombre, apellido, telefono FROM usuarios WHERE rol = 'lector' AND activo = 1 ORDER BY apellido, nombre");
$libros = $id > 0 ? array() : consulta($cn, 'SELECT l.id, l.titulo, ' . sql_disponibles('l') . ' AS disponibles FROM libros l HAVING disponibles > 0 ORDER BY l.titulo');
$titulo = $id > 0 ? 'Editar préstamo' : 'Nuevo préstamo';
require 'cabecera.php';
?>
<h1><?php echo e($titulo); ?></h1>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
<form class="formulario panel" method="post">
  <?php echo campo_csrf(); ?>
  <?php if ($id > 0): ?>
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <p><strong>Lector:</strong> <?php echo e($p['nombre'] . ' ' . $p['apellido']); ?><br><strong>Libro:</strong> <?php echo e($p['titulo']); ?><br><strong>Prestado el:</strong> <?php echo fecha_es($p['fecha_prestamo']); ?></p>
    <div><label for="fecha_limite">Fecha límite de devolución</label><input id="fecha_limite" type="date" name="fecha_limite" required value="<?php echo e($fecha_limite); ?>"></div>
  <?php else: ?>
    <div><label for="usuario_id">Lector</label>
      <select id="usuario_id" name="usuario_id" required><option value="">-- Elige un lector --</option>
        <?php foreach ($lectores as $u): ?><option value="<?php echo (int)$u['id']; ?>" <?php echo $usuario_id === (int)$u['id'] ? 'selected' : ''; ?>><?php echo e($u['apellido'] . ' ' . $u['nombre'] . ' · ' . $u['telefono']); ?></option><?php endforeach; ?>
      </select></div>
    <div><label for="libro_id">Libro disponible</label>
      <select id="libro_id" name="libro_id" required><option value="">-- Elige un libro --</option>
        <?php foreach ($libros as $l): ?><option value="<?php echo (int)$l['id']; ?>" <?php echo $libro_id === (int)$l['id'] ? 'selected' : ''; ?>><?php echo e($l['titulo']); ?> (<?php echo (int)$l['disponibles']; ?> disp.)</option><?php endforeach; ?>
      </select></div>
    <div><label for="dias">Días de préstamo</label><input id="dias" type="number" name="dias" min="1" max="30" required value="<?php echo (int)$dias; ?>"></div>
  <?php endif; ?>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> <?php echo $id > 0 ? 'Actualizar' : 'Registrar préstamo'; ?></button><a class="btn btn-claro" href="prestamos.php">Cancelar</a></div>
</form>
<?php require 'pie.php'; ?>
