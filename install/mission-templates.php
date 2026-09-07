<?php
/**
 * The question templates a game draws its mission cards from.
 *
 * HOW A TEMPLATE WORKS
 *
 *     pattern    'There are {a} {animal}s in the {place}. {b} more arrive...'
 *     answer     '{a+b} {animal}s'
 *     variables  ['a' => ['min' => 2, 'max' => 12],
 *                 'animal' => ['list' => [...]], 'place' => ['list' => [...]]]
 *
 * Numbers are drawn between min and max; a list picks one entry at random. The
 * lists are what carry the variety: a template with two twelve-word lists and
 * two numbers reads differently almost every time it is dealt. Without them a
 * sixty-card maths deck was the same two sentences - rabbits and apples - with
 * the digits swapped, thirty times each.
 *
 * TIER is starter on every row. Templates are not sold by plan: a plan buys
 * maps, characters, card designs and the Advanced level, and what limits the
 * questions a game can reach is its LEVEL, which the plan already gates. Left
 * as it was, a Starter buyer making a 90-card Standard game - which the plan
 * sheet entitles them to - drew from the five beginner templates alone.
 *
 * Kept out of Seeder.php so that install and tools/refresh-mission-templates.php
 * read the same list.
 */

// ---------------------------------------------------------------------------
//  Word lists, shared between templates
// ---------------------------------------------------------------------------

$creatures = ['rabbits', 'kittens', 'puppies', 'ducklings', 'frogs', 'bees',
              'penguins', 'foxes', 'owls', 'turtles', 'squirrels', 'dolphins'];

$things = ['apples', 'marbles', 'stickers', 'crayons', 'shells', 'acorns',
           'buttons', 'pebbles', 'ribbons', 'conkers', 'feathers', 'cherries'];

$places = ['meadow', 'garden', 'pond', 'forest', 'playground', 'beach',
           'orchard', 'treehouse', 'barn', 'rockpool'];

$names = ['Maya', 'Ben', 'Chloe', 'Kai', 'Cora', 'Theo', 'Sana', 'Milo',
          'Bella', 'Pedro', 'Ada', 'Noor'];

$containers = ['baskets', 'boxes', 'jars', 'crates', 'baskets', 'buckets', 'trays'];

$treats = ['biscuits', 'grapes', 'sandwiches', 'strawberries', 'muffins', 'plums'];

return [

    // =======================================================================
    //  MATHS
    // =======================================================================
    [
        'code' => 'math-add', 'name' => 'Adding on', 'subject' => 'math',
        'level' => 'beginner', 'sticker' => 'star',
        'pattern' => 'There are {a} {creature} in the {place}. {b} more come along to join them. How many {creature} are there now?',
        'answer'  => '{a+b} {creature}',
        'variables' => ['a' => ['min' => 2, 'max' => 12], 'b' => ['min' => 1, 'max' => 8],
                        'creature' => ['list' => $creatures], 'place' => ['list' => $places]],
        'hint' => 'Count them all together.',
    ],
    [
        'code' => 'math-sub', 'name' => 'Taking away', 'subject' => 'math',
        'level' => 'beginner', 'sticker' => 'leaf',
        'pattern' => '{who} collected {a} {thing}. {who} gives {b} of them away. How many {thing} are left?',
        'answer'  => '{a-b} {thing}',
        'variables' => ['a' => ['min' => 6, 'max' => 20], 'b' => ['min' => 1, 'max' => 5],
                        'thing' => ['list' => $things], 'who' => ['list' => $names]],
        'hint' => 'Start with what you had and take the rest away.',
    ],
    [
        'code' => 'math-count-back', 'name' => 'Counting back', 'subject' => 'math',
        'level' => 'beginner', 'sticker' => 'footprint',
        'pattern' => 'Start at {a} and count back {b}. Where do you land?',
        'answer'  => '{a-b}',
        'variables' => ['a' => ['min' => 10, 'max' => 20], 'b' => ['min' => 2, 'max' => 8]],
        'hint' => 'Use your fingers if it helps.',
    ],
    [
        'code' => 'math-double', 'name' => 'Doubling', 'subject' => 'math',
        'level' => 'beginner', 'sticker' => 'gem',
        'pattern' => '{who} has {a} {thing} and finds exactly the same number again. How many now?',
        'answer'  => '{a+a} {thing}',
        'variables' => ['a' => ['min' => 2, 'max' => 10],
                        'thing' => ['list' => $things], 'who' => ['list' => $names]],
        'hint' => 'Double means the same amount twice.',
    ],

    [
        'code' => 'math-mul', 'name' => 'Groups of', 'subject' => 'math',
        'level' => 'standard', 'sticker' => 'sun',
        'pattern' => 'There are {a} {container} and each one holds {b} {thing}. How many {thing} altogether?',
        'answer'  => '{a*b} {thing}',
        'variables' => ['a' => ['min' => 2, 'max' => 9], 'b' => ['min' => 2, 'max' => 9],
                        'container' => ['list' => $containers], 'thing' => ['list' => $things]],
        'hint' => 'Count the groups, then count what is in each one.',
    ],
    [
        'code' => 'math-div', 'name' => 'Sharing out', 'subject' => 'math',
        'level' => 'standard', 'sticker' => 'heart',
        'pattern' => 'Share {a} {treat} equally between {b} friends. How many does each friend get?',
        'answer'  => '{a/b} {treat} each',
        'variables' => ['a' => ['min' => 12, 'max' => 48, 'step' => 6], 'b' => ['min' => 2, 'max' => 6],
                        'treat' => ['list' => $treats]],
        'hint' => 'Deal them out one at a time, like cards.',
    ],
    [
        'code' => 'math-missing', 'name' => 'The missing number', 'subject' => 'math',
        'level' => 'standard', 'sticker' => 'key',
        'pattern' => '{who} had some {thing}, was given {b} more, and now has {c}. How many did {who} start with?',
        'answer'  => '{c-b} {thing}',
        'variables' => ['b' => ['min' => 3, 'max' => 15], 'c' => ['min' => 20, 'max' => 60],
                        'thing' => ['list' => $things], 'who' => ['list' => $names]],
        'hint' => 'Work backwards from the total.',
    ],

    [
        'code' => 'math-word2', 'name' => 'Money problem', 'subject' => 'math',
        'level' => 'advanced', 'sticker' => 'gem',
        'pattern' => 'You have {a} coins. You buy {b} {thing} costing {c} coins each. How many coins are left?',
        'answer'  => '{a-b*c} coins',
        'variables' => ['a' => ['min' => 50, 'max' => 100, 'step' => 10], 'b' => ['min' => 2, 'max' => 4],
                        'c' => ['min' => 5, 'max' => 12], 'thing' => ['list' => $things]],
        'hint' => 'Work out the cost first, then take it off what you had.',
    ],
    [
        'code' => 'math-two-step', 'name' => 'Two steps', 'subject' => 'math',
        'level' => 'advanced', 'sticker' => 'rocket',
        'pattern' => '{who} packs {a} {container} with {b} {thing} in each, then {c} {thing} fall out. How many are packed?',
        'answer'  => '{a*b-c} {thing}',
        'variables' => ['a' => ['min' => 3, 'max' => 8], 'b' => ['min' => 4, 'max' => 9],
                        'c' => ['min' => 2, 'max' => 10], 'container' => ['list' => $containers],
                        'thing' => ['list' => $things], 'who' => ['list' => $names]],
        'hint' => 'Multiply first, then subtract.',
    ],

    // =======================================================================
    //  LITERACY
    // =======================================================================
    [
        'code' => 'lit-rhyme', 'name' => 'Find a rhyme', 'subject' => 'literacy',
        'level' => 'beginner', 'sticker' => 'book',
        'pattern' => 'Say a word that rhymes with "{word}".',
        'answer'  => 'Any real rhyming word counts',
        'variables' => ['word' => ['list' => ['cat', 'star', 'tree', 'moon', 'bell', 'cake',
                                              'frog', 'snail', 'nose', 'ring', 'boat', 'hen']]],
        'hint' => 'Say it out loud and listen to the ending.',
    ],
    [
        'code' => 'lit-letter', 'name' => 'Starting sound', 'subject' => 'literacy',
        'level' => 'beginner', 'sticker' => 'bulb',
        'pattern' => 'Name {a} things that begin with the letter "{letter}".',
        'answer'  => 'Any {a} valid words',
        'variables' => ['a' => ['min' => 2, 'max' => 4],
                        'letter' => ['list' => ['B', 'C', 'D', 'F', 'G', 'H', 'L', 'M', 'P', 'R', 'S', 'T']]],
        'hint' => 'Look around the room for ideas.',
    ],
    [
        'code' => 'lit-opposite', 'name' => 'Opposites', 'subject' => 'literacy',
        'level' => 'beginner', 'sticker' => 'flag',
        'pattern' => 'What is the opposite of "{word}"?',
        'answer'  => 'The correct opposite',
        'variables' => ['word' => ['list' => ['hot', 'big', 'fast', 'happy', 'day', 'up',
                                              'loud', 'wet', 'full', 'early', 'hard', 'near']]],
        'hint' => 'Think of the word that means the other way round.',
    ],

    [
        'code' => 'lit-define', 'name' => 'In your own words', 'subject' => 'literacy',
        'level' => 'standard', 'sticker' => 'book',
        'pattern' => 'What does the word "{word}" mean? Explain it in your own words.',
        'answer'  => 'A sensible explanation counts',
        'variables' => ['word' => ['list' => ['courage', 'curious', 'gentle', 'ancient', 'fragile',
                                              'generous', 'stubborn', 'grateful', 'anxious', 'loyal']]],
        'hint' => 'Try using it in a sentence first.',
    ],
    [
        'code' => 'lit-sentence', 'name' => 'Build a sentence', 'subject' => 'literacy',
        'level' => 'standard', 'sticker' => 'star',
        'pattern' => 'Make up a sentence that uses both "{word}" and "{other}".',
        'answer'  => 'Any sentence using both words',
        'variables' => ['word'  => ['list' => ['river', 'castle', 'storm', 'lantern', 'harbour', 'meadow']],
                        'other' => ['list' => ['silent', 'golden', 'sudden', 'enormous', 'frozen', 'crooked']]],
        'hint' => 'It can be as silly as you like.',
    ],

    [
        'code' => 'lit-story', 'name' => 'Carry the story on', 'subject' => 'literacy',
        'level' => 'advanced', 'sticker' => 'book',
        'pattern' => 'Tell the next part of the story: "{opening}"',
        'answer'  => 'Any story that follows on',
        'variables' => ['opening' => ['list' => [
            'The door at the top of the stairs had never been open before...',
            'The map showed an island that was not on any other map...',
            'Everyone in the village woke up speaking backwards...',
            'The old lighthouse switched itself back on after twenty years...',
            'A letter arrived addressed to someone who did not live there...',
        ]]],
        'hint' => 'Two or three sentences is plenty.',
    ],

    // =======================================================================
    //  ENGLISH
    // =======================================================================
    [
        'code' => 'eng-plural', 'name' => 'One and many', 'subject' => 'english',
        'level' => 'beginner', 'sticker' => 'leaf',
        'pattern' => 'What is the plural of "{word}"?',
        'answer'  => 'The correct plural',
        'variables' => ['word' => ['list' => ['child', 'mouse', 'leaf', 'foot', 'box',
                                              'knife', 'goose', 'shelf', 'tooth', 'baby']]],
        'hint' => 'Some words change more than you expect.',
    ],
    [
        'code' => 'eng-verb', 'name' => 'Yesterday', 'subject' => 'english',
        'level' => 'standard', 'sticker' => 'flag',
        'pattern' => 'Put this into the past tense: "I {verb} every day."',
        'answer'  => 'The correct past tense of "{verb}"',
        'variables' => ['verb' => ['list' => ['run', 'swim', 'eat', 'write', 'sing',
                                              'think', 'bring', 'catch', 'draw', 'fly']]],
        'hint' => 'Say it as if it already happened.',
    ],
    [
        'code' => 'eng-describe', 'name' => 'Describe it', 'subject' => 'english',
        'level' => 'advanced', 'sticker' => 'bulb',
        'pattern' => 'Describe a {thing} to somebody who has never seen one - without using the word "{thing}".',
        'answer'  => 'Any clear description',
        'variables' => ['thing' => ['list' => ['bicycle', 'umbrella', 'kettle', 'staircase',
                                               'telescope', 'sandcastle', 'windmill', 'kite']]],
        'hint' => 'Start with what it is for.',
    ],

    // =======================================================================
    //  SCIENCE
    // =======================================================================
    [
        'code' => 'sci-sense', 'name' => 'Which sense?', 'subject' => 'science',
        'level' => 'beginner', 'sticker' => 'bulb',
        'pattern' => 'Which part of your body do you use to {sense}?',
        'answer'  => 'The matching sense organ',
        'variables' => ['sense' => ['list' => ['smell a flower', 'hear a bell', 'taste honey',
                                               'see a rainbow', 'feel warm sand']]],
        'hint' => 'Point to it.',
    ],
    [
        'code' => 'sci-float', 'name' => 'Float or sink?', 'subject' => 'science',
        'level' => 'standard', 'sticker' => 'drop',
        'pattern' => 'Would a {thing} float or sink in water? Say why you think so.',
        'answer'  => 'Either answer with a reason',
        'variables' => ['thing' => ['list' => ['cork', 'stone', 'apple', 'coin', 'feather',
                                               'sponge', 'nail', 'candle', 'orange']]],
        'hint' => 'Think about how heavy it is for its size.',
    ],
    [
        'code' => 'sci-change', 'name' => 'What happens next?', 'subject' => 'science',
        'level' => 'advanced', 'sticker' => 'rocket',
        'pattern' => 'What happens when {event}? Explain as best you can.',
        'answer'  => 'A reasonable explanation',
        'variables' => ['event' => ['list' => [
            'you leave an ice cube in a warm room',
            'a plant is kept in a dark cupboard',
            'you rub your hands together quickly',
            'salt is stirred into a glass of water',
            'a balloon is left in the sun',
        ]]],
        'hint' => 'Say what you would see, then why.',
    ],

    // =======================================================================
    //  NATURE
    // =======================================================================
    [
        'code' => 'nature-animal', 'name' => 'Animal sounds', 'subject' => 'nature',
        'level' => 'beginner', 'sticker' => 'heart',
        'pattern' => 'What sound does a {animal} make? Do your best impression!',
        'answer'  => 'Any good attempt counts',
        'variables' => ['animal' => ['list' => ['cow', 'duck', 'lion', 'sheep', 'owl', 'frog',
                                                'horse', 'bee', 'cat', 'wolf', 'goat', 'crow']]],
        'hint' => 'Nobody is marking you on this one.',
    ],
    [
        'code' => 'nature-home', 'name' => 'Where does it live?', 'subject' => 'nature',
        'level' => 'beginner', 'sticker' => 'leaf',
        'pattern' => 'Where does a {animal} live?',
        'answer'  => 'The right kind of home',
        'variables' => ['animal' => ['list' => ['bee', 'rabbit', 'penguin', 'camel', 'fish',
                                                'owl', 'mole', 'crab', 'bat', 'squirrel']]],
        'hint' => 'Think about what it needs to stay safe.',
    ],
    [
        'code' => 'nature-season', 'name' => 'Seasons', 'subject' => 'nature',
        'level' => 'standard', 'sticker' => 'sun',
        'pattern' => 'Name two things that happen in {season}.',
        'answer'  => 'Any two sensible answers',
        'variables' => ['season' => ['list' => ['spring', 'summer', 'autumn', 'winter']]],
        'hint' => 'Think about the weather, and about plants.',
    ],
    [
        'code' => 'nature-chain', 'name' => 'Who eats what', 'subject' => 'nature',
        'level' => 'advanced', 'sticker' => 'leaf',
        'pattern' => 'What might a {animal} eat, and what might eat a {animal}?',
        'answer'  => 'A sensible food chain',
        'variables' => ['animal' => ['list' => ['mouse', 'frog', 'rabbit', 'fish', 'caterpillar', 'sparrow']]],
        'hint' => 'Every animal is somebody\'s dinner.',
    ],

    // =======================================================================
    //  LOGIC
    // =======================================================================
    [
        'code' => 'logic-odd', 'name' => 'Odd one out', 'subject' => 'logic',
        'level' => 'beginner', 'sticker' => 'key',
        'pattern' => 'Which is the odd one out: {set}? Say why.',
        'answer'  => 'Any answer with a good reason',
        'variables' => ['set' => ['list' => [
            'apple, banana, carrot, pear',
            'dog, cat, fish, rabbit',
            'red, blue, square, green',
            'shoe, sock, hat, spoon',
            'car, boat, bicycle, tree',
        ]]],
        'hint' => 'There can be more than one right answer.',
    ],
    [
        'code' => 'logic-seq', 'name' => 'What comes next?', 'subject' => 'logic',
        'level' => 'standard', 'sticker' => 'key',
        'pattern' => 'What comes next: {a}, {b}, {c}, ... ?',
        'answer'  => 'The number that continues the pattern',
        'variables' => ['a' => ['min' => 2, 'max' => 6], 'b' => ['min' => 8, 'max' => 12],
                        'c' => ['min' => 14, 'max' => 20]],
        'hint' => 'Work out the step between them.',
    ],
    [
        'code' => 'logic-riddle', 'name' => 'Riddle', 'subject' => 'logic',
        'level' => 'advanced', 'sticker' => 'bulb',
        'pattern' => '{riddle}',
        'answer'  => 'The riddle\'s answer',
        'variables' => ['riddle' => ['list' => [
            'I have hands but cannot clap. What am I?',
            'I get wetter the more I dry. What am I?',
            'I have keys but open no locks. What am I?',
            'The more you take of me, the more you leave behind. What am I?',
            'I go up but never come down. What am I?',
        ]]],
        'hint' => 'Read it twice - the trick is in the wording.',
    ],

    // =======================================================================
    //  LIFE SKILLS
    // =======================================================================
    [
        'code' => 'life-kind', 'name' => 'A kind thing', 'subject' => 'life',
        'level' => 'beginner', 'sticker' => 'heart',
        'pattern' => 'Name one kind thing you could do for {who} today.',
        'answer'  => 'Any kind idea counts',
        'variables' => ['who' => ['list' => ['a friend', 'someone in your family', 'a neighbour',
                                             'a new person at school', 'someone feeling sad']]],
        'hint' => 'Small things count too.',
    ],
    [
        'code' => 'life-safe', 'name' => 'Staying safe', 'subject' => 'life',
        'level' => 'standard', 'sticker' => 'flag',
        'pattern' => 'What should you do if {situation}?',
        'answer'  => 'Any sensible, safe answer',
        'variables' => ['situation' => ['list' => [
            'you get separated from your grown-up in a shop',
            'a stranger asks you to go with them',
            'you smell burning at home',
            'you see someone being hurt',
            'you feel unwell at school',
        ]]],
        'hint' => 'Finding a trusted adult is usually the first step.',
    ],
    [
        'code' => 'life-choice', 'name' => 'What would you do?', 'subject' => 'life',
        'level' => 'advanced', 'sticker' => 'trophy',
        'pattern' => 'What would you do if {situation}?',
        'answer'  => 'There is no single right answer - just be kind',
        'variables' => ['situation' => ['list' => [
            'a friend takes the blame for something you did',
            'you forgot to bring your homework',
            'you found something that belongs to someone else',
            'you promised two people the same afternoon',
            'somebody copied your work and was praised for it',
            'you broke something and nobody saw',
        ]]],
        'hint' => 'Say what you would do, and why.',
    ],

    // =======================================================================
    //  GEOGRAPHY
    // =======================================================================
    [
        'code' => 'geo-where', 'name' => 'Near and far', 'subject' => 'geography',
        'level' => 'beginner', 'sticker' => 'flag',
        'pattern' => 'Would you find {thing} near your home, or far away?',
        'answer'  => 'Either, with a reason',
        'variables' => ['thing' => ['list' => ['a mountain', 'a beach', 'a river', 'a desert',
                                               'a forest', 'a harbour', 'a volcano']]],
        'hint' => 'Think about what is around where you live.',
    ],
    [
        'code' => 'geo-direction', 'name' => 'Which way?', 'subject' => 'geography',
        'level' => 'standard', 'sticker' => 'footprint',
        'pattern' => 'If you face {direction} and turn right, which way are you facing now?',
        'answer'  => 'The direction to the right of {direction}',
        'variables' => ['direction' => ['list' => ['north', 'south', 'east', 'west']]],
        'hint' => 'Picture a compass, or use your hands.',
    ],
    [
        'code' => 'geo-place', 'name' => 'Places on Earth', 'subject' => 'geography',
        'level' => 'advanced', 'sticker' => 'gem',
        'pattern' => 'Name a country where you would expect to find {feature}, and say why.',
        'answer'  => 'Any country that fits, with a reason',
        'variables' => ['feature' => ['list' => ['a rainforest', 'a desert', 'snow all year round',
                                                 'active volcanoes', 'coral reefs', 'very long rivers']]],
        'hint' => 'Think about how hot or cold it is there.',
    ],
];
