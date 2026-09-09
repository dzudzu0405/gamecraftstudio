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
    ],

    'answers' => [
        'warn'  => '<b>Pour la personne qui anime la partie.</b> Retire ces dernières feuilles du bas de '
                 . 'la pile et garde-les. Les cartes mission ne portent pas les réponses.',
        'in_order' => 'Dans l’ordre où les cartes sont imprimées',
    ],
    // La page de l’histoire. Les {marqueurs} sont remplis avec les données du jeu.
    'story' => [
        'hero_default' => 'notre jeune héros',
        'place' => 'Le décor de toute l’histoire : {place} - un endroit qui, jusqu’à ce matin, n’avait jamais donné '
                 . 'à personne une raison de s’inquiéter.',
        'p2'    => 'La nouvelle circule vite et arrive à {hero} avant tout le monde. Quelque part '
                 . 'là-bas se trouve {rescue}, qui attend, sans savoir si quelqu’un viendra. Aucun adulte '
                 . 'ne veut y aller. Alors {hero} prépare un sac, ne dit rien à personne et part pendant '
                 . 'qu’il fait encore jour.',
        'setout' => 'Personne ne part pour un voyage pareil les poches vides, alors {hero} emporte du pain, '
                  . 'une couverture, un bout de ficelle qui se révélera la chose la plus utile de '
                  . 'toutes, et bien moins de courage que la situation n’en demande.',
        'wrong'  => 'Il y aura des moments où la réponse ne viendra pas. C’est permis. La route te '
                  . 'fait reculer d’un pas, attend le temps que tu réfléchisses encore, puis te '
                  . 'laisse repartir. Personne n’a jamais été renvoyé chez lui pour s’être trompé : '
                  . 'la seule façon de perdre un voyage comme celui-ci, c’est d’arrêter de le faire.',
        'last'   => 'Et puis, d’un coup, il ne reste plus rien entre {hero} et {rescue} qu’une '
                  . 'dernière question.',
        'p3'    => 'La route se partage en {cells} étapes, et aucune ne laisse passer gratuitement. '
                 . '{trouble} À chaque étape une question attend, et bien y répondre est le seul moyen '
                 . 'd’avancer.',
        'p4'    => 'Arrive au bout et {rescue} rentre à la maison, et l’histoire de ce jour-là appartient '
                 . 'à {hero} pour toujours. Cette histoire s’appelle « {title} ».',

        'opening' => [
            'forest' => 'La vieille forêt s’est tue. Même les feuilles dorées ont cessé de tomber, arrêtées en plein vol.',
            'dino'   => 'Un énorme rugissement monte de la vallée. Quelque part en bas, un bébé dinosaure s’est perdu.',
            'space'  => 'La station spatiale lance un appel de détresse : une petite planète va perdre sa lumière pour toujours.',
            'ocean'  => 'Le récif de corail devient tout gris et les petits poissons appellent à l’aide.',
            'pirate' => 'Une carte usée arrive sur la plage dans une bouteille et promet un trésor que le monde a oublié.',
            'magic'  => 'La flamme magique de la vieille tour s’est éteinte et tout le royaume s’enfonce dans la brume.',
            'castle' => 'La cloche dorée du château a été volée la veille de la grande fête.',
            'desert' => 'Le seul oasis du désert sèche un peu plus chaque jour.',
            'arctic' => 'La plaque de glace où vivent les manchots fond beaucoup trop vite.',
            'candy'  => 'La rivière de chocolat du Pays des Bonbons a gelé pendant la nuit.',
            'robot'  => 'L’usine de robots n’a plus de courant et toutes les machines se sont arrêtées en plein geste.',
            'farm'   => 'Tous les animaux de la ferme ont disparu pendant une nuit de grand vent.',
        ],

        'rescue' => [
            'forest' => 'le plus petit renardeau du bois',
            'dino'   => 'un bébé dinosaure séparé de son troupeau',
            'space'  => 'le dernier gardien d’une étoile qui s’éteint',
            'ocean'  => 'une jeune tortue prise au piège loin de chez elle',
            'pirate' => 'un marin abandonné sur une île sans nom',
            'magic'  => 'l’apprentie qui gardait la grande flamme allumée',
            'castle' => 'le sonneur de cloches enfermé dans la plus haute tour',
            'desert' => 'une caravane de voyageurs perdue entre les dunes',
            'arctic' => 'un bébé manchot à la dérive sur un glaçon qui se casse',
            'candy'  => 'la pâtissière de sucre glacée dans sa propre cuisine',
            'robot'  => 'le petit robot réparateur qui faisait tourner la ville',
            'farm'   => 'tous les animaux disparus dans la nuit',
        ],

        'trouble' => [
            'forest' => 'Les sentiers changent de place tout seuls et les arbres n’indiquent plus la direction.',
            'dino'   => 'Le sol tremble sans prévenir et les passages sûrs changent à chaque secousse.',
            'space'  => 'Il reste peu de carburant, les cartes sont vieilles et aucune étoile n’est à sa place.',
            'ocean'  => 'Les courants vont à l’envers et l’eau devient plus sombre à chaque brasse.',
            'pirate' => 'La carte est déchirée par endroits et un autre équipage suit les mêmes indices.',
            'magic'  => 'La brume avale chaque sort et la magie d’habitude fiable rate tout.',
            'castle' => 'Les portes ne répondent qu’aux devinettes et les gardes ont oublié toutes les réponses.',
            'desert' => 'Le vent enterre chaque repère quelques minutes après qu’on l’a trouvé.',
            'arctic' => 'La glace craque sous les pieds et le jour commence déjà à tomber.',
            'candy'  => 'Tout ce qui est sucré est devenu cassant et les ponts se brisent si on traverse trop lentement.',
            'robot'  => 'La moitié des machines suit encore d’anciennes consignes et ignore que la ville est en panne.',
            'farm'   => 'Toutes les barrières sont restées ouvertes et les traces partent dans tous les sens.',
        ],

        // Qui vient avec eux. C’est là que se trouve presque tout le plaisir
        // de l’histoire : chacun est un petit personnage, pas une description.
        'companion' => [
            'forest' => 'Une vieille chouette annonce qu’elle ira jusqu’au deuxième virage et pas plus loin. Elle fait finalement tout le chemin, en se plaignant du temps à chaque étape.',
            'dino'   => 'Un petit ptérosaure extrêmement bruyant se nomme lui-même guetteur. Il n’a jamais rien repéré d’utile, mais il ne se tait jamais, ce qui vaut presque aussi bien.',
            'space'  => 'La station envoie un drone de réparation avec un seul œil en état de marche et l’habitude de fredonner. Il connaît la route vers exactement une planète et pense que c’est la bonne.',
            'ocean'  => 'Un vieux crabe grognon accepte de venir, à une seule condition : que personne ne parle de sa lenteur. Personne n’en parle. Il suit bien mieux qu’on ne le croyait.',
            'pirate' => 'Le perroquet du navire se porte volontaire en premier, surtout parce qu’il connaît la carte par cœur et ne supporte pas de rater le moment où quelqu’un la lit à voix haute.',
            'magic'  => 'Un bout de bougie qui refuse de s’éteindre flotte derrière eux, éclairant tout ce qu’il ne faut pas au moment où il ne faut pas, et très fier de lui.',
            'castle' => 'La chatte du château vient aussi. Elle est passée dans chaque pièce, sous chaque plancher et derrière chaque rideau, et elle se souvient de tout.',
            'desert' => 'Une jeune chamelle qui a ses idées bien à elle sur la marche se joint à eux à la porte. Elle s’arrête quand ça lui plaît et repart quand ça lui plaît, et elle ne s’est jamais perdue.',
            'arctic' => 'Un petit phoque tout rond les suit dès la première étape, filant devant sur le ventre pour tester la glace et revenant chaque fois dire si elle tiendra.',
            'candy'  => 'Une souris en pain d’épice sert de guide. Elle grignote une encoche dans chaque panneau pour retrouver le chemin du retour, et mange plusieurs panneaux en entier.',
            'robot'  => 'Un robot balayeur rouillé sort par une porte de côté et se joint à eux sans rien demander. Sa carte a quarante ans, mais il connaît un raccourci, et le raccourci existe vraiment.',
            'farm'   => 'Le chien de la ferme n’a besoin d’aucune invitation. Il attend au portail depuis le lever du jour, le nez pointé vers la route, absolument certain du chemin à prendre.',
        ],

        // La dernière étape : le monde qui répond, juste avant la fin.
        'final' => [
            'forest' => 'À la dernière étape, les arbres se penchent pour écouter, et les feuilles dorées qui s’étaient arrêtées ce matin recommencent, très lentement, à tomber.',
            'dino'   => 'À la dernière étape, le sol se calme enfin, et de derrière la crête monte un petit rugissement : pas un rugissement de peur cette fois, mais d’espoir.',
            'space'  => 'À la dernière étape, la petite planète est assez proche pour qu’on la voie : une lumière faible dans tout ce noir, qui vacille comme une bougie sur le point de s’éteindre.',
            'ocean'  => 'À la dernière étape, un fil de couleur revient dans le corail, et les plus petits poissons en sortent pour venir à la rencontre des voyageurs.',
            'pirate' => 'À la dernière étape, la carte déchirée s’arrête tout à fait, et il faut deviner la suite rien qu’à la forme de la côte.',
            'magic'  => 'À la dernière étape, la brume se défait complètement, et la vieille tour attend là, sombre et patiente, avec une lampe froide tout en haut.',
            'castle' => 'À la dernière étape, les drapeaux de la fête sont déjà hissés, et tout le royaume attend sur la place une cloche qui n’a pas encore sonné.',
            'desert' => 'À la dernière étape, le vent tombe, le sable se pose, et le vert de l’oasis apparaît à l’horizon exactement là où la carte l’avait promis.',
            'arctic' => 'À la dernière étape, le jour revient pour une heure encore, et une heure suffit à distinguer une petite forme sombre sur un petit glaçon blanc.',
            'candy'  => 'À la dernière étape, la rivière de chocolat gelée craque une fois, tout doucement, comme craque la glace quand elle a décidé de fondre.',
            'robot'  => 'À la dernière étape, une lumière s’allume au fond de l’usine, puis deux, puis toute une rangée, comme si le bâtiment se réveillait pour regarder.',
            'farm'   => 'À la dernière étape, une empreinte de sabot apparaît dans la boue, puis une autre, et elles mènent enfin quelque part au lieu de partout à la fois.',
        ],
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
        'return_cards' => 'Remets la carte mission sous la pioche mission, et la carte déplacement sous la sienne.',
        'return_dice'  => 'Remets la carte mission sous la pioche mission.',
        'win'          => 'Le premier joueur qui atteint la case ARRIVÉE gagne la carte du champion.',
    ],
];
