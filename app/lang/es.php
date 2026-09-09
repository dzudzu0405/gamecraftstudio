<?php
/**
 * Spanish. Mirrors app/lang/en.php key for key.
 *
 * Written for a parent or teacher reading aloud to a child of four to nine,
 * so the register is plain and warm rather than formal: tu, never usted.
 */
return [

    'bar' => [
        'pages'   => ['one' => '{n} página', 'other' => '{n} páginas'],
        'spaces'  => ['one' => '{n} casilla', 'other' => '{n} casillas'],
        'back'    => 'Volver al Studio',
        'preview' => 'Vista previa',
        'print'   => 'Imprimir / Guardar como PDF',
        'hint'    => 'En el cuadro de impresión elige <b>Destino: Guardar como PDF</b>, activa '
                   . '<b>Gráficos de fondo</b> y pon <b>Márgenes: Ninguno</b> para que los colores '
                   . 'y las líneas de corte salgan bien.',
    ],

    'sheet' => [
        'page' => 'Página {n}',

        'map'       => 'Tablero del juego',
        'map_sub'   => ['one' => '{n} casilla de misión', 'other' => '{n} casillas de misión'],

        'story'     => 'La historia',

        'howto'     => 'Cómo se juega',
        'players'   => ['one' => '{n} jugador', 'other' => '{n} jugadores'],
        'players_range' => '{min}-{max} jugadores',

        'move'      => 'Cartas de movimiento',
        'move_sub'  => ['one' => 'Recorta por la línea de puntos - {n} carta',
                        'other' => 'Recorta por las líneas de puntos - {n} cartas'],

        'dice'      => 'Dado de papel',
        'dice_sub'  => 'Recorta, dobla por las líneas y pega las pestañas',

        'mission'      => 'Cartas de misión',
        'mission_none' => 'Todavía no hay ninguna',
        'sheet_of'     => 'Hoja {page} de {total}',
        'cards'        => ['one' => '{n} carta', 'other' => '{n} cartas'],

        'hero'      => 'Carta de campeón',
        'hero_sub'  => 'Una por juego',

        'tokens'     => 'Fichas de los jugadores',
        'tokens_sub' => 'Recorta y pega sobre cartulina',

        'answers'      => 'Soluciones',
        'answers_keep' => 'Guarda esta hoja',
    ],

    'howto' => [
        'prepare'       => 'Qué hay que preparar:',
        'prepare_move'  => ['one' => '{n} carta de movimiento', 'other' => '{n} cartas de movimiento'],
        'prepare_dice'  => 'el dado recortable',
        'prepare_cards' => '{total} cartas de misión barajadas en un solo montón',
        'prepare_hero'  => ['one' => '{n} carta de campeón', 'other' => '{n} cartas de campeón'],
        'prepare_token' => 'y una ficha para cada jugador',
    ],

    'dice' => [
        'alt'     => 'Dado para recortar y doblar',
        'steps'   => [
            'Recorta el contorno de toda la figura, con las pestañas incluidas.',
            'Dobla por todas las líneas interiores, para que las seis caras queden hacia dentro.',
            'Pega cada pestaña debajo de la cara vecina y sujeta hasta que seque.',
            'Con un solo dado basta para toda la mesa: tira y avanza esas casillas.',
        ],
        'missing' => 'Falta el dibujo del dado. Pon un archivo llamado <code>dice-net.png</code> en '
                   . '<code>uploads/library/</code> y vuelve a imprimir, o juega con cualquier dado '
                   . 'normal de seis caras.',
    ],

    'move_card' => [
        'forward' => ['one' => 'Avanza {n} casilla', 'other' => 'Avanza {n} casillas'],
        'back'    => ['one' => 'Respuesta incorrecta: retrocede {n} casilla',
                      'other' => 'Respuesta incorrecta: retrocede {n} casillas'],
    ],

    'mission' => [
        'empty' => 'Este proyecto todavía no tiene cartas de misión. Vuelve al Studio y elige '
                 . '"Match mission cards".',
    ],

    'hero_card' => [
        'champion' => 'Campeón',
        'hero_default' => 'nuestro héroe',
        'eyebrow'  => 'Héroe de',
        'line'     => 'Superó los {n} retos y llegó el primero a la meta.',
        'congrats' => '¡Enhorabuena, {name}!',
        'winner'   => 'Tu nombre',
        'date'     => 'Fecha',
    ],

    'level' => [
        'beginner' => 'Principiante',
        'standard' => 'Intermedio',
        'advanced' => 'Avanzado',
    ],

    'board' => [
        'start'  => 'SALIDA',
        'finish' => 'META',
    ],

    'tokens' => [
        'player' => 'Jugador {n}',
        'note' => 'Cada jugador recibe dos fichas: una para jugar y otra de repuesto. Pégalas sobre '
                . 'cartulina gruesa y recorta alrededor del círculo para que se sostengan en el tablero.',
    ],

    'answers' => [
        'warn'  => '<b>Para quien dirige el juego.</b> Separa estas últimas hojas del final del '
                 . 'montón y guárdalas. Las cartas de misión no llevan la respuesta.',
        'in_order' => 'En el orden en que se imprimen las cartas',
    ],
    // La página de la historia. Los {marcadores} se rellenan con los datos del juego.
    'story' => [
        'hero_default' => 'nuestro joven héroe',
        'place' => [
            'Todo sucede en {place}, un lugar que hasta esta mañana nunca había dado a nadie '
                . 'un motivo de preocupación.',
            'Todo transcurre en {place}, que hasta hoy se las había arreglado perfectamente bien.',
            'Esto pasa en {place}, un sitio al que nunca había hecho falta ir a rescatar a nadie.',
        ],

        'p2' => [
            'La noticia corre deprisa y llega a {hero} antes que a nadie. Ahí fuera está '
                . '{rescue}, esperando, sin saber si alguien vendrá a ayudar. Ninguna persona mayor '
                . 'quiere ir. Así que {hero} prepara una mochila, no dice nada a nadie y sale mientras '
                . 'todavía hay luz.',
            'La noticia se extiende, como se extienden las noticias, y {hero} es quien primero la '
                . 'oye. Ahí fuera, en alguna parte, está {rescue}, esperando y sin ninguna seguridad '
                . 'de que alguien vaya a venir. Los mayores lo hablan y deciden que queda demasiado '
                . 'lejos. Así que {hero} llena una mochila, no avisa a nadie y se marcha mientras aún '
                . 'hay luz para caminar.',
            'A media mañana ya se ha enterado todo el pueblo, y {hero} lo ha oído dos veces. Más '
                . 'allá de las últimas casas está {rescue}, contando con alguien que todavía no ha '
                . 'salido. Ningún adulto se ofrece, así que {hero} prepara una mochila, se va sin '
                . 'decir palabra y toma el camino mientras el día aguanta.',
        ],

        'setout' => [
            'Nadie sale a un viaje así con los bolsillos vacíos, así que {hero} lleva pan, una manta, '
                . 'un trozo de cuerda que acabará siendo lo más útil de todo, y bastante menos '
                . 'valor del que hace falta.',
            'Por un camino así no se anda con las manos vacías, así que {hero} se lleva pan, una '
                . 'manta, una vela y un trozo de cuerda cuya utilidad nadie habría podido imaginar '
                . 'entonces.',
            'En la mochila va lo que {hero} pudo encontrar con prisas: pan, una manta, medio mapa '
                . 'y una piedrecita guardada por suerte desde un verano que ya nadie recuerda.',
        ],

        'p3' => [
            'El camino se divide en {cells} etapas, y ninguna deja pasar gratis. {trouble} En cada '
                . 'etapa espera una pregunta, y responderla bien es la única forma de avanzar.',
            'Hay {cells} etapas entre aquí y allí, y todas piden algo. {trouble} En cada una espera '
                . 'una pregunta, y una buena respuesta es el único peaje que el camino acepta.',
            'El camino se parte en {cells} etapas, y ninguna es generosa. {trouble} Cada una tiene '
                . 'una pregunta atravesada, y la única llave que encaja es una respuesta acertada.',
        ],

        'wrong' => [
            'Habrá momentos en los que la respuesta no llegue. Está permitido. El camino te '
                . 'hace retroceder un paso, espera mientras lo piensas otra vez y luego te deja '
                . 'seguir. A nadie lo mandan a casa por equivocarse: la única forma de perder un '
                . 'viaje como este es dejar de andarlo.',
            'Algunas preguntas no se abren al primer intento. No pasa nada, y es lo esperable. El '
                . 'camino te devuelve un paso, te deja darle otra vuelta y luego sigue igual que '
                . 'antes. A nadie lo han mandado nunca a casa por una respuesta equivocada: el '
                . 'camino solo se acaba para quien deja de andarlo.',
            'Habrá una pregunta que se quede ahí quieta, y por mucho que la mires no se moverá. Le '
                . 'pasa a todo el mundo. Pierdes un paso, lo piensas otra vez y sigues. Equivocarse '
                . 'nunca ha terminado un viaje; solo soltar la mochila para siempre.',
        ],

        'last' => [
            'Y entonces, de golpe, ya no queda nada entre {hero} y {rescue} salvo una última '
                . 'pregunta.',
            'Y ya solo queda una pregunta en pie entre {hero} y {rescue}.',
            'Una pregunta más, y nada más, separa a {hero} de {rescue}.',
        ],

        'p4' => [
            'Llega al final y {rescue} vuelve a casa, y la historia de cómo ocurrió pertenece a '
                . '{hero} desde ese día. Esa historia se llama "{title}".',
            'Llega hasta el final y {rescue} vuelve a casa, y desde ese día la historia de cómo '
                . 'pasó le pertenece a {hero}. Se conoce con el nombre de "{title}".',
            'Termina el camino y {rescue} duerme esta noche en casa. Contarlo le corresponde a '
                . '{hero} para siempre, bajo el nombre de "{title}".',
        ],

        'opening' => [
            'forest' => [
                'El bosque antiguo se ha quedado en silencio. Hasta las hojas doradas han dejado de caer, detenidas en el aire.',
                'Todos los senderos del bosque viejo se han quedado callados a la vez, y ningún pájaro quiere decir por qué.',
            ],
            'dino' => [
                'Un rugido enorme sube desde el valle. En algún lugar ahí abajo, un bebé dinosaurio se ha perdido.',
                'El valle entero lleva rugiendo desde el amanecer, y una de esas voces es demasiado pequeña.',
            ],
            'space' => [
                'La estación espacial lanza una llamada de auxilio: un pequeño planeta está a punto de perder su luz para siempre.',
                'Llega una señal desde el borde del mapa, débil y repetida: un planeta pequeño se está quedando a oscuras.',
            ],
            'ocean' => [
                'El arrecife de coral se está volviendo gris y los peces pequeños piden ayuda.',
                'Al arrecife se le ha ido algo durante la noche: primero el color y después el ruido.',
            ],
            'pirate' => [
                'Un mapa gastado llega a la orilla dentro de una botella y promete un tesoro que el mundo olvidó.',
                'La marea trae una botella con medio mapa dentro y una promesa que ha durado más que el barco del que salió.',
            ],
            'magic' => [
                'La llama mágica de la torre vieja se ha apagado y todo el reino se hunde en la niebla.',
                'La torre vieja se ha quedado fría por primera vez en trescientos años, y la niebla baja por el valle a mirar.',
            ],
            'castle' => [
                'Robaron la campana dorada del castillo la noche antes de la gran fiesta.',
                'Esta mañana la gran campana no sonó, y al mediodía todo el reino sabía que ya no estaba.',
            ],
            'desert' => [
                'El único oasis del desierto se seca un poco más cada día que pasa.',
                'El pozo salió turbio esta mañana, luego escaso, y el oasis lleva encogiendo desde entonces.',
            ],
            'arctic' => [
                'El bloque de hielo donde viven los pingüinos se derrite demasiado deprisa.',
                'El hielo ha empezado a hablar solo allá en la bahía, y el témpano donde viven los pingüinos es más pequeño que ayer.',
            ],
            'candy' => [
                'El río de chocolate del País de los Dulces se ha congelado de la noche a la mañana.',
                'El río de chocolate dejó de correr por la noche, y esta mañana se puede cruzar andando.',
            ],
            'robot' => [
                'La fábrica de robots se ha quedado sin electricidad y todas las máquinas se han parado a media tarea.',
                'Todas las máquinas de la fábrica se pararon en el mismo segundo, a mitad de lo que estuvieran haciendo.',
            ],
            'farm' => [
                'Todos los animales de la granja desaparecieron en una noche de mucho viento.',
                'Al amanecer la cancela estaba abierta, el corral vacío, y ningún animal contestó cuando lo llamaron.',
            ],
        ],

        'rescue' => [
            'forest' => 'el zorrito más pequeño del bosque',
            'dino'   => 'un bebé dinosaurio separado de su manada',
            'space'  => 'el último guardián de una estrella que se apaga',
            'ocean'  => 'una tortuga joven enredada muy lejos de casa',
            'pirate' => 'un compañero de barco abandonado en una isla sin nombre',
            'magic'  => 'la aprendiza que mantenía encendida la gran llama',
            'castle' => 'el campanero encerrado en la torre más alta',
            'desert' => 'una caravana de viajeros perdida entre las dunas',
            'arctic' => 'un pingüino pequeño a la deriva sobre un hielo que se rompe',
            'candy'  => 'la pastelera de caramelo congelada en su propia cocina',
            'robot'  => 'el pequeño robot reparador que mantenía la ciudad en marcha',
            'farm'   => 'todos los animales que desaparecieron por la noche',
        ],

        'trouble' => [
            'forest' => 'Los senderos cambian de sitio solos y los árboles han dejado de indicar el camino.',
            'dino'   => 'El suelo tiembla sin avisar y los pasos seguros cambian con cada temblor.',
            'space'  => 'Queda poco combustible, los mapas están anticuados y ninguna estrella está donde debería.',
            'ocean'  => 'Las corrientes van al revés y el agua se vuelve más oscura a cada brazada.',
            'pirate' => 'El mapa está roto por partes y otra tripulación sigue las mismas pistas.',
            'magic'  => 'La niebla se traga cada hechizo y la magia de siempre ahora falla.',
            'castle' => 'Las puertas solo responden a adivinanzas y los guardias han olvidado todas las respuestas.',
            'desert' => 'El viento entierra cada señal a los pocos minutos de encontrarla.',
            'arctic' => 'El hielo cruje bajo los pies y la luz del día ya empieza a irse.',
            'candy'  => 'Todo lo dulce se ha vuelto quebradizo y los puentes se parten si cruzas despacio.',
            'robot'  => 'La mitad de las máquinas sigue con órdenes viejas y no sabe que la ciudad está averiada.',
            'farm'   => 'Todas las puertas quedaron abiertas y las huellas van en todas las direcciones a la vez.',
        ],

        // Quién los acompaña. Aquí está casi toda la gracia de la historia,
        // así que cada uno es un personaje con carácter, no una descripción.
        'companion' => [
            'forest' => [
                'Una lechuza vieja anuncia que solo llegará hasta la segunda curva. Al final hace el camino entero, quejándose del tiempo en cada etapa.',
                'Un erizo con un farol muy pequeño se empeña en venir. Anda tan despacio que todo el grupo aprende el nombre de cosas por las que habría pasado de largo.',
            ],
            'dino' => [
                'Un pterosaurio pequeño y muy ruidoso se nombra a sí mismo vigía. Nunca ha visto nada útil, pero no se calla jamás, y eso resulta casi igual de bueno.',
                'Una triceratops joven se une al grupo sin que nadie se lo pida. No sabe trepar ni nadar, y aparta un árbol caído del camino en menos de un minuto.',
            ],
            'space' => [
                'La estación envía un dron de reparación con un solo ojo que funciona y la costumbre de tararear. Conoce la ruta a exactamente un planeta y está casi seguro de que es el correcto.',
                'Un robot de carga con una rueda que chirría viene a llevar los bultos. Ha estado dos veces en el borde del mapa y lo menciona más o menos cada diez minutos.',
            ],
            'ocean' => [
                'Un cangrejo viejo y gruñón acepta venir con una condición: que nadie mencione lo despacio que nada. Nadie lo menciona. Aguanta el ritmo mucho mejor de lo que todos esperaban.',
                'Un pulpo muy joven se apunta y cambia de color cada vez que alguien lo mira, que es como todos saben en todo momento lo que está pensando.',
            ],
            'pirate' => [
                'El loro del barco se apunta el primero, sobre todo porque se sabe el mapa de memoria y no soporta perderse la parte en que alguien lo lee en voz alta.',
                'El cocinero del barco también viene, y trae una sartén, una cuchara y una opinión firme sobre cada decisión que se tome a partir de ahora.',
            ],
            'magic' => [
                'Un cabo de vela que se niega a apagarse va flotando detrás, alumbrando justo lo que no toca en el momento que no toca, y muy orgulloso de sí mismo.',
                'Una rana que antes fue algo más importante se ofrece de guía. Solo recuerda la mitad de un hechizo, pero resulta ser una mitad muy útil.',
            ],
            'castle' => [
                'La gata del castillo también viene. Ha estado en todas las salas, bajo todos los suelos y detrás de todas las cortinas, y se acuerda de cada una.',
                'El pinche de cocina viene cargando el segundo mejor farol, y resulta que conoce todas las escaleras traseras del reino.',
            ],
            'desert' => [
                'Una camella joven con ideas muy firmes sobre caminar se une en la puerta. Para cuando quiere y arranca cuando quiere, y no se ha perdido nunca.',
                'Un zorro del desierto con unas orejas enormes se une la segunda noche. Oye el agua tres colinas antes que nadie, y está insoportablemente satisfecho de ello.',
            ],
            'arctic' => [
                'Una foca pequeña y redonda los sigue desde la primera etapa, deslizándose por delante sobre la barriga para probar el hielo y volviendo cada vez a decir si aguanta.',
                'Un frailecillo que lleva nueve inviernos volando esta costa viene de navegante, y se toma el encargo más en serio que nadie se ha tomado nada.',
            ],
            'candy' => [
                'Un ratón de jengibre hace de guía. Mordisquea una muesca en cada cartel para que la vuelta sea fácil de encontrar, y se come unos cuantos carteles enteros.',
                'Un oso de mazapán rueda detrás del grupo, comiéndose un poco del camino según avanza, pidiendo perdón cada vez y volviéndolo a hacer.',
            ],
            'robot' => [
                'Un robot barrendero oxidado sale por una puerta lateral y se apunta sin más. Su mapa tiene cuarenta años, pero conoce un atajo, y el atajo existe de verdad.',
                'Un dron pequeño con la lente rajada va al lado. Lo graba todo, que no le hace falta a nadie, y alumbra los rincones oscuros, que le hace falta a todos.',
            ],
            'farm' => [
                'El perro de la granja no necesita ninguna invitación. Lleva esperando en la cancela desde el amanecer con el hocico apuntando al camino, seguro del todo de por dónde hay que ir.',
                'La oca más vieja de la granja también viene. Se ha escapado once veces de este corral y conoce todos los huecos de todas las vallas.',
            ],
        ],

        // La última etapa: el mundo respondiendo, justo antes del final.
        'final' => [
            'forest' => 'En la última etapa los árboles se han inclinado a escuchar, y las hojas doradas que dejaron de caer esta mañana empiezan, muy despacio, a caer otra vez.',
            'dino'   => 'En la última etapa el suelo por fin se queda quieto, y desde detrás de la loma llega un rugido pequeño: esta vez no de miedo, sino de esperanza.',
            'space'  => 'En la última etapa el planeta pequeño ya se ve: una luz débil en toda esa oscuridad, parpadeando como una vela a punto de apagarse.',
            'ocean'  => 'En la última etapa vuelve al coral una línea fina de color, y los peces más pequeños salen por ella a recibir a los viajeros.',
            'pirate' => 'En la última etapa el mapa roto se acaba del todo, y hay que averiguar el camino solo por la forma de la costa.',
            'magic'  => 'En la última etapa la niebla se deshace hasta desaparecer, y la torre vieja está allí esperando, oscura y paciente, con una lámpara fría en lo más alto.',
            'castle' => 'En la última etapa ya ondean las banderas de la fiesta, y el reino entero espera en la plaza una campana que todavía no ha sonado.',
            'desert' => 'En la última etapa el viento cae, la arena se posa y el verde del oasis aparece en el horizonte justo donde el mapa lo prometía.',
            'arctic' => 'En la última etapa la luz del día vuelve una hora más, y una hora basta para distinguir una forma oscura y pequeña sobre un témpano blanco.',
            'candy'  => 'En la última etapa el río de chocolate helado cruje una vez, muy bajito, como cruje el hielo cuando ha decidido empezar a derretirse.',
            'robot'  => 'En la última etapa se enciende una luz en el fondo de la fábrica, y luego dos, y luego una fila entera, como si el edificio despertara para mirar.',
            'farm'   => 'En la última etapa aparece una huella en el barro, y luego otra, y por fin llevan a algún sitio en vez de a todas partes a la vez.',
        ],
    ],

    // Los veinte tipos de aventura, contados como un lugar para la historia
    'settings' => [
        'Treasure Hunt'        => 'una isla pirata con calas escondidas, palmeras y un tesoro enterrado',
        'Dinosaur Rescue'      => 'un valle prehistórico de helechos gigantes y volcanes humeantes',
        'Jungle Adventure'     => 'una selva espesa de lianas, cascadas y ruinas cubiertas de plantas',
        'Ocean Rescue'         => 'un arrecife de coral luminoso bajo un mar lleno de sol',
        'Space Mission'        => 'un trozo de espacio estrellado con planetas pequeños y una estación de aterrizaje',
        'Save the City'        => 'una ciudad amable de edificios altos, parques y calles con mucho movimiento',
        'Forest Guardian'      => 'un bosque antiguo de árboles altos, piedras con musgo y claros tranquilos',
        'Museum Mystery'       => 'un gran museo de salas largas, vitrinas y escaleras de mármol',
        'Safari Expedition'    => 'una sabana amplia de hierba dorada, acacias y charcas',
        'Lost Island'          => 'una isla perdida rodeada de acantilados, selva y una laguna quieta',
        'Time Travel Quest'    => 'un lugar donde se juntan las épocas: un castillo, una pirámide y una ciudad del futuro',
        'Robot Workshop'       => 'un taller de robots lleno de engranajes, cintas transportadoras y máquinas con lucecitas',
        'Candy Kingdom Quest'  => 'un reino de caramelo con árboles de piruleta, ríos de chocolate y casas de galleta',
        'Arctic Expedition'    => 'un ártico helado de placas de hielo, colinas nevadas y auroras boreales',
        'Desert Pyramid Quest' => 'un desierto dorado de dunas, oasis con palmeras y pirámides antiguas',
        'Circus Adventure'     => 'un circo de colores con carpas de rayas, banderines y carromatos pintados',
        'Farm Rescue'          => 'una granja soleada de graneros rojos, pacas de paja y campos verdes',
        'Mountain Rescue'      => 'una montaña alta de cumbres nevadas, pinos y puentes de cuerda',
        'Storm Chasers'        => 'una llanura enorme bajo un cielo de nubes de tormenta y relámpagos',
        'Fairy Tale Kingdom'   => 'un reino de cuento con torres de castillo, colinas suaves y caminos que serpentean',
    ],

    'rules' => [
        'start'        => 'Cada jugador elige una ficha y la coloca en la casilla de SALIDA.',
        'move_cards'   => 'En tu turno, roba una carta de movimiento y avanza las casillas que indique. '
                        . 'Deja la carta delante de ti.',
        'move_dice'    => 'En tu turno, tira el dado y avanza ese número de casillas.',
        'star'         => 'Si caes en una casilla con estrella, coge la carta de arriba del montón '
                        . 'de misiones. Si caes en cualquier otra, tu turno termina sin más.',
        'answer'       => 'Responde la pregunta. Si aciertas, te quedas donde estás.',
        'wrong_cards'  => 'Si fallas, retrocede lo que indique el castigo escrito en la carta de movimiento que robaste.',
        'wrong_dice'   => 'Si fallas, retrocede una casilla.',
        'return_cards' => 'Pon la carta de misión al fondo del montón de misiones, y la de movimiento al fondo de su mazo.',
        'return_dice'  => 'Pon la carta de misión al fondo del montón de misiones.',
        'win'          => 'El primer jugador que llegue a la casilla de META gana la carta de campeón.',
    ],
];
