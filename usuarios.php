<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$yo = (int)$_SESSION['usuario_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $u = fila($cn, 'SELECT foto FROM usuarios WHERE id = ?', 'i', array($id));
    $activos = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado IN ('Solicitado','Activo')", 'i', array($id));
    if ($id === $yo) {
        flash('error', 'No puedes eliminar tu propia cuenta.');
    } elseif (!$u) {
        flash('error', 'El usuario no existe.');
    } elseif ($activos > 0) {
        flash('error', 'No se puede eliminar: el usuario tiene libros prestados o solicitudes en curso. Puedes desactivar su cuenta.');
    } else {
        ejecutar($cn, 'DELETE FROM usuarios WHERE id = ?', 'i', array($id));
        borrar_avatar($u['foto']);
        flash('ok', 'Usuario eliminado junto con su actividad en la biblioteca.');
    }
    redirigir('usuarios.php');
}
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$rol = isset($_GET['rol']) ? $_GET['rol'] : '';
if ($rol !== 'admin' && $rol !== 'lector') { $rol = ''; }
$donde = ' WHERE 1=1';
$tipos = '';
$params = array();
if ($q !== '') {
    $donde .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ? OR u.telefono LIKE ?)';
    $like = '%' . $q . '%';
    $tipos .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if ($rol !== '') { $donde .= ' AND u.rol = ?'; $tipos .= 's'; $params[] = $rol; }
$filas = consulta($cn, "SELECT u.id, u.nombre, u.apellido, u.correo, u.telefono, u.rol, u.activo, u.creado_en, (SELECT COUNT(*) FROM prestamos p WHERE p.usuario_id = u.id AND p.estado = 'Activo') AS prestados, (SELECT COUNT(*) FROM prestamos p WHERE p.usuario_id = u.id AND p.estado = 'Activo' AND p.fecha_limite < CURDATE()) AS vencidos FROM usuarios u" . $donde . ' ORDER BY u.rol, u.apellido, u.nombre', $tipos, $params);
$pendientes = (int)valor($cn, "SELECT COUNT(*) FROM cambios_edad WHERE estado = 'Pendiente'");
$titulo = 'Usuarios';
require 'cabecera.php';
?>
<div class="seccion-titulo" style="margin-top:0"><h1>Usuarios</h1>
  <div class="acciones"><a class="btn btn-oro" href="solicitudes_edad.php"><?php echo icono('reloj', 16); ?> Solicitudes de cambio de fecha<?php if ($pendientes > 0): ?> <span class="globo"><?php echo $pendientes; ?></span><?php endif; ?></a><a class="btn" href="usuario_form.php"><?php echo icono('mas', 16); ?> Nuevo administrador</a></div></div>
<p class="ayuda">Los lectores se crean solos al registrarse en la página. Desde aquí puedes activar o desactivar cuentas, cambiar el perfil y eliminar usuarios. Por privacidad no se muestra el perfil personal de los lectores.</p>
<form class="barra-acciones" method="get">
  <div class="buscar"><input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Buscar por nombre, correo o teléfono"><button class="btn" type="submit"><?php echo icono('buscar', 16); ?></button></div>
  <select name="rol" style="width:auto" onchange="this.form.submit()"><option value="">Todos los perfiles</option><option value="admin" <?php echo $rol === 'admin' ? 'selected' : ''; ?>>Administradores</option><option value="lector" <?php echo $rol === 'lector' ? 'selected' : ''; ?>>Lectores</option></select>
  <?php if ($q !== '' || $rol !== ''): ?><a href="usuarios.php">Limpiar</a><?php endif; ?>
</form>
<?php if (!count($filas)): ?><div class="vacio">No hay usuarios para mostrar.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Perfil</th><th>Cuenta</th><th>Prestados</th><th>Registro</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><strong><?php echo e($f['nombre'] . ' ' . $f['apellido']); ?></strong></td>
      <td><?php echo e($f['correo']); ?></td>
      <td><a class="tel" href="<?php echo e(telefono_enlace($f['telefono'])); ?>"><?php echo icono('telefono', 15); ?> <?php echo e($f['telefono']); ?></a></td>
      <td><?php echo $f['rol'] === 'admin' ? 'Administrador' : 'Lector'; ?></td>
      <td><span class="<?php echo (int)$f['activo'] ? 'est est-devuelto' : 'est est-rechazado'; ?>"><?php echo (int)$f['activo'] ? 'Activa' : 'Inactiva'; ?></span></td>
      <td><?php echo (int)$f['prestados']; ?><?php if ((int)$f['vencidos'] > 0): ?> <span class="est est-vencido"><?php echo (int)$f['vencidos']; ?> vencido(s)</span><?php endif; ?></td>
      <td><?php echo fecha_es($f['creado_en']); ?></td>
      <td class="acciones">
        <?php if ($f['rol'] === 'lector' && (int)$f['activo']): ?><a class="btn btn-sm btn-oro" href="prestamo_form.php?usuario_id=<?php echo (int)$f['id']; ?>">Prestar libro</a><?php endif; ?>
        <a class="btn btn-sm btn-claro" href="usuario_form.php?id=<?php echo (int)$f['id']; ?>">Gestionar</a>
        <?php if ((int)$f['id'] !== $yo): ?>
        <form method="post" onsubmit="return confirm('¿Eliminar a <?php echo e(addslashes($f['nombre'] . ' ' . $f['apellido'])); ?>? Se borrará también su actividad de lectura.');"><?php echo campo_csrf(); ?><input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>"><button class="btn btn-sm btn-peligro" type="submit"><?php echo icono('borrar', 15); ?> Eliminar</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php require 'pie.php'; ?>
