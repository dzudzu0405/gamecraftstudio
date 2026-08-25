<?php
use App\Core\Helper as H;
use App\Core\Url;
?>
<h1 style="margin:0 0 14px;font-size:21px;font-weight:800;">Welcome aboard, <?= H::e($firstName) ?>!</h1>

<p style="margin:0 0 14px;">
  Your GameCraft Studio account is ready. You are on the
  <strong><?= H::e($planName) ?></strong> plan, which unlocks
  <?= (int) $maps ?> map frames and <?= (int) $characters ?> character sets.
</p>

<p style="margin:0 0 10px;font-weight:700;">Making your first game takes five steps:</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
  <tr><td style="padding:3px 0;font-size:14px;">1. Pick a difficulty, theme and map frame</td></tr>
  <tr><td style="padding:3px 0;font-size:14px;">2. Copy the image prompt we write for you</td></tr>
  <tr><td style="padding:3px 0;font-size:14px;">3. Generate a background and upload it</td></tr>
  <tr><td style="padding:3px 0;font-size:14px;">4. Let us match the mission cards</td></tr>
  <tr><td style="padding:3px 0;font-size:14px;">5. Preview and export a print-ready file</td></tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
  <tr><td style="border-radius:10px;background:#6C4BD6;">
    <a href="<?= H::e(Url::full('/create')) ?>"
       style="display:inline-block;padding:12px 24px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">
      Create your first game
    </a>
  </td></tr>
</table>

<p style="margin:0;font-size:13px;color:#7A7288;">
  Not sure where to begin? The
  <a href="<?= H::e(Url::full('/templates')) ?>" style="color:#6C4BD6;">ready-made templates</a>
  give you a finished game to start from.
</p>
