<?php
/**
 * FR-27 - the complete print bundle in the required order:
 *   1 Map  2 Story  3 How to play  4 Move cards  5 Mission cards  6 Hero card
 * (7 Player tokens - a cut-out accessory, placed last)
 */
use App\Core\Helper as H;
use App\Core\Url;
use App\Models\Project;
use App\Services\Art;
use App\Services\Difficulty;
use App\Services\Lang;
use App\Services\MapComposer;
use App\Services\PrintBundle;

$pid      = (int) $project['id'];

// Everything the buyer prints is written in the game's own language; the
// Studio around it stays in English.
$lang = Lang::of($project);
$t    = fn (string $key, array $r = []) => Lang::get($key, $lang, $r);
$tn   = fn (string $key, int $n, array $r = []) => Lang::choose($key, $n, $lang, $r);
// The sheet foot names the buyer's game, not this app: the pages are their
// product, and a maker selling a printed board should not be handing out
// somebody else's brand with it.
$brand    = (string) $project['title'];

/*
 * The same name goes faintly on every card. A household with three printed
 * games has a few hundred small cards that look alike, and once a box is
 * tipped out there is otherwise nothing to sort them by.
 *
 * Truncated: at 7px in a narrow column a long title would wrap and steal a
 * line from the question.
 */
$cardName = H::e(H::truncate((string) $project['title'], 24));
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

$foot = function () use ($brand, $lang, &$sheetNo) {
    echo '<div class="sheet__foot"><span>' . H::e($brand) . '</span><span>'
       . H::e(Lang::get('sheet.page', $lang, ['n' => $sheetNo])) . '</span></div>';
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
            --mission-caption: <?= $frames['caption']['mission'] ?>%;
            --move-caption: <?= $frames['caption']['move'] ?>%;
            <?php if ($frames['window']): ?>
            --hero-top: <?= $frames['window']['top'] ?>%;
            --hero-height: <?= $frames['window']['height'] ?>%;
            --hero-left: <?= $frames['window']['left'] ?? 16 ?>%;
            --hero-right: <?= $frames['window']['right'] ?? 16 ?>%;
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
            <?= H::e($tn('bar.pages', (int) $totalPages)) ?> &middot;
            <?= H::e($t('level.' . $project['difficulty'])) ?> &middot;
            <?= H::e($tn('bar.spaces', (int) $project['cells'])) ?>
        </div>
    </div>
    <div class="printbar__spacer"></div>
    <a href="<?= Url::to('/studio/' . $pid) ?>"><?= H::e($t('bar.back')) ?></a>
    <a href="<?= Url::to('/preview/' . $pid) ?>"><?= H::e($t('bar.preview')) ?></a>
    <button type="button" class="primary" data-print><?= H::e($t('bar.print')) ?></button>

    <div class="printbar__hint"><?= $t('bar.hint') ?></div>
</div>

<div class="sheets">

<?php foreach ($sections as $section): ?>
    <?php $d = $section['data']; ?>

    <?php if ($section['key'] === 'map'): ?>
        <!-- ===== 1. Game map ===== -->
        <div class="sheet sheet--landscape">
            <?php $head('1', $t('sheet.map'), $tn('sheet.map_sub', (int) $project['cells'])); ?>
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
        <?php /* No picture here. It used to carry a scene drawn from the theme
                 palette, which had nothing to do with the game's own artwork
                 and took a third of the page from the words. The title goes on
                 the first sheet only - a long story runs on, and repeating it
                 would read as a new story starting rather than the same one
                 continuing. */ ?>
        <?php foreach ($d['pages'] as $page => $paragraphs): ?>
            <div class="sheet">
                <?php $head('2', $t('sheet.story'),
                    count($d['pages']) > 1
                        ? $t('sheet.sheet_of', ['page' => $page + 1, 'total' => count($d['pages'])])
                        : $project['title']); ?>
                <div class="sheet__body">
                    <div class="prose">
                        <?php if ($page === 0): ?>
                            <h2><?= H::e($project['title']) ?></h2>
                        <?php endif; ?>
                        <?php foreach ($paragraphs as $para): ?>
                            <p><?= nl2br(H::e($para)) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php $foot(); ?>
            </div>
        <?php endforeach; ?>

    <?php elseif ($section['key'] === 'howto'): ?>
        <!-- ===== 3. How to play ===== -->
        <div class="sheet">
            <?php $head('3', $t('sheet.howto'), PrintBundle::playerRange($project)); ?>
            <div class="sheet__body">
                <ol class="rules">
                    <?php foreach (PrintBundle::ruleSteps((string) $d['text']) as $rule): ?>
                        <li><?= H::e($rule) ?></li>
                    <?php endforeach; ?>
                </ol>

                <div class="callout">
                    <b><?= H::e($t('howto.prepare')) ?></b>
                    <?= H::e(PrintBundle::prepareLine($project)) ?>
                </div>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'move'): ?>
        <!-- ===== 4. Move cards ===== -->
        <?php foreach (array_chunk($d['cards'], $perSheet) as $page => $chunk): ?>
            <div class="sheet">
                <?php $head('4', $t('sheet.move'), $tn('sheet.move_sub', count($d['cards']))); ?>
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

                                <div class="card-cut__game"><?= $cardName ?></div>
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
            <?php $head('4', $t('sheet.dice'), $t('sheet.dice_sub')); ?>
            <div class="sheet__body">
                <?php if (!empty($d['image'])): ?>
                    <div class="dice-net">
                        <img src="<?= H::e($d['image']) ?>" alt="<?= H::e($t('dice.alt')) ?>">
                    </div>
                    <ol class="dice-steps">
                        <?php foreach (Lang::all('dice.steps', $lang) as $stepLine): ?>
                            <li><?= H::e($stepLine) ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <div class="prose">
                        <p><?= $t('dice.missing') ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'mission'): ?>
        <!-- ===== 5. Mission cards ===== -->
        <?php if (!$d['cards']): ?>
            <div class="sheet">
                <?php $head('5', $t('sheet.mission'), $t('sheet.mission_none')); ?>
                <div class="sheet__body prose">
                    <p><?= H::e($t('mission.empty')) ?></p>
                </div>
                <?php $foot(); ?>
            </div>
        <?php else: ?>
            <?php foreach (array_chunk($d['cards'], $perSheet) as $page => $chunk): ?>
                <div class="sheet">
                    <?php $head('5', $t('sheet.mission'),
                        $t('sheet.sheet_of', ['page' => $page + 1,
                                              'total' => (int) ceil(count($d['cards']) / $perSheet)])
                        . ' - ' . $tn('sheet.cards', count($d['cards']))); ?>
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

                                        <?php /* No answer here - a card the child holds must not
                                                 carry it. Every answer is on the key at the back. */ ?>

                                    </div>

                                    <div class="card-cut__game"><?= $cardName ?></div>

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
            <?php $head('6', $t('sheet.hero'), $t('sheet.hero_sub')); ?>
            <div class="sheet__body">
                <?php /* One piece of markup for all five designs - what a style
                         does not use, its CSS hides. */ ?>
                <div class="hero-card hero-card--<?= H::e(Project::heroStyle($project)) ?>">
                    <div class="hero-card__rays"></div>

                    <div class="hero-card__medal">
                        <div class="hero-card__art">
                            <img src="<?= H::e($d['character']) ?>" alt="">
                        </div>
                    </div>

                    <div class="hero-card__ribbon"><?= H::e($t('hero_card.champion')) ?></div>
                    <div class="hero-card__tails"></div>

                    <div class="hero-card__stars">
                        <?php for ($s = 0; $s < 5; $s++): ?>
                            <img src="<?= H::e(Art::dataUri(Art::sticker('star', '#E0952E', 15))) ?>" alt="">
                        <?php endfor; ?>
                    </div>

                    <div class="hero-card__eyebrow"><?= H::e($t('hero_card.eyebrow')) ?></div>
                    <div class="hero-card__name"><?= H::e($project['title']) ?></div>
                    <div class="hero-card__line">
                        <?= H::e($t('hero_card.line', ['n' => (int) $project['cells']])) ?><br>
                        <?= str_replace('{name}', '<b>' . H::e($d['hero_name']) . '</b>',
                                        H::e($t('hero_card.congrats', ['name' => '{name}']))) ?>
                    </div>

                    <div class="hero-card__signrow">
                        <div class="hero-card__sign"><?= H::e($t('hero_card.winner')) ?></div>
                        <div class="hero-card__sign"><?= H::e($t('hero_card.date')) ?></div>
                    </div>
                </div>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'tokens'): ?>
        <!-- ===== 7. Player tokens ===== -->
        <div class="sheet">
            <?php $head('7', $t('sheet.tokens'), $t('sheet.tokens_sub')); ?>
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
                    <?= H::e($t('tokens.note')) ?>
                </div>
            </div>
            <?php $foot(); ?>
        </div>

    <?php elseif ($section['key'] === 'answers'): ?>
        <!-- ===== 8. Answer key - the last sheets, for the game master ===== -->
        <?php foreach ($d['pages'] as $page => $rows): ?>
            <div class="sheet">
                <?php $head('8', $t('sheet.answers'),
                    count($d['pages']) > 1
                        ? $t('sheet.sheet_of', ['page' => $page + 1, 'total' => count($d['pages'])])
                        : $t('sheet.answers_keep')); ?>
                <div class="sheet__body">

                    <?php if ($page === 0): ?>
                        <div class="answer-warn"><?= $t('answers.warn') ?></div>
                    <?php endif; ?>

                    <div class="answer-key__order"><?= H::e($t('answers.in_order')) ?></div>

                    <div class="answer-key">
                        <?php foreach ($rows as $r): ?>
                            <div class="answer-key__row">
                                <span class="answer-key__n"><?= (int) $r['n'] ?></span>
                                <span class="answer-key__q"><?= H::e($r['question']) ?></span>
                                <span class="answer-key__a"><?= H::e($r['answer']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
                <?php $foot(); ?>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
<?php endforeach; ?>

</div>

<script>
document.querySelectorAll('[data-print]').forEach(function (b) {
  b.addEventListener('click', function () { window.print(); });
});
</script>
