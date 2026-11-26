<?php
require_once 'seguridad.php';
if (logueado()) { exigir_login(); }
if (es_admin()) { redirigir('panel.php'); }
$cn = db();
$tot_libros = (int)valor($cn, 'SELECT COUNT(*) FROM libros');
$tot_ejemplares = (int)valor($cn, 'SELECT COALESCE(SUM(ejemplares),0) FROM libros');
$tot_prestados = (int)valor($cn, "SELECT COUNT(*) FROM prestamos WHERE estado = 'Activo'");
$tot_categorias = (int)valor($cn, 'SELECT COUNT(*) FROM categorias');
$tot_leibles = (int)valor($cn, 'SELECT COUNT(*) FROM libros l WHERE ' . sql_leible('l'));
$categorias = consulta($cn, 'SELECT c.id, c.nombre, COUNT(l.id) AS total FROM categorias c LEFT JOIN libros l ON l.categoria_id = c.id GROUP BY c.id, c.nombre ORDER BY c.nombre');
$novedades = consulta($cn, 'SELECT l.id, l.titulo, l.ejemplares, l.categoria_id, c.nombre AS categoria, a.nombre AS autor, ' . sql_disponibles('l') . ' AS disponibles, ' . sql_extra_tarjeta() . ' FROM libros l JOIN autores a ON a.id = l.autor_id JOIN categorias c ON c.id = l.categoria_id ORDER BY l.creado_en DESC, l.id DESC LIMIT 8');
$mas_vistos = consulta($cn, 'SELECT l.id, l.titulo, l.ejemplares, l.categoria_id, c.nombre AS categoria, a.nombre AS autor, ' . sql_disponibles('l') . ' AS disponibles, ' . sql_extra_tarjeta() . ' FROM libros l JOIN autores a ON a.id = l.autor_id JOIN categorias c ON c.id = l.categoria_id ORDER BY promedio DESC, votos DESC, l.id DESC LIMIT 4');
$destacado = count($novedades) ? $novedades[0] : null;
$titulo = 'Inicio';
require 'cabecera.php';
?>
<div class="inicio-bdb">
  <section class="portada-inicio" aria-labelledby="titulo-inicio">
    <div class="portada-copia">
      <span class="sobrelinea"><?php echo e($configuracion_inicio = config_sitio()['nombre_sitio']); ?> · BIBLIOTECA DIGITAL</span>
      <h1 id="titulo-inicio">Un mundo de historias, ideas y conocimiento.</h1>
      <p>Explora nuestro catálogo, descubre nuevas lecturas y encuentra contenidos para aprender, imaginar y compartir.</p>
      <form class="busqueda-editorial" method="get" action="catalogo.php" role="search">
        <label class="sr-only" for="buscar-inicio">Buscar por título, autor o tema</label>
        <input id="buscar-inicio" type="search" name="q" placeholder="¿Qué quieres descubrir hoy?" aria-label="Buscar por título, autor o tema">
        <label class="sr-only" for="categoria-inicio">Filtrar por colección o categoría</label>
        <select id="categoria-inicio" name="categoria">
          <option value="0">Todas las colecciones</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo e($c['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"><?php echo icono('buscar', 18); ?> Buscar</button>
      </form>
      <div class="atajos-portada">
        <a href="catalogo.php?leer=1">Libros para leer en línea</a>
        <a href="catalogo.php">Explorar todo el catálogo</a>
      </div>
    </div>
    <div class="portada-arte" aria-hidden="true">
      <div class="libro-ilustrado libro-grande"><span>LECTURAS<br>QUE INSPIRAN</span><small>Una biblioteca para descubrir</small></div>
      <div class="libro-ilustrado libro-pequeno"><span>IDEAS<br>EN MOVIMIENTO</span></div>
      <span class="sello-lectura">LEE<br>DESCUBRE<br>IMAGINA</span>
    </div>
  </section>

  <div class="franja-datos" aria-label="Datos de la biblioteca">
    <div><strong><?php echo $tot_libros; ?></strong><span>títulos en catálogo</span></div>
    <div><strong><?php echo $tot_leibles; ?></strong><span>lecturas en línea</span></div>
    <div><strong><?php echo $tot_categorias; ?></strong><span>colecciones temáticas</span></div>
    <div><strong><?php echo $tot_ejemplares; ?></strong><span>ejemplares físicos</span></div>
  </div>

  <?php if ($destacado): ?>
  <section class="destacado-editorial" aria-labelledby="titulo-destacado">
    <div class="destacado-visual">
      <div class="portada-destacada" style="--tono:<?php echo (int)(((int)$destacado['categoria_id'] * 47) % 360); ?>">
        <span class="etiqueta-portada">ENCUENTRA TU PRÓXIMA LECTURA</span>
        <?php echo icono('abierto', 42); ?>
        <strong><?php echo e($destacado['titulo']); ?></strong>
        <small><?php echo e($destacado['autor']); ?></small>
      </div>
    </div>
    <div class="destacado-texto">
      <span class="sobrelinea">RECIÉN LLEGADO AL CATÁLOGO</span>
      <h2 id="titulo-destacado"><?php echo e($destacado['titulo']); ?></h2>
      <p class="firma-destacado">Por <?php echo e($destacado['autor']); ?> · <?php echo e($destacado['categoria']); ?></p>
      <p>Una nueva lectura te espera. Conoce su ficha, consulta la disponibilidad y descubre si puedes leerlo directamente desde la biblioteca.</p>
      <div class="acciones-destacado">
        <a class="btn" href="libro.php?id=<?php echo (int)$destacado['id']; ?>">Descubrir libro <?php echo icono('mas', 16); ?></a>
        <?php if (!empty($destacado['leible'])): ?><a class="enlace-editorial" href="<?php echo logueado() ? 'leer.php?id=' . (int)$destacado['id'] : 'login.php'; ?>">Leer en línea</a><?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="bloque-editorial">
    <div class="cabecera-bloque">
      <div><span class="sobrelinea">EXPLORA POR INTERESES</span><h2>Colecciones para cada curiosidad</h2><p>Encuentra libros agrupados por temas y áreas de conocimiento.</p></div>
      <a href="catalogo.php">Ver todas las colecciones <span aria-hidden="true">→</span></a>
    </div>
    <?php if (count($categorias)): ?>
      <div class="colecciones-editoriales">
        <?php foreach (array_slice($categorias, 0, 6) as $i => $c): ?>
          <a class="coleccion-editorial coleccion-<?php echo ($i % 4) + 1; ?>" href="catalogo.php?categoria=<?php echo (int)$c['id']; ?>">
            <span class="numero-coleccion">0<?php echo $i + 1; ?></span>
            <span class="nombre-coleccion"><?php echo e($c['nombre']); ?></span>
            <span class="cantidad-coleccion"><?php echo (int)$c['total']; ?> libro(s)</span>
            <span class="flecha-coleccion" aria-hidden="true">↗</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="vacio">Las colecciones aparecerán aquí cuando se registren categorías.</div>
    <?php endif; ?>
  </section>

  <section class="bloque-editorial bloque-novedades">
    <div class="cabecera-bloque">
      <div><span class="sobrelinea">PARA EMPEZAR A EXPLORAR</span><h2>Últimas novedades</h2><p>Conoce los títulos que se han incorporado recientemente.</p></div>
      <a href="catalogo.php">Ir al catálogo <span aria-hidden="true">→</span></a>
    </div>
    <?php if (count($novedades) > 1): ?>
      <div class="rejilla rejilla-editorial">
        <?php foreach (array_slice($novedades, 1, 4) as $l) { echo tarjeta_libro($l); } ?>
      </div>
    <?php elseif (!$destacado): ?>
      <div class="vacio">Aún no hay libros registrados. Pronto encontrarás nuevas lecturas aquí.</div>
    <?php else: ?>
      <div class="vacio">Estamos preparando más novedades para ti. Mientras tanto, descubre el libro destacado.</div>
    <?php endif; ?>
  </section>

  <section class="bloque-editorial bloque-mas-vistos">
    <div class="cabecera-bloque">
      <div><span class="sobrelinea">RECOMENDADOS POR LA COMUNIDAD</span><h2>Lecturas destacadas</h2><p>Libros que sobresalen por las valoraciones de sus lectores.</p></div>
      <a href="catalogo.php?orden=disp_desc">Explorar disponibilidad <span aria-hidden="true">→</span></a>
    </div>
    <?php if (count($mas_vistos)): ?>
      <div class="rejilla rejilla-editorial">
        <?php foreach ($mas_vistos as $l) { echo tarjeta_libro($l); } ?>
      </div>
    <?php else: ?>
      <div class="vacio">Las lecturas destacadas aparecerán cuando haya libros en el catálogo.</div>
    <?php endif; ?>
  </section>

  <section class="invitacion-lector">
    <div><span class="sobrelinea">TU PRÓXIMA HISTORIA COMIENZA AQUÍ</span><h2>Haz de la lectura parte de tu día.</h2><p>Guarda tus avances, crea tu espacio de lectura y vuelve a tus libros favoritos cuando quieras.</p></div>
    <div class="invitacion-acciones">
      <?php if (!logueado()): ?><a class="btn btn-claro" href="registro.php">Crear cuenta gratis</a><a class="enlace-invitacion" href="login.php">Ya tengo una cuenta</a><?php else: ?><a class="btn btn-claro" href="mi_biblioteca.php">Ir a mi biblioteca</a><?php endif; ?>
    </div>
  </section>
</div>
<?php require 'pie.php'; ?>
