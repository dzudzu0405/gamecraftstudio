<?php
namespace App\Services;

/**
 * Builds the map background prompts (FR-30, step 2 of SRS section 2.3).
 *
 * This class only writes the prompt text. Today the buyer copies it, runs it
 * in an image generator and uploads the result back.
 */
class PromptGenerator
{
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
     * @param array  $project Project record (theme, cells, title, difficulty)
     * @param string $style   A key from STYLES
     */
    public static function background(array $project, string $style = 'storybook'): string
    {
        $theme = (string) ($project['theme'] ?? 'forest');
        $cells = MapComposer::normalizeCells((int) ($project['cells'] ?? 18));
        $title = trim((string) ($project['title'] ?? ''));

        $scene   = self::THEME_EN[$theme] ?? self::THEME_EN['forest'];
        $styleEn = self::STYLE_EN[$style] ?? self::STYLE_EN['storybook'];

        $lines = [];
        $lines[] = 'Create a horizontal background illustration for a printable children board game map.';
        $lines[] = '';
        $lines[] = 'SCENE: ' . $scene . '.';
        $lines[] = 'STYLE: ' . $styleEn . '. Bright, cheerful, age-appropriate for children.';
        $lines[] = 'ASPECT RATIO: 16:11 landscape (about 1600 x 1100 pixels).';
        $lines[] = '';
        $lines[] = 'IMPORTANT COMPOSITION RULES:';
        $lines[] = '- Do NOT draw any game board, path, stepping stones, numbered circles or squares.';
        $lines[] = '  Those will be added later by software on top of this image.';
        $lines[] = '- Do NOT include any text, letters, numbers, logos or watermarks.';
        $lines[] = '- Keep the MIDDLE of the image visually calm and uncluttered, so ' . $cells
                 . ' numbered game spaces placed across the centre stay easy to read.';
        $lines[] = '- Put the most detailed elements near the outer edges and corners.';
        $lines[] = '- Avoid very dark areas and avoid busy high-contrast patterns in the centre.';
        $lines[] = '- Leave a calm horizontal band across the top for the game title.';
        $lines[] = '';
        $lines[] = 'OUTPUT: one single image, full bleed, no borders, no frame, no margins.';

        if ($title !== '') {
            $lines[] = '';
            $lines[] = '(Reference only, do not write it on the image - the game is called "' . $title . '".)';
        }

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

        $story = $opening . "\n\n"
               . 'And so ' . $hero . ' sets out. Ahead lie ' . $cells . ' stages of the journey, each hiding a '
               . 'challenge of its own. Get through them all and ' . $hero
               . ' becomes the hero of "' . $title . '".';

        $howTo = implode("\n", [
            '1. Each player picks a token and places it on the START space.',
            '2. On your turn, draw a move card and move forward the number of spaces shown.',
            '3. Wherever you land, draw a mission card from that space and answer the question.',
            '4. Answer correctly and you stay put. Answer incorrectly and you move back one space.',
            '5. Put the mission card back at the bottom of its pile once you are done.',
            '6. The first player to reach the FINISH space wins the hero card.',
            '',
            'You will need: ' . Difficulty::MOVE_CARDS_PER_GAME . ' move cards, '
                . $cells * Difficulty::MISSIONS_PER_CELL . ' mission cards, and '
                . Difficulty::HERO_CARDS_PER_GAME . ' hero card.',
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
