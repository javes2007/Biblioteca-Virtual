<?php
require_once 'seguridad.php';
require_once 'exportar.php';
exigir_admin();
$cn = db();
$tipos_validos = array('prestamos' => 'Préstamos', 'lectura' => 'Lectura', 'inventario' => 'Inventario', 'sugerencias' => 'Sugerencias');
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'prestamos';
if (!isset($tipos_validos[$tipo])) { $tipo = 'prestamos'; }
$desde = trim(isset($_GET['desde']) ? $_GET['desde'] : '');
$hasta = trim(isset($_GET['hasta']) ? $_GET['hasta'] : '');
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$categoria = (int)(isset($_GET['categoria']) ? $_GET['categoria'] : 0);
$usuario = (int)(isset($_GET['usuario']) ? $_GET['usuario'] : 0);
$libro = (int)(isset($_GET['libro']) ? $_GET['libro'] : 0);
if (!fecha_valida($desde)) { $desde = ''; }
if (!fecha_valida($hasta)) { $hasta = ''; }

$encabezados = array();
$filas = array();
$resumen = array();
$extra = array();
$nombre_informe = 'Informe de ' . strtolower($tipos_validos[$tipo]);

if ($tipo === 'prestamos') {
    $estados = array('Solicitado', 'Activo', 'Vencido', 'Devuelto', 'Rechazado');
    if (!in_array($estado, $estados, true)) { $estado = ''; }
    $ref = 'COALESCE(p.fecha_prestamo, DATE(p.creado_en))';
    $donde = ' WHERE 1=1';
    $tp = '';
    $pr = array();
    if ($desde !== '') { $donde .= ' AND ' . $ref . ' >= ?'; $tp .= 's'; $pr[] = $desde; }
    if ($hasta !== '') { $donde .= ' AND ' . $ref . ' <= ?'; $tp .= 's'; $pr[] = $hasta; }
    if ($usuario > 0) { $donde .= ' AND p.usuario_id = ?'; $tp .= 'i'; $pr[] = $usuario; }
    if ($categoria > 0) { $donde .= ' AND l.categoria_id = ?'; $tp .= 'i'; $pr[] = $categoria; }
    if ($estado === 'Vencido') {
        $donde .= " AND p.estado = 'Activo' AND p.fecha_limite < CURDATE()";
    } elseif ($estado === 'Activo') {
        $donde .= " AND p.estado = 'Activo' AND p.fecha_limite >= CURDATE()";
    } elseif ($estado !== '') {
        $donde .= ' AND p.estado = ?';
        $tp .= 's';
        $pr[] = $estado;
    }
    $desde_sql = ' FROM prestamos p JOIN usuarios u ON u.id = p.usuario_id JOIN libros l ON l.id = p.libro_id JOIN categorias c ON c.id = l.categoria_id' . $donde;
    $datos = consulta($cn, 'SELECT p.id, ' . $ref . ' AS fecha, u.nombre, u.apellido, u.telefono, l.titulo, c.nombre AS categoria, p.fecha_limite, p.fecha_devolucion, ' . sql_estado_real() . ' AS estado_real' . $desde_sql . ' ORDER BY p.id DESC', $tp, $pr);
    $encabezados = array('N°', 'Fecha', 'Lector', 'Teléfono', 'Libro', 'Categoría', 'Fecha límite', 'Devolución', 'Estado');
    $conteo = array('Solicitado' => 0, 'Activo' => 0, 'Vencido' => 0, 'Devuelto' => 0, 'Rechazado' => 0);
    foreach ($datos as $d) {
        $filas[] = array($d['id'], fecha_es($d['fecha']), $d['nombre'] . ' ' . $d['apellido'], $d['telefono'], $d['titulo'], $d['categoria'], fecha_es($d['fecha_limite']), fecha_es($d['fecha_devolucion']), $d['estado_real']);
        $conteo[$d['estado_real']]++;
    }
    $resumen = array(array('Registros', count($filas)), array('Solicitados', $conteo['Solicitado']), array('Activos', $conteo['Activo']), array('Vencidos', $conteo['Vencido']), array('Devueltos', $conteo['Devuelto']));
    $top = consulta($cn, 'SELECT l.titulo, COUNT(*) AS total' . $desde_sql . ' GROUP BY l.id, l.titulo ORDER BY total DESC, l.titulo ASC LIMIT 5', $tp, $pr);
    $extra[] = array('Libros más prestados', array('Libro', 'Préstamos'), array_map(function ($t) { return array($t['titulo'], $t['total']); }, $top));
} elseif ($tipo === 'lectura') {
    $estados = array('Leyendo', 'Terminado');
    if (!in_array($estado, $estados, true)) { $estado = ''; }
    $donde = ' WHERE 1=1';
    $tp = '';
    $pr = array();
    if ($desde !== '') { $donde .= ' AND DATE(ls.actualizado_en) >= ?'; $tp .= 's'; $pr[] = $desde; }
    if ($hasta !== '') { $donde .= ' AND DATE(ls.actualizado_en) <= ?'; $tp .= 's'; $pr[] = $hasta; }
    if ($libro > 0) { $donde .= ' AND ls.libro_id = ?'; $tp .= 'i'; $pr[] = $libro; }
    if ($categoria > 0) { $donde .= ' AND l.categoria_id = ?'; $tp .= 'i'; $pr[] = $categoria; }
    if ($estado === 'Leyendo') { $donde .= ' AND ls.terminado = 0'; }
    if ($estado === 'Terminado') { $donde .= ' AND ls.terminado = 1'; }
    $desde_sql = ' FROM lecturas ls JOIN usuarios u ON u.id = ls.usuario_id JOIN libros l ON l.id = ls.libro_id JOIN categorias c ON c.id = l.categoria_id' . $donde;
    $datos = consulta($cn, 'SELECT u.nombre, u.apellido, l.titulo, c.nombre AS categoria, ls.capitulo_orden, (SELECT COUNT(*) FROM capitulos cp WHERE cp.libro_id = l.id) AS caps, ls.progreso, ls.terminado, ls.iniciado_en, ls.actualizado_en' . $desde_sql . ' ORDER BY ls.actualizado_en DESC', $tp, $pr);
    $encabezados = array('Lector', 'Libro', 'Categoría', 'Capítulo actual', 'Total capítulos', 'Avance (%)', 'Estado', 'Inicio', 'Última lectura');
    $term = 0;
    $suma = 0;
    foreach ($datos as $d) {
        $filas[] = array($d['nombre'] . ' ' . $d['apellido'], $d['titulo'], $d['categoria'], $d['capitulo_orden'], $d['caps'], $d['progreso'], (int)$d['terminado'] ? 'Terminado' : 'Leyendo', fecha_hora_es($d['iniciado_en']), fecha_hora_es($d['actualizado_en']));
        $term += (int)$d['terminado'];
        $suma += (int)$d['progreso'];
    }
    $resumen = array(array('Lecturas registradas', count($filas)), array('Libros terminados', $term), array('Lecturas en curso', count($filas) - $term), array('Avance promedio (%)', count($filas) ? round($suma / count($filas)) : 0));
    $porlibro = consulta($cn, 'SELECT l.titulo, COUNT(*) AS lectores, SUM(ls.terminado) AS terminados, ROUND(AVG(ls.progreso)) AS promedio' . $desde_sql . ' GROUP BY l.id, l.titulo ORDER BY lectores DESC, l.titulo ASC', $tp, $pr);
    $extra[] = array('Resumen por libro', array('Libro', 'Lectores', 'Terminaron', 'Avance promedio (%)'), array_map(function ($t) { return array($t['titulo'], $t['lectores'], (int)$t['terminados'], (int)$t['promedio']); }, $porlibro));
} elseif ($tipo === 'inventario') {
    $donde = ' WHERE 1=1';
    $tp = '';
    $pr = array();
    if ($categoria > 0) { $donde .= ' AND l.categoria_id = ?'; $tp .= 'i'; $pr[] = $categoria; }
    $datos = consulta($cn, 'SELECT l.codigo, l.titulo, a.nombre AS autor, c.nombre AS categoria, l.editorial, l.anio, l.ejemplares, (SELECT COUNT(*) FROM prestamos px WHERE px.libro_id = l.id AND px.estado = \'Activo\') AS prestados, ' . sql_disponibles('l') . ' AS disponibles, (SELECT COUNT(*) FROM capitulos cp WHERE cp.libro_id = l.id) AS caps, (l.archivo IS NOT NULL) AS pdf, ' . sql_promedio('l') . ' AS promedio, ' . sql_votos('l') . ' AS votos FROM libros l JOIN autores a ON a.id = l.autor_id JOIN categorias c ON c.id = l.categoria_id' . $donde . ' ORDER BY l.titulo ASC', $tp, $pr);
    $encabezados = array('Código', 'Título', 'Autor', 'Categoría', 'Editorial', 'Año', 'Ejemplares', 'Prestados', 'Disponibles', 'Capítulos', 'PDF', 'Calificación', 'Votos');
    $te = 0; $tprest = 0; $tdisp = 0;
    foreach ($datos as $d) {
        $filas[] = array($d['codigo'], $d['titulo'], $d['autor'], $d['categoria'], $d['editorial'], $d['anio'], $d['ejemplares'], $d['prestados'], $d['disponibles'], $d['caps'], (int)$d['pdf'] ? 'Sí' : 'No', $d['promedio'] === null ? '' : $d['promedio'], $d['votos']);
        $te += (int)$d['ejemplares']; $tprest += (int)$d['prestados']; $tdisp += (int)$d['disponibles'];
    }
    $resumen = array(array('Títulos', count($filas)), array('Ejemplares físicos', $te), array('Prestados', $tprest), array('Disponibles', $tdisp));
} else {
    $estados = array('Pendiente', 'Aprobada', 'Rechazada', 'Adquirida');
    if (!in_array($estado, $estados, true)) { $estado = ''; }
    $donde = ' WHERE 1=1';
    $tp = '';
    $pr = array();
    if ($desde !== '') { $donde .= ' AND DATE(s.creado_en) >= ?'; $tp .= 's'; $pr[] = $desde; }
    if ($hasta !== '') { $donde .= ' AND DATE(s.creado_en) <= ?'; $tp .= 's'; $pr[] = $hasta; }
    if ($estado !== '') { $donde .= ' AND s.estado = ?'; $tp .= 's'; $pr[] = $estado; }
    $datos = consulta($cn, 'SELECT s.creado_en, u.nombre, u.apellido, s.titulo, s.autor, s.motivo, s.estado, s.respuesta FROM sugerencias s JOIN usuarios u ON u.id = s.usuario_id' . $donde . ' ORDER BY s.id DESC', $tp, $pr);
    $encabezados = array('Fecha', 'Lector', 'Libro sugerido', 'Autor', 'Motivo', 'Estado', 'Respuesta');
    $conteo = array('Pendiente' => 0, 'Aprobada' => 0, 'Rechazada' => 0, 'Adquirida' => 0);
    foreach ($datos as $d) {
        $filas[] = array(fecha_es($d['creado_en']), $d['nombre'] . ' ' . $d['apellido'], $d['titulo'], $d['autor'], $d['motivo'], $d['estado'], $d['respuesta']);
        $conteo[$d['estado']]++;
    }
    $resumen = array(array('Sugerencias', count($filas)), array('Pendientes', $conteo['Pendiente']), array('Aprobadas', $conteo['Aprobada']), array('Adquiridas', $conteo['Adquirida']), array('Rechazadas', $conteo['Rechazada']));
}

if (isset($_GET['excel'])) {
    exportar_excel('informe_' . $tipo, $encabezados, $filas);
}

$cats = consulta($cn, 'SELECT id, nombre FROM categorias ORDER BY nombre');
$lectores = consulta($cn, "SELECT id, nombre, apellido FROM usuarios WHERE rol = 'lector' ORDER BY apellido, nombre");
$lista_libros = consulta($cn, 'SELECT id, titulo FROM libros ORDER BY titulo');
$parametros = array('tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'estado' => $estado, 'categoria' => $categoria, 'usuario' => $usuario, 'libro' => $libro);
$titulo = 'Informes';
require 'cabecera.php';
?>
<div class="seccion-titulo no-imprimir" style="margin-top:0"><h1>Informes</h1>
  <div class="acciones"><button class="btn btn-claro" type="button" onclick="window.print()"><?php echo icono('imprimir', 16); ?> Imprimir / Guardar PDF</button><a class="btn" href="informes.php?<?php echo e(http_build_query(array_merge($parametros, array('excel' => 1)))); ?>"><?php echo icono('descargar', 16); ?> Descargar Excel</a></div></div>
<div class="pestanas no-imprimir">
  <?php foreach ($tipos_validos as $k => $nombre): ?><a class="<?php echo $k === $tipo ? 'activo' : ''; ?>" href="informes.php?tipo=<?php echo e($k); ?>"><?php echo e($nombre); ?></a><?php endforeach; ?>
</div>

<form class="panel no-imprimir" method="get">
  <input type="hidden" name="tipo" value="<?php echo e($tipo); ?>">
  <div class="filtros">
    <?php if ($tipo !== 'inventario'): ?>
    <div><label for="desde">Desde</label><input id="desde" type="date" name="desde" value="<?php echo e($desde); ?>"></div>
    <div><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="<?php echo e($hasta); ?>"></div>
    <?php endif; ?>
    <?php if ($tipo !== 'inventario'): ?>
    <div><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><?php foreach ($estados as $es): ?><option value="<?php echo e($es); ?>" <?php echo $estado === $es ? 'selected' : ''; ?>><?php echo e($es); ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <?php if ($tipo === 'prestamos'): ?>
    <div><label for="usuario">Lector</label><select id="usuario" name="usuario"><option value="0">Todos</option><?php foreach ($lectores as $u): ?><option value="<?php echo (int)$u['id']; ?>" <?php echo $usuario === (int)$u['id'] ? 'selected' : ''; ?>><?php echo e($u['apellido'] . ' ' . $u['nombre']); ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <?php if ($tipo === 'lectura'): ?>
    <div><label for="libro">Libro</label><select id="libro" name="libro"><option value="0">Todos</option><?php foreach ($lista_libros as $l): ?><option value="<?php echo (int)$l['id']; ?>" <?php echo $libro === (int)$l['id'] ? 'selected' : ''; ?>><?php echo e($l['titulo']); ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <?php if ($tipo !== 'sugerencias'): ?>
    <div><label for="categoria">Categoría</label><select id="categoria" name="categoria"><option value="0">Todas</option><?php foreach ($cats as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $categoria === (int)$c['id'] ? 'selected' : ''; ?>><?php echo e($c['nombre']); ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <div><button class="btn" type="submit"><?php echo icono('buscar', 16); ?> Generar informe</button> <a href="informes.php?tipo=<?php echo e($tipo); ?>">Limpiar</a></div>
  </div>
</form>

<h2><?php echo e($nombre_informe); ?> <small style="font-weight:400;font-size:.6em">· <?php echo date('d/m/Y H:i'); ?></small></h2>
<section class="estadisticas">
  <?php foreach ($resumen as $r): ?><div class="dato"><div><strong><?php echo e($r[1]); ?></strong><small><?php echo e($r[0]); ?></small></div></div><?php endforeach; ?>
</section>

<?php if (!count($filas)): ?><div class="vacio">No hay datos con esos filtros.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><?php foreach ($encabezados as $h): ?><th><?php echo e($h); ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?><tr><?php foreach ($f as $v): ?><td><?php echo e($v); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>

<?php foreach ($extra as $t): ?>
  <h3 style="margin-top:26px"><?php echo e($t[0]); ?></h3>
  <?php if (!count($t[2])): ?><div class="vacio">Sin datos.</div><?php else: ?>
  <div class="tabla-wrap"><table>
    <thead><tr><?php foreach ($t[1] as $h): ?><th><?php echo e($h); ?></th><?php endforeach; ?></tr></thead>
    <tbody><?php foreach ($t[2] as $f): ?><tr><?php foreach ($f as $v): ?><td><?php echo e($v); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
<?php endforeach; ?>
<?php require 'pie.php'; ?>
