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
        'models' => 'Mejor aún: usa una figurita para marcar dónde estás. Sirve cualquier cosa que quepa en una casilla: un muñequito, un botón, una cuenta o una moneda.',
    ],

    'answers' => [
        'warn'  => '<b>Para quien dirige el juego.</b> Separa estas últimas hojas del final del '
                 . 'montón y guárdalas. Las cartas de misión no llevan la respuesta.',
        'in_order' => 'En el orden en que se imprimen las cartas',
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
        'back_star'    => 'Retroceder nunca te cuesta una pregunta: si así caes en una estrella, no robas carta.',
        'return_cards' => 'Pon la carta de misión al fondo del montón de misiones, y la de movimiento al fondo de su mazo.',
        'return_dice'  => 'Pon la carta de misión al fondo del montón de misiones.',
        'win'          => 'El primer jugador que llegue a la casilla de META gana la carta de campeón.',
    ],
];
