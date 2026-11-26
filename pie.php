</main>
<footer class="pie">
  <div class="pie-in">
    <span><?php echo icono('libro', 18); ?> <?php $pie_cfg = config_sitio(); echo e($pie_cfg['nombre_sitio']); ?></span>
    <span>Lee, busca y administra libros desde cualquier dispositivo.</span>
  </div>
</footer>
<script>
(function () {
  function prepararCamposPassword() {
    var campos = document.querySelectorAll('input[type="password"]:not([data-ojo-listo])');
    campos.forEach(function (campo) {
      campo.setAttribute('data-ojo-listo', '1');
      var envoltura = document.createElement('span');
      envoltura.className = 'campo-password';
      campo.parentNode.insertBefore(envoltura, campo);
      envoltura.appendChild(campo);
      var boton = document.createElement('button');
      boton.type = 'button';
      boton.className = 'ojo-password';
      boton.setAttribute('aria-label', 'Mostrar contraseña');
      boton.setAttribute('aria-pressed', 'false');
      boton.title = 'Mostrar contraseña';
      boton.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 12s3.8-7 10.5-7 10.5 7 10.5 7-3.8 7-10.5 7S1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
      boton.addEventListener('click', function () {
        var mostrar = campo.type === 'password';
        campo.type = mostrar ? 'text' : 'password';
        boton.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        boton.title = mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña';
      });
      envoltura.appendChild(boton);
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', prepararCamposPassword);
  } else {
    prepararCamposPassword();
  }
})();
</script>
</body>
</html>
