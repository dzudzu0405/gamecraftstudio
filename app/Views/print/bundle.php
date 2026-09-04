<?php
/**
 * FR-27 - the complete print bundle in the required order:
 *   1 Map  2 Story  3 How to play  4 Move cards  5 Mission cards  6 Hero card
 * (7 Player tokens - a cut-out accessory, placed last)
 */
use App\Core\Helper as H;
use App\Core\Url;
use App\Services\Art;
use App\Services\Difficulty;
use App\Services\MapComposer;
use App\Services\PrintBundle;

$pid      = (int) $project['id'];
$brand    = 'GameCraft Studio - ' . $project['title'];
$sheetNo  = 0;
$perSheet = PrintBundle::CARDS_PER_SHEET;

/** Header on every sheet */
$head = function (string $order, string $title, string $sub = '') use (&$sheetNo) {
    $sheetNo++;
    echo '<div class="sheet__head">';
    echo '<span class="sheet__no">' . H::e($order) . '</span>';
    echo '<span class="sheet__title">' . H::e($title) . '</span>';
    if ($sub !== '') {
        echo '<span class="sheet__sub">' . H::e($sub) . '</span>';
    }
    echo '</div>';
};

$foot = function () use ($brand, &$sheetNo) {
    echo '<div class="sheet__foot"><span>' . H::e($brand) . '</span><span>Page ' . $sheetNo . '</span></div>';
};

// Card frames the buyer supplied, if this style has any
$frames = PrintBundle::cardFrames($project);

// Mission cards walk through the character's poses instead of repeating one
$poseCount = count($frames['heroes']);
$poseNo    = 0;
$movePose  = 0;   // move cards keep their own place in the pose cycle

/*
 * Step the question's type size down as it gets longer, so a long one stays
 * inside its card. The thresholds are character counts, chosen against the
 * seeded templates: "7 + 5 = ?" is tiny, a word problem runs past 130.
 */
$qSize = function (string $question): string {
    $len = mb_strlen(trim($question));

    if ($len <= 24)  return 'card-cut__q--lg';
    if ($len <= 80)  return '';
    if ($len <= 130) return 'card-cut__q--sm';
    return 'card-cut__q--xs';
};
?>

<?php if ($frames['mission'] || $frames['move']): ?>
    <?php /* One copy of each picture for the whole document, not one per card */ ?>
    <style>
        :root {
            --mission-top: <?= $frames['zone']['mission'][0] ?>%;
            --mission-bottom: <?= $frames['zone']['mission'][1] ?>%;
            --move-top: <?= $frames['zone']['move'][0] ?>%;
            --move-bottom: <?= $frames['zone']['move'][1] ?>%;
            <?php if ($frames['window']): ?>
            --hero-top: <?= $frames['window']['top'] ?>%;
            --hero-height: <?= $frames['window']['height'] ?>%;
            <?php endif; ?>
        }
        <?php if ($frames['mission']): ?>
        .card-cut--art { background-image: url('<?= $frames['mission'] ?>'); }
        <?php endif; ?>
        <?php if ($frames['move']): ?>
        .card-move--art { background-image: url('<?= $frames['move'] ?>'); }
        <?php endif; ?>
        <?php foreach ($frames['heroes'] as $i => $pose): ?>
        .hero-<?= $i + 1 ?> { background-image: url('<?= $pose ?>'); }
        <?php endforeach; ?>
    </style>
<?php endif; ?>

<!-- Toolbar: never printed -->
<div class="printbar no-print">
    <div>
        <div class="printbar__title"><?= H::e($project['title']) ?></div>
        <div class="printbar__meta">
            <?= (int) $totalPages ?> pages &middot;
            <?= H::e(Difficulty::name((string) $project['difficulty'])) ?> &middot;
            <?= (int) $project['cells'] ?> spaces
        </div>
    </div>
    <div class="printbar__spacer"></div>
    <a href="<?= Url::to('/studio/' . $pid) ?>">Back to Studio</a>
    <a href="<?= Url::to('/preview/' . $pid) ?>">Preview</a>
    <button type="button" class="primary" data-print>Print / Save as PDF</button>

    <div class="printbar__hint">
        In the print dialog choose <b>Destination: Save as PDF</b>, turn on <b>Background graphics</b>
        and set <b>Margins: None</b> so the colours and cut lines come out correctly.
    </div>
</div>

<div class="sheets">

<?php foreach ($sections as $section): ?>
    <?php $d = $section['data']; ?>

    <?php if ($section['key'] === 'map'): ?>
        <!-- ===== 1. Game map ===== -->
        <div class="sheet sheet--landscape">
            <?php $head('1', 'Game map', (int) $project['cells'] . ' mission spaces'); ?>
            <div class="sheet__body map-wrap">
                <?= MapComposer::render($project, $d['background'], [
                    'width'    => MapComposer::WIDTH,
                    'height'   => MapComposer::HEIGHT,
                    'frameUrl' => PrintBundle::mapFrameUrl($project),
                ]) ?>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'story'): ?>
        <!-- ===== 2. Story ===== -->
        <div class="sheet">
            <?php $head('2', 'The story', $project['title']); ?>
            <div class="sheet__body">
                <div class="story-hero">
                    <img src="<?= H::e(Art::dataUri(Art::scene((string) $project['theme'], (string) $project['cover_seed'], 900, 340))) ?>" alt="">
                </div>
                <div class="prose">
                    <h2><?= H::e($project['title']) ?></h2>
                    <?php foreach (preg_split('/\n\s*\n/', trim((string) $d['text'])) ?: [] as $para): ?>
                        <p><?= nl2br(H::e(trim($para))) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'howto'): ?>
        <!-- ===== 3. How to play ===== -->
        <div class="sheet">
            <?php $head('3', 'How to play', H::playerRange((int) $project['players_min'], (int) $project['players_max'])); ?>
            <div class="sheet__body">
                <ol class="rules">
                    <?php
                    $lines = array_values(array_filter(array_map('trim', explode("\n", (string) $d['text']))));
                    $extra = [];
                    foreach ($lines as $line) {
                        // Strip any leading numbering - the CSS numbers these itself
                        $clean = preg_replace('/^\d+[\.\)]\s*/', '', $line);
                        if ($clean === '') { continue; }
                        if (stripos($clean, 'You will need') === 0) { $extra[] = $clean; continue; }
                        echo '<li>' . H::e($clean) . '</li>';
                    }
                    ?>
                </ol>

                <div class="callout">
                    <b>What to prepare:</b>
                    <?= Difficulty::MOVE_CARDS_PER_GAME ?> move cards,
                    <?= (int) $project['cells'] * Difficulty::MISSIONS_PER_CELL ?> mission cards split into
                    <?= (int) $project['cells'] ?> piles (<?= Difficulty::MISSIONS_PER_CELL ?> per space),
                    <?= Difficulty::HERO_CARDS_PER_GAME ?> hero card,
                    and one token for each player.
                    <?php foreach ($extra as $e): ?>
                        <br><?= H::e($e) ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'move'): ?>
        <!-- ===== 4. Move cards ===== -->
        <?php foreach (array_chunk($d['cards'], $perSheet) as $page => $chunk): ?>
            <div class="sheet">
                <?php $head('4', 'Move cards', 'Cut along the dashed lines - ' . count($d['cards']) . ' cards'); ?>
                <div class="sheet__body">
                    <div class="cards">
                        <?php foreach ($chunk as $c): ?>
                            <div class="card-cut card-move<?= $frames['move'] ? ' card-cut--framed card-move--art' : '' ?>">
                                <div class="card-cut__inner">
                                    <?php /* The chosen character rides the move cards too, cycling
                                             its poses. With no character set, fall back to the
                                             sticker that used to be the only thing here. */ ?>
                                    <?php if ($poseCount): ?>
                                        <div class="card-move__hero hero-<?= ($movePose++ % $poseCount) + 1 ?>"></div>
                                    <?php else: ?>
                                        <div class="card-move__icon">
                                            <img src="<?= H::e(Art::dataUri(Art::sticker($c['sticker'], '#6C4BD6', 26))) ?>" alt="">
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-move__steps <?= $c['steps'] < 0 ? 'is-back' : '' ?>">
                                        <?= $c['steps'] < 0 ? '&minus;' . abs($c['steps']) : '+' . $c['steps'] ?>
                                    </div>
                                    <div class="card-move__label"><?= H::e($c['label']) ?></div>
                                    <?php /* The same card also carries the cost of a wrong answer */ ?>
                                    <div class="card-move__penalty"><?= H::e($c['penalty']) ?></div>
                                </div>
                                <div class="card-cut__brand">GameCraft</div>
                            </div>
                        <?php endforeach; ?>

                        <?php for ($i = count($chunk); $i < $perSheet; $i++): ?>
                            <div class="card-cut"></div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php $foot(); ?>
            </div>
        <?php endforeach; ?>

    <?php elseif ($section['key'] === 'dice'): ?>
        <!-- ===== 4. Paper die (printed instead of move cards) ===== -->
        <div class="sheet">
            <?php $head('4', 'Paper die', 'Cut out, fold along the lines and glue the tabs'); ?>
            <div class="sheet__body">
                <?php if (!empty($d['image'])): ?>
                    <div class="dice-net">
                        <img src="<?= H::e($d['image']) ?>" alt="Die to cut out and fold">
                    </div>
                    <ol class="dice-steps">
                        <li>Cut around the outside of the whole shape, tabs included.</li>
                        <li>Fold along every inside line, so the six faces turn inwards.</li>
                        <li>Glue the tabs under the neighbouring face and hold until dry.</li>
                        <li>One die is enough for the whole table - roll and move that many spaces.</li>
                    </ol>
                <?php else: ?>
                    <div class="prose">
                        <p>The die artwork is missing. Put a file named <code>dice-net.png</code>
                           into <code>uploads/library/</code> and print again, or play with any
                           ordinary six-sided die.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'mission'): ?>
        <!-- ===== 5. Mission cards ===== -->
        <?php if (!$d['cards']): ?>
            <div class="sheet">
                <?php $head('5', 'Mission cards', 'None generated yet'); ?>
                <div class="sheet__body prose">
                    <p>This project has no mission cards yet. Go back to the Studio and choose
                       "Match mission cards".</p>
                </div>
                <?php $foot(); ?>
            </div>
        <?php else: ?>
            <?php foreach (array_chunk($d['cards'], $perSheet) as $page => $chunk): ?>
                <div class="sheet">
                    <?php $head('5', 'Mission cards',
                        'Sheet ' . ($page + 1) . ' of ' . (int) ceil(count($d['cards']) / $perSheet)
                        . ' - ' . count($d['cards']) . ' cards'); ?>
                    <div class="sheet__body">
                        <div class="cards">
                            <?php foreach ($chunk as $m): ?>
                                <div class="card-cut<?= $frames['mission'] ? ' card-cut--framed card-cut--art' : '' ?><?= $frames['window'] ? ' card-cut--tight' : '' ?>">
                                    <?php $pose = $poseCount ? ' hero-' . ($poseNo++ % $poseCount + 1) : ''; ?>

                                    <?php if ($poseCount && $frames['window']): ?>
                                        <?php /* The frame drew a window for a picture - fill it */ ?>
                                        <div class="card-cut__window<?= $pose ?>"></div>
                                    <?php endif; ?>

                                    <div class="card-cut__inner">
                                        <?php if ($poseCount && !$frames['window']): ?>
                                            <div class="card-cut__hero<?= $pose ?>"></div>
                                        <?php endif; ?>

                                        <?php /* The "Space 1 - Maths" line is gone: it told the
                                                 player nothing they needed and took room the
                                                 question wanted. The sticker stays. */ ?>
                                        <div class="card-cut__top">
                                            <span class="card-cut__sticker">
                                                <img src="<?= H::e(Art::dataUri(Art::sticker((string) $m['sticker'], '#6C4BD6', 16))) ?>" alt="">
                                            </span>
                                        </div>

                                        <div class="card-cut__q <?= $qSize((string) $m['question']) ?>"><?= H::e($m['question']) ?></div>

                                        <?php if (trim((string) $m['answer']) !== ''): ?>
                                            <div class="card-cut__a">Answer: <?= H::e($m['answer']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-cut__brand">GameCraft</div>
                                </div>
                            <?php endforeach; ?>

                            <?php for ($i = count($chunk); $i < $perSheet; $i++): ?>
                                <div class="card-cut"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php $foot(); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php elseif ($section['key'] === 'hero'): ?>
        <!-- ===== 6. Winner hero card ===== -->
        <div class="sheet">
            <?php $head('6', 'Winner hero card', 'One per game'); ?>
            <div class="sheet__body">
                <div class="hero-card">
                    <div class="hero-card__rays"></div>

                    <div class="hero-card__medal">
                        <div class="hero-card__art">
                            <img src="<?= H::e($d['character']) ?>" alt="">
                        </div>
                    </div>

                    <div class="hero-card__ribbon">Champion</div>

                    <div class="hero-card__stars">
                        <?php for ($s = 0; $s < 5; $s++): ?>
                            <img src="<?= H::e(Art::dataUri(Art::sticker('star', '#E0952E', 15))) ?>" alt="">
                        <?php endfor; ?>
                    </div>

                    <div class="hero-card__eyebrow">Hero of</div>
                    <div class="hero-card__name"><?= H::e($project['title']) ?></div>
                    <div class="hero-card__line">
                        Made it through all <?= (int) $project['cells'] ?> challenges
                        and reached the finish first.<br>
                        Congratulations, <b><?= H::e($d['hero_name']) ?></b>!
                    </div>

                    <div class="hero-card__signrow">
                        <div class="hero-card__sign">Winner's name</div>
                        <div class="hero-card__sign">Date</div>
                    </div>
                </div>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'tokens'): ?>
        <!-- ===== 7. Player tokens ===== -->
        <div class="sheet">
            <?php $head('7', 'Player tokens', 'Cut out and glue onto card'); ?>
            <div class="sheet__body">
                <div class="tokens">
                    <?php foreach ($d['players'] as $p): ?>
                        <?php for ($copy = 0; $copy < 2; $copy++): ?>
                            <div class="token-cut">
                                <div class="token-cut__circle">
                                    <img src="<?= H::e(Art::dataUri(Art::token(
                                        (string) $p['color'],
                                        mb_substr((string) $p['name'], 0, 1),
                                        200
                                    ))) ?>" alt="">
                                </div>
                                <div class="token-cut__name"><?= H::e($p['name']) ?></div>
                            </div>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                </div>

                <div class="callout" style="margin-top:10mm">
                    Each player gets two tokens - one to use and one spare.
                    Glue them onto thick card and cut around the circle so they stand up on the map.
                </div>
            </div>
            <?php $foot(); ?>
        </div>

    <?php endif; ?>
<?php endforeach; ?>

</div>

<script>
document.querySelectorAll('[data-print]').forEach(function (b) {
  b.addEventListener('click', function () { window.print(); });
});
</script>
