<?php
require_once 'seguridad.php';
if (logueado()) { redirigir(es_admin() ? 'panel.php' : 'index.php'); }
$cn = db();
$error = '';
$correo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $correo = trim(isset($_POST['correo']) ? $_POST['correo'] : '');
    $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
    $bloqueo = isset($_SESSION['bloqueo_hasta']) ? (int)$_SESSION['bloqueo_hasta'] : 0;
    if ($bloqueo > time()) {
        $error = 'Demasiados intentos. Espera ' . ($bloqueo - time()) . ' segundos.';
    } elseif ($correo === '' || $clave === '') {
        $error = 'Escribe tu correo y tu contraseña.';
    } else {
        $u = fila($cn, 'SELECT id, nombre, clave, rol, activo FROM usuarios WHERE correo = ?', 's', array($correo));
        if ($u && password_verify($clave, $u['clave'])) {
            if ((int)$u['activo'] !== 1) {
                $error = 'Tu cuenta está desactivada. Habla con el administrador.';
            } else {
                session_regenerate_id(true);
                unset($_SESSION['intentos'], $_SESSION['bloqueo_hasta']);
                $_SESSION['usuario_id'] = (int)$u['id'];
                $_SESSION['nombre'] = $u['nombre'];
                $_SESSION['rol'] = $u['rol'];
                flash('ok', 'Bienvenido, ' . $u['nombre'] . '.');
                redirigir($u['rol'] === 'admin' ? 'panel.php' : 'catalogo.php');
            }
        } else {
            $_SESSION['intentos'] = (isset($_SESSION['intentos']) ? (int)$_SESSION['intentos'] : 0) + 1;
            if ($_SESSION['intentos'] >= 5) { $_SESSION['bloqueo_hasta'] = time() + 60; $_SESSION['intentos'] = 0; }
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
$titulo = 'Iniciar sesión';
require 'cabecera.php';
?>
<div class="tarjeta-form">
  <h1>Iniciar sesión</h1>
  <p>Ingresa con tu cuenta de lector o de administrador.</p>
  <?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>
  <form class="formulario" method="post" autocomplete="on">
    <?php echo campo_csrf(); ?>
    <div><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" required value="<?php echo e($correo); ?>" autocomplete="username"></div>
    <div><label for="clave">Contraseña</label><input id="clave" type="password" name="clave" required autocomplete="current-password"></div>
    <button class="btn" type="submit"><?php echo icono('check', 18); ?> Ingresar</button>
  </form>
  <p style="margin-top:18px">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
</div>
<?php require 'pie.php'; ?>
