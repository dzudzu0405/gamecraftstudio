<?php
/**
 * German. Mirrors app/lang/en.php key for key.
 *
 * Addressed to the child, so du and the familiar imperative - schneide, ruecke
 * vor - which is what a German game for four to nine year olds uses.
 */
return [

    'bar' => [
        'pages'   => ['one' => '{n} Seite', 'other' => '{n} Seiten'],
        'spaces'  => ['one' => '{n} Feld', 'other' => '{n} Felder'],
        'back'    => 'Zurück zum Studio',
        'preview' => 'Vorschau',
        'print'   => 'Drucken / Als PDF speichern',
        'hint'    => 'Wähle im Druckfenster <b>Ziel: Als PDF speichern</b>, schalte '
                   . '<b>Hintergrundgrafiken</b> ein und stelle <b>Ränder: Keine</b> ein, damit Farben '
                   . 'und Schnittlinien richtig herauskommen.',
    ],

    'sheet' => [
        'page' => 'Seite {n}',

        'map'       => 'Spielplan',
        'map_sub'   => ['one' => '{n} Missionsfeld', 'other' => '{n} Missionsfelder'],

        'story'     => 'Die Geschichte',

        'howto'     => 'So wird gespielt',
        'players'   => ['one' => '{n} Spieler', 'other' => '{n} Spieler'],
        'players_range' => '{min}-{max} Spieler',

        'move'      => 'Zugkarten',
        'move_sub'  => ['one' => 'An der gestrichelten Linie ausschneiden - {n} Karte',
                        'other' => 'An den gestrichelten Linien ausschneiden - {n} Karten'],

        'dice'      => 'Papierwürfel',
        'dice_sub'  => 'Ausschneiden, an den Linien falten und die Laschen ankleben',

        'mission'      => 'Missionskarten',
        'mission_none' => 'Noch keine vorhanden',
        'sheet_of'     => 'Blatt {page} von {total}',
        'cards'        => ['one' => '{n} Karte', 'other' => '{n} Karten'],

        'hero'      => 'Siegerkarte',
        'hero_sub'  => 'Eine pro Spiel',

        'tokens'     => 'Spielfiguren',
        'tokens_sub' => 'Ausschneiden und auf Karton kleben',

        'answers'      => 'Lösungen',
        'answers_keep' => 'Dieses Blatt aufbewahren',
    ],

    'howto' => [
        'prepare'       => 'Das wird gebraucht:',
        'prepare_move'  => ['one' => '{n} Zugkarte', 'other' => '{n} Zugkarten'],
        'prepare_dice'  => 'der ausgeschnittene Würfel',
        'prepare_cards' => '{total} Missionskarten in einem gemischten Stapel',
        'prepare_hero'  => ['one' => '{n} Siegerkarte', 'other' => '{n} Siegerkarten'],
        'prepare_token' => 'und eine Spielfigur für jeden Spieler',
    ],

    'dice' => [
        'alt'     => 'Würfel zum Ausschneiden und Falten',
        'steps'   => [
            'Schneide die ganze Form außen aus, die Laschen gehören dazu.',
            'Falte an jeder inneren Linie, damit sich die sechs Seiten nach innen legen.',
            'Klebe jede Lasche unter die Nachbarseite und halte sie fest, bis sie trocken ist.',
            'Ein Würfel reicht für den ganzen Tisch: würfeln und so viele Felder vorrücken.',
        ],
        'missing' => 'Die Zeichnung des Würfels fehlt. Lege eine Datei namens <code>dice-net.png</code> '
                   . 'in <code>uploads/library/</code> und drucke erneut, oder spiele mit einem ganz '
                   . 'normalen sechsseitigen Würfel.',
    ],

    'mission' => [
        'empty' => 'Dieses Projekt hat noch keine Missionskarten. Geh zurück ins Studio und wähle '
                 . '"Match mission cards".',
    ],

    'hero_card' => [
        'champion' => 'Champion',
        'hero_default' => 'unser Held',
        'eyebrow'  => 'Held von',
        'line'     => 'Hat alle {n} Aufgaben geschafft und ist zuerst im Ziel angekommen.',
        'congrats' => 'Herzlichen Glückwunsch, {name}!',
        'winner'   => 'Dein Name',
        'date'     => 'Datum',
    ],

    'level' => [
        'beginner' => 'Anfänger',
        'standard' => 'Mittel',
        'advanced' => 'Fortgeschritten',
    ],

    'board' => [
        'start'  => 'START',
        'finish' => 'ZIEL',
    ],

    'tokens' => [
        'player' => 'Spieler {n}',
        'note' => 'Jeder Spieler bekommt zwei Figuren: eine zum Spielen und eine als Ersatz. Klebe sie '
                . 'auf festen Karton und schneide rund um den Kreis aus, damit sie auf dem Plan stehen.',
        'models' => 'Noch besser: Nimm eine kleine Spielfigur, um zu zeigen, wo du stehst. Alles, was auf ein Feld passt, geht - eine Figur, ein Knopf, eine Perle oder eine Münze.',
    ],

    'answers' => [
        'warn'  => '<b>Für die Person, die das Spiel leitet.</b> Nimm diese letzten Blätter hinten vom '
                 . 'Stapel weg und behalte sie. Auf den Missionskarten selbst stehen die Antworten nicht.',
        'in_order' => 'In der Reihenfolge, in der die Karten gedruckt sind',
    ],
    // Die zwanzig Abenteuer, als Ort für die Geschichte erzählt
    'settings' => [
        'Treasure Hunt'        => 'eine Pirateninsel mit versteckten Buchten, Palmen und einem vergrabenen Schatz',
        'Dinosaur Rescue'      => 'ein urzeitliches Tal aus Riesenfarnen und dampfenden Vulkanen',
        'Jungle Adventure'     => 'ein dichter Dschungel aus Lianen, Wasserfällen und überwucherten Ruinen',
        'Ocean Rescue'         => 'ein leuchtendes Korallenriff unter einem sonnigen Meer',
        'Space Mission'        => 'ein Sternenfeld im Weltall mit kleinen Planeten und einer Landestation',
        'Save the City'        => 'eine freundliche Stadt mit hohen Häusern, Parks und belebten Straßen',
        'Forest Guardian'      => 'ein alter Wald aus hohen Bäumen, bemoosten Steinen und stillen Lichtungen',
        'Museum Mystery'       => 'ein großes Museum mit langen Sälen, Vitrinen und Marmortreppen',
        'Safari Expedition'    => 'eine weite Savanne aus goldenem Gras, Akazien und Wasserstellen',
        'Lost Island'          => 'eine verlorene Insel, umgeben von Klippen, Dschungel und einer stillen Lagune',
        'Time Travel Quest'    => 'ein Ort, an dem die Zeiten sich treffen: eine Burg, eine Pyramide und eine Stadt der Zukunft',
        'Robot Workshop'       => 'eine helle Roboterwerkstatt voller Zahnräder, Förderbänder und blinkender Maschinen',
        'Candy Kingdom Quest'  => 'ein Bonbonkönigreich mit Lutscherbäumen, Schokoladenflüssen und Lebkuchenhäusern',
        'Arctic Expedition'    => 'eine gefrorene Arktis aus Eisschollen, verschneiten Hügeln und Nordlichtern',
        'Desert Pyramid Quest' => 'eine goldene Wüste aus Dünen, Palmenoasen und alten Pyramiden',
        'Circus Adventure'     => 'ein bunter Zirkus mit gestreiften Zelten, Fahnen und bemalten Wagen',
        'Farm Rescue'          => 'ein sonniger Bauernhof mit roten Scheunen, Strohballen und grünen Feldern',
        'Mountain Rescue'      => 'ein hoher Berg mit verschneiten Gipfeln, Tannen und Hängebrücken',
        'Storm Chasers'        => 'eine weite Ebene unter einem Himmel aus Gewitterwolken und Blitzen',
        'Fairy Tale Kingdom'   => 'ein Märchenkönigreich mit Burgtürmen, sanften Hügeln und gewundenen Wegen',
    ],

    'rules' => [
        'start'        => 'Jeder Spieler wählt eine Figur und stellt sie auf das START-Feld.',
        'move_cards'   => 'Ziehe in deinem Zug eine Zugkarte und rücke so viele Felder vor, wie darauf '
                        . 'steht. Lege die Karte vor dich hin.',
        'move_dice'    => 'Würfle in deinem Zug und rücke so viele Felder vor.',
        'star'         => 'Landest du auf einem Feld mit Stern, nimmst du die oberste Karte vom '
                        . 'Missionsstapel. Auf jedem anderen Feld endet dein Zug einfach.',
        'answer'       => 'Beantworte die Frage. Ist sie richtig, bleibst du stehen, wo du bist.',
        'wrong_cards'  => 'Ist sie falsch, gehst du so viele Felder zurück, wie auf deiner gezogenen '
                        . 'Zugkarte steht.',
        'wrong_dice'   => 'Ist sie falsch, gehst du ein Feld zurück.',
        'back_star'    => 'Rückwärts kostet dich nie eine Frage: Landest du so auf einem Stern, ziehst du keine Karte.',
        'return_cards' => 'Lege die Missionskarte unter den Missionsstapel und die Zugkarte unter den Zugstapel.',
        'return_dice'  => 'Lege die Missionskarte unter den Missionsstapel.',
        'win'          => 'Wer zuerst das ZIEL-Feld erreicht, gewinnt die Siegerkarte.',
    ],
];
