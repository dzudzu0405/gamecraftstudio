<?php
/**
 * The question templates in Spanish. Keyed by the English template's code;
 * see install/mission-templates-all.php for how the two halves are joined.
 *
 * GENDER
 *
 * A blank dropped into a Spanish sentence has to agree with it, and the
 * sentence is written once for a dozen different words. So each word list is
 * kept to a single gender - every creature masculine, every container feminine
 * - and the places carry their own article. That way "Hay 5 conejos en la
 * playa" and "Hay 5 patitos en el jardin" are both correct without the sentence
 * having to know which word turned up.
 *
 * The reading and spelling questions are not translations. Rhymes, plurals and
 * past tenses only exist inside one language, so those are written fresh here.
 */

$creatures = ['conejos', 'gatitos', 'cachorros', 'patitos', 'zorros', 'búhos',
              'pingüinos', 'delfines', 'erizos', 'caballos', 'ratones', 'cocodrilos'];

$things = ['botones', 'cromos', 'caramelos', 'lápices', 'guijarros', 'globos',
           'bloques', 'coches', 'sellos', 'dados', 'palitos', 'cubos'];

$places = ['el prado', 'el jardín', 'el estanque', 'el bosque', 'el parque',
           'la playa', 'el huerto', 'la casa del árbol', 'el granero', 'la charca'];

$names = ['Lucía', 'Mateo', 'Sofía', 'Hugo', 'Carmen', 'Diego',
          'Noa', 'Bruno', 'Elena', 'Pablo', 'Ada', 'Nur'];

$containers = ['cestas', 'cajas', 'bolsas', 'huchas', 'bandejas', 'cubetas', 'latas'];

$treats = ['bizcochos', 'plátanos', 'bocadillos', 'caramelos', 'pasteles', 'higos'];

return [

    // ----------------------------------------------------------------- MATHS
    'math-add' => [
        'name'    => 'Sumar',
        'pattern' => 'Hay {a} {creature} en {place}. Llegan {b} más. ¿Cuántos {creature} hay ahora?',
        'answer'  => '{a+b} {creature}',
        'hint'    => 'Cuéntalos todos juntos.',
        'lists'   => ['creature' => $creatures, 'place' => $places],
    ],
    'math-sub' => [
        'name'    => 'Restar',
        'pattern' => '{who} ha reunido {a} {thing}. {who} regala {b}. ¿Cuántos {thing} le quedan?',
        'answer'  => '{a-b} {thing}',
        'hint'    => 'Empieza por lo que tenías y quita el resto.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-count-back' => [
        'name'    => 'Contar hacia atrás',
        'pattern' => 'Empieza en {a} y cuenta {b} hacia atrás. ¿Dónde llegas?',
        'answer'  => '{a-b}',
        'hint'    => 'Usa los dedos si te ayuda.',
    ],
    'math-double' => [
        'name'    => 'El doble',
        'pattern' => '{who} tiene {a} {thing} y encuentra exactamente la misma cantidad otra vez. ¿Cuántos tiene ahora?',
        'answer'  => '{a+a} {thing}',
        'hint'    => 'El doble es la misma cantidad dos veces.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-mul' => [
        'name'    => 'Grupos de',
        'pattern' => 'Hay {a} {container} y cada una lleva {b} {thing}. ¿Cuántos {thing} hay en total?',
        'answer'  => '{a*b} {thing}',
        'hint'    => 'Cuenta los grupos y luego lo que hay en cada uno.',
        'lists'   => ['container' => $containers, 'thing' => $things],
    ],
    'math-div' => [
        'name'    => 'Repartir',
        'pattern' => 'Reparte {a} {treat} en partes iguales entre {b} amigos. ¿Cuántos le tocan a cada uno?',
        'answer'  => '{a/b} {treat} para cada uno',
        'hint'    => 'Répartelos de uno en uno, como si fueran cartas.',
        'lists'   => ['treat' => $treats],
    ],
    'math-missing' => [
        'name'    => 'El número que falta',
        'pattern' => '{who} tenía algunos {thing}, le dieron {b} más y ahora tiene {c}. ¿Cuántos tenía al principio?',
        'answer'  => '{c-b} {thing}',
        'hint'    => 'Ve hacia atrás desde el total.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-word2' => [
        'name'    => 'Problema de monedas',
        'pattern' => 'Tienes {a} monedas. Compras {b} {thing} que cuestan {c} monedas cada uno. ¿Cuántas monedas te quedan?',
        'answer'  => '{a-b*c} monedas',
        'hint'    => 'Calcula primero lo que gastas y réstalo de lo que tenías.',
        'lists'   => ['thing' => $things],
    ],
    'math-two-step' => [
        'name'    => 'Dos pasos',
        'pattern' => '{who} llena {a} {container} con {b} {thing} en cada una, y luego se caen {c} {thing}. ¿Cuántos quedan guardados?',
        'answer'  => '{a*b-c} {thing}',
        'hint'    => 'Multiplica primero y resta después.',
        'lists'   => ['container' => $containers, 'thing' => $things, 'who' => $names],
    ],

    // -------------------------------------------------------------- LITERACY
    'lit-rhyme' => [
        'name'    => 'Busca una rima',
        'pattern' => 'Di una palabra que rime con "{word}".',
        'answer'  => 'Vale cualquier palabra que rime de verdad',
        'hint'    => 'Díla en voz alta y escucha el final.',
        'lists'   => ['word' => ['gato', 'flor', 'ratón', 'sol', 'pan', 'mano',
                                 'rana', 'estrella', 'nariz', 'pelota', 'barco', 'campana']],
    ],
    'lit-letter' => [
        'name'    => 'El sonido del principio',
        'pattern' => 'Di {a} cosas que empiecen por la letra "{letter}".',
        'answer'  => 'Cualquier {a} palabras válidas',
        'hint'    => 'Mira a tu alrededor para buscar ideas.',
        'lists'   => ['letter' => ['B', 'C', 'D', 'F', 'G', 'L', 'M', 'P', 'R', 'S', 'T', 'Z']],
    ],
    'lit-opposite' => [
        'name'    => 'Contrarios',
        'pattern' => '¿Cuál es lo contrario de "{word}"?',
        'answer'  => 'El contrario correcto',
        'hint'    => 'Piensa en la palabra que significa justo al revés.',
        'lists'   => ['word' => ['caliente', 'grande', 'rápido', 'contento', 'día', 'arriba',
                                 'ruidoso', 'mojado', 'lleno', 'temprano', 'duro', 'cerca']],
    ],
    'lit-define' => [
        'name'    => 'Con tus palabras',
        'pattern' => '¿Qué significa la palabra "{word}"? Explícalo con tus palabras.',
        'answer'  => 'Vale cualquier explicación razonable',
        'hint'    => 'Prueba a usarla primero en una frase.',
        'lists'   => ['word' => ['valentía', 'curioso', 'amable', 'antiguo', 'frágil',
                                 'generoso', 'testarudo', 'agradecido', 'nervioso', 'leal']],
    ],
    'lit-sentence' => [
        'name'    => 'Construye una frase',
        'pattern' => 'Inventa una frase que use "{word}" y "{other}" a la vez.',
        'answer'  => 'Cualquier frase con las dos palabras',
        'hint'    => 'Puede ser todo lo tonta que quieras.',
        'lists'   => ['word'  => ['río', 'castillo', 'tormenta', 'farol', 'puerto', 'prado'],
                      'other' => ['silencioso', 'dorado', 'repentino', 'enorme', 'helado', 'torcido']],
    ],
    'lit-story' => [
        'name'    => 'Sigue el cuento',
        'pattern' => 'Cuenta la parte siguiente de la historia: "{opening}"',
        'answer'  => 'Cualquier continuación que encaje',
        'hint'    => 'Con dos o tres frases basta.',
        'lists'   => ['opening' => [
            'La puerta del final de la escalera nunca había estado abierta...',
            'El mapa mostraba una isla que no salía en ningún otro mapa...',
            'Todo el pueblo se despertó hablando al revés...',
            'El faro viejo se encendió solo después de veinte años...',
            'Llegó una carta a nombre de alguien que no vivía allí...',
        ]],
    ],

    // --------------------------------------------------------------- ENGLISH
    // In Spanish this is Spanish grammar: plurals and past tenses of its own.
    'eng-plural' => [
        'name'    => 'Uno y muchos',
        'pattern' => '¿Cuál es el plural de "{word}"?',
        'answer'  => 'El plural correcto',
        'hint'    => 'Algunas palabras cambian más de lo que esperas.',
        'lists'   => ['word' => ['lápiz', 'pez', 'ratón', 'jardín', 'luz',
                                 'camión', 'rey', 'flor', 'pared', 'bebé']],
    ],
    'eng-verb' => [
        'name'    => 'Ayer',
        'pattern' => 'Ponlo en pasado: "Todos los días {verb}."',
        'answer'  => 'El pasado correcto de "{verb}"',
        'hint'    => 'Dílo como si ya hubiera pasado.',
        'lists'   => ['verb' => ['corro', 'nado', 'como', 'escribo', 'canto',
                                 'pienso', 'traigo', 'juego', 'dibujo', 'duermo']],
    ],
    'eng-describe' => [
        'name'    => 'Descríbelo',
        'pattern' => 'Describe {thing} a alguien que no sabe qué es, sin decir la palabra.',
        'answer'  => 'Cualquier descripción clara',
        'hint'    => 'Empieza por para qué sirve.',
        'lists'   => ['thing' => ['una bicicleta', 'un paraguas', 'una tetera', 'una escalera',
                                  'un telescopio', 'un castillo de arena', 'un molino', 'una cometa']],
    ],

    // --------------------------------------------------------------- SCIENCE
    'sci-sense' => [
        'name'    => '¿Qué sentido?',
        'pattern' => '¿Qué parte del cuerpo usas para {sense}?',
        'answer'  => 'El órgano del sentido que toca',
        'hint'    => 'Señálala.',
        'lists'   => ['sense' => ['oler una flor', 'oír una campana', 'saborear la miel',
                                  'ver un arco iris', 'notar la arena caliente']],
    ],
    'sci-float' => [
        'name'    => '¿Flota o se hunde?',
        'pattern' => '¿{thing} flotaría o se hundiría en el agua? Di por qué lo piensas.',
        'answer'  => 'Cualquiera de las dos con un motivo',
        'hint'    => 'Piensa en lo que pesa para el tamaño que tiene.',
        'lists'   => ['thing' => ['Un corcho', 'Una piedra', 'Una manzana', 'Una moneda', 'Una pluma',
                                  'Una esponja', 'Un clavo', 'Una vela', 'Una naranja']],
    ],
    'sci-change' => [
        'name'    => '¿Qué pasa después?',
        'pattern' => '¿Qué pasa si {event}? Explícalo lo mejor que puedas.',
        'answer'  => 'Una explicación razonable',
        'hint'    => 'Di lo que verías y luego por qué.',
        'lists'   => ['event' => [
            'dejas un cubito de hielo en una habitación cálida',
            'guardas una planta en un armario oscuro',
            'te frotas las manos muy deprisa',
            'echas sal en un vaso de agua y lo remueves',
            'dejas un globo al sol',
        ]],
    ],

    // ---------------------------------------------------------------- NATURE
    'nature-animal' => [
        'name'    => 'Sonidos de animales',
        'pattern' => '¿Qué sonido hace {animal}? ¡Imítalo lo mejor que puedas!',
        'answer'  => 'Vale cualquier intento',
        'hint'    => 'Aquí nadie te pone nota.',
        'lists'   => ['animal' => ['una vaca', 'un pato', 'un león', 'una oveja', 'un búho',
                                   'una rana', 'un caballo', 'una abeja', 'un gato', 'un lobo',
                                   'una cabra', 'un cuervo']],
    ],
    'nature-home' => [
        'name'    => '¿Dónde vive?',
        'pattern' => '¿Dónde vive {animal}?',
        'answer'  => 'El tipo de hogar correcto',
        'hint'    => 'Piensa en lo que necesita para estar a salvo.',
        'lists'   => ['animal' => ['una abeja', 'un conejo', 'un pingüino', 'un camello', 'un pez',
                                   'un búho', 'un topo', 'un cangrejo', 'un murciélago', 'una ardilla']],
    ],
    'nature-season' => [
        'name'    => 'Las estaciones',
        'pattern' => 'Di dos cosas que pasan en {season}.',
        'answer'  => 'Dos respuestas razonables',
        'hint'    => 'Piensa en el tiempo que hace y en las plantas.',
        'lists'   => ['season' => ['primavera', 'verano', 'otoño', 'invierno']],
    ],
    'nature-chain' => [
        'name'    => 'Quién come qué',
        'pattern' => '¿Qué puede comer {animal}, y quién puede comérselo a él?',
        'answer'  => 'Una cadena alimentaria razonable',
        'hint'    => 'Todos los animales son la cena de alguien.',
        'lists'   => ['animal' => ['un ratón', 'una rana', 'un conejo', 'un pez', 'una oruga', 'un gorrión']],
    ],

    // ----------------------------------------------------------------- LOGIC
    'logic-odd' => [
        'name'    => 'El intruso',
        'pattern' => '¿Cuál sobra: {set}? Di por qué.',
        'answer'  => 'Cualquier respuesta bien razonada',
        'hint'    => 'Puede haber más de una respuesta buena.',
        'lists'   => ['set' => [
            'manzana, plátano, zanahoria, pera',
            'perro, gato, pez, conejo',
            'rojo, azul, cuadrado, verde',
            'zapato, calcetín, gorro, cuchara',
            'coche, barco, bicicleta, árbol',
        ]],
    ],
    'logic-seq' => [
        'name'    => '¿Qué viene después?',
        'pattern' => '¿Qué número sigue: {a}, {b}, {c}, ... ?',
        'answer'  => 'El número que continúa la serie',
        'hint'    => 'Averigua el salto que hay entre ellos.',
    ],
    'logic-riddle' => [
        'name'    => 'Adivinanza',
        'pattern' => '{riddle}',
        'answer'  => 'La solución de la adivinanza',
        'hint'    => 'Léela dos veces: el truco está en cómo lo dice.',
        'lists'   => ['riddle' => [
            'Tengo agujas pero no coso. ¿Qué soy?',
            'Cuanto más seco, más mojada estoy. ¿Qué soy?',
            'Tengo teclas pero no abro ninguna puerta. ¿Qué soy?',
            'Cuanto más me quitas, más grande me hago. ¿Qué soy?',
            'Subo y bajo sin moverme del sitio. ¿Qué soy?',
        ]],
    ],

    // ------------------------------------------------------------ LIFE SKILLS
    'life-kind' => [
        'name'    => 'Algo amable',
        'pattern' => 'Di una cosa amable que puedas hacer hoy por {who}.',
        'answer'  => 'Vale cualquier idea amable',
        'hint'    => 'Las cosas pequeñas también cuentan.',
        'lists'   => ['who' => ['un amigo', 'alguien de tu familia', 'un vecino',
                                'alguien nuevo en el colegio', 'alguien que está triste']],
    ],
    'life-safe' => [
        'name'    => 'Estar a salvo',
        'pattern' => '¿Qué deberías hacer si {situation}?',
        'answer'  => 'Cualquier respuesta sensata y segura',
        'hint'    => 'Buscar a un adulto de confianza suele ser el primer paso.',
        'lists'   => ['situation' => [
            'te pierdes de tu adulto en una tienda',
            'un desconocido te pide que vayas con él',
            'hueles a quemado en casa',
            'ves que están haciendo daño a alguien',
            'te encuentras mal en el colegio',
        ]],
    ],
    'life-choice' => [
        'name'    => '¿Qué harías tú?',
        'pattern' => '¿Qué harías si {situation}?',
        'answer'  => 'No hay una sola respuesta correcta: basta con ser amable',
        'hint'    => 'Di qué harías y por qué.',
        'lists'   => ['situation' => [
            'un amigo carga con la culpa de algo que hiciste tú',
            'se te olvida traer los deberes',
            'te encuentras algo que es de otra persona',
            'has prometido la misma tarde a dos personas',
            'alguien copia tu trabajo y lo felicitan por él',
            'rompes algo y nadie te ha visto',
        ]],
    ],

    // ------------------------------------------------------------- GEOGRAPHY
    'geo-where' => [
        'name'    => 'Cerca y lejos',
        'pattern' => '¿Encontrarías {thing} cerca de tu casa, o lejos?',
        'answer'  => 'Cualquiera de las dos, con un motivo',
        'hint'    => 'Piensa en lo que hay alrededor de donde vives.',
        'lists'   => ['thing' => ['una montaña', 'una playa', 'un río', 'un desierto',
                                  'un bosque', 'un puerto', 'un volcán']],
    ],
    'geo-direction' => [
        'name'    => '¿Hacia dónde?',
        'pattern' => 'Si miras hacia el {direction} y giras a la derecha, ¿hacia dónde miras ahora?',
        'answer'  => 'La dirección que queda a la derecha del {direction}',
        'hint'    => 'Imagina una brújula, o usa las manos.',
        'lists'   => ['direction' => ['norte', 'sur', 'este', 'oeste']],
    ],
    'geo-place' => [
        'name'    => 'Lugares del mundo',
        'pattern' => 'Di un país donde esperarías encontrar {feature}, y explica por qué.',
        'answer'  => 'Cualquier país que encaje, con un motivo',
        'hint'    => 'Piensa en el frío o el calor que hace allí.',
        'lists'   => ['feature' => ['una selva tropical', 'un desierto', 'nieve todo el año',
                                    'volcanes activos', 'arrecifes de coral', 'ríos muy largos']],
    ],
];
