<?php
/**
 * Step 3 - the map background and the story.
 * FR-30: the system writes the map background prompt.
 * FR-31: upload the result and compose it with the chosen map frame.
 */
use App\Core\Csrf;
use App\Core\Helper as H;
use App\Core\Icon;
use App\Core\Url;
use App\Core\View;
use App\Models\Project;
use App\Services\Uploader;

echo View::partial('partials/stepbar', compact('project', 'step', 'labels'));
$pid = (int) $project['id'];
?>

<div class="wizard">
    <div class="wizard__main">

        <?php $ownBackground = !Project::usesThemeBackground($project); ?>

        <div class="notice notice--info">
            <?= Icon::get('info', 17) ?>
            <span>
                <?php if ($ownBackground): ?>
                    Two prompts on this page: one for the map background, one for the story.
                    Copy them both, run them in the same chat, and bring the picture and the
                    words back here.
                <?php else: ?>
                    Your map uses a ready-made scene, so there is no picture to make. Copy the
                    story prompt below, run it wherever you like, and paste the story back here.
                <?php endif; ?>
            </span>
        </div>

        <?php if ($ownBackground): ?>

        <!-- 1. The prompt -->
        <div class="card mb-2">
            <div class="card__head">
                <h3>1. Copy the prompt</h3>
                <span class="small muted">Already sized 16:11 with room for <?= (int) $project['cells'] ?> spaces</span>
            </div>
            <div class="card__body">
                <div class="prompt-box">
                    <div class="prompt-box__head">
                        <?= Icon::get('sparkles', 15) ?>
                        <span>Map background prompt</span>
                        <span class="spacer"></span>
                        <button type="button" class="btn btn--sm btn--copy" data-copy="#prompt-text">
                            <?= Icon::get('copy', 14) ?> Copy prompt
                        </button>
                    </div>
                    <pre class="prompt-box__text" id="prompt-text"><?= H::e($prompt) ?></pre>
                </div>

                <div class="flex gap-1 flex-wrap mt-2">
                    <a class="btn btn--ghost btn--sm" href="https://chatgpt.com" target="_blank" rel="noopener noreferrer">
                        <?= Icon::get('external', 14) ?> Open ChatGPT
                    </a>
                    <a class="btn btn--ghost btn--sm" href="https://gemini.google.com" target="_blank" rel="noopener noreferrer">
                        <?= Icon::get('external', 14) ?> Open Gemini
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Upload -->
        <div class="card">
            <div class="card__head">
                <h3>2. Upload the background</h3>
                <?php if ($background): ?>
                    <span class="badge badge--ready"><?= Icon::get('check', 11) ?> Uploaded</span>
                <?php endif; ?>
            </div>
            <div class="card__body">

                <?php if ($background): ?>
                    <div class="upload-preview mb-2">
                        <img src="<?= Url::upload($background['thumb_path'] ?: $background['path']) ?>" alt="Uploaded background">
                    </div>
                    <div class="small muted mb-2">
                        <?= H::e($background['original_name'] ?? 'background') ?> &middot;
                        <?= (int) $background['width'] ?>&times;<?= (int) $background['height'] ?> &middot;
                        <?= H::e(\App\Services\Uploader::formatBytes((int) $background['size_bytes'])) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= Url::to('/create/' . $pid . '/upload') ?>" enctype="multipart/form-data">
                    <?= Csrf::field() ?>

                    <label class="dropzone" data-dropzone data-preview="#bg-preview" data-autosubmit>
                        <div class="dropzone__icon"><?= Icon::get('upload', 30) ?></div>
                        <div class="dropzone__title">
                            <?= $background ? 'Replace with a different image' : 'Drag an image here' ?>
                        </div>
                        <div class="dropzone__hint" data-dropzone-label>
                            or click to browse &middot; JPG, PNG, WEBP &middot; up to <?= H::e(Uploader::maxUploadLabel()) ?>
                        </div>
                        <input type="file" name="background" accept="image/jpeg,image/png,image/webp">
                    </label>

                    <div id="bg-preview" class="upload-preview mt-2 hidden"></div>

                    <noscript>
                        <button class="btn btn--primary mt-2" type="submit">Upload</button>
                    </noscript>
                </form>

                <p class="small muted mt-2 mb-0">
                    No image yet? That is fine - a placeholder scene is used so you can carry on,
                    and you can swap it in at any time.
                </p>
            </div>
        </div>

        <?php endif; ?>

        <!-- The story - the one part every game needs -->
        <div class="card<?= $ownBackground ? ' mt-2' : '' ?>" id="story">
            <div class="card__head">
                <h3><?= $ownBackground ? '3. ' : '' ?>Write the story</h3>
                <?php if (trim((string) $project['story']) !== ''): ?>
                    <span class="badge badge--ready"><?= Icon::get('check', 11) ?> Written</span>
                <?php endif; ?>
                <span class="spacer" style="flex:1"></span>
                <span class="small muted">Printed as its own page</span>
            </div>
            <div class="card__body">

                <p class="small muted mb-2">
                    <?= $ownBackground
                        ? 'While you are in there, paste this second prompt as well.'
                        : 'Paste this into ChatGPT, Gemini or whichever tool you use.' ?>
                    It already carries everything you chose at step 1 - the adventure, the
                    hero, who needs rescuing, the age of the children - and asks for the
                    story in
                    <b><?= H::e(\App\Services\Lang::name(\App\Services\Lang::of($project))) ?></b>.
                </p>

                <div class="prompt-box">
                    <div class="prompt-box__head">
                        <?= Icon::get('sparkles', 15) ?>
                        <span>Story prompt</span>
                        <span class="spacer"></span>
                        <button type="button" class="btn btn--sm btn--copy" data-copy="#story-prompt-text">
                            <?= Icon::get('copy', 14) ?> Copy prompt
                        </button>
                    </div>
                    <pre class="prompt-box__text" id="story-prompt-text"><?= H::e($storyPrompt) ?></pre>
                </div>

                <?php if (!$ownBackground): ?>
                    <?php /* The links live with the background prompt when there is one */ ?>
                    <div class="flex gap-1 flex-wrap mt-2">
                        <a class="btn btn--ghost btn--sm" href="https://chatgpt.com" target="_blank" rel="noopener noreferrer">
                            <?= Icon::get('external', 14) ?> Open ChatGPT
                        </a>
                        <a class="btn btn--ghost btn--sm" href="https://gemini.google.com" target="_blank" rel="noopener noreferrer">
                            <?= Icon::get('external', 14) ?> Open Gemini
                        </a>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= Url::to('/create/' . $pid . '/story') ?>" class="mt-2">
                    <?= Csrf::field() ?>

                    <div class="field">
                        <label class="label" for="story-text">
                            Paste the story here
                            <span class="label__hint">(you can edit it afterwards in the Studio)</span>
                        </label>
                        <textarea class="textarea" id="story-text" name="story" maxlength="8000"
                                  style="min-height:200px"
                                  data-word-count="#story-words"
                                  data-word-limit="<?= (int) $storyWords ?>"
                                  data-word-limit-first="<?= (int) $storyWordsFirst ?>"
                                  placeholder="Paste what the AI wrote, or write your own story straight into this box."><?= H::e($project['story']) ?></textarea>
                    </div>

                    <div class="flex items-center gap-1 flex-wrap">
                        <button class="btn btn--primary" type="submit">
                            <?= Icon::get('check', 16) ?> Save the story
                        </button>
                        <span class="small muted" id="story-words"></span>
                    </div>

                    <p class="small muted mt-2 mb-0">
                        <b><i>Read it through before you print.</i></b> An AI can drift off the
                        subject or write something that does not suit the age you chose - and
                        this is the page a child hears first. Anything over
                        <?= (int) $storyWordsFirst ?> words runs on to a second printed sheet, which
                        is allowed. Leave the box empty and the game simply prints without a story page.
                    </p>
                </form>
            </div>
        </div>

    </div>

    <aside class="wizard__side">
        <div class="card mb-2">
            <div class="card__body">
                <div class="small muted mb-1">Composed map</div>
                <div style="border-radius:10px;overflow:hidden;border:1px solid var(--line)">
                    <img src="<?= Url::to('art/map/' . $pid . '.svg?w=800&h=550') ?>"
                         alt="Composed map preview" style="width:100%;display:block">
                </div>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card__body">
                <div class="bold mb-1" style="font-size:13px">What to do</div>
                <ol class="howto-list">
                    <?php foreach ($instructions as $line): ?>
                        <li><?= H::e($line) ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>

        <form method="post" action="<?= Url::to('/create/' . $pid . '/step/3') ?>">
            <?= Csrf::field() ?>
            <button class="btn btn--primary btn--lg btn--block" type="submit">
                Continue <?= Icon::get('arrow-right', 17) ?>
            </button>
        </form>
        <a class="btn btn--ghost btn--block mt-1" href="<?= Url::to('/create/' . $pid . '/step/2') ?>">
            <?= Icon::get('arrow-left', 15) ?> Back
        </a>
    </aside>
</div>
