SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS seguimiento;

DROP TABLE IF EXISTS cambios_edad;

DROP TABLE IF EXISTS sugerencias;

DROP TABLE IF EXISTS resenas;

DROP TABLE IF EXISTS marcadores;

DROP TABLE IF EXISTS resaltados;

DROP TABLE IF EXISTS lecturas;

DROP TABLE IF EXISTS capitulos;

DROP TABLE IF EXISTS prestamos;

DROP TABLE IF EXISTS libros;

DROP TABLE IF EXISTS autores;

DROP TABLE IF EXISTS categorias;

DROP TABLE IF EXISTS configuracion;

DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  correo VARCHAR(120) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  fecha_nacimiento DATE NOT NULL,
  clave VARCHAR(255) NOT NULL,
  rol ENUM('admin','lector') NOT NULL DEFAULT 'lector',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  foto VARCHAR(80) DEFAULT NULL,
  tema ENUM('auto','claro','oscuro') NOT NULL DEFAULT 'auto',
  edad_editable TINYINT(1) NOT NULL DEFAULT 0,
  cambiar_clave TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracion (
  clave VARCHAR(40) NOT NULL,
  valor TEXT DEFAULT NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(60) NOT NULL,
  descripcion VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE autores (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  nacionalidad VARCHAR(60) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_autores_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE libros (
  id INT NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(30) NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  autor_id INT NOT NULL,
  categoria_id INT NOT NULL,
  editorial VARCHAR(100) DEFAULT NULL,
  anio SMALLINT DEFAULT NULL,
  ejemplares INT NOT NULL DEFAULT 0,
  descripcion TEXT DEFAULT NULL,
  archivo VARCHAR(80) DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_libros_codigo (codigo),
  KEY idx_libros_titulo (titulo),
  KEY idx_libros_autor (autor_id),
  KEY idx_libros_categoria (categoria_id),
  CONSTRAINT fk_libros_autor FOREIGN KEY (autor_id) REFERENCES autores(id) ON DELETE RESTRICT,
  CONSTRAINT fk_libros_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE capitulos (
  id INT NOT NULL AUTO_INCREMENT,
  libro_id INT NOT NULL,
  orden INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  contenido MEDIUMTEXT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_capitulos_orden (libro_id, orden),
  CONSTRAINT fk_capitulos_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prestamos (
  id INT NOT NULL AUTO_INCREMENT,
  libro_id INT NOT NULL,
  usuario_id INT NOT NULL,
  estado ENUM('Solicitado','Activo','Devuelto','Rechazado') NOT NULL DEFAULT 'Solicitado',
  fecha_prestamo DATE DEFAULT NULL,
  fecha_limite DATE DEFAULT NULL,
  fecha_devolucion DATE DEFAULT NULL,
  registrado_por INT DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_prestamos_libro (libro_id),
  KEY idx_prestamos_usuario (usuario_id),
  KEY idx_prestamos_estado (estado, fecha_limite),
  CONSTRAINT fk_prestamos_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
  CONSTRAINT fk_prestamos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_prestamos_registrador FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lecturas (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  libro_id INT NOT NULL,
  capitulo_orden INT NOT NULL DEFAULT 1,
  progreso TINYINT UNSIGNED NOT NULL DEFAULT 0,
  terminado TINYINT(1) NOT NULL DEFAULT 0,
  iniciado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lecturas (usuario_id, libro_id),
  KEY idx_lecturas_libro (libro_id),
  CONSTRAINT fk_lecturas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_lecturas_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE resaltados (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  capitulo_id INT NOT NULL,
  inicio INT NOT NULL,
  fin INT NOT NULL,
  texto VARCHAR(300) NOT NULL,
  color ENUM('amarillo','verde','rosa','azul') NOT NULL DEFAULT 'amarillo',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_resaltados (usuario_id, capitulo_id),
  CONSTRAINT fk_resaltados_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_resaltados_capitulo FOREIGN KEY (capitulo_id) REFERENCES capitulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE marcadores (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  capitulo_id INT NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_marcadores (usuario_id, capitulo_id),
  CONSTRAINT fk_marcadores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_marcadores_capitulo FOREIGN KEY (capitulo_id) REFERENCES capitulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE resenas (
  id INT NOT NULL AUTO_INCREMENT,
  libro_id INT NOT NULL,
  usuario_id INT NOT NULL,
  puntuacion TINYINT UNSIGNED NOT NULL,
  comentario VARCHAR(600) DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_resenas (libro_id, usuario_id),
  CONSTRAINT fk_resenas_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
  CONSTRAINT fk_resenas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sugerencias (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  autor VARCHAR(100) DEFAULT NULL,
  motivo VARCHAR(500) NOT NULL,
  estado ENUM('Pendiente','Aprobada','Rechazada','Adquirida') NOT NULL DEFAULT 'Pendiente',
  respuesta VARCHAR(300) DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sugerencias_usuario (usuario_id),
  CONSTRAINT fk_sugerencias_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cambios_edad (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  motivo VARCHAR(500) NOT NULL,
  estado ENUM('Pendiente','Aprobada','Rechazada','Usada') NOT NULL DEFAULT 'Pendiente',
  respuesta VARCHAR(300) DEFAULT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resuelto_en DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_cambios_usuario (usuario_id),
  CONSTRAINT fk_cambios_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO usuarios (nombre, apellido, correo, telefono, fecha_nacimiento, clave, rol, cambiar_clave) VALUES
('Administrador','Biblioteca','admin@biblioteca.com','3000000000','1990-01-01','$2y$12$/CZO/n86DoT1CLSCiMRTdOi9NfG4VT2CMjoYxrr58WaqpOYwtV4nO','admin',0);

INSERT INTO usuarios (nombre, apellido, correo, telefono, fecha_nacimiento, clave, rol) VALUES
('Laura','Gómez','laura@biblioteca.com','3105550101','2001-05-14','$2y$12$B5E.tkO3SbhfFoNex1i.9elnmCC24PiaHNKhNpF5uhMTZ4xfKV1d6','lector'),
('Carlos','Pérez','carlos@biblioteca.com','3205550202','1998-11-02','$2y$12$B5E.tkO3SbhfFoNex1i.9elnmCC24PiaHNKhNpF5uhMTZ4xfKV1d6','lector');

INSERT INTO configuracion (clave, valor) VALUES
('nombre_sitio','Biblioteca Virtual'),
('color_primario','#1f5f46'),
('color_acento','#c8963e'),
('color_fondo','#f6f1e6'),
('color_fondo_oscuro','#101a16'),
('css_extra','');

INSERT INTO categorias (nombre, descripcion) VALUES
('Poesía','Poemas y antologías'),
('Clásicos','Obras clásicas de la literatura'),
('Fábulas','Fábulas y relatos con moraleja'),
('Cuentos','Cuentos y narrativa breve'),
('Tecnología','Informática, redes y programación'),
('Matemáticas','Matemáticas y lógica'),
('Guías','Guías y manuales de uso');

INSERT INTO autores (nombre, nacionalidad) VALUES
('Gustavo Adolfo Bécquer','España'),
('José Martí','Cuba'),
('Miguel de Cervantes','España'),
('Esopo','Grecia antigua'),
('Redacción Biblioteca Virtual',NULL);

INSERT INTO libros (codigo, titulo, autor_id, categoria_id, editorial, anio, ejemplares, descripcion) VALUES
('BV-0001','Rimas (selección)',(SELECT id FROM autores WHERE nombre='Gustavo Adolfo Bécquer'),(SELECT id FROM categorias WHERE nombre='Poesía'),'Imprenta de Fortanet',1871,2,'Cinco rimas del poeta sevillano, una de las voces centrales del Romanticismo español.'),
('BV-0002','Versos sencillos (selección)',(SELECT id FROM autores WHERE nombre='José Martí'),(SELECT id FROM categorias WHERE nombre='Poesía'),'Louis Weiss & Co.',1891,2,'Dos poemas de la colección que Martí publicó en Nueva York en 1891.'),
('BV-0003','Don Quijote de la Mancha (fragmento del capítulo I)',(SELECT id FROM autores WHERE nombre='Miguel de Cervantes'),(SELECT id FROM categorias WHERE nombre='Clásicos'),'Juan de la Cuesta',1605,3,'Los primeros párrafos de la novela: presentación del hidalgo y su afición a los libros de caballerías.'),
('BV-0004','Fábulas de Esopo (versión adaptada)',(SELECT id FROM autores WHERE nombre='Esopo'),(SELECT id FROM categorias WHERE nombre='Fábulas'),'Biblioteca Virtual',2026,3,'Cuatro fábulas clásicas contadas con palabras de hoy, ideales para leer en voz alta.'),
('BV-0005','El faro de las palabras',(SELECT id FROM autores WHERE nombre='Redacción Biblioteca Virtual'),(SELECT id FROM categorias WHERE nombre='Cuentos'),'Biblioteca Virtual',2026,2,'Cuento original en tres partes sobre una niña, un faro y las palabras que se pierden.'),
('BV-0006','Fundamentos de redes de computadores (apuntes)',(SELECT id FROM autores WHERE nombre='Redacción Biblioteca Virtual'),(SELECT id FROM categorias WHERE nombre='Tecnología'),'Biblioteca Virtual',2026,2,'Apuntes breves para empezar: qué es una red, los modelos de capas y cómo funcionan las direcciones.'),
('BV-0007','Lógica y acertijos matemáticos',(SELECT id FROM autores WHERE nombre='Redacción Biblioteca Virtual'),(SELECT id FROM categorias WHERE nombre='Matemáticas'),'Biblioteca Virtual',2026,2,'Tres problemas clásicos para practicar el razonamiento, cada uno con su solución explicada.'),
('BV-0008','Guía del lector: cómo usar la Biblioteca Virtual',(SELECT id FROM autores WHERE nombre='Redacción Biblioteca Virtual'),(SELECT id FROM categorias WHERE nombre='Guías'),'Biblioteca Virtual',2026,4,'Todo lo que puedes hacer en la biblioteca: buscar, leer, resaltar, calificar, pedir préstamos y sugerir libros.');

INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES
((SELECT id FROM libros WHERE codigo='BV-0001'),1,'Rima VII','Del salón en el ángulo oscuro,
de su dueña tal vez olvidada,
silenciosa y cubierta de polvo,
veíase el arpa.

¡Cuánta nota dormía en sus cuerdas,
como el pájaro duerme en las ramas,
esperando la mano de nieve
que sabe arrancarlas!

¡Ay!, pensé; ¡cuántas veces el genio
así duerme en el fondo del alma,
y una voz, como Lázaro, espera
que le diga: «Levántate y anda»!'),
((SELECT id FROM libros WHERE codigo='BV-0001'),2,'Rima XI','—Yo soy ardiente, yo soy morena,
yo soy el símbolo de la pasión;
de ansia de goces mi alma está llena.
¿A mí me buscas? —No es a ti, no.

—Mi frente es pálida; mis trenzas, de oro;
puedo brindarte dichas sin fin;
yo de ternuras guardo un tesoro.
¿A mí me llamas? —No; no es a ti.

—Yo soy un sueño, un imposible,
vano fantasma de niebla y luz;
soy incorpórea, soy intangible;
no puedo amarte. —¡Oh, ven; ven tú!'),
((SELECT id FROM libros WHERE codigo='BV-0001'),3,'Rima XXI','¿Qué es poesía?, dices mientras clavas
en mi pupila tu pupila azul.
¿Qué es poesía? ¿Y tú me lo preguntas?
Poesía... eres tú.'),
((SELECT id FROM libros WHERE codigo='BV-0001'),4,'Rima XXIII','Por una mirada, un mundo;
por una sonrisa, un cielo;
por un beso... ¡yo no sé
qué te diera por un beso!'),
((SELECT id FROM libros WHERE codigo='BV-0001'),5,'Rima LIII','Volverán las oscuras golondrinas
en tu balcón sus nidos a colgar,
y otra vez con el ala a sus cristales
jugando llamarán;
pero aquellas que el vuelo refrenaban
tu hermosura y mi dicha a contemplar,
aquellas que aprendieron nuestros nombres...
ésas... ¡no volverán!

Volverán las tupidas madreselvas
de tu jardín las tapias a escalar,
y otra vez a la tarde aún más hermosas
sus flores se abrirán;
pero aquellas cuajadas de rocío
cuyas gotas mirábamos temblar
y caer como lágrimas del día...
ésas... ¡no volverán!

Volverán del amor en tus oídos
las palabras ardientes a sonar;
tu corazón de su profundo sueño
tal vez despertará;
pero mudo y absorto y de rodillas
como se adora a Dios ante su altar,
como yo te he querido..., desengáñate,
¡así... no te querrán!');

INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES
((SELECT id FROM libros WHERE codigo='BV-0002'),1,'Verso I','Yo soy un hombre sincero
de donde crece la palma,
y antes de morirme quiero
echarme los versos del alma.

Yo vengo de todas partes,
y hacia todas partes voy:
arte soy entre las artes,
en los montes, monte soy.

Yo sé los nombres extraños
de las yerbas y las flores,
y de mortales engaños,
y de sublimes dolores.

Yo he visto en la noche oscura
llover sobre mi cabeza
los rayos de lumbre pura
de la divina belleza.

Alas nacer vi en los hombros
de las mujeres hermosas;
y salir de los escombros,
volando, las mariposas.'),
((SELECT id FROM libros WHERE codigo='BV-0002'),2,'Verso XXXIX','Cultivo una rosa blanca
en julio como en enero
para el amigo sincero
que me da su mano franca.

Y para el cruel que me arranca
el corazón con que vivo,
cardo ni ortiga cultivo:
cultivo la rosa blanca.');

INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES
((SELECT id FROM libros WHERE codigo='BV-0003'),1,'Capítulo primero (fragmento)','En un lugar de la Mancha, de cuyo nombre no quiero acordarme, no ha mucho tiempo que vivía un hidalgo de los de lanza en astillero, adarga antigua, rocín flaco y galgo corredor. Una olla de algo más vaca que carnero, salpicón las más noches, duelos y quebrantos los sábados, lentejas los viernes, algún palomino de añadidura los domingos, consumían las tres partes de su hacienda. El resto della concluían sayo de velarte, calzas de velludo para las fiestas con sus pantuflos de lo mismo, y los días de entresemana se honraba con su vellorí de lo más fino.

Tenía en su casa una ama que pasaba de los cuarenta, y una sobrina que no llegaba a los veinte, y un mozo de campo y plaza, que así ensillaba el rocín como tomaba la podadera. Frisaba la edad de nuestro hidalgo con los cincuenta años; era de complexión recia, seco de carnes, enjuto de rostro, gran madrugador y amigo de la caza. Quieren decir que tenía el sobrenombre de Quijada, o Quesada, que en esto hay alguna diferencia en los autores que deste caso escriben; aunque por conjeturas verosímiles se deja entender que se llamaba Quijana. Pero esto importa poco a nuestro cuento; basta que en la narración dél no se salga un punto de la verdad.

Es, pues, de saber que este sobredicho hidalgo, los ratos que estaba ocioso —que eran los más del año—, se daba a leer libros de caballerías con tanta afición y gusto, que olvidó casi de todo punto el ejercicio de la caza, y aun la administración de su hacienda; y llegó a tanto su curiosidad y desatino en esto, que vendió muchas hanegas de tierra de sembradura para comprar libros de caballerías en que leer, y así llevó a su casa todos cuantos pudo haber dellos.');

INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES
((SELECT id FROM libros WHERE codigo='BV-0004'),1,'La liebre y la tortuga','Una liebre se burlaba todos los días de la lentitud de una tortuga. Cansada de las burlas, la tortuga le propuso una carrera hasta el viejo roble de la colina.

La liebre aceptó riendo. Salió disparada y, cuando miró atrás, la tortuga era apenas un punto en el camino. «Tengo tiempo de sobra», pensó, y se echó a dormir bajo un árbol.

La tortuga no se detuvo ni un instante. Paso a paso, sin prisa y sin pausa, pasó junto a la liebre dormida y llegó a la meta. Cuando la liebre despertó y corrió con todas sus fuerzas, ya era tarde.

Moraleja: la constancia vale más que la velocidad presumida.'),
((SELECT id FROM libros WHERE codigo='BV-0004'),2,'La zorra y las uvas','Una zorra hambrienta vio un racimo de uvas maduras colgando de una parra muy alta. Se le hizo agua la boca.

Saltó una vez y no llegó. Saltó otra vez, con más impulso, y tampoco. Probó una y otra vez hasta quedar agotada.

Entonces se alejó con la cabeza en alto y dijo: «Seguro que están verdes; no vale la pena comerlas».

Moraleja: es fácil despreciar lo que no podemos alcanzar.'),
((SELECT id FROM libros WHERE codigo='BV-0004'),3,'El león y el ratón','Un león dormía cuando un ratoncito se le subió por la melena. El león despertó furioso y lo atrapó con su garra.

«Perdóname la vida —suplicó el ratón—. Algún día sabré devolverte el favor». El león se rio de la idea, pero lo soltó.

Unos días después, el león cayó en la red de unos cazadores y rugió sin poder liberarse. El ratón reconoció el rugido, corrió hasta allí y royó las cuerdas hasta abrir un hueco.

Moraleja: nadie es tan pequeño que no pueda ayudar.'),
((SELECT id FROM libros WHERE codigo='BV-0004'),4,'La cigarra y la hormiga','Durante todo el verano, la cigarra cantó bajo el sol mientras la hormiga acarreaba granos hasta su hormiguero.

«¿Por qué trabajas tanto? —preguntaba la cigarra—. Ven a cantar conmigo». La hormiga respondía: «Estoy guardando comida para el invierno».

Cuando llegó el frío, la cigarra no tenía nada que comer y tocó a la puerta de la hormiga. Esta le ofreció un poco de grano, pero le recordó que cada estación pide su tarea.

Moraleja: prepararse a tiempo evita tener que pedir ayuda después.');

INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES
((SELECT id FROM libros WHERE codigo='BV-0005'),1,'I. La llegada','Marina llegó al pueblo de la costa en el último autobús de octubre, con una maleta azul y una libreta vacía. Su abuelo, Anselmo, la esperaba en la parada con una linterna y una sonrisa de pocos dientes.

—El faro lleva días apagado —le dijo mientras caminaban—. No porque falle la luz, sino porque se nos están acabando las palabras.

Marina creyó que era una broma de abuelo. Pero esa noche, desde la ventana de su cuarto, vio la torre blanca en lo alto del acantilado, oscura como un ojo cerrado.'),
((SELECT id FROM libros WHERE codigo='BV-0005'),2,'II. El libro de los vientos','A la mañana siguiente subieron los ciento doce escalones del faro. Arriba, junto a la lámpara enorme, había un cuaderno grueso con la tapa salada por el mar.

—Cada vez que alguien del pueblo cuenta una historia —explicó Anselmo—, una palabra se queda atrapada aquí y alimenta la luz. Ahora casi nadie cuenta nada.

Marina pasó las hojas. Estaban llenas de frases de pescadores, de canciones de cuna, de recetas y de nombres de barcos. Las últimas páginas, en cambio, estaban en blanco desde hacía años.'),
((SELECT id FROM libros WHERE codigo='BV-0005'),3,'III. La luz','Esa tarde, Marina recorrió el pueblo con su libreta. Le preguntó a la panadera por el día en que nevó en la costa, al maestro por su primer viaje en tren, y a una pescadora por la tormenta que casi hundió su barca.

Al anochecer volvió al faro con la libreta llena. Leyó en voz alta, página por página, y a cada historia la lámpara parpadeaba un poco más fuerte.

Cuando terminó, una luz dorada barrió el mar de punta a punta. En el pueblo, las ventanas se fueron encendiendo una a una, y alguien, abajo, empezó a contar otra historia.');

INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES
((SELECT id FROM libros WHERE codigo='BV-0006'),1,'1. ¿Qué es una red?','Una red de computadores es un conjunto de dispositivos conectados que comparten información y recursos, como archivos, impresoras o acceso a Internet.

Según su alcance se clasifican en: PAN (personal, pocos metros), LAN (local, una casa u oficina), MAN (metropolitana, una ciudad) y WAN (extensa, entre ciudades o países). Internet es una WAN formada por millones de redes más pequeñas.

Los elementos básicos son los dispositivos finales (computadores, teléfonos), los dispositivos intermedios (switches, routers, puntos de acceso) y los medios de transmisión (cable de cobre, fibra óptica, ondas de radio).'),
((SELECT id FROM libros WHERE codigo='BV-0006'),2,'2. Modelos OSI y TCP/IP','Para ordenar la comunicación, las redes se describen en capas. El modelo OSI tiene siete: física, enlace de datos, red, transporte, sesión, presentación y aplicación. El modelo TCP/IP, el que se usa en la práctica, agrupa estas funciones en cuatro: acceso a la red, internet, transporte y aplicación.

Cada capa presta servicios a la de arriba y se apoya en la de abajo. Por ejemplo, cuando abres una página web, el navegador usa HTTP en la capa de aplicación, TCP en la de transporte, IP en la de internet y Ethernet o Wi-Fi en la de acceso a la red.

Esta separación permite cambiar una tecnología, como pasar de cable a Wi-Fi, sin tener que rehacer todo lo demás.'),
((SELECT id FROM libros WHERE codigo='BV-0006'),3,'3. Direcciones IP y DNS','Cada dispositivo en una red necesita una dirección IP. IPv4 usa 32 bits escritos en cuatro números del 0 al 255, por ejemplo 192.168.1.10. IPv6 usa 128 bits y existe porque las direcciones IPv4 son limitadas.

Una máscara de subred indica qué parte de la dirección identifica la red y cuál al dispositivo. En una red 192.168.1.0/24, los primeros 24 bits son la red y quedan 254 direcciones utilizables para equipos.

Como las personas recordamos mejor los nombres que los números, el sistema DNS traduce nombres como biblioteca.ejemplo.com a direcciones IP. Es como una agenda telefónica de Internet.');

INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES
((SELECT id FROM libros WHERE codigo='BV-0007'),1,'1. El caracol del pozo','Un caracol cae en un pozo de 10 metros. Cada día sube 3 metros y cada noche resbala 2. ¿Cuántos días tarda en salir?

Solución: la respuesta intuitiva es 10 días, pero es incorrecta. Cada día completo avanza 1 metro neto, así que al final del día 7 está a 7 metros. El día 8 sube 3 metros y llega a 10 metros antes de que llegue la noche, por lo que sale del pozo. Tarda 8 días.'),
((SELECT id FROM libros WHERE codigo='BV-0007'),2,'2. Los tres interruptores','Fuera de una habitación hay tres interruptores. Solo uno enciende la lámpara que está dentro, y solo puedes entrar una vez. ¿Cómo sabes cuál es?

Solución: enciende el primer interruptor y déjalo así unos minutos. Apágalo, enciende el segundo y entra de inmediato. Si la lámpara está encendida, es el segundo interruptor. Si está apagada pero caliente, es el primero. Si está apagada y fría, es el tercero.'),
((SELECT id FROM libros WHERE codigo='BV-0007'),3,'3. La suma de 1 a 100','¿Cuánto vale 1 + 2 + 3 + ... + 100?

Solución: se cuenta que el matemático Carl Friedrich Gauss resolvió este problema siendo niño. Si sumas el primero con el último (1 + 100), el segundo con el penúltimo (2 + 99) y así sucesivamente, cada pareja da 101. Hay 50 parejas, así que el resultado es 50 × 101 = 5050.

La fórmula general es n × (n + 1) / 2, que para n = 100 también da 5050.');

INSERT INTO capitulos (libro_id, orden, titulo, contenido) VALUES
((SELECT id FROM libros WHERE codigo='BV-0008'),1,'1. Buscar y elegir un libro','En el catálogo puedes buscar por título, autor, código o editorial, filtrar por categoría o autor, mostrar solo los libros disponibles y recorrer el índice alfabético. Cada libro muestra iconos con los ejemplares físicos disponibles y la calificación que le han dado otros lectores.

Si un libro tiene el botón «Leer ahora», puedes abrirlo y leerlo completo desde cualquier dispositivo.'),
((SELECT id FROM libros WHERE codigo='BV-0008'),2,'2. Leer, resaltar y marcar','Dentro del lector puedes cambiar el tamaño de la letra, pasar de capítulo y ver cuánto llevas leído. Selecciona cualquier fragmento del texto y elige un color para resaltarlo; tus resaltados quedan guardados en tu cuenta. Toca un resaltado para quitarlo.

Con el botón «Marcar capítulo» dejas un marcador para volver más tarde. Todo aparece en «Mi biblioteca», junto con los libros que estás leyendo y los que ya terminaste.'),
((SELECT id FROM libros WHERE codigo='BV-0008'),3,'3. Préstamos, reseñas y sugerencias','Además de la lectura en línea, puedes solicitar el préstamo de un ejemplar físico: el administrador aprobará tu solicitud y te dirá hasta cuándo debes devolverlo.

Después de leer, califica el libro de 1 a 5 estrellas y deja un comentario. Si no encuentras un libro que quieres, envía una sugerencia con el motivo y el administrador la revisará.

Por tu seguridad, tu fecha de nacimiento no se puede cambiar libremente: si te equivocaste al registrarte, solicita el cambio desde tu perfil explicando el motivo.');

INSERT INTO prestamos (libro_id, usuario_id, estado, fecha_prestamo, fecha_limite, fecha_devolucion, registrado_por) VALUES
((SELECT id FROM libros WHERE codigo='BV-0003'),(SELECT id FROM usuarios WHERE correo='laura@biblioteca.com'),'Activo',DATE_SUB(CURDATE(),INTERVAL 3 DAY),DATE_ADD(CURDATE(),INTERVAL 4 DAY),NULL,(SELECT id FROM usuarios WHERE correo='admin@biblioteca.com')),
((SELECT id FROM libros WHERE codigo='BV-0006'),(SELECT id FROM usuarios WHERE correo='carlos@biblioteca.com'),'Activo',DATE_SUB(CURDATE(),INTERVAL 15 DAY),DATE_SUB(CURDATE(),INTERVAL 8 DAY),NULL,(SELECT id FROM usuarios WHERE correo='admin@biblioteca.com')),
((SELECT id FROM libros WHERE codigo='BV-0004'),(SELECT id FROM usuarios WHERE correo='laura@biblioteca.com'),'Devuelto',DATE_SUB(CURDATE(),INTERVAL 30 DAY),DATE_SUB(CURDATE(),INTERVAL 23 DAY),DATE_SUB(CURDATE(),INTERVAL 25 DAY),(SELECT id FROM usuarios WHERE correo='admin@biblioteca.com')),
((SELECT id FROM libros WHERE codigo='BV-0001'),(SELECT id FROM usuarios WHERE correo='carlos@biblioteca.com'),'Solicitado',NULL,NULL,NULL,NULL);

INSERT INTO lecturas (usuario_id, libro_id, capitulo_orden, progreso, terminado) VALUES
((SELECT id FROM usuarios WHERE correo='laura@biblioteca.com'),(SELECT id FROM libros WHERE codigo='BV-0001'),3,60,0),
((SELECT id FROM usuarios WHERE correo='carlos@biblioteca.com'),(SELECT id FROM libros WHERE codigo='BV-0008'),3,100,1),
((SELECT id FROM usuarios WHERE correo='carlos@biblioteca.com'),(SELECT id FROM libros WHERE codigo='BV-0004'),2,50,0);

INSERT INTO resenas (libro_id, usuario_id, puntuacion, comentario) VALUES
((SELECT id FROM libros WHERE codigo='BV-0001'),(SELECT id FROM usuarios WHERE correo='laura@biblioteca.com'),5,'La rima LIII me parece preciosa. Ideal para leer con calma.'),
((SELECT id FROM libros WHERE codigo='BV-0004'),(SELECT id FROM usuarios WHERE correo='carlos@biblioteca.com'),4,'Fábulas cortas y muy bien contadas.'),
((SELECT id FROM libros WHERE codigo='BV-0008'),(SELECT id FROM usuarios WHERE correo='carlos@biblioteca.com'),5,'Muy útil para empezar a usar la biblioteca.');

INSERT INTO marcadores (usuario_id, capitulo_id) VALUES
((SELECT id FROM usuarios WHERE correo='laura@biblioteca.com'),(SELECT c.id FROM capitulos c WHERE c.libro_id=(SELECT id FROM libros WHERE codigo='BV-0001') AND c.orden=2));

INSERT INTO sugerencias (usuario_id, titulo, autor, motivo) VALUES
((SELECT id FROM usuarios WHERE correo='carlos@biblioteca.com'),'Cien años de soledad','Gabriel García Márquez','Es una novela muy mencionada en mis clases de literatura y no la encuentro en el catálogo.');

INSERT INTO cambios_edad (usuario_id, motivo) VALUES
((SELECT id FROM usuarios WHERE correo='laura@biblioteca.com'),'Me equivoqué de año al digitar mi fecha de nacimiento cuando me registré.');
