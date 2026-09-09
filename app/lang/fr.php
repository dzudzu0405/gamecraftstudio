<?php
/**
 * French. Mirrors app/lang/en.php key for key.
 *
 * Addressed to the child at the table, so the imperative is the familiar one -
 * decoupe, avance - as a French game for this age always is. The apostrophe is
 * the typographic one, which is what printed French uses.
 */
return [

    'bar' => [
        'pages'   => ['one' => '{n} page', 'other' => '{n} pages'],
        'spaces'  => ['one' => '{n} case', 'other' => '{n} cases'],
        'back'    => 'Retour au Studio',
        'preview' => 'Aperçu',
        'print'   => 'Imprimer / Enregistrer en PDF',
        'hint'    => 'Dans la fenêtre d’impression, choisis <b>Destination : Enregistrer au format PDF</b>, '
                   . 'active <b>Graphiques d’arrière-plan</b> et mets <b>Marges : aucune</b> pour que les '
                   . 'couleurs et les traits de découpe sortent correctement.',
    ],

    'sheet' => [
        'page' => 'Page {n}',

        'map'       => 'Plateau de jeu',
        'map_sub'   => ['one' => '{n} case mission', 'other' => '{n} cases mission'],

        'story'     => 'L’histoire',

        'howto'     => 'Comment jouer',
        'players'   => ['one' => '{n} joueur', 'other' => '{n} joueurs'],
        'players_range' => '{min}-{max} joueurs',

        'move'      => 'Cartes déplacement',
        'move_sub'  => ['one' => 'Découpe le long des pointillés - {n} carte',
                        'other' => 'Découpe le long des pointillés - {n} cartes'],

        'dice'      => 'Dé en papier',
        'dice_sub'  => 'Découpe, plie le long des traits et colle les languettes',

        'mission'      => 'Cartes mission',
        'mission_none' => 'Aucune pour l’instant',
        'sheet_of'     => 'Feuille {page} sur {total}',
        'cards'        => ['one' => '{n} carte', 'other' => '{n} cartes'],

        'hero'      => 'Carte du champion',
        'hero_sub'  => 'Une par partie',

        'tokens'     => 'Pions des joueurs',
        'tokens_sub' => 'Découpe et colle sur du carton',

        'answers'      => 'Corrigé',
        'answers_keep' => 'Garde cette feuille',
    ],

    'howto' => [
        'prepare'       => 'Ce qu’il faut préparer :',
        'prepare_move'  => ['one' => '{n} carte déplacement', 'other' => '{n} cartes déplacement'],
        'prepare_dice'  => 'le dé à découper',
        'prepare_cards' => '{total} cartes mission mélangées en une seule pioche',
        'prepare_hero'  => ['one' => '{n} carte du champion', 'other' => '{n} cartes du champion'],
        'prepare_token' => 'et un pion pour chaque joueur',
    ],

    'dice' => [
        'alt'     => 'Dé à découper et à plier',
        'steps'   => [
            'Découpe tout le contour de la forme, languettes comprises.',
            'Plie le long de chaque trait intérieur, pour que les six faces se referment vers l’intérieur.',
            'Colle chaque languette sous la face voisine et maintiens jusqu’à ce que ça sèche.',
            'Un seul dé suffit pour toute la table : lance-le et avance d’autant de cases.',
        ],
        'missing' => 'Le dessin du dé est manquant. Place un fichier nommé <code>dice-net.png</code> dans '
                   . '<code>uploads/library/</code> puis relance l’impression, ou joue avec n’importe quel '
                   . 'dé ordinaire à six faces.',
    ],

    'move_card' => [
        'forward' => ['one' => 'Avance de {n} case', 'other' => 'Avance de {n} cases'],
        'back'    => ['one' => 'Mauvaise réponse : recule de {n} case',
                      'other' => 'Mauvaise réponse : recule de {n} cases'],
    ],

    'mission' => [
        'empty' => 'Ce projet n’a pas encore de cartes mission. Retourne dans le Studio et choisis '
                 . '"Match mission cards".',
    ],

    'hero_card' => [
        'champion' => 'Champion',
        'hero_default' => 'notre héros',
        'eyebrow'  => 'Héros de',
        'line'     => 'A relevé les {n} défis et franchi la ligne d’arrivée en premier.',
        'congrats' => 'Bravo, {name} !',
        'winner'   => 'Ton nom',
        'date'     => 'Date',
    ],

    'level' => [
        'beginner' => 'Débutant',
        'standard' => 'Intermédiaire',
        'advanced' => 'Avancé',
    ],

    'board' => [
        'start'  => 'DÉPART',
        'finish' => 'ARRIVÉE',
    ],

    'tokens' => [
        'player' => 'Joueur {n}',
        'note' => 'Chaque joueur reçoit deux pions : un pour jouer et un de rechange. Colle-les sur du '
                . 'carton épais et découpe autour du cercle pour qu’ils tiennent debout sur le plateau.',
        'models' => 'Encore mieux : prends une petite figurine pour marquer où tu es. Tout ce qui tient sur une case fait l’affaire - une figurine, un bouton, une perle ou une pièce.',
    ],

    'answers' => [
        'warn'  => '<b>Pour la personne qui anime la partie.</b> Retire ces dernières feuilles du bas de '
                 . 'la pile et garde-les. Les cartes mission ne portent pas les réponses.',
        'in_order' => 'Dans l’ordre où les cartes sont imprimées',
    ],
    // Les vingt aventures, dites comme un lieu pour l’histoire
    'settings' => [
        'Treasure Hunt'        => 'une île de pirates avec des criques cachées, des palmiers et un trésor enterré',
        'Dinosaur Rescue'      => 'une vallée préhistorique de fougères géantes et de volcans fumants',
        'Jungle Adventure'     => 'une jungle épaisse de lianes, de cascades et de ruines envahies par les plantes',
        'Ocean Rescue'         => 'un récif de corail lumineux sous un océan plein de soleil',
        'Space Mission'        => 'un coin d’espace étoilé avec de petites planètes et une station d’atterrissage',
        'Save the City'        => 'une ville accueillante avec de grands immeubles, des parcs et des rues animées',
        'Forest Guardian'      => 'une vieille forêt de grands arbres, de pierres couvertes de mousse et de clairières calmes',
        'Museum Mystery'       => 'un grand musée aux longues salles, aux vitrines et aux escaliers de marbre',
        'Safari Expedition'    => 'une large savane d’herbe dorée, d’acacias et de points d’eau',
        'Lost Island'          => 'une île perdue entourée de falaises, de jungle et d’un lagon tranquille',
        'Time Travel Quest'    => 'un endroit où les époques se rencontrent : un château, une pyramide et une ville du futur',
        'Robot Workshop'       => 'un atelier de robots plein d’engrenages, de tapis roulants et de machines qui clignotent',
        'Candy Kingdom Quest'  => 'un royaume de bonbons avec des arbres à sucettes, des rivières de chocolat et des maisons en pain d’épices',
        'Arctic Expedition'    => 'un arctique gelé de plaques de glace, de collines enneigées et d’aurores boréales',
        'Desert Pyramid Quest' => 'un désert doré de dunes, d’oasis de palmiers et de pyramides anciennes',
        'Circus Adventure'     => 'un cirque coloré avec des chapiteaux rayés, des drapeaux et des roulottes peintes',
        'Farm Rescue'          => 'une ferme ensoleillée avec des granges rouges, des bottes de foin et des champs verts',
        'Mountain Rescue'      => 'une haute montagne de sommets enneigés, de sapins et de ponts de corde',
        'Storm Chasers'        => 'une grande plaine sous un ciel de nuages d’orage et d’éclairs',
        'Fairy Tale Kingdom'   => 'un royaume de conte avec des tours de château, des collines douces et des chemins qui serpentent',
    ],

    'rules' => [
        'start'        => 'Chaque joueur choisit un pion et le pose sur la case DÉPART.',
        'move_cards'   => 'À ton tour, tire une carte déplacement et avance du nombre de cases indiqué. '
                        . 'Garde la carte devant toi.',
        'move_dice'    => 'À ton tour, lance le dé et avance d’autant de cases.',
        'star'         => 'Si tu tombes sur une case avec une étoile, prends la carte du dessus de la '
                        . 'pioche mission. Sur n’importe quelle autre case, ton tour s’arrête là.',
        'answer'       => 'Réponds à la question. Si c’est juste, tu restes où tu es.',
        'wrong_cards'  => 'Si c’est faux, recule du nombre de cases inscrit sur la carte déplacement que tu as tirée.',
        'wrong_dice'   => 'Si c’est faux, recule d’une case.',
        'back_star'    => 'Reculer ne coûte jamais une question : si tu arrives ainsi sur une étoile, tu ne tires pas de carte.',
        'return_cards' => 'Remets la carte mission sous la pioche mission, et la carte déplacement sous la sienne.',
        'return_dice'  => 'Remets la carte mission sous la pioche mission.',
        'win'          => 'Le premier joueur qui atteint la case ARRIVÉE gagne la carte du champion.',
    ],
];
