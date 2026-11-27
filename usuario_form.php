<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$yo = (int)$_SESSION['usuario_id'];
$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$error = '';
$d = array('nombre' => '', 'apellido' => '', 'correo' => '', 'telefono' => '', 'fecha_nacimiento' => '', 'rol' => 'bibliotecario', 'activo' => 1);
if ($id > 0) {
    $actual = fila($cn, 'SELECT nombre, apellido, correo, rol, activo FROM usuarios WHERE id = ?', 'i', array($id));
    if (!$actual) { flash('error', 'El usuario no existe.'); redirigir('usuarios.php'); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    if ($id > 0) {
        $rol = isset($_POST['rol']) ? $_POST['rol'] : $actual['rol'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        if ($id === $yo) { $rol = 'admin'; $activo = 1; }
        if (!in_array($rol, array('admin', 'bibliotecario', 'lector'), true)) {
            $error = 'El perfil elegido no es válido.';
        } else {
            ejecutar($cn, 'UPDATE usuarios SET rol = ?, activo = ? WHERE id = ?', 'sii', array($rol, $activo, $id));
            flash('ok', 'Usuario actualizado con éxito.');
            redirigir('usuarios.php');
        }
        $actual['rol'] = $rol;
        $actual['activo'] = $activo;
    } else {
        foreach (array('nombre', 'apellido', 'correo', 'telefono', 'fecha_nacimiento') as $k) { $d[$k] = trim(isset($_POST[$k]) ? $_POST[$k] : ''); }
        $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
        $rol_nuevo = isset($_POST['rol']) ? $_POST['rol'] : 'bibliotecario';
        $d['rol'] = $rol_nuevo;
        if ($d['nombre'] === '' || $d['apellido'] === '' || mb_strlen($d['nombre']) > 80 || mb_strlen($d['apellido']) > 80) {
            $error = 'Escribe el nombre y el apellido.';
        } elseif (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['correo']) > 120) {
            $error = 'Escribe un correo válido.';
        } elseif (!telefono_valido($d['telefono'])) {
            $error = 'Escribe un teléfono válido.';
        } elseif (!nacimiento_valido($d['fecha_nacimiento'])) {
            $error = 'Escribe una fecha de nacimiento válida.';
        } elseif (!in_array($rol_nuevo, array('admin', 'bibliotecario'), true)) {
            $error = 'El perfil nuevo debe ser administrador o bibliotecario.';
        } elseif (strlen($clave) < 8) {
            $error = 'La contraseña debe tener mínimo 8 caracteres.';
        } else {
            try {
                ejecutar($cn, "INSERT INTO usuarios (nombre, apellido, correo, telefono, fecha_nacimiento, clave, rol, cambiar_clave) VALUES (?, ?, ?, ?, ?, ?, ?, 0)", 'sssssss', array($d['nombre'], $d['apellido'], $d['correo'], $d['telefono'], $d['fecha_nacimiento'], password_hash($clave, PASSWORD_DEFAULT), $rol_nuevo));
                flash('ok', $rol_nuevo === 'admin' ? 'Administrador creado con éxito.' : 'Bibliotecario creado con éxito.');
                redirigir('usuarios.php');
            } catch (mysqli_sql_exception $ex) {
                $error = ((int)$ex->getCode() === 1062) ? 'Ese correo ya está registrado.' : 'No se pudo guardar el usuario.';
            }
        }
    }
}
$titulo = $id > 0 ? 'Gestionar usuario' : 'Nuevo usuario interno';
require 'cabecera.php';
?>
<h1><?php echo e($titulo); ?></h1>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
<form class="formulario panel" method="post">
  <?php echo campo_csrf(); ?>
  <?php if ($id > 0): ?>
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <p><strong><?php echo e($actual['nombre'] . ' ' . $actual['apellido']); ?></strong><br><?php echo e($actual['correo']); ?></p>
    <div><label for="rol">Perfil</label><select id="rol" name="rol" <?php echo $id === $yo ? 'disabled' : ''; ?>><option value="lector" <?php echo $actual['rol'] === 'lector' ? 'selected' : ''; ?>>Lector</option><option value="bibliotecario" <?php echo $actual['rol'] === 'bibliotecario' ? 'selected' : ''; ?>>Bibliotecario</option><option value="admin" <?php echo $actual['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option></select></div>
    <div><label><input type="checkbox" name="activo" value="1" <?php echo (int)$actual['activo'] ? 'checked' : ''; ?> <?php echo $id === $yo ? 'disabled' : ''; ?>> Cuenta activa</label><div class="ayuda">Una cuenta inactiva no puede iniciar sesión.</div></div>
  <?php else: ?>
    <p class="ayuda">Crea una cuenta interna de administrador o bibliotecario. Los lectores se registran por su cuenta.</p>
    <div><label for="rol">Perfil</label><select id="rol" name="rol"><option value="bibliotecario" <?php echo $d['rol'] === 'bibliotecario' ? 'selected' : ''; ?>>Bibliotecario · gestiona préstamos</option><option value="admin" <?php echo $d['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador · acceso completo</option></select></div>
    <div class="doble">
      <div><label for="nombre">Nombre</label><input id="nombre" type="text" name="nombre" required maxlength="80" value="<?php echo e($d['nombre']); ?>"></div>
      <div><label for="apellido">Apellido</label><input id="apellido" type="text" name="apellido" required maxlength="80" value="<?php echo e($d['apellido']); ?>"></div>
    </div>
    <div class="doble">
      <div><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" required maxlength="120" value="<?php echo e($d['correo']); ?>"></div>
      <div><label for="telefono">Teléfono</label><input id="telefono" type="text" name="telefono" required maxlength="20" value="<?php echo e($d['telefono']); ?>"></div>
    </div>
    <div class="doble">
      <div><label for="fecha_nacimiento">Fecha de nacimiento</label><input id="fecha_nacimiento" type="date" name="fecha_nacimiento" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($d['fecha_nacimiento']); ?>"></div>
      <div><label for="clave">Contraseña</label><input id="clave" type="password" name="clave" required minlength="8" autocomplete="new-password"><div class="ayuda">Mínimo 8 caracteres.</div></div>
    </div>
  <?php endif; ?>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> <?php echo $id > 0 ? 'Actualizar' : 'Crear usuario'; ?></button><a class="btn btn-claro" href="usuarios.php">Cancelar</a></div>
</form>
<?php require 'pie.php'; ?>
