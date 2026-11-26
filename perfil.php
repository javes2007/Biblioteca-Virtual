<?php
require_once 'seguridad.php';
exigir_login();
$cn = db();
$uid = (int)$_SESSION['usuario_id'];
$error = '';
$u = fila($cn, 'SELECT nombre, apellido, correo, telefono, fecha_nacimiento, rol, foto, tema, edad_editable, cambiar_clave FROM usuarios WHERE id = ?', 'i', array($uid));
$forzar = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    if ($forzar && $accion !== 'clave') {
        $error = 'Primero debes cambiar tu contraseña.';
    } elseif ($accion === 'datos') {
        $nombre = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
        $apellido = trim(isset($_POST['apellido']) ? $_POST['apellido'] : '');
        $correo = trim(isset($_POST['correo']) ? $_POST['correo'] : '');
        $telefono = trim(isset($_POST['telefono']) ? $_POST['telefono'] : '');
        $u['nombre'] = $nombre; $u['apellido'] = $apellido; $u['correo'] = $correo; $u['telefono'] = $telefono;
        if ($nombre === '' || $apellido === '' || mb_strlen($nombre) > 80 || mb_strlen($apellido) > 80) {
            $error = 'Escribe tu nombre y tu apellido.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
            $error = 'Escribe un correo válido.';
        } elseif (!telefono_valido($telefono)) {
            $error = 'Escribe un teléfono válido.';
        } else {
            try {
                ejecutar($cn, 'UPDATE usuarios SET nombre = ?, apellido = ?, correo = ?, telefono = ? WHERE id = ?', 'ssssi', array($nombre, $apellido, $correo, $telefono, $uid));
                flash('ok', 'Tus datos fueron actualizados.');
                redirigir('perfil.php');
            } catch (mysqli_sql_exception $ex) {
                $error = ((int)$ex->getCode() === 1062) ? 'Ese correo ya lo usa otra cuenta.' : 'No se pudieron guardar los datos.';
            }
        }
    } elseif ($accion === 'tema') {
        $tema = isset($_POST['tema']) ? $_POST['tema'] : '';
        if (in_array($tema, array('auto', 'claro', 'oscuro'), true)) {
            ejecutar($cn, 'UPDATE usuarios SET tema = ? WHERE id = ?', 'si', array($tema, $uid));
            flash('ok', 'Preferencia de apariencia guardada.');
            redirigir('perfil.php');
        }
        $error = 'Tema no válido.';
    } elseif ($accion === 'foto') {
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Elige una imagen para subir.';
        } else {
            $r = guardar_avatar($_FILES['foto'], $uid);
            if ($r[0]) {
                borrar_avatar($u['foto']);
                ejecutar($cn, 'UPDATE usuarios SET foto = ? WHERE id = ?', 'si', array($r[1], $uid));
                flash('ok', 'Tu foto de perfil fue actualizada.');
                redirigir('perfil.php');
            }
            $error = $r[1];
        }
    } elseif ($accion === 'quitar_foto') {
        borrar_avatar($u['foto']);
        ejecutar($cn, 'UPDATE usuarios SET foto = NULL WHERE id = ?', 'i', array($uid));
        flash('ok', 'Tu foto de perfil fue eliminada.');
        redirigir('perfil.php');
    } elseif ($accion === 'clave') {
        $actual = isset($_POST['clave_actual']) ? $_POST['clave_actual'] : '';
        $nueva = isset($_POST['clave_nueva']) ? $_POST['clave_nueva'] : '';
        $confirmar = isset($_POST['clave_confirmar']) ? $_POST['clave_confirmar'] : '';
        $guardada = valor($cn, 'SELECT clave FROM usuarios WHERE id = ?', 'i', array($uid));
        if (!password_verify($actual, (string)$guardada)) {
            $error = 'La contraseña actual no es correcta.';
        } elseif (strlen($nueva) < 8) {
            $error = 'La contraseña nueva debe tener mínimo 8 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $error = 'La confirmación de la contraseña no coincide.';
        } elseif ($nueva === $actual) {
            $error = 'La contraseña nueva debe ser distinta de la actual.';
        } else {
            ejecutar($cn, 'UPDATE usuarios SET clave = ?, cambiar_clave = 0 WHERE id = ?', 'si', array(password_hash($nueva, PASSWORD_DEFAULT), $uid));
            flash('ok', 'Tu contraseña fue cambiada.');
            redirigir('perfil.php');
        }
    } elseif ($accion === 'pedir_edad' && es_lector()) {
        $motivo = trim(isset($_POST['motivo']) ? $_POST['motivo'] : '');
        $abierta = (int)valor($cn, "SELECT COUNT(*) FROM cambios_edad WHERE usuario_id = ? AND estado IN ('Pendiente','Aprobada')", 'i', array($uid));
        if ($abierta > 0) {
            $error = 'Ya tienes una solicitud en curso.';
        } elseif (mb_strlen($motivo) < 15 || mb_strlen($motivo) > 500) {
            $error = 'Explica el motivo con al menos 15 letras (máximo 500).';
        } else {
            ejecutar($cn, 'INSERT INTO cambios_edad (usuario_id, motivo) VALUES (?, ?)', 'is', array($uid, $motivo));
            flash('ok', 'Enviaste tu solicitud. El administrador la revisará.');
            redirigir('perfil.php');
        }
    } elseif ($accion === 'cambiar_edad' && es_lector() && (int)$u['edad_editable'] === 1) {
        $nueva = trim(isset($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : '');
        if (!nacimiento_valido($nueva)) {
            $error = 'Escribe una fecha de nacimiento válida.';
        } else {
            ejecutar($cn, 'UPDATE usuarios SET fecha_nacimiento = ?, edad_editable = 0 WHERE id = ?', 'si', array($nueva, $uid));
            ejecutar($cn, "UPDATE cambios_edad SET estado = 'Usada' WHERE usuario_id = ? AND estado = 'Aprobada'", 'i', array($uid));
            flash('ok', 'Tu fecha de nacimiento fue corregida.');
            redirigir('perfil.php');
        }
    } else {
        $error = 'Acción no válida.';
    }
}
$solicitud = es_lector() ? fila($cn, 'SELECT estado, motivo, respuesta, creado_en FROM cambios_edad WHERE usuario_id = ? ORDER BY id DESC LIMIT 1', 'i', array($uid)) : null;
$titulo = 'Mi perfil';
require 'cabecera.php';
?>
<h1>Mi perfil</h1>
<p>Perfil: <strong><?php echo $u['rol'] === 'admin' ? 'Administrador' : 'Lector'; ?></strong></p>
<?php if ($forzar): ?><div class="mensaje mensaje-aviso">Estás usando una contraseña temporal. Elige tu propia contraseña para continuar.</div><?php endif; ?>
<?php if ($error !== ''): ?><div class="mensaje mensaje-error"><?php echo e($error); ?></div><?php endif; ?>

<form class="panel formulario" method="post">
  <?php echo campo_csrf(); ?><input type="hidden" name="accion" value="clave">
  <h2><?php echo icono('check', 20); ?> Contraseña</h2>
  <div class="doble">
    <div><label for="clave_actual">Contraseña actual</label><input id="clave_actual" type="password" name="clave_actual" required autocomplete="current-password"></div>
    <div><label for="clave_nueva">Contraseña nueva</label><input id="clave_nueva" type="password" name="clave_nueva" required minlength="8" autocomplete="new-password"></div>
  </div>
  <div><label for="clave_confirmar">Confirmar contraseña nueva</label><input id="clave_confirmar" type="password" name="clave_confirmar" required minlength="8" autocomplete="new-password"></div>
  <div class="acciones"><button class="btn" type="submit">Cambiar contraseña</button></div>
</form>

<?php if (!$forzar): ?>
<div class="panel">
  <h2><?php echo icono('usuario', 20); ?> Foto de perfil</h2>
  <div class="foto-perfil">
    <?php echo avatar_html(array('nombre' => $u['nombre'], 'apellido' => $u['apellido'], 'foto' => $u['foto']), 96); ?>
    <form method="post" enctype="multipart/form-data" class="formulario" style="flex:1;min-width:220px">
      <?php echo campo_csrf(); ?><input type="hidden" name="accion" value="foto">
      <div><label for="foto">Sube una imagen JPG, PNG o WEBP (máximo 2 MB)</label><input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp" required></div>
      <div class="acciones"><button class="btn" type="submit">Guardar foto</button></div>
    </form>
    <?php if ($u['foto']): ?>
    <form method="post" onsubmit="return confirm('¿Quitar tu foto de perfil?');"><?php echo campo_csrf(); ?><input type="hidden" name="accion" value="quitar_foto"><button class="btn btn-claro" type="submit"><?php echo icono('borrar', 16); ?> Quitar foto</button></form>
    <?php endif; ?>
  </div>
</div>

<form class="panel formulario" method="post">
  <?php echo campo_csrf(); ?><input type="hidden" name="accion" value="datos">
  <h2><?php echo icono('pluma', 20); ?> Mis datos</h2>
  <div class="doble">
    <div><label for="nombre">Nombre</label><input id="nombre" type="text" name="nombre" required maxlength="80" value="<?php echo e($u['nombre']); ?>"></div>
    <div><label for="apellido">Apellido</label><input id="apellido" type="text" name="apellido" required maxlength="80" value="<?php echo e($u['apellido']); ?>"></div>
  </div>
  <div class="doble">
    <div><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" required maxlength="120" value="<?php echo e($u['correo']); ?>"></div>
    <div><label for="telefono">Teléfono</label><input id="telefono" type="text" name="telefono" required maxlength="20" value="<?php echo e($u['telefono']); ?>"></div>
  </div>
  <div class="acciones"><button class="btn" type="submit"><?php echo icono('check', 18); ?> Guardar datos</button></div>
</form>

<form class="panel formulario" method="post">
  <?php echo campo_csrf(); ?><input type="hidden" name="accion" value="tema">
  <h2><?php echo icono('luna', 20); ?> Apariencia</h2>
  <div class="opciones-tema">
    <label><input type="radio" name="tema" value="auto" <?php echo $u['tema'] === 'auto' ? 'checked' : ''; ?>> Automático (según tu dispositivo)</label>
    <label><input type="radio" name="tema" value="claro" <?php echo $u['tema'] === 'claro' ? 'checked' : ''; ?>> Modo claro</label>
    <label><input type="radio" name="tema" value="oscuro" <?php echo $u['tema'] === 'oscuro' ? 'checked' : ''; ?>> Modo oscuro</label>
  </div>
  <div class="acciones"><button class="btn" type="submit">Guardar apariencia</button></div>
</form>

<?php if (es_lector()): ?>
<div class="panel formulario">
  <h2><?php echo icono('reloj', 20); ?> Fecha de nacimiento</h2>
  <p>Tu fecha de nacimiento: <strong><?php echo fecha_es($u['fecha_nacimiento']); ?></strong> (<?php echo edad_desde($u['fecha_nacimiento']); ?> años). No se puede cambiar libremente.</p>
  <?php if ((int)$u['edad_editable'] === 1): ?>
    <div class="mensaje">El administrador aprobó tu solicitud. Puedes corregir tu fecha una sola vez.</div>
    <form method="post" class="formulario"><?php echo campo_csrf(); ?><input type="hidden" name="accion" value="cambiar_edad">
      <div><label for="fn">Nueva fecha de nacimiento</label><input id="fn" type="date" name="fecha_nacimiento" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($u['fecha_nacimiento']); ?>"></div>
      <div class="acciones"><button class="btn" type="submit">Corregir fecha</button></div>
    </form>
  <?php elseif ($solicitud && $solicitud['estado'] === 'Pendiente'): ?>
    <div class="mensaje mensaje-aviso">Tu solicitud está pendiente de revisión. Motivo enviado: <?php echo e($solicitud['motivo']); ?></div>
  <?php else: ?>
    <?php if ($solicitud && ($solicitud['estado'] === 'Rechazada' || $solicitud['estado'] === 'Usada')): ?>
      <div class="mensaje <?php echo $solicitud['estado'] === 'Rechazada' ? 'mensaje-error' : ''; ?>">Última solicitud: <?php echo e($solicitud['estado']); ?><?php echo $solicitud['respuesta'] ? ' · ' . e($solicitud['respuesta']) : ''; ?></div>
    <?php endif; ?>
    <form method="post" class="formulario"><?php echo campo_csrf(); ?><input type="hidden" name="accion" value="pedir_edad">
      <div><label for="motivo">Si necesitas corregirla, explica el motivo al administrador</label><textarea id="motivo" name="motivo" required minlength="15" maxlength="500" rows="3"></textarea></div>
      <div class="acciones"><button class="btn btn-claro" type="submit">Solicitar permiso para cambiarla</button></div>
    </form>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php require 'pie.php'; ?>
