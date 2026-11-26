<?php
require_once 'seguridad.php';
exigir_admin();
$cn = db();
$libro_id = (int)(isset($_REQUEST['libro']) ? $_REQUEST['libro'] : 0);
$libro = fila($cn, 'SELECT id, titulo, archivo FROM libros WHERE id = ?', 'i', array($libro_id));
if (!$libro) {
    flash('error', 'El libro no existe.');
    redirigir('libros.php');
}

function dividir_texto($texto, $unir) {
    $texto = str_replace(array("\r\n", "\r"), "\n", $texto);
    if (!mb_check_encoding($texto, 'UTF-8')) { $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252'); }
    $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
    $patron = '/^\s*((cap[ií]tulo|parte|libro)\s+([0-9]+|[ivxlcdm]+|primero|segundo|tercero|cuarto|quinto|sexto|séptimo|octavo|noveno|décimo)\b.*)$/iu';
    $caps = array();
    $titulo = null;
    $buf = array();
    foreach (explode("\n", $texto) as $ln) {
        if (mb_strlen(trim($ln)) <= 100 && preg_match($patron, $ln, $m)) {
            if ($titulo !== null || trim(implode("\n", $buf)) !== '') { $caps[] = array($titulo === null ? 'Inicio' : $titulo, implode("\n", $buf)); }
            $titulo = trim($m[1]);
            $buf = array();
        } else {
            $buf[] = $ln;
        }
    }
    if ($titulo !== null || trim(implode("\n", $buf)) !== '') { $caps[] = array($titulo === null ? 'Texto completo' : $titulo, implode("\n", $buf)); }
    if (count($caps) === 1 && mb_strlen($caps[0][1]) > 6000) {
        $partes = array();
        $actual = '';
        foreach (preg_split('/\n{2,}/', trim($caps[0][1])) as $b) {
            if ($actual !== '' && mb_strlen($actual) + mb_strlen($b) > 4000) { $partes[] = $actual; $actual = ''; }
            $actual .= ($actual === '' ? '' : "\n\n") . $b;
        }
        if ($actual !== '') { $partes[] = $actual; }
        $caps = array();
        foreach ($partes as $i => $p) { $caps[] = array('Parte ' . ($i + 1), $p); }
    }
    $salida = array();
    foreach ($caps as $c) {
        $contenido = trim($c[1]);
        if ($contenido === '') { continue; }
        if ($unir) {
            $bloques = array();
            foreach (preg_split('/\n{2,}/', $contenido) as $b) { $bloques[] = trim(preg_replace('/\s*\n\s*/', ' ', $b)); }
            $contenido = implode("\n\n", $bloques);
        }
        $salida[] = array(mb_substr($c[0], 0, 150), mb_substr($contenido, 0, 200000));
    }
    return array_slice($salida, 0, 300);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    if ($accion === 'eliminar_cap') {
        $cid = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        $n = ejecutar($cn, 'DELETE FROM capitulos WHERE id = ? AND libro_id = ?', 'ii', array($cid, $libro_id));
        flash($n > 0 ? 'ok' : 'error', $n > 0 ? 'Capítulo eliminado.' : 'El capítulo no existe.');
    } elseif ($accion === 'importar') {
        if (!isset($_FILES['texto']) || $_FILES['texto']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'No se pudo subir el archivo de texto. Revisa el límite de tamaño de tu servidor.');
        } elseif (strtolower(pathinfo($_FILES['texto']['name'], PATHINFO_EXTENSION)) !== 'txt' || $_FILES['texto']['size'] > 5 * 1024 * 1024) {
            flash('error', 'Sube un archivo .txt de máximo 5 MB.');
        } else {
            $caps = dividir_texto((string)file_get_contents($_FILES['texto']['tmp_name']), isset($_POST['unir']));
            if (!count($caps)) {
                flash('error', 'El archivo está vacío.');
            } else {
                $cn->begin_transaction();
                try {
                    if (isset($_POST['reemplazar'])) { ejecutar($cn, 'DELETE FROM capitulos WHERE libro_id = ?', 'i', array($libro_id)); }
                    $orden = (int)valor($cn, 'SELECT COALESCE(MAX(orden), 0) FROM capitulos WHERE libro_id = ?', 'i', array($libro_id));
                    foreach ($caps as $c) {
                        $orden++;
                        ejecutar($cn, 'INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES (?, ?, ?, ?)', 'iiss', array($libro_id, $orden, $c[0], $c[1]));
                    }
                    $cn->commit();
                    flash('ok', 'Se importaron ' . count($caps) . ' capítulo(s). El libro ya se puede leer en línea.');
                } catch (mysqli_sql_exception $ex) {
                    $cn->rollback();
                    flash('error', 'No se pudo importar el texto.');
                }
            }
        }
    } elseif ($accion === 'subir_pdf') {
        if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Elige un archivo PDF.');
        } else {
            $r = guardar_pdf($_FILES['pdf'], $libro_id);
            if ($r[0]) {
                borrar_pdf($libro['archivo']);
                ejecutar($cn, 'UPDATE libros SET archivo = ? WHERE id = ?', 'si', array($r[1], $libro_id));
                flash('ok', 'El PDF fue guardado. Los lectores ya pueden abrirlo en línea.');
            } else {
                flash('error', $r[1]);
            }
        }
    } elseif ($accion === 'quitar_pdf') {
        borrar_pdf($libro['archivo']);
        ejecutar($cn, 'UPDATE libros SET archivo = NULL WHERE id = ?', 'i', array($libro_id));
        flash('ok', 'El PDF fue eliminado.');
    }
    redirigir('capitulos.php?libro=' . $libro_id);
}
$caps = consulta($cn, 'SELECT id, orden, titulo, CHAR_LENGTH(contenido) AS largo FROM capitulos WHERE libro_id = ? ORDER BY orden', 'i', array($libro_id));
$libro = fila($cn, 'SELECT id, titulo, archivo FROM libros WHERE id = ?', 'i', array($libro_id));
$titulo = 'Capítulos de ' . $libro['titulo'];
require 'cabecera.php';
?>
<p><a href="libros.php">← Volver a los libros</a></p>
<div class="seccion-titulo" style="margin-top:0"><h1>Lectura en línea: <?php echo e($libro['titulo']); ?></h1>
  <div class="acciones"><a class="btn" href="capitulo_form.php?libro=<?php echo $libro_id; ?>"><?php echo icono('mas', 16); ?> Nuevo capítulo</a><?php if (count($caps) || $libro['archivo']): ?><a class="btn btn-claro" href="leer.php?id=<?php echo $libro_id; ?>"><?php echo icono('abierto', 16); ?> Probar lectura</a><?php endif; ?></div></div>

<h2>Capítulos</h2>
<?php if (!count($caps)): ?><div class="vacio">Este libro todavía no tiene capítulos. Crea uno o importa un archivo de texto.</div><?php else: ?>
<div class="tabla-wrap"><table>
  <thead><tr><th>Orden</th><th>Título</th><th>Caracteres</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($caps as $c): ?>
    <tr>
      <td><?php echo (int)$c['orden']; ?></td>
      <td><strong><?php echo e($c['titulo']); ?></strong></td>
      <td><?php echo number_format((int)$c['largo'], 0, ',', '.'); ?></td>
      <td class="acciones">
        <a class="btn btn-sm btn-claro" href="capitulo_form.php?id=<?php echo (int)$c['id']; ?>">Editar</a>
        <form method="post" onsubmit="return confirm('¿Eliminar este capítulo? También se borrarán los resaltados y marcadores de los lectores en él.');"><?php echo campo_csrf(); ?><input type="hidden" name="libro" value="<?php echo $libro_id; ?>"><input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>"><input type="hidden" name="accion" value="eliminar_cap"><button class="btn btn-sm btn-peligro" type="submit"><?php echo icono('borrar', 15); ?> Eliminar</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>

<div class="dos-columnas" style="margin-top:26px">
  <form class="panel formulario" method="post" enctype="multipart/form-data">
    <h2><?php echo icono('descargar', 20); ?> Importar texto (.txt)</h2>
    <p class="ayuda">El sistema divide el texto en capítulos cuando encuentra líneas como «Capítulo 1», «Parte II» o similares. Si no las hay, lo parte en secciones automáticamente.</p>
    <?php echo campo_csrf(); ?><input type="hidden" name="libro" value="<?php echo $libro_id; ?>"><input type="hidden" name="accion" value="importar">
    <div><label for="texto">Archivo .txt (UTF-8, máximo 5 MB)</label><input id="texto" type="file" name="texto" accept=".txt,text/plain" required></div>
    <div><label><input type="checkbox" name="unir" value="1" checked> Unir las líneas de un mismo párrafo (recomendado para libros en prosa)</label></div>
    <div><label><input type="checkbox" name="reemplazar" value="1"> Reemplazar los capítulos actuales</label></div>
    <div class="acciones"><button class="btn" type="submit">Importar capítulos</button></div>
  </form>
  <div class="panel formulario">
    <h2><?php echo icono('libro', 20); ?> Archivo PDF</h2>
    <?php if ($libro['archivo']): ?>
      <div class="mensaje">Este libro tiene un PDF. Los lectores pueden abrirlo desde el lector.</div>
      <form method="post" onsubmit="return confirm('¿Eliminar el PDF de este libro?');"><?php echo campo_csrf(); ?><input type="hidden" name="libro" value="<?php echo $libro_id; ?>"><input type="hidden" name="accion" value="quitar_pdf"><button class="btn btn-peligro" type="submit"><?php echo icono('borrar', 16); ?> Quitar PDF</button></form>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="formulario">
      <?php echo campo_csrf(); ?><input type="hidden" name="libro" value="<?php echo $libro_id; ?>"><input type="hidden" name="accion" value="subir_pdf">
      <div><label for="pdf"><?php echo $libro['archivo'] ? 'Reemplazar el PDF' : 'Subir un PDF'; ?> (máximo 25 MB)</label><input id="pdf" type="file" name="pdf" accept="application/pdf,.pdf" required></div>
      <p class="ayuda">Sube solo libros de dominio público o que tengas derecho a compartir.</p>
      <div class="acciones"><button class="btn" type="submit">Subir PDF</button></div>
    </form>
  </div>
</div>
<?php require 'pie.php'; ?>
