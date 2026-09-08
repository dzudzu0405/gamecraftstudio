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
        'prepare_cards' => '{total} Missionskarten, aufgeteilt auf {piles} Stapel ({each} pro Feld)',
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

    'move_card' => [
        'forward' => ['one' => 'Rücke {n} Feld vor', 'other' => 'Rücke {n} Felder vor'],
        'back'    => ['one' => 'Falsche Antwort: {n} Feld zurück',
                      'other' => 'Falsche Antwort: {n} Felder zurück'],
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

    'tokens' => [
        'note' => 'Jeder Spieler bekommt zwei Figuren: eine zum Spielen und eine als Ersatz. Klebe sie '
                . 'auf festen Karton und schneide rund um den Kreis aus, damit sie auf dem Plan stehen.',
    ],

    'answers' => [
        'warn'  => '<b>Für die Person, die das Spiel leitet.</b> Nimm diese letzten Blätter hinten vom '
                 . 'Stapel weg und behalte sie. Auf den Missionskarten selbst stehen die Antworten nicht.',
        'space' => 'Feld {n}',
    ],
    // Die Geschichtenseite. Die {Platzhalter} werden aus dem Spiel gefüllt.
    // Der Schauplatz steht hinter einem Doppelpunkt: welche Präposition davor
    // gehörte - im Wald, auf der Insel - hängt vom Ort ab, und den schreibt
    // die Käuferin selbst.
    'story' => [
        'hero_default' => 'unser junger Held',
        'place' => 'Schauplatz der ganzen Geschichte: {place} - ein Ort, der bis zu diesem Morgen '
                 . 'noch niemandem einen Grund zur Sorge gegeben hatte.',
        'p2'    => 'Die Nachricht spricht sich schnell herum und erreicht {hero} zuerst. Irgendwo '
                 . 'da draußen wartet {rescue} und weiß nicht, ob Hilfe kommt. Keiner der Erwachsenen '
                 . 'will los. Also packt {hero} einen Rucksack, sagt niemandem etwas und geht, solange '
                 . 'es noch hell ist.',
        'p3'    => 'Der Weg teilt sich in {cells} Abschnitte, und keiner lässt dich umsonst vorbei. '
                 . '{trouble} An jedem Abschnitt wartet eine Frage, und nur wer gut antwortet, kommt '
                 . 'weiter. Bei einem Fehler nimmt dir der Weg einen Schritt zurück - aber er schließt '
                 . 'sich nie ganz.',
        'p4'    => 'Kommst du ans Ziel, kehrt {rescue} nach Hause zurück, und die Geschichte davon '
                 . 'gehört von da an {hero}. Diese Geschichte heißt „{title}“.',

        'opening' => [
            'forest' => 'Im alten Wald ist es still geworden. Sogar die goldenen Blätter fallen nicht mehr, sie stehen mitten in der Luft.',
            'dino'   => 'Ein lautes Brüllen steigt aus dem Tal herauf. Irgendwo dort unten hat sich ein Dinosaurierbaby verlaufen.',
            'space'  => 'Die Raumstation schickt einen Notruf: Ein kleiner Planet verliert gleich für immer sein Licht.',
            'ocean'  => 'Das bunte Korallenriff wird grau, und die kleinen Fische rufen um Hilfe.',
            'pirate' => 'Eine alte Karte wird in einer Flasche an den Strand gespült und verspricht einen Schatz, den die Welt vergessen hat.',
            'magic'  => 'Die Zauberflamme im alten Turm ist ausgegangen, und das ganze Königreich versinkt im Nebel.',
            'castle' => 'Die goldene Glocke der Burg wurde in der Nacht vor dem großen Fest gestohlen.',
            'desert' => 'Die einzige Oase in der Wüste trocknet mit jedem Tag ein Stück weiter aus.',
            'arctic' => 'Die Eisscholle, auf der die Pinguine wohnen, schmilzt viel zu schnell.',
            'candy'  => 'Der Schokoladenfluss im Bonbonland ist über Nacht zugefroren.',
            'robot'  => 'In der Roboterfabrik ist der Strom ausgefallen, und jede Maschine steht mitten in der Bewegung still.',
            'farm'   => 'In einer sehr windigen Nacht sind alle Tiere vom Bauernhof verschwunden.',
        ],

        'rescue' => [
            'forest' => 'das kleinste Fuchsjunge im Wald',
            'dino'   => 'ein Dinosaurierbaby, das seine Herde verloren hat',
            'space'  => 'der letzte Wächter eines erlöschenden Sterns',
            'ocean'  => 'eine junge Schildkröte, die sich weit weg von zu Hause verheddert hat',
            'pirate' => 'ein Schiffskamerad, der auf einer namenlosen Insel zurückblieb',
            'magic'  => 'die junge Zauberschülerin, die die große Flamme am Brennen hielt',
            'castle' => 'der Glöckner, der im höchsten Turm eingeschlossen ist',
            'desert' => 'eine Karawane von Reisenden, die zwischen den Dünen verloren ging',
            'arctic' => 'ein Pinguinküken, das auf einer brechenden Scholle treibt',
            'candy'  => 'die Zuckerbäckerin, die in ihrer eigenen Küche festgefroren ist',
            'robot'  => 'der kleine Reparaturroboter, der die Stadt am Laufen hielt',
            'farm'   => 'jedes Tier, das in der Nacht verschwunden ist',
        ],

        'trouble' => [
            'forest' => 'Die Pfade legen sich immer wieder neu, und die Bäume zeigen den Weg nicht mehr an.',
            'dino'   => 'Der Boden bebt ohne Vorwarnung, und die sicheren Übergänge wechseln mit jedem Beben.',
            'space'  => 'Der Treibstoff wird knapp, die Karten sind veraltet, und kein Stern steht dort, wo er sollte.',
            'ocean'  => 'Die Strömungen laufen verkehrt herum, und das Wasser wird mit jedem Zug dunkler.',
            'pirate' => 'Die Karte ist an einigen Stellen zerrissen, und eine andere Mannschaft liest dieselben Hinweise.',
            'magic'  => 'Der Nebel schluckt jeden Zauber, und Magie, auf die immer Verlass war, geht daneben.',
            'castle' => 'Die Tore antworten nur auf Rätsel, und die Wachen haben alle Lösungen vergessen.',
            'desert' => 'Der Wind begräbt jedes Zeichen wenige Minuten, nachdem man es gefunden hat.',
            'arctic' => 'Das Eis knirscht unter den Füßen, und das Tageslicht geht schon zur Neige.',
            'candy'  => 'Alles Süße ist brüchig geworden, und die Brücken brechen, wenn man zu langsam geht.',
            'robot'  => 'Die Hälfte der Maschinen folgt noch alten Befehlen und weiß nicht, dass die Stadt kaputt ist.',
            'farm'   => 'Alle Tore blieben offen, und die Spuren führen in alle Richtungen gleichzeitig.',
        ],
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
        'star'         => 'Landest du auf einem Feld mit Stern, ziehst du eine Missionskarte von diesem '
                        . 'Feld. Auf jedem anderen Feld endet dein Zug einfach.',
        'answer'       => 'Beantworte die Frage. Ist sie richtig, bleibst du stehen, wo du bist.',
        'wrong_cards'  => 'Ist sie falsch, gehst du so viele Felder zurück, wie auf deiner gezogenen '
                        . 'Zugkarte steht.',
        'wrong_dice'   => 'Ist sie falsch, gehst du ein Feld zurück.',
        'return_cards' => 'Lege die Missionskarte unter ihren Stapel und die Zugkarte unter den Zugstapel.',
        'return_dice'  => 'Lege die Missionskarte unter ihren Stapel.',
        'win'          => 'Wer zuerst das ZIEL-Feld erreicht, gewinnt die Siegerkarte.',
    ],
];
