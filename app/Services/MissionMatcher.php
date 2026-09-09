<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Project;
use App\Services\Lang;

/**
 * Matches and generates mission cards.
 *
 *  - FR-24: picks suitable mission cards from the library based on the question
 *           subjects and difficulty the user chose, so nothing has to be done
 *           by hand.
 *
 *  - FR-35: expands 15 BASE TEMPLATES into as many cards as a game needs
 *           (60 / 90 / 120 depending on difficulty - SRS section 10) by
 *           substituting numbers and words into the blanks.
 *
 * How a template works:
 *
 *     pattern    "There are {a} rabbits and {b} hop away. How many are left?"
 *     answer     "{a-b} rabbits"
 *     variables  {"a": {"min": 5, "max": 20}, "b": {"min": 1, "max": 4}}
 *
 * Each time a card is generated the system draws random values for a and b and
 * fills them in, so 15 templates produce thousands of distinct questions.
 */
class MissionMatcher
{
    /** Question subjects (FR-23: "question subject areas") */
    public const SUBJECTS = [
        'math'       => 'Maths',
        'literacy'   => 'Reading & writing',
        'english'    => 'Vocabulary',
        'science'    => 'Science',
        'nature'     => 'Nature',
        'logic'      => 'Logic & reasoning',
        'life'       => 'Life skills',
        'geography'  => 'Geography',
    ];

    public static function subjectLabel(string $key): string
    {
        return self::SUBJECTS[$key] ?? ucfirst($key);
    }

    public static function subjectKeys(): array
    {
        return array_keys(self::SUBJECTS);
    }

    // ---------------------------------------------------------------
    //  FR-24  -  Auto-matching
    // ---------------------------------------------------------------

    /**
     * Base templates that match what the project asked for.
     *
     * @param array  $subjects  Subject keys, e.g. ['math', 'nature']
     * @param string $level     beginner | standard | advanced
     * @param string $plan      The user's plan, used to filter by entitlement
     */
    public static function matchTemplates(array $subjects, string $level, ?string $plan, ?string $locale = null): array
    {
        $locale = Lang::normalize($locale);
        $tiers  = Tiers::unlockedTiers($plan);
        $tierIn = implode(', ', array_fill(0, count($tiers), '?'));

        $sql    = 'SELECT * FROM mission_templates WHERE is_active = 1 AND tier IN (' . $tierIn . ')'
                . ' AND locale = ?';
        $params = $tiers;
        $params[] = $locale;

        // An easy game uses only easy templates; a hard game may also use easier ones
        $levels = self::levelsUpTo($level);
        $sql   .= ' AND level IN (' . implode(', ', array_fill(0, count($levels), '?')) . ')';
        $params = array_merge($params, $levels);

        $subjects = array_values(array_filter($subjects, fn($s) => isset(self::SUBJECTS[$s])));
        if ($subjects) {
            $sql   .= ' AND subject IN (' . implode(', ', array_fill(0, count($subjects), '?')) . ')';
            $params = array_merge($params, $subjects);
        }

        $sql .= ' ORDER BY subject ASC, id ASC';
        $rows = Database::all($sql, $params);

        // If the filter was too narrow, relax it by dropping the subject condition
        if (!$rows && $subjects) {
            $rows = self::matchTemplates([], $level, $plan, $locale);
        }

        /*
         * A language nobody has written questions for yet still has to deal a
         * playable deck, so it falls back to English rather than to nothing.
         * The rest of the printed game is still in the buyer's language.
         */
        if (!$rows && $locale !== Lang::DEFAULT) {
            return self::matchTemplates($subjects, $level, $plan, Lang::DEFAULT);
        }

        return $rows;
    }

    /**
     * Estimates how many DISTINCT questions these templates can produce.
     *
     * Used to warn ahead of time: if the user picks a single subject with few
     * variations (the "what sound does this animal make" template only has ten
     * animals) there is no way to fill 120 unique cards, so questions would
     * repeat inside one game.
     */
    public static function estimateVariants(array $templates): int
    {
        $total = 0;

        foreach ($templates as $tpl) {
            $spec = is_string($tpl['variables'] ?? null)
                ? json_decode((string) $tpl['variables'], true)
                : ($tpl['variables'] ?? []);

            if (!is_array($spec) || !$spec) {
                $total += 1;   // no blanks in this template - only one question
                continue;
            }

            $combos = 1;
            foreach ($spec as $rule) {
                if (!is_array($rule)) {
                    continue;
                }

                if (isset($rule['list']) && is_array($rule['list'])) {
                    $combos *= max(1, count($rule['list']));
                    continue;
                }

                $min  = (int) ($rule['min'] ?? 1);
                $max  = (int) ($rule['max'] ?? 10);
                $step = max(1, (int) ($rule['step'] ?? 1));
                if ($max < $min) {
                    [$min, $max] = [$max, $min];
                }
                $combos *= max(1, (int) floor(($max - $min) / $step) + 1);

                // Cap it so templates with many variables do not overflow
                if ($combos > 1000000) {
                    $combos = 1000000;
                    break;
                }
            }

            $total += $combos;
        }

        return $total;
    }

    /**
     * Questions this buyer's other games already print.
     *
     * A household that makes three games should not find the same card in all
     * three, so these are handed to generate() as already dealt. Capped: a
     * buyer with forty games does not need every question they have ever had
     * excluded, and the most recent ones are the ones they would notice.
     */
    public static function questionsAlreadyUsed(int $userId, ?int $exceptProjectId = null, int $limit = 1500): array
    {
        if ($userId <= 0) {
            return [];
        }

        $sql = 'SELECT m.question
                  FROM project_missions m
                  JOIN projects p ON p.id = m.project_id
                 WHERE p.user_id = ?';
        $params = [$userId];

        if ($exceptProjectId !== null) {
            $sql .= ' AND p.id <> ?';
            $params[] = $exceptProjectId;
        }

        $sql .= ' ORDER BY m.id DESC LIMIT ' . (int) $limit;

        return array_column(Database::all($sql, $params), 'question');
    }

    // ---------------------------------------------------------------
    //  Questions the buyer wrote themselves
    // ---------------------------------------------------------------

    /**
     * Reads a pasted list of questions.
     *
     * One question per line. An answer may follow after a tab or a vertical
     * bar, so a teacher can paste two columns straight out of a spreadsheet:
     *
     *     What is 7 x 8?<tab>56
     *     Name three rivers in France | Any three real rivers
     *     How do you spell "necessary"?
     *
     * Blank lines are skipped, and a line with no answer simply has none -
     * plenty of good questions are answered out loud by whoever is playing.
     */
    public static function parseOwnQuestions(string $text): array
    {
        $out = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $question = $line;
            $answer   = '';

            foreach (["\t", '|'] as $separator) {
                if (str_contains($line, $separator)) {
                    [$question, $answer] = array_map('trim', explode($separator, $line, 2));
                    break;
                }
            }

            if ($question === '') {
                continue;
            }

            $out[] = [
                'question' => mb_substr($question, 0, 500),
                'answer'   => mb_substr($answer, 0, 500),
            ];
        }

        return $out;
    }

    /**
     * Deals the buyer's own questions across the map.
     *
     * If they wrote fewer than the game needs, the list goes round again -
     * twenty questions across sixty cards is a perfectly good game, and it is
     * their choice to make. The stickers rotate so the cards still look varied.
     */
    public static function fromOwnQuestions(array $questions, int $cells, int $total): array
    {
        if (!$questions) {
            return self::fallbackCards($cells, $total);
        }

        $stickers = ['star', 'heart', 'leaf', 'bulb', 'gem', 'key'];
        $perCell  = (int) max(1, ceil($total / max(1, $cells)));
        $cards    = [];
        $i        = 0;

        for ($cell = 1; $cell <= $cells; $cell++) {
            for ($slot = 1; $slot <= $perCell; $slot++) {
                if (count($cards) >= $total) {
                    break 2;
                }

                $q = $questions[$i % count($questions)];

                $cards[] = [
                    'template_id' => null,
                    'subject'     => null,
                    'source'      => 'custom',
                    'sticker'     => $stickers[$i % count($stickers)],
                    'question'    => $q['question'],
                    'answer'      => $q['answer'],
                    'cell_no'     => $cell,
                    'slot_no'     => $slot,
                ];

                $i++;
            }
        }

        return $cards;
    }

    /** Levels at or below the one selected */
    private static function levelsUpTo(string $level): array
    {
        $order = [Difficulty::BEGINNER, Difficulty::STANDARD, Difficulty::ADVANCED];
        $idx   = array_search($level, $order, true);
        return $idx === false ? $order : array_slice($order, 0, $idx + 1);
    }

    // ---------------------------------------------------------------
    //  FR-35  -  Generating variations
    // ---------------------------------------------------------------

    /**
     * Generates a full set of mission cards for a project.
     *
     * @param int $cells  Mission spaces on the map (12 / 18 / 24)
     * @param int $total  Cards required (60 / 90 / 120)
     * @return array Cards with cell_no, slot_no, question, answer, sticker, subject, template_id
     */
    public static function generate(array $subjects, string $level, ?string $plan, int $cells, int $total, ?int $randomSeed = null, ?string $locale = null, array $avoid = []): array
    {
        $templates = self::matchTemplates($subjects, $level, $plan, $locale);

        if (!$templates) {
            return self::fallbackCards($cells, $total);
        }

        mt_srand($randomSeed ?? random_int(1, PHP_INT_MAX));

        /*
         * Where each template starts in its list of wordings.
         *
         * Starting them all at the written wording made every game open the
         * same way: the first maths card was phrased identically in game one,
         * game two and game three. A random start per game moves that around
         * while still walking through all the wordings before repeating one.
         */
        $used = [];
        foreach ($templates as $i => $tpl) {
            $used[$i] = mt_rand(0, max(0, count(self::phrasings($tpl)) - 1)) - 1;
        }

        /*
         * The loop below deals the templates in turn, which keeps the subjects
         * evenly spread. Dealing them in list order as well made the pattern
         * visible: with four maths templates, cards 1, 5 and 9 were always the
         * same kind of question. Shuffling the order once keeps the balance and
         * loses the rhythm, and stays reproducible because the seed is set.
         */
        shuffle($templates);

        $perCell = (int) max(1, ceil($total / max(1, $cells)));
        $cards   = [];

        /*
         * Questions already spoken for. Anything the buyer's other games use
         * starts in here, so a second game reaches for something else before
         * it reaches for the same card again.
         */
        $seen = [];
        foreach ($avoid as $question) {
            $seen[mb_strtolower(trim((string) $question))] = true;
        }
        $ti      = 0;
        $guard   = 0;

        for ($cell = 1; $cell <= $cells; $cell++) {
            for ($slot = 1; $slot <= $perCell; $slot++) {
                if (count($cards) >= $total) {
                    break 2;
                }

                // Rotate through the templates so every subject shows up
                $start = $ti % count($templates);
                $ti++;

                $card = null;

                /*
                 * Some templates have few variations. Rather than accept a
                 * duplicate, move on to the next template - no question should
                 * appear twice in the same game.
                 */
                for ($offset = 0; $offset < count($templates) && $card === null; $offset++) {
                    $index = ($start + $offset) % count($templates);
                    $tpl   = $templates[$index];

                    /*
                     * Each time a template comes round it is asked a different
                     * way, so the second maths card does not open with the same
                     * six words as the first.
                     */
                    $used[$index] = ($used[$index] ?? -1) + 1;

                    for ($try = 0; $try < 20; $try++) {
                        $candidate = self::renderTemplate($tpl, $used[$index]);
                        $key = mb_strtolower(trim($candidate['question']));

                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $card = $candidate;
                            break;
                        }
                        $guard++;
                    }
                }

                /*
                 * Everything is spoken for. Rather than leave a blank card,
                 * take one - and only then, so a repeat is the last resort
                 * rather than the first thing reached for.
                 */
                if ($card === null) {
                    $card = self::renderTemplate($templates[$start], $used[$start] ?? null);
                }

                $card['cell_no'] = $cell;
                $card['slot_no'] = $slot;
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * Fills a base template in, producing one concrete card.
     */
    public static function renderTemplate(array $tpl, ?int $phrasing = null): array
    {
        $vars     = self::drawVariables($tpl['variables'] ?? null);
        $wordings = self::phrasings($tpl);
        $which    = $phrasing === null
            ? array_rand($wordings)
            : $phrasing % count($wordings);
        $pattern  = $wordings[$which];

        return [
            'template_id' => (int) $tpl['id'],
            // which wording was used - not stored, but it lets the deck be
            // checked for shapes coming round rather than only questions
            'phrasing'    => $which,
            'subject'     => (string) $tpl['subject'],
            'sticker'     => (string) ($tpl['sticker'] ?? 'star'),
            'question'    => self::fill($pattern, $vars),
            'answer'      => self::fill((string) ($tpl['answer'] ?? ''), $vars),
        ];
    }

    /**
     * Every way this template can ask its question.
     *
     * A child spots the SHAPE of a sentence long before they notice the
     * numbers have changed, so "there are 5 rabbits and 3 more arrive" wears
     * out even while every card is technically different. A template can
     * therefore carry alternative wordings of the same question, and the deck
     * works through them rather than repeating the first one.
     *
     * @return string[] the written pattern first, then any alternatives
     */
    public static function phrasings(array $tpl): array
    {
        $out = [(string) $tpl['pattern']];

        $extra = $tpl['patterns'] ?? null;
        if (is_string($extra)) {
            $extra = json_decode($extra, true);
        }

        if (is_array($extra)) {
            foreach ($extra as $one) {
                if (is_string($one) && trim($one) !== '') {
                    $out[] = $one;
                }
            }
        }

        return $out;
    }

    /**
     * How many different sentence shapes these templates can print.
     *
     * estimateVariants counts questions; this counts shapes, which is what a
     * player actually notices repeating.
     */
    public static function estimateShapes(array $templates): int
    {
        $total = 0;

        foreach ($templates as $tpl) {
            $total += count(self::phrasings($tpl));
        }

        return $total;
    }

    /** Draws a random value for each of the template's variables */
    private static function drawVariables($json): array
    {
        $spec = is_string($json) ? json_decode($json, true) : (is_array($json) ? $json : []);
        if (!is_array($spec)) {
            return [];
        }

        $vars = [];
        foreach ($spec as $name => $rule) {
            if (!is_array($rule)) {
                $vars[$name] = $rule;
                continue;
            }

            // List type: pick one entry at random
            if (isset($rule['list']) && is_array($rule['list']) && $rule['list']) {
                $vars[$name] = $rule['list'][mt_rand(0, count($rule['list']) - 1)];
                continue;
            }

            // Numeric type: draw between min and max, optionally stepping
            $min  = (int) ($rule['min'] ?? 1);
            $max  = (int) ($rule['max'] ?? 10);
            $step = max(1, (int) ($rule['step'] ?? 1));
            if ($max < $min) {
                [$min, $max] = [$max, $min];
            }
            $steps = (int) floor(($max - $min) / $step);
            $vars[$name] = $min + mt_rand(0, max(0, $steps)) * $step;
        }

        return $vars;
    }

    /**
     * Fills the blanks in a string.
     * Supports plain names and simple arithmetic: {a}, {a+b}, {a-b}, {a*b}, {a*b+c}
     */
    private static function fill(string $text, array $vars): string
    {
        if ($text === '') {
            return '';
        }

        return (string) preg_replace_callback('/\{([^}]+)\}/u', function ($m) use ($vars) {
            $expr = trim($m[1]);

            // Simplest case: just a variable name
            if (array_key_exists($expr, $vars)) {
                return (string) $vars[$expr];
            }

            /*
             * {Thing} is {thing} with its first letter raised, for a blank
             * that starts a sentence. Only the first letter moves, so
             * "a lake and a sea" becomes "A lake and a sea" and nothing else
             * about the drawn words changes.
             */
            $lower = mb_strtolower(mb_substr($expr, 0, 1)) . mb_substr($expr, 1);
            if ($expr !== $lower && array_key_exists($lower, $vars)) {
                $value = (string) $vars[$lower];
                return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
            }

            $value = self::evaluate($expr, $vars);
            return $value === null ? $m[0] : (string) $value;
        }, $text);
    }

    /**
     * Safely evaluates a simple arithmetic expression (never uses eval).
     * Accepts variable names, whole numbers and the operators + - * / %
     * Multiplication and division bind tighter than addition and subtraction.
     */
    private static function evaluate(string $expr, array $vars): ?float
    {
        // Only allow safe characters through
        if (!preg_match('/^[A-Za-z0-9_+\-*\/%\s.]+$/', $expr)) {
            return null;
        }

        // Split into tokens: values and operators
        if (!preg_match_all('/([A-Za-z_][A-Za-z0-9_]*|\d+(?:\.\d+)?)|([+\-*\/%])/', $expr, $m, PREG_SET_ORDER)) {
            return null;
        }

        $values = [];
        $ops    = [];

        foreach ($m as $token) {
            if (($token[1] ?? '') !== '') {
                $t = $token[1];
                if (is_numeric($t)) {
                    $values[] = (float) $t;
                } elseif (array_key_exists($t, $vars) && is_numeric($vars[$t])) {
                    $values[] = (float) $vars[$t];
                } else {
                    return null;   // unknown variable, or not a number
                }
            } else {
                $ops[] = $token[2];
            }
        }

        if (!$values || count($ops) !== count($values) - 1) {
            return null;
        }

        // Pass 1: multiply, divide, modulo
        $v = [$values[0]];
        $o = [];
        for ($i = 0; $i < count($ops); $i++) {
            $op   = $ops[$i];
            $next = $values[$i + 1];
            if ($op === '*' || $op === '/' || $op === '%') {
                $last = array_pop($v);
                if (($op === '/' || $op === '%') && abs($next) < 1e-9) {
                    return null;   // never divide by zero
                }
                if ($op === '*') {
                    $v[] = $last * $next;
                } elseif ($op === '/') {
                    $v[] = $last / $next;
                } else {
                    $v[] = fmod($last, $next);
                }
            } else {
                $o[] = $op;
                $v[] = $next;
            }
        }

        // Pass 2: add and subtract
        $result = $v[0];
        for ($i = 0; $i < count($o); $i++) {
            $result = $o[$i] === '+' ? $result + $v[$i + 1] : $result - $v[$i + 1];
        }

        // Return a whole number when the result is exact
        return abs($result - round($result)) < 1e-9 ? round($result) : round($result, 2);
    }

    /** No templates available - still produce blank cards for the user to fill in (FR-25) */
    private static function fallbackCards(int $cells, int $total): array
    {
        $perCell = (int) max(1, ceil($total / max(1, $cells)));
        $cards   = [];

        for ($cell = 1; $cell <= $cells; $cell++) {
            for ($slot = 1; $slot <= $perCell; $slot++) {
                if (count($cards) >= $total) {
                    break 2;
                }
                $cards[] = [
                    'template_id' => null,
                    'subject'     => null,
                    'sticker'     => 'star',
                    'question'    => 'Blank question - write your own here.',
                    'answer'      => '',
                    'cell_no'     => $cell,
                    'slot_no'     => $slot,
                ];
            }
        }

        return $cards;
    }

    // ---------------------------------------------------------------
    //  Saving to a project
    // ---------------------------------------------------------------

    /** Replaces every mission card on a project with a freshly generated set */
    public static function saveForProject(int $projectId, array $cards): void
    {
        Database::transaction(function () use ($projectId, $cards) {
            Database::delete('project_missions', ['project_id' => $projectId]);
            $now = date('Y-m-d H:i:s');
            foreach ($cards as $c) {
                Database::insert('project_missions', [
                    'project_id'  => $projectId,
                    'cell_no'     => (int) $c['cell_no'],
                    'slot_no'     => (int) $c['slot_no'],
                    'source'      => (string) ($c['source'] ?? 'library'),
                    'template_id' => $c['template_id'] ?? null,
                    'subject'     => $c['subject'] ?? null,
                    'question'    => (string) $c['question'],
                    'answer'      => (string) ($c['answer'] ?? ''),
                    'sticker'     => (string) ($c['sticker'] ?? 'star'),
                    'created_at'  => $now,
                ]);
            }
        });
    }

    /** Swaps one card for another variation of the same template (FR-26 "swap a card") */
    public static function reroll(int $missionId, ?string $plan): ?array
    {
        $row = Database::first('SELECT * FROM project_missions WHERE id = ? LIMIT 1', [$missionId]);
        if (!$row) {
            return null;
        }

        $project = Database::first('SELECT * FROM projects WHERE id = ? LIMIT 1',
                                   [(int) $row['project_id']]) ?: [];

        /*
         * A game whose questions the buyer wrote has no template to swap for,
         * and quietly dropping a library question into their list would be
         * worse than doing nothing. The Studio says so and offers the editor.
         */
        if ($project && Project::usesOwnQuestions($project)) {
            return null;
        }

        $tpl = null;
        if (!empty($row['template_id'])) {
            $tpl = Database::first('SELECT * FROM mission_templates WHERE id = ? LIMIT 1', [(int) $row['template_id']]);
        }

        // No base template left - fall back to any template in the same subject,
        // in this game's own language and at its own level
        if (!$tpl) {
            $candidates = self::matchTemplates(
                $row['subject'] ? [$row['subject']] : [],
                (string) ($project['difficulty'] ?? Difficulty::STANDARD),
                $plan,
                Lang::of($project)
            );
            if (!$candidates) {
                return null;
            }
            $tpl = $candidates[array_rand($candidates)];
        }

        $new = self::renderTemplate($tpl);

        Database::update('project_missions', [
            'source'      => 'library',
            'template_id' => $new['template_id'],
            'subject'     => $new['subject'],
            'question'    => $new['question'],
            'answer'      => $new['answer'],
            'sticker'     => $new['sticker'],
        ], ['id' => $missionId]);

        return array_merge($row, $new);
    }

    /** A project's mission cards, ordered by map space */
    public static function forProject(int $projectId): array
    {
        return Database::all(
            'SELECT * FROM project_missions WHERE project_id = ? ORDER BY cell_no ASC, slot_no ASC',
            [$projectId]
        );
    }

    public static function countForProject(int $projectId): int
    {
        return Database::count('SELECT COUNT(*) FROM project_missions WHERE project_id = ?', [$projectId]);
    }
}
