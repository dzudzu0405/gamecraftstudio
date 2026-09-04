<?php
namespace App\Services;

use App\Models\Project;

/**
 * Builds the map background prompts (FR-30, step 2 of SRS section 2.3).
 *
 * This class only writes the prompt text. Today the buyer copies it, runs it
 * in an image generator and uploads the result back.
 */
class PromptGenerator
{
    /** The one style every map is drawn in */
    public const DEFAULT_STYLE = 'storybook';

    /** Art styles - these change the wording of the prompt */
    public const STYLES = [
        'storybook'  => 'Storybook illustration',
        'watercolor' => 'Watercolour',
        'flat'       => 'Flat vector',
        'papercut'   => 'Paper cut-out',
        'pixel'      => 'Pixel art',
        'crayon'     => 'Crayon drawing',
    ];

    private const STYLE_EN = [
        'storybook'  => "children's storybook illustration, soft rounded shapes, warm friendly lighting",
        'watercolor' => 'soft watercolour painting, visible paper texture, gentle colour bleeds',
        'flat'       => 'flat vector illustration, bold clean shapes, minimal shading',
        'papercut'   => 'layered paper-cut collage style, subtle drop shadows between layers',
        'pixel'      => 'charming 16-bit pixel art, limited palette, crisp pixels',
        'crayon'     => 'wax crayon drawing, slightly uneven strokes, playful hand-made feel',
    ];

    private const THEME_EN = [
        'forest' => 'a lush enchanted forest with tall friendly trees, mossy rocks and a winding dirt trail',
        'dino'   => 'a prehistoric valley with gentle dinosaurs, giant ferns, volcanoes far in the background',
        'space'  => 'a colourful outer-space scene with planets, drifting asteroids and a starry nebula',
        'ocean'  => 'a bright underwater world with coral reefs, friendly fish and rays of sunlight',
        'pirate' => 'a sunny tropical pirate cove with a wooden ship, palm trees and a sandy beach',
        'magic'  => 'a whimsical wizard realm with floating crystals, glowing mushrooms and starlight',
        'castle' => 'a storybook kingdom with a fairytale castle, rolling green hills and banners',
        'desert' => 'a warm desert canyon with cacti, sandstone arches and a hidden oasis',
        'arctic' => 'an icy polar landscape with snow drifts, icebergs and playful penguins',
        'candy'  => 'a sweet candy land with lollipop trees, chocolate rivers and gumdrop hills',
        'robot'  => 'a friendly robot factory city with pipes, gears and glowing control panels',
        'farm'   => 'a cheerful countryside farm with a red barn, vegetable patches and haystacks',
    ];

    /** Who needs saving, when the buyer did not say */
    private const RESCUE_EN = [
        'forest' => 'the smallest fox cub in the wood',
        'dino'   => 'a baby dinosaur separated from its herd',
        'space'  => 'the last keeper of a dying star',
        'ocean'  => 'a young turtle tangled far from home',
        'pirate' => 'a shipmate marooned on a nameless island',
        'magic'  => 'the apprentice who kept the great flame alight',
        'castle' => 'the bellringer locked in the tallest tower',
        'desert' => 'a caravan of travellers lost between the dunes',
        'arctic' => 'a penguin chick adrift on a breaking floe',
        'candy'  => 'the sugar-plum baker frozen in her own kitchen',
        'robot'  => 'the little repair robot who kept the city running',
        'farm'   => 'every animal that vanished in the night',
    ];

    /** What stands in the way, to give the middle of the story some weight */
    private const TROUBLE_EN = [
        'forest' => 'The paths keep rearranging themselves, and the trees have stopped giving directions.',
        'dino'   => 'The ground shakes without warning, and the safe crossings change with every tremor.',
        'space'  => 'Fuel is short, the charts are out of date, and no two stars sit where they should.',
        'ocean'  => 'The currents run the wrong way, and the deeper water grows darker with every stroke.',
        'pirate' => 'The map is torn in places, and a rival crew is reading the very same clues.',
        'magic'  => 'The mist swallows every spell, and magic that used to be reliable now misfires.',
        'castle' => 'The gates answer only to riddles, and the guards have forgotten all the answers.',
        'desert' => 'The wind buries each landmark within minutes of finding it.',
        'arctic' => 'The ice groans underfoot, and the daylight is already beginning to fail.',
        'candy'  => 'Everything sweet has turned brittle, and the bridges snap if you cross too slowly.',
        'robot'  => 'Half the machines still run on old instructions, and they do not know the city is broken.',
        'farm'   => 'The gates were all left open, and the tracks lead in every direction at once.',
    ];

    public static function styleKeys(): array
    {
        return array_keys(self::STYLES);
    }

    public static function styleLabel(string $key): string
    {
        return self::STYLES[$key] ?? ucfirst($key);
    }

    /**
     * The main prompt for generating a map background image.
     *
     * The scene comes from what the buyer typed - the place, and who they are
     * setting out to rescue. The theme only supplies the colours and a fallback
     * scene for when they left the place blank, so the picture follows their
     * words while still matching the frame and cards printed alongside it.
     *
     * Everything here pushes towards a PALE picture. A game board with white
     * squares and bold outlines gets laid on top, and a vivid background wins
     * that fight every time.
     *
     * @param array  $project Project record (setting, rescue_target, theme, cells)
     * @param string $style   A key from STYLES
     */
    public static function background(array $project, string $style = 'storybook'): string
    {
        $theme  = (string) ($project['theme'] ?? 'forest');
        $cells  = MapComposer::normalizeCells((int) ($project['cells'] ?? 18));
        $scene  = trim((string) ($project['setting'] ?? ''));
        $rescue = trim((string) ($project['rescue_target'] ?? ''));

        // Nothing typed -> fall back to the theme's own scene
        if ($scene === '') {
            $scene = self::THEME_EN[$theme] ?? self::THEME_EN['forest'];
        }

        $styleEn = self::STYLE_EN[$style] ?? self::STYLE_EN['storybook'];
        $palette = Art::palette($theme);

        $lines = [];
        $lines[] = 'Create a horizontal BACKDROP illustration for a printable children\'s board game.';
        $lines[] = '';
        $lines[] = 'SCENE: ' . rtrim($scene, '.') . '.';

        if ($rescue !== '') {
            $lines[] = 'STORY: the players are travelling to rescue ' . rtrim($rescue, '.')
                     . '. You may hint at this somewhere in the scenery, but keep it small';
            $lines[] = '       and off to one side - it must not become the subject of the picture.';
        }

        $lines[] = 'STYLE: ' . $styleEn . '.';
        $lines[] = 'ASPECT RATIO: 16:11 landscape (about 1600 x 1100 pixels).';
        $lines[] = '';
        $lines[] = 'THIS IS THE MOST IMPORTANT INSTRUCTION - KEEP IT PALE:';
        $lines[] = 'A printed game board will be laid on top of this image and will cover almost';
        $lines[] = 'all of it. That board has white squares, bright green squares and bold black';
        $lines[] = 'outlines, and it has to stay perfectly readable over whatever you draw.';
        $lines[] = '- Draw everything as a soft, faded wash, like a watercolour left in the sun.';
        $lines[] = '- Use pale, desaturated tints only. Nothing darker than a light mid-tone.';
        $lines[] = '- No black, no strong outlines, no heavy shadows, no deep saturated colour.';
        $lines[] = '- Low contrast throughout. Two neighbouring areas should differ only slightly.';
        $lines[] = '- If in doubt, make it lighter. An almost-empty picture is the right answer.';
        $lines[] = '';
        $lines[] = 'COLOUR: tint the picture towards this palette, using only its palest versions:';
        $lines[] = '  ' . implode('  ', $palette);
        $lines[] = 'The darker entries are the colours printed on top, so never use them at full';
        $lines[] = 'strength in the backdrop - they are listed so your tints belong to the same family.';
        $lines[] = '';
        $lines[] = 'COMPOSITION:';
        $lines[] = '- The board sits across the whole picture, so keep the WHOLE picture quiet.';
        $lines[] = '  There is no safe area in the middle - ' . $cells . ' game spaces run corner to corner.';
        $lines[] = '- Put what little detail there is in the outermost 5% of the edges, and keep';
        $lines[] = '  the top 15% almost empty for the game title.';
        $lines[] = '- Do NOT draw any game board, path, stepping stones, numbered circles or squares.';
        $lines[] = '- Do NOT include any text, letters, numbers, logos or watermarks.';
        $lines[] = '';
        $lines[] = 'OUTPUT: one single image, full bleed, no borders, no frame, no margins.';

        return implode("\n", $lines);
    }

    /**
     * Prompt for a custom character set, for users who want their own instead
     * of one from the library. The pose count follows the plan (SRS section 9).
     */
    public static function character(array $project, ?string $plan, string $style = 'storybook'): string
    {
        $poses   = Tiers::characterPoses($plan);
        $theme   = (string) ($project['theme'] ?? 'forest');
        $styleEn = self::STYLE_EN[$style] ?? self::STYLE_EN['storybook'];
        $scene   = self::THEME_EN[$theme] ?? self::THEME_EN['forest'];

        $lines = [];
        $lines[] = 'Create a character sheet for a printable children board game.';
        $lines[] = '';
        $lines[] = 'CHARACTER: a friendly young explorer who belongs in ' . $scene . '.';
        $lines[] = 'STYLE: ' . $styleEn . '.';
        $lines[] = 'LAYOUT: ' . $poses . ' full-body poses of the SAME character, evenly spaced in a grid,';
        $lines[] = '        each pose fully separated with clear empty space around it.';
        $lines[] = '';
        $lines[] = 'POSES: standing and waving, walking forward, jumping with joy, thinking with a hand on chin,';
        $lines[] = '       pointing ahead, celebrating with both arms up, holding a map, sitting and resting.';
        $lines[] = '       (Use the first ' . $poses . ' of these.)';
        $lines[] = '';
        $lines[] = 'RULES:';
        $lines[] = '- Plain WHITE background, no scenery, no shadows on the ground.';
        $lines[] = '- Keep the face, hair colour, skin tone and outfit IDENTICAL in every pose.';
        $lines[] = '- No text, no numbers, no frames around the poses.';
        $lines[] = '- Thick clean outlines so the character stays readable when printed small.';

        return implode("\n", $lines);
    }

    /**
     * Prompt for a card back (move / mission / reward).
     * @param string $kind move | mission | reward
     */
    public static function cardBack(array $project, string $kind, string $style = 'storybook'): string
    {
        $theme   = (string) ($project['theme'] ?? 'forest');
        $styleEn = self::STYLE_EN[$style] ?? self::STYLE_EN['storybook'];
        $scene   = self::THEME_EN[$theme] ?? self::THEME_EN['forest'];

        $subject = [
            'move'    => 'a pair of footprints and a small compass',
            'mission' => 'a waving flag planted on a small hill',
            'reward'  => 'a golden trophy surrounded by sparkles',
        ][$kind] ?? 'a bright star';

        $lines = [];
        $lines[] = 'Create the BACK of a playing card for a printable children board game.';
        $lines[] = '';
        $lines[] = 'CENTRAL MOTIF: ' . $subject . '.';
        $lines[] = 'BACKGROUND: a simple decorative pattern inspired by ' . $scene . '.';
        $lines[] = 'STYLE: ' . $styleEn . '.';
        $lines[] = 'ASPECT RATIO: 5:7 portrait (standard playing card, about 750 x 1050 pixels).';
        $lines[] = '';
        $lines[] = 'RULES:';
        $lines[] = '- Symmetrical and evenly balanced, it must look the same from any side.';
        $lines[] = '- No text, no numbers, no logos.';
        $lines[] = '- Leave a clean margin of about 8% around the edge for print trimming.';
        $lines[] = '- Colours must stay consistent so all cards in the deck look like a set.';

        return implode("\n", $lines);
    }

    /**
     * Starter story and rules. This is pre-written content (step 1 of SRS
     * section 2.3 - the operations team prepares it with a Claude Project);
     * nothing calls an AI at runtime.
     */
    public static function storySeed(array $project): array
    {
        $theme = (string) ($project['theme'] ?? 'forest');
        $title = trim((string) ($project['title'] ?? 'The adventure'));
        $cells = MapComposer::normalizeCells((int) ($project['cells'] ?? 18));
        $hero  = trim((string) ($project['hero_name'] ?? '')) ?: 'our young hero';

        $openings = [
            'forest' => 'The old forest has fallen silent. Even the golden leaves have stopped falling, caught still in mid-air.',
            'dino'   => 'A great roar rolls up from the valley below. Somewhere down there, a baby dinosaur has lost its way.',
            'space'  => 'The space station sends out a distress call: a small planet is about to lose its light forever.',
            'ocean'  => 'The bright coral reef is fading to grey, and the little fish are calling out for help.',
            'pirate' => 'A weathered map washes ashore inside a bottle, promising a treasure the world forgot.',
            'magic'  => 'The magic flame in the old tower has gone out, and the whole kingdom is sinking into mist.',
            'castle' => 'The golden bell of the castle was stolen the night before the great festival.',
            'desert' => 'The only oasis in the desert is drying up a little more with every passing day.',
            'arctic' => 'The ice floe the penguins call home is melting far too quickly.',
            'candy'  => 'The chocolate river in Candy Land has frozen solid overnight.',
            'robot'  => 'The robot factory has lost power, and every machine has stopped mid-step.',
            'farm'   => 'All the animals on the farm vanished during one very windy night.',
        ];

        $opening = $openings[$theme] ?? $openings['forest'];
        $trouble = self::TROUBLE_EN[$theme] ?? self::TROUBLE_EN['forest'];

        $rescue = trim((string) ($project['rescue_target'] ?? ''))
               ?: (self::RESCUE_EN[$theme] ?? self::RESCUE_EN['forest']);

        $place = trim((string) ($project['setting'] ?? ''));

        /*
         * Four beats, in the order a read-aloud story wants them: the world and
         * what broke, who is waiting to be found, what makes the journey hard,
         * and what winning means. Long enough to be worth reading at bedtime.
         */
        $p1 = $opening;
        if ($place !== '') {
            $p1 .= ' All of it happens in ' . rtrim($place, '.') . ', a place that until this morning'
                 . ' had never given anyone a reason to worry.';
        }

        $p2 = 'Word travels quickly, and it reaches ' . $hero . ' before anyone else. '
            . 'Somewhere out there is ' . rtrim($rescue, '.') . ', waiting, with no idea whether help is coming. '
            . 'Nobody older is willing to go. So ' . $hero . ' packs a bag, says nothing to anybody, '
            . 'and leaves while the light is still good.';

        $p3 = 'The road ahead breaks into ' . $cells . ' stages, and not one of them lets you pass for free. '
            . $trouble . ' At every stage there is a question to answer, and answering well is the only way forward. '
            . 'Get one wrong and the road takes a step back from you - but it never closes.';

        $p4 = 'Reach the end and ' . rtrim($rescue, '.') . ' comes home, and the story of how it happened '
            . 'belongs to ' . $hero . ' from then on. That story is called "' . $title . '".';

        $story = implode("\n\n", [$p1, $p2, $p3, $p4]);

        /*
         * The rules follow whichever way this game moves. Only the star spaces
         * carry a question, and the penalty for getting one wrong is written on
         * the move card you drew - which is why the dice version needs its own
         * fixed penalty instead.
         */
        $byCards = Project::usesMoveCards($project);

        $howTo = implode("\n", array_merge(
            ['1. Each player picks a token and places it on the START space.'],
            $byCards
                ? ['2. On your turn, draw a move card and go forward the number of spaces on it. Keep the card in front of you.']
                : ['2. On your turn, roll the die and go forward that many spaces.'],
            [
                '3. Land on a space with a star and you draw a mission card from that space.',
                '   Land anywhere else and your turn simply ends.',
                '4. Answer the question. Get it right and you stay where you are.',
            ],
            $byCards
                ? ['5. Get it wrong and you go back by the penalty printed on the move card you drew.']
                : ['5. Get it wrong and you go back one space.'],
            [
                '6. Put the mission card back at the bottom of its pile'
                    . ($byCards ? ', and the move card at the bottom of its deck.' : '.'),
                '7. The first player to reach the FINISH space wins the hero card.',
                '',
                'You will need: '
                    . ($byCards
                        ? Difficulty::MOVE_CARDS_PER_GAME . ' move cards'
                        : 'the cut-out die')
                    . ', ' . $cells * Difficulty::MISSIONS_PER_CELL . ' mission cards, and '
                    . Difficulty::HERO_CARDS_PER_GAME . ' hero card.',
            ]
        ));

        return ['story' => $story, 'how_to_play' => $howTo];
    }

    /**
     * Short instructions shown next to the prompt, explaining what to do with it.
     */
    public static function instructions(): array
    {
        return [
            'Click "Copy prompt" below.',
            'Open an image generator such as ChatGPT or Google Gemini, paste the prompt and send it.',
            'Wait for the image, then download it to your computer.',
            'Come back here, click "Upload background" and choose that image.',
            'The system composes your image together with the map frame you picked.',
        ];
    }
}
