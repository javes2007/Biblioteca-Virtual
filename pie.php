</main>
<footer class="pie">
  <div class="pie-in">
    <span><?php echo icono('libro', 18); ?> <?php $pie_cfg = config_sitio(); echo e($pie_cfg['nombre_sitio']); ?></span>
    <span>Lee, busca y administra libros desde cualquier dispositivo.</span>
  </div>
</footer>
<script>
(function(){
var boton=document.getElementById('boton-tema'),texto=document.getElementById('texto-tema');
var nombres={auto:'Auto',claro:'Claro',oscuro:'Oscuro'};
function actual(){return document.documentElement.getAttribute('data-tema')||'auto';}
function pintar(){if(texto){texto.textContent=nombres[actual()]||'Auto';}}
pintar();
if(boton){boton.addEventListener('click',function(){
var orden=['auto','claro','oscuro'],sig=orden[(orden.indexOf(actual())+1)%3];
document.documentElement.setAttribute('data-tema',sig);pintar();
if(document.body.getAttribute('data-logueado')==='1'){
var d=new URLSearchParams();d.append('csrf',document.body.getAttribute('data-csrf'));d.append('tema',sig);
fetch('tema.php',{method:'POST',body:d,credentials:'same-origin'}).catch(function(){});
}else{try{localStorage.setItem('bv_tema',sig);}catch(e){}}
});}
})();
</script>
</body>
</html>
