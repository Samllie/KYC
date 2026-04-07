<?php
/**
 * Device-known account helpers for safer Switch Account behavior.
 *
 * Stores a signed list of emails that have successfully logged in from
 * this browser/device. Used only for account suggestion/switch list.
 */

if (!defined('DEVICE_ACCOUNTS_COOKIE_NAME')) {
    define('DEVICE_ACCOUNTS_COOKIE_NAME', 'kyc_device_accounts');
}

if (!defined('DEVICE_ACCOUNTS_VERSION')) {
    define('DEVICE_ACCOUNTS_VERSION', 1);
}

if (!defined('DEVICE_ACCOUNTS_MAX_ITEMS')) {
    define('DEVICE_ACCOUNTS_MAX_ITEMS', 20);
}

function deviceAccountsBase64UrlEncode($value)
{
    return rtrim(strtr(base64_encode((string)$value), '+/', '-_'), '=');
}

function deviceAccountsBase64UrlDecode($value)
{
    $normalized = strtr((string)$value, '-_', '+/');
    $padding = strlen($normalized) % 4;
    if ($padding > 0) {
        $normalized .= str_repeat('=', 4 - $padding);
    }

    return base64_decode($normalized, true);
}

function deviceAccountsCookieSecret()
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }

    $parts = [
        'kyc-device-accounts',
        'sterling-kyc-v1',
        __FILE__,
    ];

    $secret = hash('sha256', implode('|', $parts));
    return $secret;
}

function deviceAccountsCookieOptions($expiryTimestamp)
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    return [
        'expires' => intval($expiryTimestamp),
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function deviceAccountsReadPayload()
{
    $raw = $_COOKIE[DEVICE_ACCOUNTS_COOKIE_NAME] ?? '';
    if (!is_string($raw) || trim($raw) === '') {
        return [
            'version' => DEVICE_ACCOUNTS_VERSION,
            'accounts' => [],
        ];
    }

    $parts = explode('.', $raw, 2);
    if (count($parts) !== 2) {
        return [
            'version' => DEVICE_ACCOUNTS_VERSION,
            'accounts' => [],
        ];
    }

    $encodedPayload = trim((string)$parts[0]);
    $providedSignature = strtolower(trim((string)$parts[1]));
    $expectedSignature = hash_hmac('sha256', $encodedPayload, deviceAccountsCookieSecret());

    if (!hash_equals($expectedSignature, $providedSignature)) {
        return [
            'version' => DEVICE_ACCOUNTS_VERSION,
            'accounts' => [],
        ];
    }

    $decodedJson = deviceAccountsBase64UrlDecode($encodedPayload);
    if (!is_string($decodedJson) || $decodedJson === '') {
        return [
            'version' => DEVICE_ACCOUNTS_VERSION,
            'accounts' => [],
        ];
    }

    $decoded = json_decode($decodedJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return [
            'version' => DEVICE_ACCOUNTS_VERSION,
            'accounts' => [],
        ];
    }

    $accounts = [];
    $rawAccounts = $decoded['accounts'] ?? [];
    if (is_array($rawAccounts)) {
        foreach ($rawAccounts as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $email = strtolower(trim((string)($entry['email'] ?? '')));
            $lastSeenAt = intval($entry['last_seen_at'] ?? 0);

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if ($lastSeenAt <= 0) {
                $lastSeenAt = time();
            }

            $accounts[] = [
                'email' => $email,
                'last_seen_at' => $lastSeenAt,
            ];
        }
    }

    usort($accounts, function ($a, $b) {
        return intval($b['last_seen_at']) <=> intval($a['last_seen_at']);
    });

    if (count($accounts) > DEVICE_ACCOUNTS_MAX_ITEMS) {
        $accounts = array_slice($accounts, 0, DEVICE_ACCOUNTS_MAX_ITEMS);
    }

    return [
        'version' => DEVICE_ACCOUNTS_VERSION,
        'accounts' => $accounts,
    ];
}

function deviceAccountsWritePayload($payload)
{
    $accounts = [];
    $rawAccounts = is_array($payload['accounts'] ?? null) ? $payload['accounts'] : [];

    foreach ($rawAccounts as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $email = strtolower(trim((string)($entry['email'] ?? '')));
        $lastSeenAt = intval($entry['last_seen_at'] ?? 0);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $accounts[] = [
            'email' => $email,
            'last_seen_at' => $lastSeenAt > 0 ? $lastSeenAt : time(),
        ];
    }

    usort($accounts, function ($a, $b) {
        return intval($b['last_seen_at']) <=> intval($a['last_seen_at']);
    });

    if (count($accounts) > DEVICE_ACCOUNTS_MAX_ITEMS) {
        $accounts = array_slice($accounts, 0, DEVICE_ACCOUNTS_MAX_ITEMS);
    }

    $safePayload = [
        'version' => DEVICE_ACCOUNTS_VERSION,
        'accounts' => $accounts,
    ];

    $json = json_encode($safePayload, JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || $json === '') {
        return;
    }

    $encodedPayload = deviceAccountsBase64UrlEncode($json);
    $signature = hash_hmac('sha256', $encodedPayload, deviceAccountsCookieSecret());
    $cookieValue = $encodedPayload . '.' . $signature;

    $expiry = time() + (60 * 60 * 24 * 365);
    setcookie(DEVICE_ACCOUNTS_COOKIE_NAME, $cookieValue, deviceAccountsCookieOptions($expiry));
    $_COOKIE[DEVICE_ACCOUNTS_COOKIE_NAME] = $cookieValue;
}

function deviceAccountsRememberEmail($email)
{
    $normalizedEmail = strtolower(trim((string)$email));
    if ($normalizedEmail === '' || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $payload = deviceAccountsReadPayload();
    $accounts = is_array($payload['accounts'] ?? null) ? $payload['accounts'] : [];
    $lookup = [];

    foreach ($accounts as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $entryEmail = strtolower(trim((string)($entry['email'] ?? '')));
        if ($entryEmail === '' || !filter_var($entryEmail, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $lookup[$entryEmail] = [
            'email' => $entryEmail,
            'last_seen_at' => intval($entry['last_seen_at'] ?? time()),
        ];
    }

    $lookup[$normalizedEmail] = [
        'email' => $normalizedEmail,
        'last_seen_at' => time(),
    ];

    deviceAccountsWritePayload([
        'accounts' => array_values($lookup),
    ]);
}

function deviceAccountsGetKnownEmails()
{
    $payload = deviceAccountsReadPayload();
    $accounts = is_array($payload['accounts'] ?? null) ? $payload['accounts'] : [];

    $emails = [];
    foreach ($accounts as $entry) {
        $email = strtolower(trim((string)($entry['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        $emails[] = $email;
    }

    return array_values(array_unique($emails));
}

function deviceAccountsEmailAllowed($email)
{
    $normalizedEmail = strtolower(trim((string)$email));
    if ($normalizedEmail === '' || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    return in_array($normalizedEmail, deviceAccountsGetKnownEmails(), true);
}
