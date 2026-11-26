<?php
require_once 'seguridad.php';
exigir_login();
$cn = db();
$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$libro = fila($cn, 'SELECT l.id, l.titulo, l.archivo, a.nombre AS autor FROM libros l JOIN autores a ON a.id = l.autor_id WHERE l.id = ?', 'i', array($id));
if (!$libro) {
    flash('error', 'El libro no existe.');
    redirigir('catalogo.php');
}
$caps = consulta($cn, 'SELECT id, orden, titulo FROM capitulos WHERE libro_id = ? ORDER BY orden', 'i', array($id));
$total = count($caps);
$tiene_pdf = !empty($libro['archivo']);
if ($total === 0 && !$tiene_pdf) {
    flash('aviso', 'Este libro todavía no tiene contenido para leer en línea.');
    redirigir('libro.php?id=' . $id);
}
$modo = ($total === 0 || (isset($_GET['modo']) && $_GET['modo'] === 'pdf' && $tiene_pdf)) ? 'pdf' : 'texto';
$lector = es_lector();
$uid = (int)$_SESSION['usuario_id'];
$lectura = $lector ? fila($cn, 'SELECT capitulo_orden, progreso, terminado FROM lecturas WHERE usuario_id = ? AND libro_id = ?', 'ii', array($uid, $id)) : null;
$cap = null;
$indice = 0;
$resaltados = array();
$marcado = false;
if ($modo === 'texto') {
    $orden = (int)(isset($_GET['cap']) ? $_GET['cap'] : 0);
    if ($orden < 1) { $orden = $lectura ? (int)$lectura['capitulo_orden'] : (int)$caps[0]['orden']; }
    foreach ($caps as $i => $c) { if ((int)$c['orden'] === $orden) { $indice = $i; } }
    $cap = fila($cn, 'SELECT id, orden, titulo, contenido FROM capitulos WHERE id = ?', 'i', array((int)$caps[$indice]['id']));
    $progreso = (int)round((($indice + 1) / $total) * 100);
    if ($lector) {
        ejecutar($cn, 'INSERT INTO lecturas (usuario_id, libro_id, capitulo_orden, progreso) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE capitulo_orden = VALUES(capitulo_orden), progreso = GREATEST(progreso, VALUES(progreso)), actualizado_en = NOW()', 'iiii', array($uid, $id, (int)$cap['orden'], $progreso));
        $resaltados = consulta($cn, 'SELECT id, inicio, fin, color FROM resaltados WHERE usuario_id = ? AND capitulo_id = ? ORDER BY inicio', 'ii', array($uid, (int)$cap['id']));
        $marcado = (int)valor($cn, 'SELECT COUNT(*) FROM marcadores WHERE usuario_id = ? AND capitulo_id = ?', 'ii', array($uid, (int)$cap['id'])) > 0;
    }
} elseif ($lector) {
    ejecutar($cn, 'INSERT INTO lecturas (usuario_id, libro_id, capitulo_orden, progreso) VALUES (?, ?, 1, 0) ON DUPLICATE KEY UPDATE actualizado_en = NOW()', 'ii', array($uid, $id));
}
$ya_terminado = $lectura && (int)$lectura['terminado'] === 1;
$titulo = $libro['titulo'];
require 'cabecera.php';
?>
<div class="lector-barra no-imprimir">
  <div class="grupo"><a class="btn btn-sm btn-claro" href="libro.php?id=<?php echo $id; ?>">← <?php echo e(mb_substr($libro['titulo'], 0, 28)); ?></a></div>
  <?php if ($modo === 'texto'): ?>
  <form class="grupo" method="get" action="leer.php">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <label for="cap" class="ayuda" style="margin:0">Capítulo</label>
    <select id="cap" name="cap" onchange="this.form.submit()">
      <?php foreach ($caps as $c): ?><option value="<?php echo (int)$c['orden']; ?>" <?php echo (int)$c['orden'] === (int)$cap['orden'] ? 'selected' : ''; ?>><?php echo (int)$c['orden']; ?>. <?php echo e(mb_substr($c['titulo'], 0, 40)); ?></option><?php endforeach; ?>
    </select>
  </form>
  <div class="grupo">
    <button class="btn btn-sm btn-claro" type="button" id="menos" aria-label="Letra más pequeña">A−</button>
    <button class="btn btn-sm btn-claro" type="button" id="mas" aria-label="Letra más grande">A+</button>
  </div>
  <?php if ($lector): ?>
  <div class="grupo"><button class="btn btn-sm <?php echo $marcado ? '' : 'btn-claro'; ?>" type="button" id="marcador" data-activo="<?php echo $marcado ? '1' : '0'; ?>"><?php echo icono('marcador', 16); ?> <span><?php echo $marcado ? 'Capítulo marcado' : 'Marcar capítulo'; ?></span></button></div>
  <?php endif; ?>
  <?php endif; ?>
  <?php if ($modo === 'texto' && $tiene_pdf): ?><a class="btn btn-sm btn-claro" href="leer.php?id=<?php echo $id; ?>&modo=pdf">Ver PDF</a><?php endif; ?>
  <?php if ($modo === 'pdf' && $total > 0): ?><a class="btn btn-sm btn-claro" href="leer.php?id=<?php echo $id; ?>">Leer como texto</a><?php endif; ?>
  <?php if ($lector && $modo === 'texto'): ?><span class="ayuda" style="margin-left:auto">Selecciona un texto para resaltarlo · Tu avance: <?php echo (int)$progreso; ?>%</span><?php endif; ?>
</div>

<?php if ($modo === 'texto'): ?>
  <article class="lector-texto" id="contenedor-lector">
    <h1><?php echo e($cap['titulo']); ?></h1>
    <div id="texto" data-capitulo="<?php echo (int)$cap['id']; ?>" data-puede="<?php echo $lector ? '1' : '0'; ?>" data-resaltados="<?php echo e(json_encode($resaltados)); ?>">
      <?php
      $contenido = str_replace(array("\r\n", "\r"), "\n", $cap['contenido']);
      foreach (preg_split('/\n{2,}/', trim($contenido)) as $bloque) {
          echo '<p>' . nl2br(e($bloque)) . '</p>';
      }
      ?>
    </div>
  </article>
  <div class="acciones no-imprimir" style="justify-content:center;margin-top:20px">
    <?php if ($indice > 0): ?><a class="btn btn-claro" href="leer.php?id=<?php echo $id; ?>&cap=<?php echo (int)$caps[$indice - 1]['orden']; ?>">← Anterior</a><?php endif; ?>
    <?php if ($indice < $total - 1): ?>
      <a class="btn" href="leer.php?id=<?php echo $id; ?>&cap=<?php echo (int)$caps[$indice + 1]['orden']; ?>">Siguiente →</a>
    <?php elseif ($lector && !$ya_terminado): ?>
      <button class="btn btn-oro" type="button" id="terminar"><?php echo icono('check', 16); ?> Terminé de leer este libro</button>
    <?php else: ?>
      <a class="btn btn-claro" href="libro.php?id=<?php echo $id; ?>">Calificar y comentar el libro</a>
    <?php endif; ?>
  </div>
  <div id="paleta-resaltar" role="toolbar" aria-label="Elige un color para resaltar">
    <button type="button" class="col-amarillo" data-color="amarillo" aria-label="Amarillo"></button>
    <button type="button" class="col-verde" data-color="verde" aria-label="Verde"></button>
    <button type="button" class="col-rosa" data-color="rosa" aria-label="Rosa"></button>
    <button type="button" class="col-azul" data-color="azul" aria-label="Azul"></button>
  </div>
  <script>
  (function () {
    var cont = document.getElementById('texto');
    var lector = document.getElementById('contenedor-lector');
    var csrf = document.body.getAttribute('data-csrf');
    var libroId = <?php echo (int)$id; ?>;
    var capId = cont.getAttribute('data-capitulo');
    var puede = cont.getAttribute('data-puede') === '1';
    var paleta = document.getElementById('paleta-resaltar');
    var guardada = null;
    function api(datos) {
      var d = new URLSearchParams();
      d.append('csrf', csrf);
      for (var k in datos) { d.append(k, datos[k]); }
      return fetch('lectura_api.php', { method: 'POST', body: d, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }
    var fs = 1.15;
    try { fs = parseFloat(localStorage.getItem('bv_lfs')) || 1.15; } catch (e) {}
    function pintarFuente() { lector.style.setProperty('--lfs', fs); try { localStorage.setItem('bv_lfs', fs); } catch (e) {} }
    pintarFuente();
    document.getElementById('menos').addEventListener('click', function () { fs = Math.max(0.8, Math.round((fs - 0.1) * 100) / 100); pintarFuente(); });
    document.getElementById('mas').addEventListener('click', function () { fs = Math.min(2, Math.round((fs + 0.1) * 100) / 100); pintarFuente(); });
    function nodosTexto() {
      var w = document.createTreeWalker(cont, NodeFilter.SHOW_TEXT, null, false), n, pos = 0, arr = [];
      while ((n = w.nextNode())) { arr.push({ nodo: n, ini: pos, fin: pos + n.nodeValue.length }); pos += n.nodeValue.length; }
      return arr;
    }
    function aplicar(r) {
      var lista = nodosTexto();
      for (var i = 0; i < lista.length; i++) {
        var t = lista[i];
        if (t.fin <= r.inicio || t.ini >= r.fin) { continue; }
        var a = Math.max(r.inicio, t.ini) - t.ini, b = Math.min(r.fin, t.fin) - t.ini;
        var rango = document.createRange();
        rango.setStart(t.nodo, a);
        rango.setEnd(t.nodo, b);
        var m = document.createElement('mark');
        m.className = 'res res-' + r.color;
        m.setAttribute('data-id', r.id);
        rango.surroundContents(m);
      }
    }
    if (puede) {
      var previos = [];
      try { previos = JSON.parse(cont.getAttribute('data-resaltados') || '[]'); } catch (e) {}
      previos.forEach(function (r) { aplicar({ id: r.id, inicio: parseInt(r.inicio, 10), fin: parseInt(r.fin, 10), color: r.color }); });
      function leerSeleccion() {
        var s = window.getSelection();
        if (!s || s.rangeCount === 0 || s.isCollapsed) { return null; }
        var r = s.getRangeAt(0);
        if (!cont.contains(r.commonAncestorContainer)) { return null; }
        var pre = document.createRange();
        pre.selectNodeContents(cont);
        pre.setEnd(r.startContainer, r.startOffset);
        var ini = pre.toString().length, txt = r.toString();
        if (!txt.trim()) { return null; }
        return { inicio: ini, fin: ini + txt.length, texto: txt, rect: r.getBoundingClientRect() };
      }
      function mostrarPaleta() {
        var sel = leerSeleccion();
        if (!sel) { paleta.style.display = 'none'; return; }
        guardada = sel;
        paleta.style.display = 'flex';
        paleta.style.top = (window.scrollY + sel.rect.bottom + 8) + 'px';
        paleta.style.left = Math.max(8, Math.min(window.scrollX + sel.rect.left, window.scrollX + document.documentElement.clientWidth - 170)) + 'px';
      }
      cont.addEventListener('mouseup', function () { setTimeout(mostrarPaleta, 10); });
      cont.addEventListener('touchend', function () { setTimeout(mostrarPaleta, 350); });
      cont.addEventListener('keyup', function () { setTimeout(mostrarPaleta, 10); });
      document.addEventListener('mousedown', function (ev) { if (!paleta.contains(ev.target)) { paleta.style.display = 'none'; } });
      paleta.addEventListener('mousedown', function (ev) { ev.preventDefault(); });
      paleta.addEventListener('click', function (ev) {
        var color = ev.target.getAttribute('data-color');
        if (!color || !guardada) { return; }
        var sel = guardada;
        api({ accion: 'resaltar', capitulo_id: capId, inicio: sel.inicio, fin: sel.fin, texto: sel.texto.substring(0, 300), color: color }).then(function (r) {
          if (r.ok) { aplicar({ id: r.id, inicio: sel.inicio, fin: sel.fin, color: color }); window.getSelection().removeAllRanges(); }
          else { alert(r.mensaje || 'No se pudo guardar el resaltado.'); }
          paleta.style.display = 'none';
          guardada = null;
        });
      });
      cont.addEventListener('click', function (ev) {
        var m = ev.target.closest ? ev.target.closest('mark.res') : null;
        if (!m || !window.getSelection().isCollapsed) { return; }
        var rid = m.getAttribute('data-id');
        if (confirm('¿Quitar este resaltado?')) {
          api({ accion: 'borrar_resaltado', id: rid }).then(function (r) {
            if (r.ok) {
              var marcas = cont.querySelectorAll('mark.res[data-id="' + rid + '"]');
              for (var i = 0; i < marcas.length; i++) {
                var p = marcas[i].parentNode;
                while (marcas[i].firstChild) { p.insertBefore(marcas[i].firstChild, marcas[i]); }
                p.removeChild(marcas[i]);
              }
              cont.normalize();
            }
          });
        }
      });
      var bm = document.getElementById('marcador');
      if (bm) {
        bm.addEventListener('click', function () {
          api({ accion: 'marcador', capitulo_id: capId }).then(function (r) {
            if (!r.ok) { return; }
            bm.setAttribute('data-activo', r.activo ? '1' : '0');
            bm.className = 'btn btn-sm' + (r.activo ? '' : ' btn-claro');
            bm.querySelector('span').textContent = r.activo ? 'Capítulo marcado' : 'Marcar capítulo';
          });
        });
      }
      var fin = document.getElementById('terminar');
      if (fin) {
        fin.addEventListener('click', function () {
          api({ accion: 'terminar', libro_id: libroId }).then(function (r) { if (r.ok) { window.location.href = 'libro.php?id=' + libroId; } });
        });
      }
    }
  })();
  </script>
<?php else: ?>
  <iframe class="lector-pdf" src="archivo.php?id=<?php echo $id; ?>" title="<?php echo e($libro['titulo']); ?>"></iframe>
  <?php if ($lector): ?>
  <div class="acciones no-imprimir" style="justify-content:center;margin-top:16px">
    <?php if (!$ya_terminado): ?>
      <button class="btn btn-oro" type="button" id="terminar-pdf"><?php echo icono('check', 16); ?> Terminé de leer este libro</button>
      <script>
      document.getElementById('terminar-pdf').addEventListener('click', function () {
        var d = new URLSearchParams();
        d.append('csrf', document.body.getAttribute('data-csrf'));
        d.append('accion', 'terminar');
        d.append('libro_id', '<?php echo (int)$id; ?>');
        fetch('lectura_api.php', { method: 'POST', body: d, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) { if (r.ok) { window.location.href = 'libro.php?id=<?php echo (int)$id; ?>'; } });
      });
      </script>
    <?php else: ?>
      <a class="btn btn-claro" href="libro.php?id=<?php echo $id; ?>">Calificar y comentar el libro</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
<?php require 'pie.php'; ?>
