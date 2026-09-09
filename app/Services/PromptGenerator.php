<?php
namespace App\Services;

use App\Models\Project;
use App\Services\Lang;

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

    /** How the story starts, one for each theme */
    private const OPENING_EN = [
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

    /**
     * One theme phrase in the game's language, falling back to English.
     *
     * @param array<string, string> $english the table this class already holds
     */
    private static function themePhrase(string $group, string $theme, string $lang, array $english): string
    {
        $translated = Lang::raw('story.' . $group . '.' . $theme, $lang);

        if (is_string($translated) && $translated !== '') {
            return $translated;
        }

        return $english[$theme] ?? $english['forest'];
    }

    /**
     * Who comes along.
     *
     * A story read to a six-year-old needs somebody beside the hero to talk
     * to, and the companion is where nearly all the fun lives - so each one
     * is a small character with an opinion, not a description of an animal.
     */
    private const COMPANION_EN = [
        'forest' => 'An old owl announces that she is coming as far as the second bend and no further. She comes the whole way instead, complaining about the weather at every single stage.',
        'dino'   => 'A small and extremely loud pterosaur appoints itself lookout. It has never once spotted anything useful, but it is never, ever quiet, which turns out to be almost as good.',
        'space'  => 'The station sends along a repair drone with one working eye and a habit of humming. It knows the route to exactly one planet and is fairly sure it is the right one.',
        'ocean'  => 'A grumpy old crab agrees to come on the strict condition that nobody mentions how slowly he swims. Nobody mentions it. He keeps up far better than anyone expects.',
        'pirate' => 'The ship parrot volunteers first, mostly because it has the map by heart and cannot bear to miss the part where somebody reads it out loud.',
        'magic'  => 'A candle stub that refuses to go out bobs along behind, lighting entirely the wrong things at entirely the wrong moments and being enormously proud of itself.',
        'castle' => 'The castle cat comes too. She has been in every room, under every floor and behind every curtain in the place, and she remembers all of them.',
        'desert' => 'A young camel with strong opinions about walking joins at the gate. She stops when she likes and starts again when she likes, and she has never once been lost.',
        'arctic' => 'A round little seal follows from the first stage on, sliding ahead on her belly to test the ice and coming back each time to say whether it will hold.',
        'candy'  => 'A gingerbread mouse comes as guide. He nibbles a notch in every signpost so the way home will be easy to find, and eats a good many signposts entirely.',
        'robot'  => 'A rusty sweeper robot trundles out of a side door and simply joins in. Its map is forty years out of date, but it knows a shortcut, and the shortcut is real.',
        'farm'   => 'The farm dog needs no invitation whatsoever. He has been waiting at the gate since sunrise with his nose pointed down the road, entirely certain which way to go.',
    ];

    /**
     * The last stretch: the world answering back, just before the end.
     *
     * The picture each one paints is the first sign that the journey worked,
     * so it echoes that theme's opening - the leaves that stopped falling
     * start again, the frozen river creaks - and never states the ending.
     */
    private const FINAL_EN = [
        'forest' => 'By the last stage the trees have begun leaning in to listen, and the golden leaves that stopped falling this morning start, very slowly, to fall again.',
        'dino'   => 'At the last stage the ground goes quiet at last, and from somewhere beyond the ridge comes a small roar - not a frightened one this time, but a hopeful one.',
        'space'  => 'At the last stage the little planet is close enough to see: one dim light in all that dark, flickering like something trying very hard to stay awake.',
        'ocean'  => 'At the last stage a thin line of colour creeps back into the coral, and the smallest fish swim out through it to meet the travellers.',
        'pirate' => 'At the last stage the torn map runs out altogether, and the way ahead has to be worked out from the shape of the coast and nothing else at all.',
        'magic'  => 'At the last stage the mist thins away to nothing, and the old tower is standing there waiting, dark and patient, with one cold lamp at the very top.',
        'castle' => 'At the last stage the festival flags are already flying, and the whole kingdom is standing about in the square waiting for a bell that has not rung yet.',
        'desert' => 'At the last stage the wind drops, the sand settles, and the green of the oasis appears on the horizon exactly where the map promised it would be.',
        'arctic' => 'At the last stage the daylight comes back for one more hour, and one hour is just enough to make out a small dark shape on a small white floe.',
        'candy'  => 'At the last stage the frozen chocolate river creaks once, very softly, the way ice does when it has quietly decided to start melting.',
        'robot'  => 'At the last stage one light comes on deep inside the factory, and then two, and then a whole row of them, as though the building were waking up to watch.',
        'farm'   => 'At the last stage a single hoofprint appears in the mud, and then another, and they lead somewhere at last instead of everywhere at once.',
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
        $scene  = Project::sceneFor($project['setting'] ?? null);
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
        $lang  = Lang::of($project);
        $theme = (string) ($project['theme'] ?? 'forest');
        $title = trim((string) ($project['title'] ?? 'The adventure'));
        $cells = MapComposer::normalizeCells((int) ($project['cells'] ?? 18));
        $hero  = trim((string) ($project['hero_name'] ?? ''))
              ?: Lang::get('story.hero_default', $lang);

        /*
         * The three phrases that change with the theme. English lives in the
         * tables above, which the picture prompts also read; the other
         * languages carry their own in app/lang, and fall back to English if
         * one is missing rather than leaving a hole in the page.
         */
        $opening   = self::themePhrase('opening', $theme, $lang, self::OPENING_EN);
        $trouble   = self::themePhrase('trouble', $theme, $lang, self::TROUBLE_EN);
        $companion = self::themePhrase('companion', $theme, $lang, self::COMPANION_EN);
        $final     = self::themePhrase('final', $theme, $lang, self::FINAL_EN);

        $rescue = trim((string) ($project['rescue_target'] ?? ''))
               ?: self::themePhrase('rescue', $theme, $lang, self::RESCUE_EN);

        $place = Project::sceneFor($project['setting'] ?? null, $lang);

        /*
         * Seven beats, in the order a read-aloud story wants them: the world
         * and what broke, who is waiting to be found, setting out and who
         * comes along, what makes the journey hard, what a wrong answer
         * really costs, the last stretch, and what winning means.
         *
         * Long enough to be worth reading at bedtime and short enough to fit
         * the printed page under the picture - about 350 words, which is one
         * sheet with the story sheet's type size.
         *
         * A wrong answer gets a beat of its own on purpose. It is the moment
         * a six-year-old is most likely to give up, and the story says the
         * same thing the rules do, in words a child hears rather than reads.
         */
        $p1 = $opening;
        if ($place !== '') {
            $p1 .= ' ' . Lang::get('story.place', $lang, ['place' => rtrim($place, '.')]);
        }

        $p2 = Lang::get('story.p2', $lang, ['hero' => $hero, 'rescue' => rtrim($rescue, '.')]);
        $p3 = Lang::get('story.setout', $lang, ['hero' => $hero]) . ' ' . $companion;
        $p4 = Lang::get('story.p3', $lang, ['cells' => $cells, 'trouble' => $trouble]);
        $p5 = Lang::get('story.wrong', $lang);
        $p6 = $final . ' ' . Lang::get('story.last', $lang, [
            'hero'   => $hero,
            'rescue' => rtrim($rescue, '.'),
        ]);
        $p7 = Lang::get('story.p4', $lang, [
            'hero'   => $hero,
            'rescue' => rtrim($rescue, '.'),
            'title'  => $title,
        ]);

        $story = implode("\n\n", [$p1, $p2, $p3, $p4, $p5, $p6, $p7]);

        /*
         * The rules follow whichever way this game moves. Only the star spaces
         * carry a question, and the penalty for getting one wrong is written on
         * the move card you drew - which is why the dice version needs its own
         * fixed penalty instead.
         *
         * What to prepare is deliberately not here: the sheet prints that under
         * the rules, counted from the game itself.
         */
        $byCards = Project::usesMoveCards($project);
        $rule    = fn (string $key) => Lang::get('rules.' . $key, $lang);

        $howTo = implode("\n", [
            '1. ' . $rule('start'),
            '2. ' . $rule($byCards ? 'move_cards' : 'move_dice'),
            '3. ' . $rule('star'),
            '4. ' . $rule('answer'),
            '5. ' . $rule($byCards ? 'wrong_cards' : 'wrong_dice'),
            '6. ' . $rule($byCards ? 'return_cards' : 'return_dice'),
            '7. ' . $rule('win'),
        ]);

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
