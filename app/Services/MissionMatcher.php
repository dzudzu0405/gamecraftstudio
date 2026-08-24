<?php
namespace App\Services;

use App\Core\Database;

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
    public static function matchTemplates(array $subjects, string $level, ?string $plan): array
    {
        $tiers  = Tiers::unlockedTiers($plan);
        $tierIn = implode(', ', array_fill(0, count($tiers), '?'));

        $sql    = 'SELECT * FROM mission_templates WHERE is_active = 1 AND tier IN (' . $tierIn . ')';
        $params = $tiers;

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
            return self::matchTemplates([], $level, $plan);
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
    public static function generate(array $subjects, string $level, ?string $plan, int $cells, int $total, ?int $randomSeed = null): array
    {
        $templates = self::matchTemplates($subjects, $level, $plan);

        if (!$templates) {
            return self::fallbackCards($cells, $total);
        }

        mt_srand($randomSeed ?? random_int(1, PHP_INT_MAX));

        $perCell = (int) max(1, ceil($total / max(1, $cells)));
        $cards   = [];
        $seen    = [];      // guards against duplicate questions
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
                    $tpl = $templates[($start + $offset) % count($templates)];

                    for ($try = 0; $try < 20; $try++) {
                        $candidate = self::renderTemplate($tpl);
                        $key = mb_strtolower(trim($candidate['question']));

                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $card = $candidate;
                            break;
                        }
                        $guard++;
                    }
                }

                // The whole library really is exhausted - accept a repeat
                if ($card === null) {
                    $card = self::renderTemplate($templates[$start]);
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
    public static function renderTemplate(array $tpl): array
    {
        $vars = self::drawVariables($tpl['variables'] ?? null);

        return [
            'template_id' => (int) $tpl['id'],
            'subject'     => (string) $tpl['subject'],
            'sticker'     => (string) ($tpl['sticker'] ?? 'star'),
            'question'    => self::fill((string) $tpl['pattern'], $vars),
            'answer'      => self::fill((string) ($tpl['answer'] ?? ''), $vars),
        ];
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
                    'source'      => 'library',
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

        $tpl = null;
        if (!empty($row['template_id'])) {
            $tpl = Database::first('SELECT * FROM mission_templates WHERE id = ? LIMIT 1', [(int) $row['template_id']]);
        }

        // No base template left - fall back to any template in the same subject
        if (!$tpl) {
            $candidates = self::matchTemplates(
                $row['subject'] ? [$row['subject']] : [],
                Difficulty::STANDARD,
                $plan
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
