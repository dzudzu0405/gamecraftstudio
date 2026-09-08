<?php
/**
 * The question templates in German. Keyed by the English template's code;
 * see install/mission-templates-all.php for how the two halves are joined.
 *
 * CASE AND GENDER
 *
 * German bends the words around a blank, and one sentence has to hold a dozen
 * different ones. So each place carries its own preposition and case - im
 * Garten, auf der Wiese - and the sentences are written to avoid an article in
 * front of a blank: "mit je 5 Murmeln" rather than "in jedem Korb". Every
 * creature and object is a plural, which needs no gender.
 *
 * The reading and grammar questions are not translations: rhymes, plurals and
 * past tenses only exist inside one language, so those are written fresh here.
 */

$creatures = ['Kaninchen', 'Kätzchen', 'Welpen', 'Entenküken', 'Frösche', 'Bienen',
              'Pinguine', 'Füchse', 'Eulen', 'Schildkröten', 'Eichhörnchen', 'Delfine'];

$things = ['Äpfel', 'Murmeln', 'Aufkleber', 'Buntstifte', 'Muscheln', 'Eicheln',
           'Knöpfe', 'Kieselsteine', 'Bänder', 'Kastanien', 'Federn', 'Kirschen'];

$places = ['auf der Wiese', 'im Garten', 'am Teich', 'im Wald', 'auf dem Spielplatz',
           'am Strand', 'im Obstgarten', 'im Baumhaus', 'in der Scheune', 'am Bach'];

$names = ['Mia', 'Ben', 'Lina', 'Jonas', 'Emma', 'Theo',
          'Sana', 'Milo', 'Bella', 'Pedro', 'Ada', 'Noor'];

$containers = ['Körbe', 'Kisten', 'Gläser', 'Eimer', 'Taschen', 'Schachteln', 'Tabletts'];

$treats = ['Kekse', 'Trauben', 'Brote', 'Erdbeeren', 'Muffins', 'Pflaumen'];

return [

    // ----------------------------------------------------------------- MATHS
    'math-add' => [
        'name'    => 'Dazuzählen',
        'pattern' => 'Es sind {a} {creature} {place}. {b} weitere kommen dazu. Wie viele {creature} sind es jetzt?',
        'answer'  => '{a+b} {creature}',
        'hint'    => 'Zähle alle zusammen.',
        'lists'   => ['creature' => $creatures, 'place' => $places],
    ],
    'math-sub' => [
        'name'    => 'Wegnehmen',
        'pattern' => '{who} hat {a} {thing} gesammelt und verschenkt {b} davon. Wie viele {thing} bleiben übrig?',
        'answer'  => '{a-b} {thing}',
        'hint'    => 'Fang bei dem an, was du hattest, und nimm den Rest weg.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-count-back' => [
        'name'    => 'Rückwärts zählen',
        'pattern' => 'Fang bei {a} an und zähle {b} zurück. Wo kommst du an?',
        'answer'  => '{a-b}',
        'hint'    => 'Nimm die Finger zu Hilfe, wenn es hilft.',
    ],
    'math-double' => [
        'name'    => 'Verdoppeln',
        'pattern' => '{who} hat {a} {thing} und findet noch einmal genauso viele. Wie viele sind es jetzt?',
        'answer'  => '{a+a} {thing}',
        'hint'    => 'Verdoppeln heißt: dieselbe Menge zweimal.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-mul' => [
        'name'    => 'Gruppen von',
        'pattern' => 'Es gibt {a} {container} mit je {b} {thing}. Wie viele {thing} sind das zusammen?',
        'answer'  => '{a*b} {thing}',
        'hint'    => 'Zähle die Gruppen und dann, was in jeder steckt.',
        'lists'   => ['container' => $containers, 'thing' => $things],
    ],
    'math-div' => [
        'name'    => 'Gerecht teilen',
        'pattern' => 'Teile {a} {treat} gerecht unter {b} Freunden auf. Wie viele bekommt jeder?',
        'answer'  => '{a/b} {treat} pro Kind',
        'hint'    => 'Gib sie einzeln aus, wie Spielkarten.',
        'lists'   => ['treat' => $treats],
    ],
    'math-missing' => [
        'name'    => 'Die fehlende Zahl',
        'pattern' => '{who} hatte ein paar {thing}, bekam {b} dazu und hat jetzt {c}. Wie viele waren es am Anfang?',
        'answer'  => '{c-b} {thing}',
        'hint'    => 'Rechne von der Gesamtzahl rückwärts.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-word2' => [
        'name'    => 'Rechnen mit Münzen',
        'pattern' => 'Du hast {a} Münzen. Du kaufst {b} {thing} für je {c} Münzen. Wie viele Münzen bleiben dir?',
        'answer'  => '{a-b*c} Münzen',
        'hint'    => 'Rechne erst die Kosten aus und zieh sie dann ab.',
        'lists'   => ['thing' => $things],
    ],
    'math-two-step' => [
        'name'    => 'Zwei Schritte',
        'pattern' => '{who} packt {a} {container} mit je {b} {thing}, dann fallen {c} {thing} heraus. Wie viele sind noch eingepackt?',
        'answer'  => '{a*b-c} {thing}',
        'hint'    => 'Erst malnehmen, dann abziehen.',
        'lists'   => ['container' => $containers, 'thing' => $things, 'who' => $names],
    ],

    // -------------------------------------------------------------- LITERACY
    'lit-rhyme' => [
        'name'    => 'Finde einen Reim',
        'pattern' => 'Sag ein Wort, das sich auf „{word}“ reimt.',
        'answer'  => 'Jedes echte Reimwort zählt',
        'hint'    => 'Sprich es laut aus und hör auf das Ende.',
        'lists'   => ['word' => ['Haus', 'Baum', 'Stern', 'Maus', 'Hand', 'Katze',
                                 'Blume', 'Schnecke', 'Nase', 'Boot', 'Glocke', 'Kuchen']],
    ],
    'lit-letter' => [
        'name'    => 'Der erste Laut',
        'pattern' => 'Nenne {a} Dinge, die mit dem Buchstaben „{letter}“ anfangen.',
        'answer'  => 'Beliebige {a} passende Wörter',
        'hint'    => 'Schau dich im Raum um, das gibt Ideen.',
        'lists'   => ['letter' => ['B', 'D', 'F', 'G', 'H', 'K', 'L', 'M', 'R', 'S', 'T', 'W']],
    ],
    'lit-opposite' => [
        'name'    => 'Gegenteile',
        'pattern' => 'Was ist das Gegenteil von „{word}“?',
        'answer'  => 'Das richtige Gegenteil',
        'hint'    => 'Denk an das Wort, das genau andersherum bedeutet.',
        'lists'   => ['word' => ['heiß', 'groß', 'schnell', 'fröhlich', 'Tag', 'oben',
                                 'laut', 'nass', 'voll', 'früh', 'hart', 'nah']],
    ],
    'lit-define' => [
        'name'    => 'Mit eigenen Worten',
        'pattern' => 'Was bedeutet das Wort „{word}“? Erkläre es mit deinen eigenen Worten.',
        'answer'  => 'Jede sinnvolle Erklärung zählt',
        'hint'    => 'Bau es erst einmal in einen Satz ein.',
        'lists'   => ['word' => ['Mut', 'neugierig', 'sanft', 'uralt', 'zerbrechlich',
                                 'großzügig', 'stur', 'dankbar', 'ängstlich', 'treu']],
    ],
    'lit-sentence' => [
        'name'    => 'Bau einen Satz',
        'pattern' => 'Denk dir einen Satz aus, in dem „{word}“ und „{other}“ beide vorkommen.',
        'answer'  => 'Jeder Satz mit beiden Wörtern',
        'hint'    => 'Er darf so albern sein, wie du willst.',
        'lists'   => ['word'  => ['Fluss', 'Burg', 'Sturm', 'Laterne', 'Hafen', 'Wiese'],
                      'other' => ['still', 'golden', 'plötzlich', 'riesig', 'gefroren', 'krumm']],
    ],
    'lit-story' => [
        'name'    => 'Erzähl weiter',
        'pattern' => 'Erzähl, wie die Geschichte weitergeht: „{opening}“',
        'answer'  => 'Jede Fortsetzung, die dazu passt',
        'hint'    => 'Zwei oder drei Sätze reichen völlig.',
        'lists'   => ['opening' => [
            'Die Tür oben an der Treppe war noch nie offen gewesen ...',
            'Auf der Karte war eine Insel, die auf keiner anderen Karte stand ...',
            'Das ganze Dorf wachte auf und sprach rückwärts ...',
            'Der alte Leuchtturm ging nach zwanzig Jahren von selbst wieder an ...',
            'Ein Brief kam für jemanden, der dort gar nicht wohnte ...',
        ]],
    ],

    // --------------------------------------------------------------- ENGLISH
    // In German this is German grammar: its own plurals and past tenses.
    'eng-plural' => [
        'name'    => 'Eins und viele',
        'pattern' => 'Wie heißt die Mehrzahl von „{word}“?',
        'answer'  => 'Die richtige Mehrzahl',
        'hint'    => 'Manche Wörter ändern sich mehr, als man denkt.',
        'lists'   => ['word' => ['Kind', 'Maus', 'Blatt', 'Fuß', 'Buch',
                                 'Messer', 'Gans', 'Haus', 'Zahn', 'Baby']],
    ],
    'eng-verb' => [
        'name'    => 'Gestern',
        'pattern' => 'Setz den Satz in die Vergangenheit: „Jeden Tag {verb} ich.“',
        'answer'  => 'Die richtige Vergangenheitsform von „{verb}“',
        'hint'    => 'Sag es so, als wäre es schon passiert.',
        'lists'   => ['verb' => ['laufe', 'schwimme', 'esse', 'schreibe', 'singe',
                                 'denke', 'bringe', 'fange', 'male', 'fliege']],
    ],
    'eng-describe' => [
        'name'    => 'Beschreib es',
        'pattern' => 'Beschreibe {thing} jemandem, der so etwas noch nie gesehen hat - ohne das Wort zu benutzen.',
        'answer'  => 'Jede klare Beschreibung',
        'hint'    => 'Fang damit an, wozu man es braucht.',
        'lists'   => ['thing' => ['ein Fahrrad', 'einen Regenschirm', 'einen Wasserkocher', 'eine Treppe',
                                  'ein Teleskop', 'eine Sandburg', 'eine Windmühle', 'einen Drachen']],
    ],

    // --------------------------------------------------------------- SCIENCE
    'sci-sense' => [
        'name'    => 'Welcher Sinn?',
        'pattern' => 'Welchen Körperteil brauchst du, um {sense}?',
        'answer'  => 'Das passende Sinnesorgan',
        'hint'    => 'Zeig darauf.',
        'lists'   => ['sense' => ['an einer Blume zu riechen', 'eine Glocke zu hören',
                                  'Honig zu schmecken', 'einen Regenbogen zu sehen',
                                  'warmen Sand zu spüren']],
    ],
    'sci-float' => [
        'name'    => 'Schwimmt es oder geht es unter?',
        'pattern' => 'Würde {thing} im Wasser schwimmen oder untergehen? Sag, warum du das denkst.',
        'answer'  => 'Beides gilt, wenn eine Begründung dabei ist',
        'hint'    => 'Denk daran, wie schwer es für seine Größe ist.',
        'lists'   => ['thing' => ['ein Korken', 'ein Stein', 'ein Apfel', 'eine Münze', 'eine Feder',
                                  'ein Schwamm', 'ein Nagel', 'eine Kerze', 'eine Orange']],
    ],
    'sci-change' => [
        'name'    => 'Was passiert dann?',
        'pattern' => 'Was passiert, wenn {event}? Erkläre es so gut du kannst.',
        'answer'  => 'Eine sinnvolle Erklärung',
        'hint'    => 'Sag, was du sehen würdest, und dann warum.',
        'lists'   => ['event' => [
            'du einen Eiswürfel in einem warmen Zimmer liegen lässt',
            'eine Pflanze in einem dunklen Schrank steht',
            'du deine Hände schnell aneinander reibst',
            'man Salz in ein Glas Wasser rührt',
            'ein Luftballon in der Sonne liegt',
        ]],
    ],

    // ---------------------------------------------------------------- NATURE
    'nature-animal' => [
        'name'    => 'Tierstimmen',
        'pattern' => 'Welches Geräusch macht {animal}? Mach es so gut nach, wie du kannst!',
        'answer'  => 'Jeder gute Versuch zählt',
        'hint'    => 'Hier gibt niemand Noten.',
        'lists'   => ['animal' => ['eine Kuh', 'eine Ente', 'ein Löwe', 'ein Schaf', 'eine Eule',
                                   'ein Frosch', 'ein Pferd', 'eine Biene', 'eine Katze',
                                   'ein Wolf', 'eine Ziege', 'eine Krähe']],
    ],
    'nature-home' => [
        'name'    => 'Wo wohnt es?',
        'pattern' => 'Wo wohnt {animal}?',
        'answer'  => 'Die richtige Art von Zuhause',
        'hint'    => 'Denk daran, was es braucht, um sicher zu sein.',
        'lists'   => ['animal' => ['eine Biene', 'ein Kaninchen', 'ein Pinguin', 'ein Kamel', 'ein Fisch',
                                   'eine Eule', 'ein Maulwurf', 'eine Krabbe', 'eine Fledermaus',
                                   'ein Eichhörnchen']],
    ],
    'nature-season' => [
        'name'    => 'Die Jahreszeiten',
        'pattern' => 'Nenne zwei Dinge, die {season} passieren.',
        'answer'  => 'Zwei sinnvolle Antworten',
        'hint'    => 'Denk an das Wetter und an die Pflanzen.',
        'lists'   => ['season' => ['im Frühling', 'im Sommer', 'im Herbst', 'im Winter']],
    ],
    'nature-chain' => [
        'name'    => 'Wer frisst was',
        'pattern' => 'Was frisst {animal} wohl, und wer frisst es vielleicht?',
        'answer'  => 'Eine sinnvolle Nahrungskette',
        'hint'    => 'Jedes Tier ist das Abendessen von jemandem.',
        'lists'   => ['animal' => ['eine Maus', 'ein Frosch', 'ein Kaninchen', 'ein Fisch',
                                   'eine Raupe', 'ein Spatz']],
    ],

    // ----------------------------------------------------------------- LOGIC
    'logic-odd' => [
        'name'    => 'Was passt nicht?',
        'pattern' => 'Was passt hier nicht dazu: {set}? Sag warum.',
        'answer'  => 'Jede Antwort mit guter Begründung',
        'hint'    => 'Es kann mehr als eine richtige Antwort geben.',
        'lists'   => ['set' => [
            'Apfel, Banane, Karotte, Birne',
            'Hund, Katze, Fisch, Kaninchen',
            'rot, blau, Quadrat, grün',
            'Schuh, Socke, Hut, Löffel',
            'Auto, Boot, Fahrrad, Baum',
        ]],
    ],
    'logic-seq' => [
        'name'    => 'Wie geht es weiter?',
        'pattern' => 'Welche Zahl kommt als Nächstes: {a}, {b}, {c}, ... ?',
        'answer'  => 'Die Zahl, die die Reihe fortsetzt',
        'hint'    => 'Finde heraus, wie groß der Schritt dazwischen ist.',
    ],
    'logic-riddle' => [
        'name'    => 'Rätsel',
        'pattern' => '{riddle}',
        'answer'  => 'Die Lösung des Rätsels',
        'hint'    => 'Lies es zweimal - der Trick steckt in den Worten.',
        'lists'   => ['riddle' => [
            'Ich habe Zeiger, aber keine Hände. Was bin ich?',
            'Je mehr ich trockne, desto nasser werde ich. Was bin ich?',
            'Ich habe Tasten, aber öffne kein Schloss. Was bin ich?',
            'Je mehr du von mir wegnimmst, desto größer werde ich. Was bin ich?',
            'Ich gehe hinauf und hinunter und bleibe doch am selben Platz. Was bin ich?',
        ]],
    ],

    // ------------------------------------------------------------ LIFE SKILLS
    'life-kind' => [
        'name'    => 'Etwas Nettes',
        'pattern' => 'Nenne eine nette Sache, die du heute für {who} tun könntest.',
        'answer'  => 'Jede nette Idee zählt',
        'hint'    => 'Auch kleine Sachen zählen.',
        'lists'   => ['who' => ['einen Freund', 'jemanden aus deiner Familie', 'einen Nachbarn',
                                'ein neues Kind in der Klasse', 'jemanden, der traurig ist']],
    ],
    'life-safe' => [
        'name'    => 'Sicher bleiben',
        'pattern' => 'Was solltest du tun, wenn {situation}?',
        'answer'  => 'Jede vernünftige, sichere Antwort',
        'hint'    => 'Eine vertraute erwachsene Person zu suchen, ist meist der erste Schritt.',
        'lists'   => ['situation' => [
            'du im Geschäft deine erwachsene Begleitung verlierst',
            'eine fremde Person dich bittet mitzukommen',
            'es zu Hause verbrannt riecht',
            'du siehst, dass jemandem wehgetan wird',
            'dir in der Schule schlecht wird',
        ]],
    ],
    'life-choice' => [
        'name'    => 'Was würdest du tun?',
        'pattern' => 'Was würdest du tun, wenn {situation}?',
        'answer'  => 'Es gibt keine einzig richtige Antwort - sei einfach freundlich',
        'hint'    => 'Sag, was du tun würdest, und warum.',
        'lists'   => ['situation' => [
            'ein Freund die Schuld für etwas auf sich nimmt, das du getan hast',
            'du deine Hausaufgaben vergessen hast',
            'du etwas findest, das jemand anderem gehört',
            'du denselben Nachmittag zwei Leuten versprochen hast',
            'jemand deine Arbeit abschreibt und dafür gelobt wird',
            'du etwas kaputt machst und niemand es sieht',
        ]],
    ],

    // ------------------------------------------------------------- GEOGRAPHY
    'geo-where' => [
        'name'    => 'Nah und fern',
        'pattern' => 'Würdest du {thing} in der Nähe deines Zuhauses finden oder weit weg?',
        'answer'  => 'Beides geht, mit einer Begründung',
        'hint'    => 'Denk daran, was es rund um deinen Wohnort gibt.',
        'lists'   => ['thing' => ['einen Berg', 'einen Strand', 'einen Fluss', 'eine Wüste',
                                  'einen Wald', 'einen Hafen', 'einen Vulkan']],
    ],
    'geo-direction' => [
        'name'    => 'In welche Richtung?',
        'pattern' => 'Du schaust nach {direction} und drehst dich nach rechts. Wohin schaust du jetzt?',
        'answer'  => 'Die Richtung rechts von {direction}',
        'hint'    => 'Stell dir einen Kompass vor, oder nimm die Hände zu Hilfe.',
        'lists'   => ['direction' => ['Norden', 'Süden', 'Osten', 'Westen']],
    ],
    'geo-place' => [
        'name'    => 'Orte auf der Erde',
        'pattern' => 'Nenne ein Land, in dem du {feature} erwarten würdest, und sag warum.',
        'answer'  => 'Jedes passende Land, mit einer Begründung',
        'hint'    => 'Denk daran, wie warm oder kalt es dort ist.',
        'lists'   => ['feature' => ['einen Regenwald', 'eine Wüste', 'das ganze Jahr Schnee',
                                    'aktive Vulkane', 'Korallenriffe', 'sehr lange Flüsse']],
    ],
];
