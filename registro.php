<?php
require_once 'seguridad.php';
if (logueado()) { redirigir(es_admin() ? 'panel.php' : (es_bibliotecario() ? 'panel_bibliotecario.php' : 'index.php')); }
$cn = db();
$error = '';
$d = array('nombre' => '', 'apellido' => '', 'correo' => '', 'telefono' => '', 'fecha_nacimiento' => '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    foreach ($d as $k => $v) { $d[$k] = trim(isset($_POST[$k]) ? $_POST[$k] : ''); }
    $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
    $confirmar = isset($_POST['confirmar']) ? $_POST['confirmar'] : '';
    if ($d['nombre'] === '' || $d['apellido'] === '' || mb_strlen($d['nombre']) > 80 || mb_strlen($d['apellido']) > 80) {
        $error = 'Escribe tu nombre y tu apellido.';
    } elseif (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['correo']) > 120) {
        $error = 'Escribe un correo válido.';
    } elseif (!telefono_valido($d['telefono'])) {
        $error = 'Escribe un teléfono válido (solo números, de 7 a 20 dígitos).';
    } elseif (!nacimiento_valido($d['fecha_nacimiento'])) {
        $error = 'Escribe una fecha de nacimiento válida.';
    } elseif (strlen($clave) < 8) {
        $error = 'La contraseña debe tener mínimo 8 caracteres.';
    } elseif ($clave !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            ejecutar($cn, "INSERT INTO usuarios (nombre, apellido, correo, telefono, fecha_nacimiento, clave, rol) VALUES (?, ?, ?, ?, ?, ?, 'lector')", 'ssssss', array($d['nombre'], $d['apellido'], $d['correo'], $d['telefono'], $d['fecha_nacimiento'], $hash));
            $nuevo = (int)$cn->insert_id;
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $nuevo;
            $_SESSION['nombre'] = $d['nombre'];
            $_SESSION['rol'] = 'lector';
            flash('ok', 'Tu cuenta fue creada. ¡Ya puedes solicitar libros!');
            redirigir('catalogo.php');
        } catch (mysqli_sql_exception $ex) {
            $error = ((int)$ex->getCode() === 1062) ? 'Ese correo ya está registrado. Inicia sesión.' : 'No se pudo crear la cuenta. Inténtalo de nuevo.';
        }
    }
}
$titulo = 'Crear cuenta';
require 'cabecera.php';
?>
<div class="tarjeta-form">
  <h1>Crear cuenta</h1>
  <p>Regístrate para solicitar libros en préstamo.</p>
  <?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
  <form class="formulario" method="post">
    <?php echo campo_csrf(); ?>
    <div class="doble">
      <div><label for="nombre">Nombre</label><input id="nombre" type="text" name="nombre" required maxlength="80" value="<?php echo e($d['nombre']); ?>" autocomplete="given-name"></div>
      <div><label for="apellido">Apellido</label><input id="apellido" type="text" name="apellido" required maxlength="80" value="<?php echo e($d['apellido']); ?>" autocomplete="family-name"></div>
    </div>
    <div><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" required maxlength="120" value="<?php echo e($d['correo']); ?>" autocomplete="email"></div>
    <div><label for="telefono">Teléfono</label><input id="telefono" type="text" name="telefono" required maxlength="20" value="<?php echo e($d['telefono']); ?>" autocomplete="tel"><div class="ayuda">La biblioteca lo usa para avisarte de tus préstamos.</div></div>
    <div><label for="fecha_nacimiento">Fecha de nacimiento</label><input id="fecha_nacimiento" type="date" name="fecha_nacimiento" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($d['fecha_nacimiento']); ?>"><div class="ayuda">Después de registrarte no podrás cambiarla sin permiso del administrador.</div></div>
    <div><label for="clave">Contraseña</label><input id="clave" type="password" name="clave" required minlength="8" autocomplete="new-password"><div class="ayuda">Mínimo 8 caracteres.</div></div>
    <div><label for="confirmar">Confirmar contraseña</label><input id="confirmar" type="password" name="confirmar" required minlength="8" autocomplete="new-password"></div>
    <button class="btn" type="submit"><?php echo icono('check', 18); ?> Registrarme</button>
  </form>
  <p style="margin-top:18px">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</div>
<?php require 'pie.php'; ?>
