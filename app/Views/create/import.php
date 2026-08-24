<?php
/** FR-06: import an existing game blueprint */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;

$sample = [
    'title'       => 'Dinosaur Rescue Mission',
    'theme'       => 'dino',
    'difficulty'  => 'standard',
    'subjects'    => ['math', 'nature'],
    'story'       => 'A great roar rolls up from the valley below...',
    'how_to_play' => '1. Each player picks a token and places it on the START space...',
    'missions'    => [
        ['question' => 'There are 8 rabbits and 3 more hop over. How many now?', 'answer' => '11 rabbits', 'subject' => 'math', 'sticker' => 'star'],
        ['question' => 'What sound does an elephant make?', 'answer' => 'Any good impression counts', 'subject' => 'nature', 'sticker' => 'heart'],
    ],
];
?>

<div class="section__head">
    <h1 class="section__title" style="font-size:22px">Import Game Blueprint</h1>
</div>

<div style="max-width:720px">

    <div class="notice notice--info">
        <?= Icon::get('info', 17) ?>
        <span>
            Use this when you already have the game content prepared elsewhere and want to bring it
            straight into the Studio instead of starting from scratch.
        </span>
    </div>

    <div class="card mb-2">
        <div class="card__head"><h3>Choose a blueprint file</h3></div>
        <div class="card__body">
            <form method="post" action="<?= Url::to('/import') ?>" enctype="multipart/form-data">
                <?= Csrf::field() ?>

                <label class="dropzone" data-dropzone>
                    <div class="dropzone__icon"><?= Icon::get('file', 30) ?></div>
                    <div class="dropzone__title">Drag a .json file here</div>
                    <div class="dropzone__hint" data-dropzone-label>
                        or click to browse &middot; up to 3 MB
                    </div>
                    <input type="file" name="blueprint" accept="application/json,.json" required>
                </label>

                <button class="btn btn--primary mt-2" type="submit">
                    <?= Icon::get('upload', 16) ?> Import blueprint
                </button>
                <a class="btn btn--ghost mt-2" href="<?= Url::to('/create') ?>">Start from scratch instead</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card__head">
            <h3>File format</h3>
            <button type="button" class="btn btn--ghost btn--sm" data-copy="#sample-json">
                <?= Icon::get('copy', 14) ?> Copy example
            </button>
        </div>
        <div class="card__body">
            <p class="small muted mb-2">
                A JSON file with the fields below. Only <code>title</code> is required;
                everything else is optional.
            </p>
            <div class="prompt-box">
                <pre class="prompt-box__text" id="sample-json"><?= H::e(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
            </div>

            <table class="data mt-2">
                <tr><td class="bold" style="padding-left:0">title</td><td class="muted">Game title (required)</td></tr>
                <tr><td class="bold" style="padding-left:0">theme</td><td class="muted">forest, dino, space, ocean, pirate, magic, castle, desert, arctic, candy, robot, farm</td></tr>
                <tr><td class="bold" style="padding-left:0">difficulty</td><td class="muted">beginner (12 spaces) / standard (18) / advanced (24)</td></tr>
                <tr><td class="bold" style="padding-left:0">subjects</td><td class="muted">math, literacy, english, science, nature, logic, life, geography</td></tr>
                <tr><td class="bold" style="padding-left:0">missions</td><td class="muted">A list of cards, each with question, answer, subject and sticker</td></tr>
            </table>
        </div>
    </div>

</div>
