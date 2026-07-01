<?php

final class GoogleOAuth
{
    public static function isConfigured(): bool
    {
        return (bool) Config::get('GOOGLE_CLIENT_ID') && (bool) Config::get('GOOGLE_CLIENT_SECRET');
    }

    public static function authorizeUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => Config::get('GOOGLE_CLIENT_ID'),
            'redirect_uri' => Config::get('GOOGLE_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * @return array{id:string,email:string,name:string,picture:?string}|null
     */
    public static function handleCallback(string $code, string $state): ?array
    {
        if (empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
            return null;
        }
        unset($_SESSION['google_oauth_state']);

        $tokenResponse = self::post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => Config::get('GOOGLE_CLIENT_ID'),
            'client_secret' => Config::get('GOOGLE_CLIENT_SECRET'),
            'redirect_uri' => Config::get('GOOGLE_REDIRECT_URI'),
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($tokenResponse['access_token'])) {
            return null;
        }

        $userInfo = self::get('https://www.googleapis.com/oauth2/v3/userinfo', $tokenResponse['access_token']);
        if (!isset($userInfo['sub'], $userInfo['email'])) {
            return null;
        }

        return [
            'id' => $userInfo['sub'],
            'email' => $userInfo['email'],
            'name' => $userInfo['name'] ?? $userInfo['email'],
            'picture' => $userInfo['picture'] ?? null,
        ];
    }

    private static function post(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string) $body, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function get(string $url, string $accessToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string) $body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
