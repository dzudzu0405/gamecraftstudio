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
        'prepare_cards' => '{total} cartas de misión repartidas en {piles} montones ({each} por casilla)',
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

    'tokens' => [
        'note' => 'Cada jugador recibe dos fichas: una para jugar y otra de repuesto. Pégalas sobre '
                . 'cartulina gruesa y recorta alrededor del círculo para que se sostengan en el tablero.',
    ],

    'answers' => [
        'warn'  => '<b>Para quien dirige el juego.</b> Separa estas últimas hojas del final del '
                 . 'montón y guárdalas. Las cartas de misión no llevan la respuesta.',
        'space' => 'Casilla {n}',
    ],
    // La página de la historia. Los {marcadores} se rellenan con los datos del juego.
    'story' => [
        'hero_default' => 'nuestro joven héroe',
        'place' => 'Todo sucede en {place}, un lugar que hasta esta mañana nunca había dado a nadie '
                 . 'un motivo de preocupación.',
        'p2'    => 'La noticia corre deprisa y llega a {hero} antes que a nadie. Ahí fuera está '
                 . '{rescue}, esperando, sin saber si alguien vendrá a ayudar. Ninguna persona mayor '
                 . 'quiere ir. Así que {hero} prepara una mochila, no dice nada a nadie y sale mientras '
                 . 'todavía hay luz.',
        'p3'    => 'El camino se divide en {cells} etapas, y ninguna deja pasar gratis. {trouble} En cada '
                 . 'etapa hay una pregunta que responder, y responder bien es la única forma de avanzar. '
                 . 'Si fallas, el camino te hace retroceder un paso, pero nunca se cierra.',
        'p4'    => 'Llega al final y {rescue} vuelve a casa, y la historia de cómo ocurrió pertenece a '
                 . '{hero} desde ese día. Esa historia se llama "{title}".',

        'opening' => [
            'forest' => 'El bosque antiguo se ha quedado en silencio. Hasta las hojas doradas han dejado de caer, detenidas en el aire.',
            'dino'   => 'Un rugido enorme sube desde el valle. En algún lugar ahí abajo, un bebé dinosaurio se ha perdido.',
            'space'  => 'La estación espacial lanza una llamada de auxilio: un pequeño planeta está a punto de perder su luz para siempre.',
            'ocean'  => 'El arrecife de coral se está volviendo gris y los peces pequeños piden ayuda.',
            'pirate' => 'Un mapa gastado llega a la orilla dentro de una botella y promete un tesoro que el mundo olvidó.',
            'magic'  => 'La llama mágica de la torre vieja se ha apagado y todo el reino se hunde en la niebla.',
            'castle' => 'Robaron la campana dorada del castillo la noche antes de la gran fiesta.',
            'desert' => 'El único oasis del desierto se seca un poco más cada día que pasa.',
            'arctic' => 'El bloque de hielo donde viven los pingüinos se derrite demasiado deprisa.',
            'candy'  => 'El río de chocolate del País de los Dulces se ha congelado de la noche a la mañana.',
            'robot'  => 'La fábrica de robots se ha quedado sin electricidad y todas las máquinas se han parado a media tarea.',
            'farm'   => 'Todos los animales de la granja desaparecieron en una noche de mucho viento.',
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
        'star'         => 'Si caes en una casilla con estrella, roba una carta de misión de esa casilla. '
                        . 'Si caes en cualquier otra, tu turno termina sin más.',
        'answer'       => 'Responde la pregunta. Si aciertas, te quedas donde estás.',
        'wrong_cards'  => 'Si fallas, retrocede lo que indique el castigo escrito en la carta de movimiento que robaste.',
        'wrong_dice'   => 'Si fallas, retrocede una casilla.',
        'return_cards' => 'Devuelve la carta de misión al fondo de su montón, y la carta de movimiento al fondo de su mazo.',
        'return_dice'  => 'Devuelve la carta de misión al fondo de su montón.',
        'win'          => 'El primer jugador que llegue a la casilla de META gana la carta de campeón.',
    ],
];
