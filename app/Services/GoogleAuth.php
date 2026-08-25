<?php
namespace App\Services;

use App\Core\Config;
use App\Core\Url;

/**
 * Sign in with Google, written straight against the OAuth 2.0 endpoints.
 * No SDK, no Composer - just two HTTPS calls with cURL.
 *
 * THE FLOW
 *   1. authUrl()      sends the visitor to Google with a one-time state value
 *   2. Google returns them to /auth/google/callback carrying a short-lived code
 *   3. exchangeCode() swaps that code for an access token
 *   4. fetchProfile() reads their name, email and picture
 *
 * SETTING IT UP
 *   1. console.cloud.google.com/apis/credentials
 *   2. Create Credentials > OAuth client ID > Web application
 *   3. Authorised redirect URI must match redirectUri() below exactly,
 *      including https:// and any sub-folder
 *   4. Paste the client id and secret into the 'google' block of config.php
 *
 * Google rejects plain http:// redirect URIs, so the site has to run on HTTPS.
 * That is the one thing that cannot be worked around.
 */
class GoogleAuth
{
    private const AUTH_ENDPOINT     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_ENDPOINT = 'https://openidconnect.googleapis.com/v1/userinfo';

    private const TIMEOUT = 15;

    /** Is Google sign-in switched on? */
    public static function isEnabled(): bool
    {
        return self::clientId() !== '' && self::clientSecret() !== '';
    }

    public static function clientId(): string
    {
        return trim((string) Config::get('google.client_id', ''));
    }

    private static function clientSecret(): string
    {
        return trim((string) Config::get('google.client_secret', ''));
    }

    /** The address Google sends people back to. Must match the console entry exactly. */
    public static function redirectUri(): string
    {
        return Url::full('/auth/google/callback');
    }

    /**
     * Where to send the visitor to start signing in.
     * The state value is the CSRF guard for the round trip - it is stored in
     * the session and has to come back unchanged.
     */
    public static function authUrl(string $state): string
    {
        return self::AUTH_ENDPOINT . '?' . http_build_query([
            'client_id'     => self::clientId(),
            'redirect_uri'  => self::redirectUri(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
            // Nothing happens offline, so there is no reason to ask for a refresh token
            'access_type'   => 'online',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Trades the one-time code for an access token.
     * @throws \RuntimeException with a message worth showing to the site owner
     */
    public static function exchangeCode(string $code): string
    {
        $response = self::post(self::TOKEN_ENDPOINT, [
            'code'          => $code,
            'client_id'     => self::clientId(),
            'client_secret' => self::clientSecret(),
            'redirect_uri'  => self::redirectUri(),
            'grant_type'    => 'authorization_code',
        ]);

        if (isset($response['error'])) {
            throw new \RuntimeException(self::explainTokenError($response));
        }

        $token = (string) ($response['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Google did not return an access token.');
        }

        return $token;
    }

    /**
     * Reads the signed-in person's details.
     * @return array{id:string,email:string,name:string,picture:string,verified:bool}
     */
    public static function fetchProfile(string $accessToken): array
    {
        $data = self::get(self::USERINFO_ENDPOINT, $accessToken);

        $id    = (string) ($data['sub'] ?? '');
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($id === '' || $email === '') {
            throw new \RuntimeException('Google did not return an email address for that account.');
        }

        return [
            'id'       => $id,
            'email'    => $email,
            'name'     => trim((string) ($data['name'] ?? '')) ?: strstr($email, '@', true),
            'picture'  => (string) ($data['picture'] ?? ''),
            'verified' => (bool) ($data['email_verified'] ?? false),
        ];
    }

    // ---------------------------------------------------------------
    //  HTTP
    // ---------------------------------------------------------------

    private static function post(string $url, array $fields): array
    {
        $ch = self::curl($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        return self::run($ch);
    }

    private static function get(string $url, string $accessToken): array
    {
        $ch = self::curl($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);

        return self::run($ch);
    }

    private static function curl(string $url)
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('The cURL extension is not enabled on this server, so Google sign-in cannot work. Turn it on under cPanel > Select PHP Version > Extensions.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT      => 'GameCraft Studio',
        ]);

        return $ch;
    }

    private static function run($ch): array
    {
        $body   = curl_exec($ch);
        $errNo  = curl_errno($ch);
        $errStr = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errNo !== 0) {
            throw new \RuntimeException('Could not reach Google: ' . $errStr);
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Google sent back something unreadable (HTTP ' . $status . ').');
        }

        return $data;
    }

    /** Turns Google's terse error codes into something actionable */
    private static function explainTokenError(array $response): string
    {
        $error       = (string) ($response['error'] ?? 'unknown');
        $description = (string) ($response['error_description'] ?? '');

        $hint = match ($error) {
            'redirect_uri_mismatch' =>
                'The redirect address does not match the one registered with Google. Add exactly this URI to your OAuth client: ' . self::redirectUri(),
            'invalid_client' =>
                'Google rejected the client id or secret. Check the google block in config.php.',
            'invalid_grant' =>
                'That sign-in code was already used or has expired. Please try signing in again.',
            default =>
                'Google refused the sign-in (' . $error . ').',
        };

        return $description !== '' ? $hint . ' Google said: ' . $description : $hint;
    }
}
