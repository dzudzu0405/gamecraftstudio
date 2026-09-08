<?php
/**
 * The question templates in French. Keyed by the English template's code;
 * see install/mission-templates-all.php for how the two halves are joined.
 *
 * GENDER AND ARTICLES
 *
 * One sentence is written for a dozen different words, so the words are chosen
 * to fit it: every creature and every object is masculine plural, every
 * container feminine, and each place carries its own preposition - dans le pre,
 * sur la plage. The sentence never has to guess which word turned up.
 *
 * The reading and grammar questions are not translations: rhymes, plurals and
 * past tenses only exist inside one language, so those are written fresh here.
 */

$creatures = ['lapins', 'chatons', 'chiots', 'canetons', 'écureuils', 'hérissons',
              'poissons', 'dauphins', 'pingouins', 'renards', 'hiboux', 'crapauds'];

$things = ['boutons', 'cailloux', 'crayons', 'autocollants', 'ballons', 'bonbons',
           'coquillages', 'glands', 'rubans', 'timbres', 'cubes', 'dés'];

$places = ['dans le pré', 'dans le jardin', 'près de la mare', 'dans la forêt',
           'dans la cour', 'sur la plage', 'dans le verger', 'dans la cabane',
           'dans la grange', 'au bord du bassin'];

$names = ['Léa', 'Hugo', 'Chloé', 'Noé', 'Camille', 'Théo',
          'Sana', 'Milo', 'Bella', 'Pedro', 'Ada', 'Nour'];

$containers = ['boîtes', 'caisses', 'corbeilles', 'bassines', 'trousses', 'valises', 'poches'];

$treats = ['biscuits', 'raisins', 'sandwichs', 'gâteaux', 'muffins', 'bonbons'];

return [

    // ----------------------------------------------------------------- MATHS
    'math-add' => [
        'name'    => 'Additionner',
        'pattern' => 'Il y a {a} {creature} {place}. {b} autres arrivent. Combien y a-t-il de {creature} maintenant ?',
        'answer'  => '{a+b} {creature}',
        'hint'    => 'Compte-les tous ensemble.',
        'lists'   => ['creature' => $creatures, 'place' => $places],
    ],
    'math-sub' => [
        'name'    => 'Soustraire',
        'pattern' => '{who} a ramassé {a} {thing}. {who} en donne {b}. Combien lui en reste-t-il ?',
        'answer'  => '{a-b} {thing}',
        'hint'    => 'Pars de ce que tu avais et enlève le reste.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-count-back' => [
        'name'    => 'Compter à rebours',
        'pattern' => 'Pars de {a} et compte {b} en arrière. Où arrives-tu ?',
        'answer'  => '{a-b}',
        'hint'    => 'Sers-toi de tes doigts si ça aide.',
    ],
    'math-double' => [
        'name'    => 'Le double',
        'pattern' => '{who} a {a} {thing} et en trouve exactement autant une deuxième fois. Combien en a-t-il maintenant ?',
        'answer'  => '{a+a} {thing}',
        'hint'    => 'Le double, c’est la même quantité deux fois.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-mul' => [
        'name'    => 'Des paquets de',
        'pattern' => 'Il y a {a} {container} et chacune contient {b} {thing}. Combien de {thing} en tout ?',
        'answer'  => '{a*b} {thing}',
        'hint'    => 'Compte les paquets, puis ce qu’il y a dans chacun.',
        'lists'   => ['container' => $containers, 'thing' => $things],
    ],
    'math-div' => [
        'name'    => 'Partager',
        'pattern' => 'Partage {a} {treat} équitablement entre {b} amis. Combien chacun en reçoit-il ?',
        'answer'  => '{a/b} {treat} chacun',
        'hint'    => 'Distribue-les un par un, comme des cartes.',
        'lists'   => ['treat' => $treats],
    ],
    'math-missing' => [
        'name'    => 'Le nombre manquant',
        'pattern' => '{who} avait des {thing}, en a reçu {b} de plus, et en a maintenant {c}. Combien en avait-il au départ ?',
        'answer'  => '{c-b} {thing}',
        'hint'    => 'Remonte à partir du total.',
        'lists'   => ['thing' => $things, 'who' => $names],
    ],
    'math-word2' => [
        'name'    => 'Problème de pièces',
        'pattern' => 'Tu as {a} pièces. Tu achètes {b} {thing} à {c} pièces chacun. Combien de pièces te reste-t-il ?',
        'answer'  => '{a-b*c} pièces',
        'hint'    => 'Calcule d’abord la dépense, puis enlève-la de ce que tu avais.',
        'lists'   => ['thing' => $things],
    ],
    'math-two-step' => [
        'name'    => 'Deux étapes',
        'pattern' => '{who} remplit {a} {container} avec {b} {thing} dans chacune, puis {c} {thing} tombent. Combien en reste-t-il de rangés ?',
        'answer'  => '{a*b-c} {thing}',
        'hint'    => 'Multiplie d’abord, soustrais ensuite.',
        'lists'   => ['container' => $containers, 'thing' => $things, 'who' => $names],
    ],

    // -------------------------------------------------------------- LITERACY
    'lit-rhyme' => [
        'name'    => 'Trouve une rime',
        'pattern' => 'Dis un mot qui rime avec « {word} ».',
        'answer'  => 'N’importe quel vrai mot qui rime',
        'hint'    => 'Dis-le à voix haute et écoute la fin.',
        'lists'   => ['word' => ['chat', 'fleur', 'souris', 'bateau', 'lune', 'main',
                                 'gâteau', 'grenouille', 'nez', 'ballon', 'étoile', 'poule']],
    ],
    'lit-letter' => [
        'name'    => 'Le son du début',
        'pattern' => 'Nomme {a} choses qui commencent par la lettre « {letter} ».',
        'answer'  => 'N’importe quels {a} mots valables',
        'hint'    => 'Regarde autour de toi pour trouver des idées.',
        'lists'   => ['letter' => ['B', 'C', 'D', 'F', 'G', 'L', 'M', 'P', 'R', 'S', 'T', 'V']],
    ],
    'lit-opposite' => [
        'name'    => 'Les contraires',
        'pattern' => 'Quel est le contraire de « {word} » ?',
        'answer'  => 'Le bon contraire',
        'hint'    => 'Pense au mot qui veut dire l’inverse.',
        'lists'   => ['word' => ['chaud', 'grand', 'rapide', 'content', 'jour', 'en haut',
                                 'bruyant', 'mouillé', 'plein', 'tôt', 'dur', 'près']],
    ],
    'lit-define' => [
        'name'    => 'Avec tes mots',
        'pattern' => 'Que veut dire le mot « {word} » ? Explique-le avec tes mots.',
        'answer'  => 'Toute explication sensée compte',
        'hint'    => 'Essaie d’abord de l’utiliser dans une phrase.',
        'lists'   => ['word' => ['courage', 'curieux', 'doux', 'ancien', 'fragile',
                                 'généreux', 'têtu', 'reconnaissant', 'inquiet', 'fidèle']],
    ],
    'lit-sentence' => [
        'name'    => 'Construis une phrase',
        'pattern' => 'Invente une phrase qui utilise à la fois « {word} » et « {other} ».',
        'answer'  => 'Toute phrase contenant les deux mots',
        'hint'    => 'Elle peut être aussi farfelue que tu veux.',
        'lists'   => ['word'  => ['rivière', 'château', 'tempête', 'lanterne', 'port', 'prairie'],
                      'other' => ['silencieux', 'doré', 'soudain', 'énorme', 'gelé', 'tordu']],
    ],
    'lit-story' => [
        'name'    => 'Continue l’histoire',
        'pattern' => 'Raconte la suite de l’histoire : « {opening} »',
        'answer'  => 'Toute suite qui se tient',
        'hint'    => 'Deux ou trois phrases suffisent.',
        'lists'   => ['opening' => [
            'La porte en haut de l’escalier n’avait jamais été ouverte...',
            'La carte montrait une île qui ne figurait sur aucune autre carte...',
            'Tout le village s’est réveillé en parlant à l’envers...',
            'Le vieux phare s’est rallumé tout seul après vingt ans...',
            'Une lettre est arrivée au nom de quelqu’un qui n’habitait pas là...',
        ]],
    ],

    // --------------------------------------------------------------- ENGLISH
    // In French this is French grammar: its own plurals and past tenses.
    'eng-plural' => [
        'name'    => 'Un et plusieurs',
        'pattern' => 'Quel est le pluriel de « {word} » ?',
        'answer'  => 'Le bon pluriel',
        'hint'    => 'Certains mots changent plus que tu ne crois.',
        'lists'   => ['word' => ['cheval', 'journal', 'oeil', 'genou', 'bijou',
                                 'travail', 'chou', 'caillou', 'vitrail', 'ciel']],
    ],
    'eng-verb' => [
        'name'    => 'Hier',
        'pattern' => 'Mets cette phrase au passé composé : « Tous les jours, je {verb}. »',
        'answer'  => 'Le passé composé correct de « {verb} »',
        'hint'    => 'Dis-le comme si c’était déjà fait.',
        'lists'   => ['verb' => ['cours', 'nage', 'mange', 'écris', 'chante',
                                 'prends', 'bois', 'pars', 'dessine', 'dors']],
    ],
    'eng-describe' => [
        'name'    => 'Décris-le',
        'pattern' => 'Décris {thing} à quelqu’un qui n’en a jamais vu, sans dire le mot.',
        'answer'  => 'Toute description claire',
        'hint'    => 'Commence par ce à quoi ça sert.',
        'lists'   => ['thing' => ['un vélo', 'un parapluie', 'une bouilloire', 'un escalier',
                                  'un télescope', 'un château de sable', 'un moulin', 'un cerf-volant']],
    ],

    // --------------------------------------------------------------- SCIENCE
    'sci-sense' => [
        'name'    => 'Quel sens ?',
        'pattern' => 'Quelle partie de ton corps utilises-tu pour {sense} ?',
        'answer'  => 'L’organe du sens qui convient',
        'hint'    => 'Montre-la du doigt.',
        'lists'   => ['sense' => ['sentir une fleur', 'entendre une cloche', 'goûter du miel',
                                  'voir un arc-en-ciel', 'sentir le sable chaud']],
    ],
    'sci-float' => [
        'name'    => 'Flotte ou coule ?',
        'pattern' => 'Est-ce que {thing} flotterait ou coulerait dans l’eau ? Dis pourquoi tu le penses.',
        'answer'  => 'Les deux réponses vont, avec une raison',
        'hint'    => 'Pense à son poids par rapport à sa taille.',
        'lists'   => ['thing' => ['un bouchon', 'une pierre', 'une pomme', 'une pièce', 'une plume',
                                  'une éponge', 'un clou', 'une bougie', 'une orange']],
    ],
    'sci-change' => [
        'name'    => 'Que se passe-t-il ?',
        'pattern' => 'Que se passe-t-il si {event} ? Explique du mieux que tu peux.',
        'answer'  => 'Une explication raisonnable',
        'hint'    => 'Dis ce que tu verrais, puis pourquoi.',
        'lists'   => ['event' => [
            'tu laisses un glaçon dans une pièce chaude',
            'on garde une plante dans un placard sombre',
            'tu frottes tes mains très vite',
            'on remue du sel dans un verre d’eau',
            'on laisse un ballon au soleil',
        ]],
    ],

    // ---------------------------------------------------------------- NATURE
    'nature-animal' => [
        'name'    => 'Les cris des animaux',
        'pattern' => 'Quel cri fait {animal} ? Imite-le du mieux que tu peux !',
        'answer'  => 'Toute tentative compte',
        'hint'    => 'Personne ne te met de note ici.',
        'lists'   => ['animal' => ['une vache', 'un canard', 'un lion', 'un mouton', 'un hibou',
                                   'une grenouille', 'un cheval', 'une abeille', 'un chat',
                                   'un loup', 'une chèvre', 'un corbeau']],
    ],
    'nature-home' => [
        'name'    => 'Où vit-il ?',
        'pattern' => 'Où vit {animal} ?',
        'answer'  => 'Le bon type d’abri',
        'hint'    => 'Pense à ce dont il a besoin pour être en sécurité.',
        'lists'   => ['animal' => ['une abeille', 'un lapin', 'un manchot', 'un chameau', 'un poisson',
                                   'un hibou', 'une taupe', 'un crabe', 'une chauve-souris', 'un écureuil']],
    ],
    'nature-season' => [
        'name'    => 'Les saisons',
        'pattern' => 'Nomme deux choses qui arrivent {season}.',
        'answer'  => 'Deux réponses sensées',
        'hint'    => 'Pense au temps qu’il fait, et aux plantes.',
        'lists'   => ['season' => ['au printemps', 'en été', 'en automne', 'en hiver']],
    ],
    'nature-chain' => [
        'name'    => 'Qui mange quoi',
        'pattern' => 'Que peut manger {animal}, et qui pourrait le manger ?',
        'answer'  => 'Une chaîne alimentaire sensée',
        'hint'    => 'Chaque animal est le dîner de quelqu’un.',
        'lists'   => ['animal' => ['une souris', 'une grenouille', 'un lapin', 'un poisson',
                                   'une chenille', 'un moineau']],
    ],

    // ----------------------------------------------------------------- LOGIC
    'logic-odd' => [
        'name'    => 'L’intrus',
        'pattern' => 'Quel est l’intrus : {set} ? Dis pourquoi.',
        'answer'  => 'Toute réponse bien justifiée',
        'hint'    => 'Il peut y avoir plusieurs bonnes réponses.',
        'lists'   => ['set' => [
            'pomme, banane, carotte, poire',
            'chien, chat, poisson, lapin',
            'rouge, bleu, carré, vert',
            'chaussure, chaussette, chapeau, cuillère',
            'voiture, bateau, vélo, arbre',
        ]],
    ],
    'logic-seq' => [
        'name'    => 'Quelle est la suite ?',
        'pattern' => 'Quel nombre vient ensuite : {a}, {b}, {c}, ... ?',
        'answer'  => 'Le nombre qui continue la suite',
        'hint'    => 'Cherche le pas entre les nombres.',
    ],
    'logic-riddle' => [
        'name'    => 'Devinette',
        'pattern' => '{riddle}',
        'answer'  => 'La réponse de la devinette',
        'hint'    => 'Relis-la deux fois : l’astuce est dans les mots.',
        'lists'   => ['riddle' => [
            'J’ai des aiguilles mais je ne couds pas. Qui suis-je ?',
            'Plus j’essuie, plus je suis mouillée. Qui suis-je ?',
            'J’ai des touches mais je n’ouvre aucune porte. Qui suis-je ?',
            'Plus tu m’enlèves, plus je deviens grand. Qui suis-je ?',
            'Je monte et je descends sans bouger de place. Qui suis-je ?',
        ]],
    ],

    // ------------------------------------------------------------ LIFE SKILLS
    'life-kind' => [
        'name'    => 'Un geste gentil',
        'pattern' => 'Nomme une chose gentille que tu pourrais faire aujourd’hui pour {who}.',
        'answer'  => 'Toute idée gentille compte',
        'hint'    => 'Les petites choses comptent aussi.',
        'lists'   => ['who' => ['un ami', 'quelqu’un de ta famille', 'un voisin',
                                'un nouvel élève à l’école', 'quelqu’un qui est triste']],
    ],
    'life-safe' => [
        'name'    => 'Rester en sécurité',
        'pattern' => 'Que devrais-tu faire si {situation} ?',
        'answer'  => 'Toute réponse prudente et sensée',
        'hint'    => 'Trouver un adulte de confiance est souvent la première chose à faire.',
        'lists'   => ['situation' => [
            'tu perds ton adulte dans un magasin',
            'un inconnu te demande de le suivre',
            'ça sent le brûlé à la maison',
            'tu vois quelqu’un se faire faire mal',
            'tu te sens mal à l’école',
        ]],
    ],
    'life-choice' => [
        'name'    => 'Que ferais-tu ?',
        'pattern' => 'Que ferais-tu si {situation} ?',
        'answer'  => 'Il n’y a pas une seule bonne réponse : sois gentil',
        'hint'    => 'Dis ce que tu ferais, et pourquoi.',
        'lists'   => ['situation' => [
            'un ami se faisait gronder à ta place',
            'tu avais oublié tes devoirs',
            'tu trouvais quelque chose qui appartient à quelqu’un d’autre',
            'tu avais promis le même après-midi à deux personnes',
            'quelqu’un copiait ton travail et était félicité pour ça',
            'tu cassais quelque chose sans que personne ne le voie',
        ]],
    ],

    // ------------------------------------------------------------- GEOGRAPHY
    'geo-where' => [
        'name'    => 'Près et loin',
        'pattern' => 'Trouverais-tu {thing} près de chez toi, ou très loin ?',
        'answer'  => 'L’un ou l’autre, avec une raison',
        'hint'    => 'Pense à ce qu’il y a autour de chez toi.',
        'lists'   => ['thing' => ['une montagne', 'une plage', 'une rivière', 'un désert',
                                  'une forêt', 'un port', 'un volcan']],
    ],
    'geo-direction' => [
        'name'    => 'De quel côté ?',
        'pattern' => 'Si tu regardes vers le {direction} et que tu tournes à droite, vers où regardes-tu ?',
        'answer'  => 'La direction à droite du {direction}',
        'hint'    => 'Imagine une boussole, ou sers-toi de tes mains.',
        'lists'   => ['direction' => ['nord', 'sud', 'est', 'ouest']],
    ],
    'geo-place' => [
        'name'    => 'Des endroits sur Terre',
        'pattern' => 'Nomme un pays où tu t’attendrais à trouver {feature}, et dis pourquoi.',
        'answer'  => 'Tout pays qui convient, avec une raison',
        'hint'    => 'Pense à la chaleur ou au froid qu’il y fait.',
        'lists'   => ['feature' => ['une forêt tropicale', 'un désert', 'de la neige toute l’année',
                                    'des volcans en activité', 'des récifs de corail', 'de très longs fleuves']],
    ],
];
