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

    /**
     * The languages by their English names.
     *
     * Lang::LOCALES holds what each language calls itself, which is right for
     * a picker and wrong inside an English prompt: "Write in Français" reads
     * like a mistake where "Write in French" reads like an instruction.
     */
    private const LANGUAGE_EN = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
    ];

    /**
     * The scene for each theme, when the buyer left the place blank.
     *
     * Empty landscapes, every one of them: no creatures and nothing glowing.
     * The backdrop prompt asks for scenery with nobody in it, and these are
     * the words it hands over - a line here that mentions playful penguins is
     * an instruction to draw playful penguins, whatever the rest of it says.
     */
    private const THEME_EN = [
        'forest' => 'a quiet old forest of tall trees, mossy rocks and a winding dirt trail',
        'dino'   => 'a prehistoric valley of giant ferns, fallen logs and volcanoes far away',
        'space'  => 'an open stretch of space with distant planets and drifting asteroids',
        'ocean'  => 'a shallow underwater scene of coral, rippled sand and seagrass',
        'pirate' => 'a tropical cove with palm trees, a wooden jetty and a sandy beach',
        'magic'  => 'a wizard realm of floating rocks, tall toadstools and pale drifting mist',
        'castle' => 'a storybook kingdom of castle towers, rolling green hills and banners',
        'desert' => 'a desert canyon of cacti, sandstone arches and a quiet oasis pool',
        'arctic' => 'a polar landscape of snow drifts, icebergs and long low hills',
        'candy'  => 'a candy land of lollipop trees, a chocolate river and gumdrop hills',
        'robot'  => 'a factory city of pipes, gantries, gears and quiet empty walkways',
        'farm'   => 'a countryside farm of red barns, vegetable patches and haystacks',
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
     * The scene comes from the place the buyer chose. The theme only supplies
     * the colours and a fallback scene for when they left the place blank, so
     * the picture follows their words while still matching the frame and the
     * cards printed alongside it.
     *
     * Two things this prompt is built around, both learned from a real one:
     *
     * It used to offer to hint at the rescue somewhere in the scenery, and
     * came back with an owl and a rabbit looking at the camera - beside the
     * printed characters, which are drawn in a different hand entirely. It
     * asks for empty scenery now, and says so four ways.
     *
     * And everything here pushes towards a PALE, even picture. A board with
     * white spaces and bold outlines is laid over the whole thing, and a
     * vivid backdrop wins that fight every time.
     *
     * @param array  $project Project record (setting, theme, cells)
     * @param string $style   A key from STYLES
     */
    public static function background(array $project, string $style = 'storybook'): string
    {
        $theme = Project::artTheme($project);
        $cells = MapComposer::normalizeCells((int) ($project['cells'] ?? 18));
        $scene = Project::sceneFor($project['setting'] ?? null);

        // Nothing typed -> fall back to the theme's own scene
        if ($scene === '') {
            $scene = self::THEME_EN[$theme] ?? self::THEME_EN['forest'];
        }

        $styleEn = self::STYLE_EN[$style] ?? self::STYLE_EN['storybook'];
        $palette = Art::palette($theme);

        $lines = [];
        $lines[] = 'Draw an EMPTY LANDSCAPE to print behind a children\'s board game.';
        $lines[] = '';
        $lines[] = 'PLACE: ' . rtrim($scene, '.') . '.';
        $lines[] = 'STYLE: ' . $styleEn . '.';
        $lines[] = 'ASPECT RATIO: 16:11 landscape (about 1600 x 1100 pixels).';
        $lines[] = '';
        $lines[] = 'NOBODY IS IN IT';
        $lines[] = 'This is scenery and nothing else. Empty countryside, with no one in sight.';
        $lines[] = '- No people, no children, no faces, no figures of any kind.';
        $lines[] = '- No animals, no birds, no fish, no creatures, no toys, no dolls.';
        $lines[] = '- Nothing with eyes, and nothing that looks back at the viewer.';
        $lines[] = 'The characters are printed on top as separate artwork. Anything alive that you';
        $lines[] = 'draw will end up sitting next to them, and the two never match.';
        $lines[] = '';
        $lines[] = 'THIS IS THE MOST IMPORTANT INSTRUCTION - IT MUST SINK BACK';
        $lines[] = 'The board is printed over the whole of this picture: ' . $cells . ' white and coloured';
        $lines[] = 'spaces, bold outlines, numbers, a title. Every one of those has to be the first';
        $lines[] = 'thing the eye finds. Your picture is the paper they are printed on, not a scene';
        $lines[] = 'to be looked at. A white wash is laid over it before printing, so anything you';
        $lines[] = 'draw at full strength arrives at about half strength anyway.';
        $lines[] = '- Draw everything as a soft, faded wash, like a watercolour left out in the sun.';
        $lines[] = '- Pale, desaturated tints only. Nothing darker than a light mid-tone.';
        $lines[] = '- No black, no strong outlines, no heavy shadows, no deep saturated colour.';
        $lines[] = '- No glow, no sparkles, no lanterns, no sunbeams, no bright light sources.';
        $lines[] = '  A bright spot survives the wash and pulls the eye straight off the board.';
        $lines[] = '- Low contrast everywhere. Two neighbouring areas should differ only slightly.';
        $lines[] = '- If in doubt, make it lighter. An almost-empty picture is the right answer.';
        $lines[] = '';
        $lines[] = 'COLOUR: tint the picture towards this palette, using only its palest versions:';
        $lines[] = '  ' . implode('  ', $palette);
        $lines[] = 'The darker entries are the colours printed on top, so never use them at full';
        $lines[] = 'strength in the backdrop - they are listed so your tints belong to the same family.';
        $lines[] = '';
        $lines[] = 'ONE EVEN PICTURE, EDGE TO EDGE';
        $lines[] = 'The board covers the middle and reaches the corners, so there is no quiet corner';
        $lines[] = 'to hide the interesting part in. Spread the same soft, quiet texture across the';
        $lines[] = 'whole sheet instead.';
        $lines[] = '- No subject and no focal point. Nothing the eye lands on, anywhere.';
        $lines[] = '- Do not make one side busy and the other empty, and do not leave a bright open';
        $lines[] = '  middle - the middle is where most of the spaces sit.';
        $lines[] = '- No hard horizon and no sharp line running across the picture. It reads as a';
        $lines[] = '  seam once the board is laid over it.';
        $lines[] = '- Keep the top 15% the emptiest part of all: the game title is printed there.';
        $lines[] = '- Carry the picture right to all four edges. No border, no frame, no vignette,';
        $lines[] = '  no dark corners, no paper texture, no rounded corners - the game prints its';
        $lines[] = '  own frame on top and a drawn one comes out as a fragment underneath it.';
        $lines[] = '- Do NOT draw any game board, path, stepping stones, numbered circles or squares.';
        $lines[] = '- Do NOT include any text, letters, numbers, logos or watermarks.';
        $lines[] = '';
        $lines[] = 'OUTPUT: one single image, full bleed, no margins.';

        return implode("\n", $lines);
    }

    /**
     * Prompt for a custom character set, for users who want their own instead
     * of one from the library. The pose count follows the plan (SRS section 9).
     */
    public static function character(array $project, ?string $plan, string $style = 'storybook'): string
    {
        $poses   = Tiers::characterPoses($plan);
        $theme   = Project::artTheme($project);
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
        $theme   = Project::artTheme($project);
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
     * The prompt the buyer runs to get their story.
     *
     * What it asks for is the OPENING - why the journey is being made - and
     * not the journey. The journey is the game: it happens at the table, and
     * a story that has already crossed the last stage and brought everybody
     * home has told the children how their own afternoon turns out.
     *
     * The app used to write the story itself, out of a few hundred stock
     * sentences. It read well, but two games in the same world could only ever
     * be rearrangements of each other - and it could never use the name of the
     * child at the table, or this term's topic, or the family dog.
     *
     * So the story is written the same way the background picture is: the app
     * writes the brief, the buyer runs it in whichever AI tool they like, and
     * pastes the result back. Nothing here calls an AI, and no key is needed.
     *
     * The prompt is in English whatever the game's language, and asks for the
     * story in that language. That is how the picture prompts already work,
     * and it leaves one prompt to look after rather than four.
     */
    public static function story(array $project): string
    {
        $lang  = Lang::of($project);
        $cells = MapComposer::normalizeCells((int) ($project['cells'] ?? 18));
        $title = trim((string) ($project['title'] ?? ''));
        $hero  = trim((string) ($project['hero_name'] ?? ''));
        $rescue = trim((string) ($project['rescue_target'] ?? ''));

        // What the buyer chose at step 1, in their own words where they wrote any
        $scene = Project::sceneFor($project['setting'] ?? null);
        if ($scene === '') {
            $scene = self::THEME_EN[Project::artTheme($project)] ?? self::THEME_EN['forest'];
        }

        $ageMin = (int) ($project['age_min'] ?? 6);
        $ageMax = (int) ($project['age_max'] ?? 9);

        $lines = [];
        $lines[] = 'Write the opening of a story for a printable children\'s board game.';
        $lines[] = '';
        $lines[] = 'THE GAME';
        $lines[] = '  Title: ' . ($title !== '' ? $title : 'not chosen yet - do not invent one');
        $lines[] = '  Where it happens: ' . rtrim($scene, '.');
        $lines[] = '  The hero: ' . ($hero !== ''
            ? $hero . ' - use this name, and do not change its spelling'
            : 'not named. Call the hero "our young hero" and never invent a name');
        $lines[] = '  Who needs rescuing: ' . ($rescue !== ''
            ? rtrim($rescue, '.')
            : 'not decided - choose someone small who belongs in this place');
        $lines[] = '  The journey: ' . $cells . ' stages, each with a question waiting on it';
        $lines[] = '  Read aloud by an adult to children aged ' . $ageMin . ' to ' . $ageMax;
        $lines[] = '';
        $lines[] = 'HOW TO WRITE IT';
        $language = self::LANGUAGE_EN[$lang] ?? self::LANGUAGE_EN['en'];
        $lines[] = '- Write in ' . $language . '. Every word of the story must be in ' . $language . '.';
        $lines[] = '- Between 280 and 380 words. It has to fit one printed page, so do not run over.';
        $lines[] = '- Five or six short paragraphs, each separated by a blank line.';
        $lines[] = '- Plain text only. No title, no headings, no bullet points, no bold, no markdown.';
        $lines[] = '- Write for the ear, not the eye: short sentences, things a child can picture,';
        $lines[] = '  and room for a joke or two. It is read out loud.';
        $lines[] = '';
        $lines[] = 'THIS IS THE MOST IMPORTANT INSTRUCTION';
        $lines[] = 'Write the OPENING of the adventure, not the adventure. This page is read out';
        $lines[] = 'once, before anybody moves, and it exists to explain why the journey is being';
        $lines[] = 'made at all. The journey itself is what the children then play, so it has not';
        $lines[] = 'happened yet and you must not tell it. Stop at the moment the hero leaves.';
        $lines[] = '';
        $lines[] = 'WHAT THE OPENING COVERS, IN THIS ORDER';
        $lines[] = '1. Something has gone wrong where the story happens. Say it in the first sentence.';
        $lines[] = '2. What that means for this place, and for whoever lives in it. Make it matter,';
        $lines[] = '   without making it frightening.';
        $lines[] = '3. Who is waiting to be found - out there somewhere, not knowing if anyone is';
        $lines[] = '   coming. The news reaches the hero, and no grown-up will go.';
        $lines[] = '4. The hero decides to go, and somebody comes along. Give this companion a';
        $lines[] = '   personality and one funny habit - this is the character a child remembers.';
        $lines[] = '5. What is known about the road ahead, and only what is known before setting';
        $lines[] = '   off: that it runs in ' . $cells . ' stages, that a question waits at each of them,';
        $lines[] = '   and what makes this particular place hard to cross. Mention, warmly and in';
        $lines[] = '   passing, that getting a question wrong costs a step and nothing more - the';
        $lines[] = '   only way to lose is to stop. A child who gets one wrong will remember this.';
        $lines[] = '6. The last line is the hero setting off, and it hands the story over to whoever';
        $lines[] = '   is about to play. What happens next is theirs.';
        $lines[] = '';
        $lines[] = 'KEEP OUT';
        $lines[] = '- Do not travel the road. No stages crossed, no questions answered, no obstacles';
        $lines[] = '  overcome, nothing found along the way. None of that has happened yet.';
        $lines[] = '- Do not say how it ends. Whether the rescue works is decided at the table, and';
        $lines[] = '  a story that gives the ending away takes the game with it.';
        $lines[] = '- No homecoming, no celebration, no "and from that day". The story stops at the';
        $lines[] = '  gate, on the way out.';
        $lines[] = '- Do not explain how the game is played. No cards, no dice, no counting spaces,';
        $lines[] = '  no turns. A separate printed page does all of that.';
        $lines[] = '- Nothing frightening: no injury, no death, nobody in real danger.';
        $lines[] = '- No moral and no lesson spelled out. The story is the point.';
        $lines[] = '';
        $lines[] = 'OUTPUT: the opening and nothing else - no preamble, no notes, no closing remark.';

        return implode("\n", $lines);
    }

    /**
     * The numbered rules for this game.
     *
     * These stay written by the app rather than by an AI: they have to match
     * the deck that actually comes out of the printer - whether it moves on
     * cards or a die, what a wrong answer costs, where a used card goes - and
     * a story tool cannot be relied on to get that right every time.
     *
     * What to prepare is deliberately not here: the sheet prints that under
     * the rules, counted from the game itself.
     */
    public static function rules(array $project): string
    {
        $lang    = Lang::of($project);
        $byCards = Project::usesMoveCards($project);
        $rule    = fn (string $key) => Lang::get('rules.' . $key, $lang);

        return implode("\n", [
            '1. ' . $rule('start'),
            '2. ' . $rule($byCards ? 'move_cards' : 'move_dice'),
            '3. ' . $rule('star'),
            '4. ' . $rule('answer'),
            // The penalty and its one exception belong together, not as two steps
            '5. ' . $rule($byCards ? 'wrong_cards' : 'wrong_dice') . ' ' . $rule('back_star'),
            '6. ' . $rule($byCards ? 'return_cards' : 'return_dice'),
            '7. ' . $rule('win'),
        ]);
    }

    /**
     * Short instructions shown next to the prompt, explaining what to do with it.
     */
    public static function instructions(): array
    {
        return [
            'Open ChatGPT or Google Gemini in another tab.',
            'Copy the map prompt, paste it there, and download the picture it draws.',
            'Copy the story prompt into the same chat and let it write the story.',
            'Come back here: upload the picture, and paste the story into the box.',
            'The picture is composed with the map frame you picked; the story is printed as it stands.',
        ];
    }
}
