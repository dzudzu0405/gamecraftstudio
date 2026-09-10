<?php
use App\Core\Helper as H;
?>
<h1 style="margin:0 0 14px;font-size:21px;font-weight:800;">Confirm your email address</h1>

<p style="margin:0 0 18px;">
  Hello <?= H::e($firstName) ?>, this is the one-off code that confirms this address
  belongs to you. Type it on the page that is waiting in your browser.
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
  <tr><td style="border-radius:12px;background:#F4EFFF;border:1px solid #DCD0F7;padding:16px 26px;">
    <span style="font-size:30px;font-weight:800;letter-spacing:.22em;color:#6C4BD6;font-family:'Courier New',Courier,monospace;">
      <?= H::e($code) ?>
    </span>
  </td></tr>
</table>

<p style="margin:0 0 14px;font-size:13px;color:#7A7288;">
  The code stops working in <?= (int) $minutes ?> minutes, and can only be used once.
  You are only asked for it the first time you sign in with a password.
</p>

<p style="margin:0;padding:12px 14px;background:#FDF1DC;border-radius:8px;font-size:13px;color:#96631A;">
  Did not ask for this? Ignore this email - nobody can get into the account without
  both the password and this code.
</p>
