<?php
/**
 * Wrapper for every outgoing email.
 *
 * Email clients strip <style> blocks and ignore most modern CSS, so everything
 * here is inline styles on a table layout. It looks dated on purpose - it is
 * the only thing Outlook and Gmail render the same way.
 */
use App\Core\Helper as H;
use App\Core\Url;

$appName = 'GameCraft Studio';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= H::e($subject ?? $appName) ?></title>
</head>
<body style="margin:0;padding:0;background:#FAF6F0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#2B2438;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FAF6F0;padding:28px 12px;">
<tr><td align="center">

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background:#FFFFFF;border:1px solid #EFE7DB;border-radius:16px;overflow:hidden;">

    <tr>
      <td style="padding:24px 30px 8px;">
        <span style="display:inline-block;width:34px;height:34px;border-radius:10px;background:#6C4BD6;text-align:center;line-height:34px;color:#ffffff;font-weight:700;font-size:15px;">&nbsp;</span> <span style="font-size:16px;font-weight:700;vertical-align:middle;margin-left:8px;"><?= H::e($appName) ?></span>
      </td>
    </tr>

    <tr>
      <td style="padding:6px 30px 26px;font-size:15px;line-height:1.6;">
        <?= $content ?>
      </td>
    </tr>

    <tr>
      <td style="padding:16px 30px 22px;border-top:1px solid #EFE7DB;font-size:12px;line-height:1.6;color:#7A7288;">
        Printable adventure games for children.<br>
        <a href="<?= H::e(Url::full('/')) ?>" style="color:#6C4BD6;text-decoration:none;"><?= H::e(Url::full('/')) ?></a>
      </td>
    </tr>

  </table>

</td></tr>
</table>
</body>
</html>
