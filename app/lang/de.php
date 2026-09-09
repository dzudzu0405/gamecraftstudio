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
    ],

    'answers' => [
        'warn'  => '<b>Für die Person, die das Spiel leitet.</b> Nimm diese letzten Blätter hinten vom '
                 . 'Stapel weg und behalte sie. Auf den Missionskarten selbst stehen die Antworten nicht.',
        'in_order' => 'In der Reihenfolge, in der die Karten gedruckt sind',
    ],
    // Die Geschichtenseite. Die {Platzhalter} werden aus dem Spiel gefüllt.
    // Der Schauplatz steht hinter einem Doppelpunkt: welche Präposition davor
    // gehörte - im Wald, auf der Insel - hängt vom Ort ab, und den schreibt
    // die Käuferin selbst.
    'story' => [
        'hero_default' => 'unser junger Held',
        // Die Rettung steht immer am Satzende hinter einem Doppelpunkt: sie
        // trägt oft einen Relativsatz, und mitten im Satz fehlt dahinter das
        // schließende Komma. Am Ende passt jede Form.
        'place' => [
            'Schauplatz der ganzen Geschichte: {place} - ein Ort, der bis zu diesem Morgen '
                . 'noch niemandem einen Grund zur Sorge gegeben hatte.',
            'Das Ganze spielt hier: {place} - und bis heute kam alles dort bestens zurecht.',
            'Der Schauplatz: {place} - ein Ort, an dem noch nie jemand gerettet werden musste.',
        ],

        'p2' => [
            'Die Nachricht spricht sich schnell herum und erreicht {hero} zuerst. Irgendwo '
                . 'da draußen wartet jemand darauf, dass endlich Hilfe kommt: {rescue}. Keiner der '
                . 'Erwachsenen will los. Also packt {hero} einen Rucksack, sagt niemandem etwas und '
                . 'geht, solange es noch hell ist.',
            'Die Nachricht macht die Runde, wie Nachrichten das tun, und {hero} hört sie als '
                . 'Erstes. Da draußen wartet jemand und weiß überhaupt nicht, ob jemand kommt: '
                . '{rescue}. Die Erwachsenen reden darüber und finden, es sei viel zu weit. Also '
                . 'füllt {hero} einen Rucksack, sagt niemandem Bescheid und geht los, solange es '
                . 'noch hell genug zum Laufen ist.',
            'Am Vormittag haben es schon alle gehört, und {hero} hat es zweimal gehört. Hinter '
                . 'den letzten Häusern verlässt sich jemand auf einen, der noch gar nicht '
                . 'aufgebrochen ist: {rescue}. Kein Erwachsener meldet sich, also packt {hero} '
                . 'stattdessen einen Rucksack, geht ohne ein Wort und nimmt den Weg, solange der '
                . 'Tag noch hält.',
        ],

        'setout' => [
            'Zu so einer Reise bricht niemand mit leeren Taschen auf, also nimmt {hero} Brot mit, '
                . 'eine Decke, ein Stück Schnur, das sich als das Nützlichste von allem '
                . 'herausstellen wird, und deutlich weniger Mut, als die Sache eigentlich verlangt.',
            'Einen solchen Weg geht man nicht mit leeren Händen, also nimmt {hero} Brot mit, eine '
                . 'Decke, eine Kerze und ein Stück Schnur, dessen Nutzen damals niemand hätte '
                . 'erraten können.',
            'Im Rucksack liegt, was {hero} in der Eile finden konnte: Brot, eine Decke, eine halbe '
                . 'Karte und ein kleiner Stein, der seit einem Sommer als Glücksbringer aufgehoben '
                . 'wird, an den sich sonst niemand erinnert.',
        ],

        'p3' => [
            'Der Weg teilt sich in {cells} Abschnitte, und keiner lässt dich umsonst vorbei. '
                . '{trouble} An jedem Abschnitt wartet eine Frage, und nur wer gut antwortet, kommt '
                . 'weiter.',
            'Zwischen hier und dort liegen {cells} Abschnitte, und jeder verlangt etwas. {trouble} '
                . 'An jedem wartet eine Frage, und eine gute Antwort ist der einzige Zoll, den der '
                . 'Weg annimmt.',
            'Der Weg zerfällt in {cells} Abschnitte, und großzügig ist keiner davon. {trouble} Vor '
                . 'jedem steht eine Frage quer, und der einzige Schlüssel, der passt, ist eine '
                . 'richtige Antwort.',
        ],

        'wrong' => [
            'Es wird Augenblicke geben, in denen die Antwort einfach nicht kommt. Das ist '
                . 'erlaubt. Der Weg nimmt dir einen Schritt zurück, wartet, bis du noch einmal '
                . 'nachgedacht hast, und lässt dich dann weitergehen. Niemand wird nach Hause '
                . 'geschickt, weil er etwas falsch beantwortet hat - man verliert eine solche '
                . 'Reise nur, wenn man aufhört zu gehen.',
            'Manche Fragen gehen beim ersten Versuch nicht auf. Das ist nicht schlimm, und es ist '
                . 'sogar vorgesehen. Der Weg gibt dir einen Schritt zurück, lässt dich die Sache '
                . 'noch einmal wenden und geht dann weiter wie zuvor. Wegen einer falschen Antwort '
                . 'wurde noch nie jemand heimgeschickt: der Weg endet nur für den, der stehen '
                . 'bleibt.',
            'Es wird eine Frage geben, die einfach dasteht, und du kannst sie anstarren, so lange '
                . 'du willst - sie rührt sich nicht. Das geht allen so. Du verlierst einen Schritt, '
                . 'denkst noch einmal nach und gehst weiter. Ein Fehler hat noch nie eine Reise '
                . 'beendet, sondern nur, den Rucksack endgültig abzustellen.',
        ],

        'last' => [
            'Und dann ist auf einmal nur noch eine einzige Frage übrig - und dahinter '
                . 'wartet {rescue}.',
            'Und es steht nur noch eine Frage zwischen {hero} und dem Ziel - und dahinter '
                . 'wartet {rescue}.',
            'Eine Frage noch, sonst nichts, und dann steht {hero} vor dem, worauf alles '
                . 'hinauslief: {rescue}.',
        ],

        'p4' => [
            'Kommst du ans Ziel, kommt nach Hause, wer dort draußen gewartet hat: {rescue}. '
                . 'Die Geschichte davon gehört von da an {hero}, und sie heißt „{title}“.',
            'Gehst du den Weg zu Ende, darf endlich heimkehren, wer da draußen ausgeharrt hat: '
                . '{rescue}. Von diesem Tag an gehört die Geschichte {hero} und trägt den Namen '
                . '„{title}“.',
            'Schaffst du die letzte Frage, schläft heute Nacht wieder zu Hause, wer so lange '
                . 'gewartet hat: {rescue}. Erzählen darf das für immer {hero}, unter dem Namen '
                . '„{title}“.',
        ],

        'opening' => [
            'forest' => [
                'Im alten Wald ist es still geworden. Sogar die goldenen Blätter fallen nicht mehr, sie stehen mitten in der Luft.',
                'Alle Pfade im alten Wald sind auf einmal still geworden, und kein Vogel will sagen, warum.',
            ],
            'dino' => [
                'Ein lautes Brüllen steigt aus dem Tal herauf. Irgendwo dort unten hat sich ein Dinosaurierbaby verlaufen.',
                'Das ganze Tal brüllt seit dem Morgengrauen, und eine der Stimmen darin ist viel zu klein.',
            ],
            'space' => [
                'Die Raumstation schickt einen Notruf: Ein kleiner Planet verliert gleich für immer sein Licht.',
                'Vom Rand der Karte kommt ein Signal herein, schwach und immer wieder: Ein kleiner Planet wird dunkel.',
            ],
            'ocean' => [
                'Das bunte Korallenriff wird grau, und die kleinen Fische rufen um Hilfe.',
                'Dem Riff ist über Nacht etwas abhandengekommen: zuerst die Farbe und dann das Geräusch.',
            ],
            'pirate' => [
                'Eine alte Karte wird in einer Flasche an den Strand gespült und verspricht einen Schatz, den die Welt vergessen hat.',
                'Die Flut bringt eine Flasche mit einer halben Karte darin und einem Versprechen, das länger gehalten hat als das Schiff, aus dem es kam.',
            ],
            'magic' => [
                'Die Zauberflamme im alten Turm ist ausgegangen, und das ganze Königreich versinkt im Nebel.',
                'Der alte Turm ist zum ersten Mal seit dreihundert Jahren kalt geworden, und der Nebel kommt das Tal herunter, um nachzusehen.',
            ],
            'castle' => [
                'Die goldene Glocke der Burg wurde in der Nacht vor dem großen Fest gestohlen.',
                'Heute Morgen hat die große Glocke nicht geläutet, und bis Mittag wusste das ganze Königreich, dass sie fort ist.',
            ],
            'desert' => [
                'Die einzige Oase in der Wüste trocknet mit jedem Tag ein Stück weiter aus.',
                'Der Brunnen kam heute Morgen trüb herauf, dann flach, und seitdem wird die Oase immer kleiner.',
            ],
            'arctic' => [
                'Die Eisscholle, auf der die Pinguine wohnen, schmilzt viel zu schnell.',
                'Das Eis draußen in der Bucht hat angefangen, mit sich selbst zu reden, und die Scholle der Pinguine ist kleiner als gestern.',
            ],
            'candy' => [
                'Der Schokoladenfluss im Bonbonland ist über Nacht zugefroren.',
                'In der Nacht ist der Schokoladenfluss stehen geblieben, und heute Morgen kann man einfach hinüberlaufen.',
            ],
            'robot' => [
                'In der Roboterfabrik ist der Strom ausgefallen, und jede Maschine steht mitten in der Bewegung still.',
                'Alle Maschinen der Fabrik sind in derselben Sekunde stehen geblieben, mitten in dem, was sie gerade taten.',
            ],
            'farm' => [
                'In einer sehr windigen Nacht sind alle Tiere vom Bauernhof verschwunden.',
                'Bei Sonnenaufgang stand das Tor offen, der Hof war leer, und kein einziges Tier antwortete, als man es rief.',
            ],
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

        // Wer mitkommt. Hier steckt fast der ganze Spaß der Geschichte, also
        // ist jeder eine kleine Figur mit eigenem Kopf, keine Beschreibung.
        'companion' => [
            'forest' => [
                'Eine alte Eule kündigt an, dass sie nur bis zur zweiten Wegbiegung mitkommt. Am Ende geht sie den ganzen Weg mit und beschwert sich an jedem Abschnitt über das Wetter.',
                'Ein Igel mit einer sehr kleinen Laterne besteht darauf mitzukommen. Er geht so langsam, dass die ganze Gruppe die Namen von Dingen lernt, an denen sie sonst vorbeigelaufen wäre.',
            ],
            'dino' => [
                'Ein kleiner, außerordentlich lauter Flugsaurier ernennt sich selbst zum Aufpasser. Er hat noch nie etwas Nützliches entdeckt, aber er ist nie still, und das ist fast genauso gut.',
                'Eine junge Triceratops schließt sich ungefragt an. Klettern kann sie nicht und schwimmen auch nicht, aber einen umgestürzten Baum schiebt sie in weniger als einer Minute vom Weg.',
            ],
            'space' => [
                'Die Station schickt eine Reparaturdrohne mit einem funktionierenden Auge mit, die beim Fliegen summt. Sie kennt den Weg zu genau einem Planeten und ist ziemlich sicher, dass es der richtige ist.',
                'Ein Frachtroboter mit einem quietschenden Rad kommt mit und trägt das Gepäck. Er war schon zweimal am Rand der Karte und erwähnt das ungefähr alle zehn Minuten.',
            ],
            'ocean' => [
                'Eine alte, mürrische Krabbe kommt mit, aber nur unter einer Bedingung: Niemand redet davon, wie langsam sie schwimmt. Niemand redet davon. Sie hält viel besser mit, als alle gedacht haben.',
                'Ein sehr junger Tintenfisch hängt sich an und wechselt die Farbe, sobald ihn jemand ansieht - so weiß immer die ganze Gruppe genau, was er gerade denkt.',
            ],
            'pirate' => [
                'Der Schiffspapagei meldet sich als Erster, vor allem weil er die Karte auswendig kann und die Stelle nicht verpassen will, an der jemand sie laut vorliest.',
                'Der Schiffskoch kommt auch mit und bringt eine Pfanne, einen Löffel und zu jeder Entscheidung, die von jetzt an fällt, eine feste Meinung.',
            ],
            'magic' => [
                'Ein Kerzenstummel, der sich weigert auszugehen, schwebt hinterher. Er leuchtet immer genau das Falsche an, immer im falschen Moment, und ist mächtig stolz auf sich.',
                'Ein Frosch, der früher etwas Größeres war, bietet sich als Führer an. Er erinnert sich nur an die Hälfte eines Zaubers, aber es ist eine sehr nützliche Hälfte.',
            ],
            'castle' => [
                'Die Burgkatze kommt auch mit. Sie war in jedem Zimmer, unter jedem Boden und hinter jedem Vorhang, und sie erinnert sich an alle.',
                'Der Küchenjunge kommt mit und trägt die zweitbeste Laterne. Wie sich zeigt, kennt er jede Hintertreppe und jede Kellertür im Königreich.',
            ],
            'desert' => [
                'Eine junge Kamelstute mit sehr festen Ansichten übers Gehen schließt sich am Tor an. Sie bleibt stehen, wann sie will, und läuft weiter, wann sie will, und verlaufen hat sie sich noch nie.',
                'Ein Wüstenfuchs mit riesigen Ohren stößt in der zweiten Nacht dazu. Er hört Wasser drei Hügel früher als alle anderen und ist unerträglich zufrieden damit.',
            ],
            'arctic' => [
                'Ein kleiner runder Seehund folgt ab dem ersten Abschnitt. Er rutscht auf dem Bauch voraus, prüft das Eis und kommt jedes Mal zurück, um zu sagen, ob es hält.',
                'Ein Papageitaucher, der diese Küste seit neun Wintern abfliegt, kommt als Navigator mit und nimmt die Aufgabe ernster, als je jemand irgendetwas genommen hat.',
            ],
            'candy' => [
                'Eine Lebkuchenmaus geht als Führerin voran. Sie knabbert in jeden Wegweiser eine Kerbe, damit der Rückweg leicht zu finden ist - und frisst dabei etliche Wegweiser ganz auf.',
                'Ein Marzipanbär rollt hinten mit, frisst unterwegs ein Stück vom Weg, entschuldigt sich jedes Mal und macht es wieder.',
            ],
            'robot' => [
                'Ein verrosteter Kehrroboter rollt aus einer Seitentür und schließt sich einfach an. Seine Karte ist vierzig Jahre alt, aber er kennt eine Abkürzung, und die Abkürzung gibt es wirklich.',
                'Eine kleine Drohne mit gesprungener Linse schwebt nebenher. Sie filmt alles, was niemand braucht, und leuchtet die dunklen Ecken aus, was alle brauchen.',
            ],
            'farm' => [
                'Der Hofhund braucht überhaupt keine Einladung. Er wartet seit Sonnenaufgang am Tor, die Nase zur Straße gerichtet, und weiß ganz genau, wo es langgeht.',
                'Die älteste Gans vom Hof kommt auch mit. Sie ist elfmal aus diesem Hof ausgebüxt und kennt jede Lücke in jedem Zaun am Weg.',
            ],
        ],

        // Der letzte Abschnitt: die Welt antwortet, kurz vor dem Ende.
        'final' => [
            'forest' => 'Am letzten Abschnitt neigen sich die Bäume zum Zuhören herüber, und die goldenen Blätter, die heute Morgen stehen geblieben sind, fallen ganz langsam wieder.',
            'dino'   => 'Am letzten Abschnitt wird der Boden endlich ruhig, und hinter dem Hügelkamm ertönt ein kleines Brüllen - diesmal kein ängstliches, sondern ein hoffnungsvolles.',
            'space'  => 'Am letzten Abschnitt ist der kleine Planet nah genug zu sehen: ein schwaches Licht in all dem Dunkel, das wie eine Kerze kurz vor dem Ausgehen flackert.',
            'ocean'  => 'Am letzten Abschnitt kehrt eine dünne Linie Farbe in die Korallen zurück, und die kleinsten Fische schwimmen hindurch den Reisenden entgegen.',
            'pirate' => 'Am letzten Abschnitt hört die zerrissene Karte ganz auf, und der weitere Weg lässt sich nur noch an der Form der Küste ablesen.',
            'magic'  => 'Am letzten Abschnitt löst sich der Nebel vollständig auf, und der alte Turm steht da und wartet, dunkel und geduldig, mit einer kalten Lampe ganz oben.',
            'castle' => 'Am letzten Abschnitt wehen schon die Festfahnen, und das ganze Königreich steht auf dem Platz und wartet auf eine Glocke, die noch nicht geläutet hat.',
            'desert' => 'Am letzten Abschnitt legt sich der Wind, der Sand kommt zur Ruhe, und das Grün der Oase erscheint am Horizont genau dort, wo die Karte es versprochen hat.',
            'arctic' => 'Am letzten Abschnitt kommt das Tageslicht noch für eine Stunde zurück, und eine Stunde reicht gerade, um einen kleinen dunklen Fleck auf einer kleinen weißen Scholle zu erkennen.',
            'candy'  => 'Am letzten Abschnitt knackt der gefrorene Schokoladenfluss einmal ganz leise, so wie Eis knackt, wenn es beschlossen hat zu schmelzen.',
            'robot'  => 'Am letzten Abschnitt geht tief in der Fabrik ein Licht an, dann zwei, dann eine ganze Reihe, als würde das Gebäude aufwachen, um zuzusehen.',
            'farm'   => 'Am letzten Abschnitt taucht ein einzelner Hufabdruck im Schlamm auf, dann noch einer, und endlich führen sie irgendwohin statt überallhin zugleich.',
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
        'star'         => 'Landest du auf einem Feld mit Stern, nimmst du die oberste Karte vom '
                        . 'Missionsstapel. Auf jedem anderen Feld endet dein Zug einfach.',
        'answer'       => 'Beantworte die Frage. Ist sie richtig, bleibst du stehen, wo du bist.',
        'wrong_cards'  => 'Ist sie falsch, gehst du so viele Felder zurück, wie auf deiner gezogenen '
                        . 'Zugkarte steht.',
        'wrong_dice'   => 'Ist sie falsch, gehst du ein Feld zurück.',
        'return_cards' => 'Lege die Missionskarte unter den Missionsstapel und die Zugkarte unter den Zugstapel.',
        'return_dice'  => 'Lege die Missionskarte unter den Missionsstapel.',
        'win'          => 'Wer zuerst das ZIEL-Feld erreicht, gewinnt die Siegerkarte.',
    ],
];
