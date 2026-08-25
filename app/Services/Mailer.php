<?php
namespace App\Services;

use App\Core\Config;
use App\Core\View;

/**
 * Sends email over SMTP, written directly against the protocol with fsockopen
 * so nothing outside PHP is required - no Composer, no PHPMailer.
 *
 * WHY SMTP RATHER THAN mail()
 * ---------------------------------------------------------------------------
 * PHP's mail() hands the message to the server's local sendmail. On shared
 * hosting that mail usually has no SPF or DKIM signature behind it, so Gmail
 * and Outlook drop it into spam or reject it outright. Sending through a real
 * mailbox on your own domain - the one cPanel gives you - means the message is
 * properly authenticated and lands in the inbox.
 *
 * SETTING IT UP ON cPANEL
 *   1. cPanel > Email Accounts > Create, for example noreply@yourdomain.com
 *   2. On that account choose "Connect Devices" to see the SMTP settings
 *   3. Copy host, port and the password into the 'mail' block of config.php
 *
 * Typical cPanel values:
 *   host       mail.yourdomain.com
 *   port       465  with encryption 'ssl'   (or 587 with 'tls')
 *   username   the full address, noreply@yourdomain.com
 *
 * THE 'log' DRIVER
 * ---------------------------------------------------------------------------
 * Set 'driver' => 'log' and nothing is sent: the whole message is appended to
 * storage/logs/mail.log instead. That makes it possible to walk through the
 * password reset flow locally without any mail credentials at all.
 */
class Mailer
{
    /** Anything slower than this and we give up rather than hang the page */
    private const TIMEOUT = 20;

    /** Longest SMTP line we will read in one go */
    private const MAX_LINE = 8192;

    private static ?string $lastError = null;

    /** The reason the last send() failed, or null if it succeeded */
    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    public static function isConfigured(): bool
    {
        $cfg = Config::get('mail', []);

        if (($cfg['driver'] ?? 'smtp') === 'log') {
            return true;
        }

        return !empty($cfg['host']) && !empty($cfg['username']) && !empty($cfg['from_email']);
    }

    /**
     * Renders one of the templates in app/Views/emails and sends it.
     *
     * @param string $template Template name, e.g. 'welcome' or 'password-reset'
     * @param array  $data     Variables for the template. 'subject' is required.
     */
    public static function sendTemplate(string $to, string $toName, string $template, array $data): bool
    {
        $subject = (string) ($data['subject'] ?? 'GameCraft Studio');

        $html = View::render('emails/' . $template, $data, 'emails/layout');
        $text = self::htmlToText($html);

        return self::send($to, $toName, $subject, $html, $text);
    }

    /**
     * Sends one message. Returns false and records lastError() on failure -
     * it never throws, because a mail problem must not break the page the
     * user is on.
     */
    public static function send(string $to, string $toName, string $subject, string $html, string $text = ''): bool
    {
        self::$lastError = null;

        $cfg = Config::get('mail', []);

        if (($cfg['enabled'] ?? true) === false) {
            self::$lastError = 'Email sending is switched off in config.php.';
            return false;
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'The recipient address is not valid.';
            return false;
        }

        $fromEmail = (string) ($cfg['from_email'] ?? $cfg['username'] ?? '');
        $fromName  = (string) ($cfg['from_name'] ?? 'GameCraft Studio');

        if ($fromEmail === '') {
            self::$lastError = 'No sender address is configured (mail.from_email in config.php).';
            return false;
        }

        $message = self::buildMessage($to, $toName, $fromEmail, $fromName, $subject, $html, $text, $cfg);

        if (($cfg['driver'] ?? 'smtp') === 'log') {
            return self::writeToLog($to, $subject, $message);
        }

        try {
            return self::sendSmtp($fromEmail, $to, $message, $cfg);
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            error_log('[GameCraft mail] ' . $e->getMessage());
            return false;
        }
    }

    // ---------------------------------------------------------------
    //  Building the message
    // ---------------------------------------------------------------

    private static function buildMessage(
        string $to,
        string $toName,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $html,
        string $text,
        array $cfg
    ): string {
        if (trim($text) === '') {
            $text = self::htmlToText($html);
        }

        $boundary = 'gc_' . bin2hex(random_bytes(12));
        $domain   = substr(strrchr($fromEmail, '@') ?: '@localhost', 1);

        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>';
        $headers[] = 'From: ' . self::formatAddress($fromName, $fromEmail);
        $headers[] = 'To: ' . self::formatAddress($toName, $to);

        $replyTo = trim((string) ($cfg['reply_to'] ?? ''));
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $headers[] = 'Subject: ' . self::encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'X-Mailer: GameCraft Studio';

        // Plain text first, HTML second: mail clients show the last part they can render
        $body = [];
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/plain; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: quoted-printable';
        $body[] = '';
        $body[] = quoted_printable_encode($text);
        $body[] = '';
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/html; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: quoted-printable';
        $body[] = '';
        $body[] = quoted_printable_encode($html);
        $body[] = '';
        $body[] = '--' . $boundary . '--';

        return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $body);
    }

    private static function formatAddress(string $name, string $email): string
    {
        $name = trim($name);
        return $name === '' ? $email : self::encodeHeader($name) . ' <' . $email . '>';
    }

    /** RFC 2047 encoding, so accented names survive the header */
    private static function encodeHeader(string $value): string
    {
        if (preg_match('/^[\x20-\x7E]*$/', $value)) {
            return $value;
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    /** A readable plain-text version, for clients that will not show HTML */
    public static function htmlToText(string $html): string
    {
        $text = preg_replace('#<(script|style)\b.*?</\1>#is', '', $html) ?? $html;

        // Turn links into "label (url)" so they survive the conversion
        $text = preg_replace_callback(
            '#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is',
            function ($m) {
                $label = trim(strip_tags($m[2]));
                $url   = trim($m[1]);
                return $label === '' || $label === $url ? $url : $label . ' ( ' . $url . ' )';
            },
            $text
        ) ?? $text;

        $text = preg_replace('#<(br|/p|/div|/h[1-6]|/tr|/li)\s*/?>#i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Tidy the whitespace the HTML indentation leaves behind
        $lines = array_map('trim', explode("\n", $text));
        $out   = [];
        $blank = 0;
        foreach ($lines as $line) {
            if ($line === '') {
                if (++$blank > 1) {
                    continue;
                }
            } else {
                $blank = 0;
            }
            $out[] = $line;
        }

        return trim(implode("\n", $out));
    }

    // ---------------------------------------------------------------
    //  The 'log' driver
    // ---------------------------------------------------------------

    private static function writeToLog(string $to, string $subject, string $message): bool
    {
        $dir  = dirname(__DIR__, 2) . '/storage/logs';
        @mkdir($dir, 0775, true);

        $entry = str_repeat('=', 74) . "\n"
               . date('Y-m-d H:i:s') . '  TO: ' . $to . '  SUBJECT: ' . $subject . "\n"
               . str_repeat('=', 74) . "\n"
               . $message . "\n\n";

        return (bool) @file_put_contents($dir . '/mail.log', $entry, FILE_APPEND);
    }

    // ---------------------------------------------------------------
    //  Talking SMTP
    // ---------------------------------------------------------------

    private static function sendSmtp(string $from, string $to, string $message, array $cfg): bool
    {
        $host       = (string) ($cfg['host'] ?? '');
        $port       = (int)    ($cfg['port'] ?? 465);
        $encryption = strtolower((string) ($cfg['encryption'] ?? 'ssl'));
        $username   = (string) ($cfg['username'] ?? '');
        $password   = (string) ($cfg['password'] ?? '');

        if ($host === '') {
            throw new \RuntimeException('No SMTP host is configured (mail.host in config.php).');
        }

        // Port 465 speaks TLS from the first byte; 587 starts plain and upgrades
        $address = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);

        $socket = @stream_socket_client(
            $address,
            $errNo,
            $errStr,
            self::TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new \RuntimeException(
                'Could not reach the mail server at ' . $host . ':' . $port
                . ($errStr !== '' ? ' - ' . $errStr : '')
                . '. Check the host and port, and that your hosting allows outbound SMTP.'
            );
        }

        stream_set_timeout($socket, self::TIMEOUT);

        try {
            self::expect($socket, 220);

            $helo = self::heloName($from);
            self::command($socket, 'EHLO ' . $helo, 250);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', 220);
                $ok = @stream_socket_enable_crypto(
                    $socket,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                    | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                    | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                );
                if (!$ok) {
                    throw new \RuntimeException('The server refused to start TLS. Try port 465 with encryption set to ssl.');
                }
                // The handshake resets the session, so greet again
                self::command($socket, 'EHLO ' . $helo, 250);
            }

            if ($username !== '') {
                self::command($socket, 'AUTH LOGIN', 334);
                self::command($socket, base64_encode($username), 334);
                self::command($socket, base64_encode($password), 235);
            }

            self::command($socket, 'MAIL FROM:<' . $from . '>', 250);
            self::command($socket, 'RCPT TO:<' . $to . '>', 250);
            self::command($socket, 'DATA', 354);

            // A lone dot on its own line ends the message, so any real one is doubled
            $payload = preg_replace('/^\./m', '..', $message);
            self::write($socket, $payload . "\r\n.");
            self::expect($socket, 250);

            self::write($socket, 'QUIT');

            return true;

        } finally {
            @fclose($socket);
        }
    }

    /** The name we introduce ourselves with - some servers reject a bare 'localhost' */
    private static function heloName(string $from): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $host = preg_replace('/:\d+$/', '', $host) ?? '';

        if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            $host = substr(strrchr($from, '@') ?: '@localhost', 1);
        }

        return $host !== '' ? $host : 'localhost';
    }

    private static function command($socket, string $line, int $expected): string
    {
        self::write($socket, $line);
        return self::expect($socket, $expected);
    }

    private static function write($socket, string $line): void
    {
        if (@fwrite($socket, $line . "\r\n") === false) {
            throw new \RuntimeException('Lost the connection while talking to the mail server.');
        }
    }

    /** Reads a reply, following multi-line responses to the end */
    private static function expect($socket, int $expected): string
    {
        $reply = '';

        while (true) {
            $line = @fgets($socket, self::MAX_LINE);
            if ($line === false) {
                $meta = stream_get_meta_data($socket);
                if (!empty($meta['timed_out'])) {
                    throw new \RuntimeException('The mail server stopped responding.');
                }
                throw new \RuntimeException('No reply from the mail server.');
            }

            $reply .= $line;

            // "250-" means more lines follow; "250 " means this was the last
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $code = (int) substr($reply, 0, 3);

        if ($code !== $expected) {
            throw new \RuntimeException(self::explain($code, trim($reply)));
        }

        return $reply;
    }

    /** Turns an SMTP error code into something worth reading */
    private static function explain(int $code, string $reply): string
    {
        $hint = match (true) {
            $code === 535 => 'The mail server rejected the username or password. Check mail.username and mail.password in config.php - the username is normally the full email address.',
            $code === 530 => 'The mail server wants authentication. Fill in mail.username and mail.password in config.php.',
            $code === 550 => 'The mail server refused the address. Check that mail.from_email is a real mailbox on your domain.',
            $code === 554 => 'The mail server rejected the message, often because the sender domain does not match the mailbox.',
            default       => 'The mail server replied with an error.',
        };

        return $hint . ' (server said: ' . $reply . ')';
    }
}
