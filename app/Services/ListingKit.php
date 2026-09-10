<?php
namespace App\Services;

use App\Core\Helper;
use App\Models\Project;

/**
 * The product kit: titles, tags and keywords for Amazon and Etsy (FR-32).
 *
 * Publisher plan only - the two tiers below it sell nothing, so they are
 * never shown this. ExportController checks that before it calls in here,
 * and available() is the same check for anywhere else that needs it.
 *
 * What this class is for is the part a seller gets wrong on their own: a
 * marketplace title is not a sentence, it is a row of search phrases with
 * the strongest one first, and every marketplace counts characters its own
 * way. Etsy stops a title at 140 and a tag at 20; Amazon allows 200 in the
 * title and gives seven backend boxes of 50.
 *
 * Two rules run through all of it, both learned the hard way:
 *
 * A word already in the title is spent. Amazon indexes the title and the
 * backend boxes together, so repeating "printable" in both wastes one of
 * only seven boxes. Everything here is filtered against the title it will
 * sit beside.
 *
 * And a tag list that is the same on every listing sets a seller's own
 * products competing with each other. So the fixed part is kept small and
 * the rest is drawn from a pool by the project's seed - two games from the
 * same account come out with overlapping, not identical, tags.
 */
class ListingKit
{
    /** Etsy: title length, tag length, number of tags */
    public const ETSY_TITLE_MAX = 140;
    public const ETSY_TAG_MAX   = 20;
    public const ETSY_TAG_COUNT = 13;

    /** Amazon: title length, then seven backend boxes of fifty */
    public const AMAZON_TITLE_MAX = 200;
    public const KEYWORD_SLOTS    = 7;
    public const KEYWORD_MAX      = 50;

    /**
     * Tags every listing of this kind wants, whatever the game is about.
     *
     * Deliberately short. These four are what a buyer actually types, so
     * they earn their place on all of them; the rest of the thirteen comes
     * out of the pool below and differs from one game to the next.
     */
    private const CORE_TAGS = [
        'printable board game',
        'instant download',
        'print at home',
        'family game night',
    ];

    /**
     * The rest of the tags, drawn from here by seed.
     *
     * Written as things a shopper searches for rather than things a seller
     * would say: nobody searches "educational product", they search "rainy
     * day activity" at four in the afternoon with the children indoors.
     */
    private const TAG_POOL = [
        'homeschool game',
        'classroom game',
        'kids party game',
        'road trip game',
        'diy board game',
        'educational game',
        'screen free play',
        'rainy day activity',
        'birthday activity',
        'learning through play',
        'teacher resource',
        'kids activity pdf',
        'quiet time activity',
        'travel game kids',
        'indoor games kids',
        'sleepover activity',
    ];

    /**
     * Where the Amazon backend words come from.
     *
     * Written as bare search terms, not sentences. The boxes are packed word
     * by word, so a phrase like "educational game that teaches while
     * playing" arrives as "that", "while" and "playing" sitting in a box on
     * their own, and "after school activity" loses the half that made it
     * mean something.
     *
     * These are the occasions and the buyers rather than the product - the
     * product is already all over the title.
     */
    private const KEYWORD_POOL = [
        'homeschool learning',
        'classroom reward teacher',
        'screen free',
        'rainy day indoor',
        'birthday party favour',
        'travel car journey',
        'quiet time toddler',
        'afterschool club',
        'preschool kindergarten',
        'sleepover camping',
        'summer holiday',
        'grandparents babysitter',
    ];

    /**
     * Words that match every search and so distinguish none of them.
     *
     * They cost nothing inside a title, where they hold a phrase together
     * and a shopper reads them. They cost fifty characters of backend box
     * where nobody does.
     */
    private const STOP_WORDS = [
        'a', 'an', 'the', 'and', 'or', 'but', 'if', 'so', 'as',
        'for', 'to', 'at', 'in', 'of', 'on', 'by', 'from', 'into', 'with',
        'is', 'are', 'be', 'it', 'its', 'this', 'that', 'these', 'those',
        'while', 'when', 'where', 'who', 'what', 'how', 'then', 'than',
        'your', 'you', 'they', 'them', 'all', 'any', 'each', 'every',
        'more', 'most', 'some', 'such', 'only', 'own', 'same', 'too', 'very',
        'can', 'will', 'just', 'do', 'does', 'did', 'has', 'have', 'had',
    ];

    /** Whether this plan may see any of it */
    public static function available(?string $plan): bool
    {
        return Tiers::canPublishToMarketplace($plan);
    }

    /**
     * Everything the listing page shows, drafted from the project.
     *
     * @param array  $project      Project record
     * @param int    $missionCount Mission cards the game actually has
     * @param string $channel      etsy | amazon - decides which title leads
     */
    public static function draft(array $project, int $missionCount, string $channel = 'etsy'): array
    {
        $channel = $channel === 'amazon' ? 'amazon' : 'etsy';

        $etsyTitle   = self::etsyTitle($project);
        $amazonTitle = self::amazonTitle($project);

        return [
            'title'            => $channel === 'amazon' ? $amazonTitle : $etsyTitle,
            'etsy_title'       => $etsyTitle,
            'amazon_title'     => $amazonTitle,
            'bullet_points'    => implode("\n", self::bullets($project, $missionCount)),
            'description'      => self::description($project, $missionCount),
            'tags'             => implode(', ', self::tags($project)),
            'backend_keywords' => implode("\n", self::backendKeywords($project, $amazonTitle)),
            'price'            => '4.99',
        ];
    }

    // ---------------------------------------------------------------
    //  Titles
    // ---------------------------------------------------------------

    /**
     * The Etsy title: search phrases in a row, strongest first.
     *
     * Etsy reads a title left to right and weights the front of it, so the
     * game's own subject leads and the boilerplate every printable shares
     * ("Instant Download") sits at the back where it costs nothing.
     *
     * A phrase already covered by the front of the title is dropped rather
     * than repeated - 140 characters go quickly, and "board game" twice buys
     * nothing the first one did not.
     */
    public static function etsyTitle(array $project): string
    {
        $f = self::facts($project);

        $lead = self::titleCase($f['subject']) . ' Printable Board Game';

        /*
         * Candidates in the order they earn their place, and more of them
         * than will fit. Etsy ranks a listing on the phrases its title
         * matches, so a title that stops at 106 of its 140 characters has
         * simply thrown the last thirty away - the loop below keeps taking
         * until the next one will not fit.
         *
         * "Standard Level" sits near the bottom because nobody searches it.
         * It goes in when there is room and drops out when there is not,
         * which is the right way round for a phrase that describes the
         * product accurately and sells nothing.
         */
        /*
         * The age range always comes second - it is the filter a parent
         * actually shops by - and the rest is rotated by the project's seed.
         *
         * Without the rotation every game in the shop ends up wearing the
         * same tail: "..., Family Game Night, Homeschool Activity, Print at
         * Home, Classroom Game". Etsy then has a dozen near-identical titles
         * from one seller to choose between, and the seller has made their
         * own listings compete.
         */
        $rest = [
            'Family Game Night',
            'Homeschool Activity',
            'Print at Home',
            'Classroom Game',
            'Screen Free Play',
            'Kids Party Game',
            'Rainy Day Activity',
            self::titleCase($f['difficulty']) . ' Level',
            'Instant Download PDF',
        ];

        $turn  = self::seed($project) % count($rest);
        $rest  = array_merge(array_slice($rest, $turn), array_slice($rest, 0, $turn));
        $parts = array_merge(['Kids Ages ' . $f['ages']], $rest);

        $seen = self::wordsOf($lead);
        $kept = [$lead];
        $len  = mb_strlen($lead);

        foreach ($parts as $part) {
            $words = self::wordsOf($part);

            // Nothing new in it -> it is only spending characters
            if ($words && !array_diff($words, $seen)) {
                continue;
            }

            $cost = mb_strlen($part) + 2; // the ", " it arrives with

            if ($len + $cost > self::ETSY_TITLE_MAX) {
                continue;
            }

            $kept[] = $part;
            $len   += $cost;
            $seen   = array_merge($seen, $words);
        }

        return self::clip(implode(', ', $kept), self::ETSY_TITLE_MAX);
    }

    /**
     * The Amazon title: one line, pipe-separated, room for the detail Etsy
     * has no space for.
     */
    public static function amazonTitle(array $project): string
    {
        $f = self::facts($project);

        $title = trim((string) ($project['title'] ?? ''));
        $lead  = $title !== '' ? $title : self::titleCase($f['subject']);

        $line = $lead
            . ' - Printable Adventure Board Game for Kids Ages ' . $f['ages']
            . ' | ' . $f['cells'] . ' Mission Spaces'
            . ' | ' . self::titleCase($f['difficulty']) . ' Level'
            . ' | ' . $f['players']
            . ' | Instant Download PDF';

        return self::clip($line, self::AMAZON_TITLE_MAX);
    }

    // ---------------------------------------------------------------
    //  Tags and keywords
    // ---------------------------------------------------------------

    /**
     * Thirteen Etsy tags: four fixed, the rest by seed.
     *
     * Every one of them is put through sanitiseTag() before it is returned,
     * because a tag Etsy rejects is worse than a tag nobody searches - the
     * seller pastes thirteen in, gets an error on three, and has no idea
     * which. Theme names are the usual culprit: "Insects & Butterflies" is
     * both too long and contains a character Etsy will not take.
     *
     * @return array<int, string>
     */
    public static function tags(array $project): array
    {
        $f    = self::facts($project);
        $seed = self::seed($project);

        // What this particular game is about, which no other listing has
        $own = [
            $f['subject'] . ' game',
            $f['theme'] . ' board game',
            // "ages 6 to 9" - the hyphen goes, but "ages 6 9" is not a search
            'ages ' . str_replace('-', ' to ', $f['ages']),
            $f['difficulty'] . ' board game',
        ];

        $tags = array_merge($own, self::CORE_TAGS);

        // Fill the remainder from the pool, starting at a point this project owns
        $pool  = self::TAG_POOL;
        $count = count($pool);
        $start = $seed % $count;

        for ($i = 0; $i < $count && count($tags) < self::ETSY_TAG_COUNT + 4; $i++) {
            $tags[] = $pool[($start + $i) % $count];
        }

        /*
         * Thirteen tags are thirteen chances to match a different search, and
         * the word "game" only needs indexing once. Left alone, ten of these
         * carry it and the shopper looking for a rainy day activity never
         * reaches the listing.
         *
         * So a tag that brings no word the others have not already brought is
         * held back, and taken only if there is nothing better left. The four
         * that describe this particular game always go in first - they are
         * the ones no other listing has.
         */
        $clean   = [];
        $seen    = [];
        $held    = [];
        $keeping = count($own);

        foreach ($tags as $index => $tag) {
            $tag = self::sanitiseTag($tag);

            if ($tag === '' || in_array($tag, $clean, true) || in_array($tag, $held, true)) {
                continue;
            }

            $words = self::wordsOf($tag);

            if ($index >= $keeping && $words && !array_diff($words, $seen)) {
                $held[] = $tag;
                continue;
            }

            $clean[] = $tag;
            $seen    = array_merge($seen, $words);

            if (count($clean) === self::ETSY_TAG_COUNT) {
                return $clean;
            }
        }

        // Short of thirteen: the held-back ones are better than nothing
        foreach ($held as $tag) {
            $clean[] = $tag;

            if (count($clean) === self::ETSY_TAG_COUNT) {
                break;
            }
        }

        return $clean;
    }

    /**
     * Seven backend keyword phrases for Amazon, none of them repeating the
     * title they sit beside.
     *
     * Amazon indexes the title and these together. A phrase whose every word
     * is already in the title adds nothing, so it is dropped and the next
     * one from the pool takes its place. Nothing is padded: seven boxes of
     * fifty characters is the most valuable search real estate a book has,
     * and filling one with "farm printable activity 5" throws it away.
     *
     * @return array<int, string>
     */
    public static function backendKeywords(array $project, string $title): array
    {
        $f    = self::facts($project);
        $seed = self::seed($project);

        /*
         * A backend box is not a sentence and nobody ever reads one.
         *
         * Amazon pours the title and all seven boxes into one bag of words
         * and matches searches against the bag, so "for", "the" and "at"
         * take up room and match nothing, and a word that appears in three
         * boxes is indexed exactly as well as a word that appears in one.
         * Written as phrases these boxes came out 70% full with "game" in
         * six of them - a third of the space thrown away.
         *
         * So the words are gathered once, anything the title already spends
         * is dropped, and what remains is packed into the boxes until they
         * are full. Ugly to read, which is the correct shape for a field
         * only a search index sees.
         */
        $spent = self::wordsOf($title);

        // Strongest first: this game, then its shape, then how it gets used
        $sources = array_merge(
            [$f['subject'], $f['theme'], 'ages ' . str_replace('-', ' ', $f['ages'])],
            self::tags($project)
        );

        $pool  = self::KEYWORD_POOL;
        $count = count($pool);
        $start = $seed % $count;

        for ($i = 0; $i < $count; $i++) {
            $sources[] = $pool[($start + $i) % $count];
        }

        // One flat list of words, in order, each appearing once
        $words = [];

        foreach ($sources as $source) {
            foreach (self::wordsOf($source) as $word) {
                if (!in_array($word, $spent, true) && !in_array($word, $words, true)) {
                    $words[] = $word;
                }
            }
        }

        // Pack them into boxes, filling each one before starting the next
        $boxes = [];
        $box   = '';

        foreach ($words as $word) {
            $next = $box === '' ? $word : $box . ' ' . $word;

            if (mb_strlen($next) <= self::KEYWORD_MAX) {
                $box = $next;
                continue;
            }

            $boxes[] = $box;
            $box     = $word;

            if (count($boxes) === self::KEYWORD_SLOTS) {
                return $boxes;
            }
        }

        if ($box !== '') {
            $boxes[] = $box;
        }

        return array_slice($boxes, 0, self::KEYWORD_SLOTS);
    }

    // ---------------------------------------------------------------
    //  Copy
    // ---------------------------------------------------------------

    /** @return array<int, string> */
    public static function bullets(array $project, int $missionCount): array
    {
        $f = self::facts($project);

        return [
            'PRINT AT HOME - instant digital download, no shipping, print as many times as you like.',
            'COMPLETE SET - game map, story, rules, ' . Difficulty::MOVE_CARDS_PER_GAME
                . ' move cards, ' . $missionCount . ' mission cards, winner hero card and player tokens.',
            $f['ageLabel'] . ' - ' . $f['cells'] . ' mission spaces, plays in about '
                . $f['minutes'] . ' minutes with ' . $f['players'] . '.',
            'LEARNING THROUGH PLAY - every space asks a question, so children practise while they play.',
            'READY TO PRINT - standard A4 and US Letter friendly, cut lines included on every card sheet.',
        ];
    }

    public static function description(array $project, int $missionCount): string
    {
        $story = trim((string) ($project['story'] ?? ''));

        return ($story !== '' ? $story . "\n\n" : '')
            . "WHAT YOU GET\n"
            . "1. Game map (1 page)\n"
            . "2. Story page\n"
            . "3. How to play page\n"
            . '4. ' . Difficulty::MOVE_CARDS_PER_GAME . " move cards\n"
            . '5. ' . $missionCount . " mission cards\n"
            . "6. Winner hero card\n"
            . "7. Player tokens\n\n"
            . "HOW TO USE\n"
            . "Download the PDF, print on A4 or Letter paper, cut along the marked lines and play.\n\n"
            . 'This is a digital product. Nothing will be shipped.';
    }

    // ---------------------------------------------------------------
    //  The bits everything above is built out of
    // ---------------------------------------------------------------

    /** The project's own details, in the shapes the copy needs them */
    private static function facts(array $project): array
    {
        $diff = Difficulty::get((string) ($project['difficulty'] ?? ''));

        $ageMin = (int) ($project['age_min'] ?? 6);
        $ageMax = (int) ($project['age_max'] ?? 9);

        $setting = trim((string) ($project['setting'] ?? ''));
        $theme   = Project::artTheme($project);

        return [
            /*
             * What the game is about: the adventure if they picked one, else
             * the theme.
             *
             * Cleaned, because a buyer who types their own adventure types it
             * as a sentence - "Dragons & Castles: The Very Big Rescue!" - and
             * a marketplace title wearing an exclamation mark reads as spam
             * to the shopper before it reaches the search index.
             */
            'subject'    => $setting !== '' ? self::plain($setting) : $theme,
            'theme'      => $theme,
            'ages'       => $ageMin . '-' . $ageMax,
            'ageLabel'   => strtoupper(Helper::ageRange($ageMin, $ageMax)),
            'cells'      => (int) $diff['cells'],
            // A range like "25-35", not a number - casting it loses the top half
            'minutes'    => (string) $diff['play_minutes'],
            'difficulty' => strtolower((string) $diff['name']),
            'players'    => Helper::playerRange(
                (int) ($project['players_min'] ?? 2),
                (int) ($project['players_max'] ?? 4)
            ),
        ];
    }

    /** Stable per project, so the same game keeps the same tags */
    private static function seed(array $project): int
    {
        $id = (int) ($project['id'] ?? 0);

        return $id > 0
            ? $id
            : (int) abs(crc32(trim((string) ($project['title'] ?? '')) . '|' . trim((string) ($project['setting'] ?? ''))));
    }

    /**
     * A tag Etsy will accept: lower case, plain letters and numbers, and
     * inside twenty characters.
     *
     * Cut on a word boundary rather than mid-word - "family game nig" is
     * not a search anybody makes.
     */
    private static function sanitiseTag(string $tag): string
    {
        $tag = mb_strtolower(trim($tag));
        $tag = str_replace('&', ' and ', $tag);
        $tag = preg_replace('/[^a-z0-9 ]+/', ' ', $tag) ?? '';
        $tag = self::squash($tag);

        if ($tag === '' || mb_strlen($tag) <= self::ETSY_TAG_MAX) {
            return $tag;
        }

        $words = explode(' ', $tag);
        $out   = '';

        foreach ($words as $word) {
            $next = $out === '' ? $word : $out . ' ' . $word;

            if (mb_strlen($next) > self::ETSY_TAG_MAX) {
                break;
            }

            $out = $next;
        }

        // One word longer than the whole limit: nothing to keep
        return $out;
    }

    /** Cut to a length without breaking a word in half */
    private static function clip(string $text, int $max): string
    {
        $text = self::squash($text);

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $gap = mb_strrpos($cut, ' ');

        if ($gap !== false && $gap > 0) {
            $cut = mb_substr($cut, 0, $gap);
        }

        return rtrim($cut, " ,-|");
    }

    /** The words of a phrase, lower case, for comparing one against another */
    private static function wordsOf(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9 ]+/', ' ', $text) ?? '';

        $words = array_filter(
            explode(' ', self::squash($text)),
            // Joining words match everything and mean nothing
            static fn (string $w): bool => $w !== '' && !in_array($w, self::STOP_WORDS, true)
        );

        return array_values(array_unique($words));
    }

    /**
     * Words, numbers and spaces, and nothing a shopper reads as shouting.
     *
     * Ampersands and hyphens survive - plenty of real adventures are named
     * with them - and everything else that arrived as punctuation goes.
     */
    private static function plain(string $text): string
    {
        return self::squash(preg_replace('/[^\p{L}\p{N} &\'-]+/u', ' ', $text) ?? '');
    }

    private static function squash(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private static function titleCase(string $text): string
    {
        return self::squash(ucwords(mb_strtolower(trim($text))));
    }
}
