<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 2592000,
    'path' => '/',
    'httponly' => true,
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'samesite' => 'Lax',
]);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function loadDotEnv(string $file): void {
    if (!is_readable($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if ($name !== '' && getenv($name) === false) putenv($name . '=' . $value);
    }
}

loadDotEnv(dirname(__DIR__) . '/.env');

function envValue(string $name, string $default = ''): string {
    $value = getenv($name);
    return $value === false ? $default : trim($value);
}

function respond(mixed $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestBody(): array {
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

function authorizeUser(string $username, string $password): string {
    $baseUrl = rtrim(envValue('MYGES_AUTH_BASE_URL', 'https://authentication.kordis.fr/oauth'), '/');
    $clientId = envValue('MYGES_CLIENT_ID', 'skolae-app');
    $url = $baseUrl . '/authorize?' . http_build_query([
        'client_id' => $clientId,
        'response_type' => 'token',
    ]);
    $location = '';
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_USERPWD => $username . ':' . $password,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$location): int {
            if (stripos($header, 'Location:') === 0) $location = trim(substr($header, 9));
            return strlen($header);
        },
    ]);
    $raw = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    if ($raw === false) respond(['error' => 'Le service MyGES est temporairement injoignable.'], 502);
    if ($status === 401 || $status === 403) respond(['error' => 'Identifiants MyGES invalides.'], 401);
    if ($status < 300 || $status >= 400 || $location === '') respond(['error' => 'Le service MyGES n’a pas retourné de jeton.'], 502);
    $fragment = parse_url($location, PHP_URL_FRAGMENT);
    parse_str(is_string($fragment) ? $fragment : '', $parameters);
    $token = $parameters['access_token'] ?? null;
    if (!is_string($token) || $token === '') respond(['error' => 'Le service MyGES n’a pas retourné de jeton.'], 502);
    return $token;
}

function upstream(string $path, string $method = 'GET', ?array $body = null, ?string $token = null, array $query = [], bool $retryableBadRequest = false): array {
    $baseUrl = rtrim(envValue('MYGES_API_BASE_URL', 'https://api.kordis.fr'), '/');
    if ($baseUrl === '') respond(['error' => 'Le proxy MyGES n’est pas configuré.'], 500);
    $url = $baseUrl . '/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
    $headers = ['Accept: application/json, application/xml;q=0.9'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    if ($body !== null) $headers[] = 'Content-Type: application/json';

    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_POSTFIELDS => $body === null ? null : json_encode($body),
    ]);
    $raw = curl_exec($handle);
    $error = curl_error($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $contentType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    curl_close($handle);
    if ($raw === false) {
        error_log('MyGES upstream network error: ' . ($error ?: 'unknown cURL error'));
        respond(['error' => 'Le service MyGES est temporairement injoignable.', 'diagnostic' => $error ?: 'Erreur réseau cURL.'], 502);
    }
    if ($status === 401 || $status === 403) {
        respond(['error' => 'Session MyGES expirée, veuillez vous reconnecter.'], 401);
    }
    if ($status === 204) {
        return [];
    }
    if ($retryableBadRequest && in_array($status, [400, 404], true)) {
        return ['__upstream_status' => $status, '__upstream_body' => $raw];
    }
    $normalizedRaw = ltrim($raw, "\xEF\xBB\xBF \t\r\n");
    $decoded = json_decode($normalizedRaw, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
    if (json_last_error() === JSON_ERROR_NONE && $decoded === null) $decoded = [];
    if (!is_array($decoded)) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($normalizedRaw);
        if ($xml !== false) $decoded = json_decode(json_encode($xml), true);
        libxml_clear_errors();
    }
    if (!is_array($decoded)) {
        $bodyPreview = trim(preg_replace('/\s+/', ' ', strip_tags($normalizedRaw)) ?? '');
        $bodyPreview = function_exists('mb_substr') ? mb_substr($bodyPreview, 0, 160) : substr($bodyPreview, 0, 160);
        error_log(sprintf('MyGES upstream invalid response: HTTP %d, type %s, body %s', $status, $contentType ?: 'unknown', $bodyPreview ?: 'empty'));
        respond(['error' => 'Réponse invalide du service MyGES.', 'diagnostic' => "HTTP {$status}, type " . ($contentType ?: 'inconnu') . ($bodyPreview !== '' ? ": {$bodyPreview}" : '')], 502);
    }
    if ($status < 200 || $status >= 300) {
        $upstreamError = $decoded['error'] ?? '';
        $message = $decoded['message'] ?? $upstreamError ?? 'Identifiants invalides ou service indisponible.';
        error_log(sprintf('MyGES upstream error: HTTP %d, type %s, error %s', $status, $contentType ?: 'unknown', $upstreamError ?: 'unknown'));
        respond(['error' => $message, 'diagnostic' => "Réponse Kordis HTTP {$status}"], 502);
    }
    return $decoded;
}

$resource = $_GET['resource'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$routes = [
    'profile' => ['path' => '/me/profile', 'requiresAuth' => true],
    'planning' => ['path' => envValue('MYGES_PLANNING_PATH', '/planning'), 'requiresAuth' => true],
    'grades' => ['path' => envValue('MYGES_GRADES_PATH', '/grades'), 'requiresAuth' => true],
    'absences' => ['path' => envValue('MYGES_ABSENCES_PATH', '/absences'), 'requiresAuth' => true],
];

if ($resource === 'login' && $method === 'POST') {
    $body = requestBody();
    if (empty($body['username']) || empty($body['password'])) respond(['error' => 'Identifiant et mot de passe requis.'], 422);
    $token = authorizeUser((string) $body['username'], (string) $body['password']);
    session_regenerate_id(true);
    $_SESSION['access_token'] = $token;
    respond(['authenticated' => true, 'student' => null]);
}

if ($resource === 'logout' && $method === 'POST') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    respond(['authenticated' => false]);
}

if ($method !== 'GET' || !isset($routes[$resource])) respond(['error' => 'Ressource inconnue.'], 404);
if (empty($_SESSION['access_token'])) respond(['error' => 'Session expirée, veuillez vous reconnecter.'], 401);
$year = (int) date('Y');
$path = str_replace('{year}', (string) $year, $routes[$resource]['path']);
$query = [];
if ($resource === 'planning') {
    $start = strtotime('today UTC');
    $end = strtotime('+30 days', $start);
    $query = [
        'start' => gmdate('Y-m-d\T00:00:00.000\Z', $start),
        'end' => gmdate('Y-m-d\T00:00:00.000\Z', $end),
    ];
}
$payload = upstream($path, 'GET', null, $_SESSION['access_token'], $query, in_array($resource, ['planning', 'absences'], true));
if ($resource === 'planning' && isset($payload['__upstream_status'])) {
    $start = strtotime('today UTC');
    $end = strtotime('+30 days', $start);
    $variants = [
        ['start' => (string) ($start * 1000), 'end' => (string) ($end * 1000)],
        ['start' => gmdate('Y-m-d\TH:i:s\Z', $start), 'end' => gmdate('Y-m-d\TH:i:s\Z', $end)],
        ['start' => gmdate('Y-m-d', $start), 'end' => gmdate('Y-m-d', $end)],
    ];
    foreach ($variants as $variant) {
        $candidate = upstream($path, 'GET', null, $_SESSION['access_token'], $variant, true);
        if (!isset($candidate['__upstream_status'])) {
            $payload = $candidate;
            break;
        }
        $payload = $candidate;
    }
}
if ($resource === 'absences' && isset($payload['__upstream_status'])) {
    $configuredPath = $routes[$resource]['path'];
    $candidatePaths = [
        str_replace('{year}', (string) ($year - 1), $configuredPath),
        '/me/absences',
    ];
    foreach ($candidatePaths as $candidatePath) {
        $candidate = upstream($candidatePath, 'GET', null, $_SESSION['access_token'], [], true);
        if (!isset($candidate['__upstream_status'])) {
            $payload = $candidate;
            break;
        }
        $payload = $candidate;
    }
}
if (isset($payload['__upstream_status'])) {
    $label = $resource === 'planning' ? 'Le format de période du planning est refusé par MyGES.' : 'L’endpoint des absences est refusé par MyGES.';
    respond(['error' => $label, 'diagnostic' => 'HTTP 400/404 après plusieurs variantes d’endpoint.'], 502);
}
respond($payload['result'] ?? $payload['data'] ?? $payload);
