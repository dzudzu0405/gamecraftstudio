<?php
use App\Core\Helper as H;
?>
<h1 style="margin:0 0 14px;font-size:21px;font-weight:800;">Reset your password</h1>

<p style="margin:0 0 14px;">
  Hello <?= H::e($firstName) ?>, we received a request to reset the password on your
  GameCraft Studio account. Click the button below to choose a new one.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
  <tr><td style="border-radius:10px;background:#6C4BD6;">
    <a href="<?= H::e($resetUrl) ?>"
       style="display:inline-block;padding:12px 24px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">
      Choose a new password
    </a>
  </td></tr>
</table>

<p style="margin:0 0 14px;font-size:13px;color:#7A7288;">
  This link stops working in <?= (int) $expiresInMinutes ?> minutes, and can only be used once.
</p>

<p style="margin:0 0 6px;font-size:13px;color:#7A7288;">
  If the button does not work, copy this address into your browser:
</p>
<p style="margin:0 0 18px;font-size:12px;word-break:break-all;">
  <a href="<?= H::e($resetUrl) ?>" style="color:#6C4BD6;"><?= H::e($resetUrl) ?></a>
</p>

<p style="margin:0;padding:12px 14px;background:#FDF1DC;border-radius:8px;font-size:13px;color:#96631A;">
  Did not ask for this? You can ignore this email - your password stays exactly as it is.
</p>
