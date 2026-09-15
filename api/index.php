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
    $url = $baseUrl . '/authorize?' . http_build_query([
        'client_id' => envValue('MYGES_CLIENT_ID', 'skolae-app'),
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

function authorizeMygesWeb(string $username, string $password): array {
    $jar = tempnam(sys_get_temp_dir(), 'myges-');
    if ($jar === false) return ['cookie' => '', 'cookie_jar' => '', 'student' => []];
    $handle = curl_init('https://myges.fr/student/marks');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true]);
    curl_exec($handle);
    $loginUrl = (string) curl_getinfo($handle, CURLINFO_REDIRECT_URL);
    curl_close($handle);
    if ($loginUrl === '') { @unlink($jar); return ['cookie' => '', 'cookie_jar' => '', 'student' => []]; }
    $handle = curl_init($loginUrl);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true]);
    $loginPage = (string) curl_exec($handle);
    curl_close($handle);
    if (!preg_match('/<form[^>]+action="([^"]+)"[^>]*>.*?<input[^>]+name="lt"[^>]+value="([^"]+)"/is', $loginPage, $matches)) { @unlink($jar); return ['cookie' => '', 'cookie_jar' => '', 'student' => []]; }
    $action = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $action = str_starts_with($action, 'http') ? $action : 'https://ges-cas.kordis.fr' . '/' . ltrim($action, '/');
    $handle = curl_init($action);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['username' => $username, 'password' => $password, 'lt' => $matches[2], '_eventId' => 'submit', 'execution' => 'e1s1']), CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
    $finalPage = (string) curl_exec($handle);
    curl_close($handle);
    $cookies = [];
    $student = [];
    if (preg_match('/id="userinfo:mg_userinfo_a".*?<span>\s*([^<]+?)\s*<\/span>\s*<br\s*\/?>.*?<span>\s*([^<]+?)\s*<\/span>/is', $finalPage, $nameMatch)) {
        $student = ['name' => trim($nameMatch[2]) . ' ' . trim($nameMatch[1])];
    } elseif (preg_match('/id="mg_portal_header_top_container".*?<span>\s*([^<]+?)\s*<\/span>.*?<span>\s*([^<]+?)\s*<\/span>/is', $finalPage, $nameMatch)) {
        $student = ['name' => trim($nameMatch[2]) . ' ' . trim($nameMatch[1])];
    }
    foreach (file($jar, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $httpOnly = str_starts_with($line, '#HttpOnly_');
        if ($httpOnly) $line = substr($line, 10);
        if ($line === '' || $line[0] === '#' || substr_count($line, "\t") < 6) continue;
        $parts = explode("\t", $line);
        if (str_ends_with($parts[0], 'myges.fr') || str_ends_with($parts[0], 'kordis.fr')) $cookies[] = $parts[5] . '=' . $parts[6];
    }
    return ['cookie' => implode('; ', $cookies), 'cookie_jar' => $jar, 'student' => $student];
}

function upstream(string $path, ?string $token = null, array $query = [], bool $retryableBadRequest = false): array {
    $baseUrl = rtrim(envValue('MYGES_API_BASE_URL', 'https://api.kordis.fr'), '/');
    if ($baseUrl === '') respond(['error' => 'Le proxy MyGES n’est pas configuré.'], 500);
    $url = $baseUrl . '/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
    $headers = ['Accept: application/json, application/xml;q=0.9'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw = curl_exec($handle);
    $error = curl_error($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    if ($raw === false) respond(['error' => 'Le service MyGES est temporairement injoignable.', 'diagnostic' => $error ?: 'Erreur réseau cURL.'], 502);
    if ($status === 401 || $status === 403) respond(['error' => 'Session MyGES expirée, veuillez vous reconnecter.'], 401);
    if ($status === 204) return [];
    if ($retryableBadRequest && in_array($status, [400, 404, 405, 500, 502, 503], true)) return ['__upstream_status' => $status, '__upstream_body' => $raw];
    $normalizedRaw = ltrim($raw, "\xEF\xBB\xBF \t\r\n");
    $decoded = json_decode($normalizedRaw, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_array($decoded)) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($normalizedRaw);
        if ($xml !== false) $decoded = json_decode(json_encode($xml), true);
        libxml_clear_errors();
    }
    if (!is_array($decoded)) {
        $bodyPreview = trim(preg_replace('/\s+/', ' ', strip_tags($normalizedRaw)) ?? '');
        $bodyPreview = function_exists('mb_substr') ? mb_substr($bodyPreview, 0, 180) : substr($bodyPreview, 0, 180);
        respond([
            'error' => 'Réponse invalide du service MyGES.',
            'diagnostic' => "HTTP {$status}" . ($bodyPreview !== '' ? ": {$bodyPreview}" : ''),
        ], 502);
    }
    if ($status < 200 || $status >= 300) {
        $message = $decoded['message'] ?? $decoded['error'] ?? $decoded['faultstring'] ?? 'Service MyGES indisponible.';
        respond(['error' => is_string($message) ? $message : 'Service MyGES indisponible.', 'diagnostic' => "Réponse MyGES HTTP {$status}"], 502);
    }
    return $decoded['result'] ?? $decoded['data'] ?? $decoded;
}

function fetchMygesMarks(string $cookie): array {
    if ($cookie === '') return ['__upstream_status' => 401];
    $headers = ['Accept: text/xml, */*;q=0.01', 'X-Requested-With: XMLHttpRequest', 'Faces-Request: partial/ajax', 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'Cookie: ' . $cookie];
    $handle = curl_init('https://myges.fr/student/marks');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPGET => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true]);
    $page = (string) curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    if ($status < 200 || $status >= 300 || !preg_match('/name="javax.faces.ViewState"[^>]+value="([^"]+)"/', $page, $stateMatch)) {
        $pageTitle = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $page, $titleMatch)) $pageTitle = trim(preg_replace('/\s+/', ' ', strip_tags($titleMatch[1])));
        return ['__upstream_status' => $status ?: 502, '__upstream_body' => $pageTitle ?: 'Page MyGES sans ViewState'];
    }
    $periods = [];
    $pageDom = new DOMDocument();
    @$pageDom->loadHTML('<?xml encoding="UTF-8">' . $page);
    foreach ($pageDom->getElementsByTagName('select') as $select) {
        $selectName = $select->getAttribute('name') . ' ' . $select->getAttribute('id');
        if (stripos($selectName, 'periodSelect') === false) continue;
        foreach ($select->getElementsByTagName('option') as $option) {
            $value = trim($option->getAttribute('value'));
            $label = trim(preg_replace('/\s+/', ' ', $option->textContent));
            if ($value !== '' && $label !== '') $periods[$value] = ['value' => $value, 'label' => $label];
        }
        if ($periods) break;
    }
    if (!$periods) {
        preg_match_all('/<option[^>]+value="([^"]+)"[^>]*>(.*?)<\/option>/is', $page, $optionMatches, PREG_SET_ORDER);
        foreach ($optionMatches as $optionMatch) {
            $label = trim(html_entity_decode(strip_tags($optionMatch[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($label !== '') $periods[$optionMatch[1]] = ['value' => $optionMatch[1], 'label' => $label];
        }
    }
    $periods['3313509'] ??= ['value' => '3313509', 'label' => '2026-2027 - ESGI - BCH_SE2_000_INI - Semestre 1'];
    $periods['3252521'] ??= ['value' => '3252521', 'label' => '2025-2026 - ESGI - BCH_SE1_000_INI - Semestre 2'];
    $periods['3132516'] ??= ['value' => '3132516', 'label' => '2025-2026 - ESGI - BCH_SE1_000_INI - Semestre 1'];
    if (!$periods && preg_match('/name="marksForm:j_idt174:periodSelect_input"[^>]+value="([^"]+)"/', $page, $periodMatch)) {
        $periods[$periodMatch[1]] = ['value' => $periodMatch[1], 'label' => 'Période actuelle'];
    }
    if (!$periods) return ['__upstream_status' => 502];
    $viewState = $stateMatch[1];
    $marks = [];
    foreach (array_values($periods) as $period) {
        $payload = [
        'javax.faces.partial.ajax' => 'true',
        'javax.faces.source' => 'marksForm:j_idt174:periodSelect',
        'javax.faces.partial.execute' => 'marksForm:j_idt174:periodSelect',
        'javax.faces.partial.render' => 'marksForm:marksWidget:coursesTable marksForm:missingsWidget:missingsTable marksForm:acceptFirmMarksPanel',
        'javax.faces.behavior.event' => 'valueChange',
        'javax.faces.partial.event' => 'change',
        'marksForm' => 'marksForm',
        'marksForm:j_idt174:periodSelect_focus' => '',
        'marksForm:j_idt174:periodSelect_input' => $period['value'],
        'marksForm:acceptFirmMarks_input' => 'on',
            'javax.faces.ViewState' => $viewState,
        ];
    $handle = curl_init('https://myges.fr/student/marks');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($payload), CURLOPT_HTTPHEADER => $headers, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true]);
    $response = (string) curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
        if (preg_match('/<update[^>]+id="javax\.faces\.ViewState"[^>]*><!\[CDATA\[(.*?)\]\]><\/update>/s', $response, $viewStateMatch)) {
            $viewState = trim($viewStateMatch[1]);
        } elseif (preg_match('/name="javax\.faces\.ViewState"[^>]+value="([^"]+)"/', $response, $viewStateMatch)) {
            $viewState = html_entity_decode($viewStateMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if ($status < 200 || $status >= 300 || !preg_match('/<table[^>]*role="grid"[^>]*>.*?<\/table>/is', $response, $tableMatch)) {
            $pageTitle = '';
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $response, $titleMatch)) $pageTitle = trim(preg_replace('/\s+/', ' ', strip_tags($titleMatch[1])));
            return ['__upstream_status' => $status ?: 502, '__upstream_body' => $pageTitle ?: 'Réponse sans tableau de notes'];
        }
        $dom = new DOMDocument();
        @$dom->loadHTML('<meta charset="utf-8">' . $tableMatch[0]);
        $table = $dom->getElementsByTagName('table')->item(0);
        $headersByIndex = [];
        foreach ($table?->getElementsByTagName('th') ?? [] as $headerIndex => $header) $headersByIndex[$headerIndex] = trim(preg_replace('/\s+/', ' ', $header->textContent));
        $coefficientIndex = array_search(true, array_map(static fn (string $label): bool => preg_match('/\bcoef(?:ficient)?\b/iu', $label) === 1, $headersByIndex), true);
        foreach ($table?->getElementsByTagName('tr') ?? [] as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 2) continue;
            $values = [];
            for ($index = 0; $index < $cells->length; $index++) {
                $label = $headersByIndex[$index] ?? '';
                if ($index < 4 || !preg_match('/^(CC\s*\d*|Exam|Partiel|Contrôle)/iu', $label)) continue;
                $value = trim(preg_replace('/\s+/', ' ', $cells->item($index)->textContent));
                if ($value !== '') $values[] = ['label' => $label, 'value' => str_replace(',', '.', $value)];
            }
            $subject = trim(preg_replace('/\s+/', ' ', $cells->item(0)->textContent));
            if (preg_match('/^(S\d+|Semestre\s*\d+)\s*-\s*(.+)$/iu', $subject, $subjectParts)) $subject = trim($subjectParts[2]);
            $coefficient = $coefficientIndex !== false && $cells->length > $coefficientIndex
                ? trim($cells->item($coefficientIndex)->textContent)
                : '';
            $creditsIndex = array_search(true, array_map(static fn (string $label): bool => preg_match('/\bects\b/iu', $label) === 1, $headersByIndex), true);
            $credits = $creditsIndex !== false && $cells->length > $creditsIndex
                ? trim($cells->item($creditsIndex)->textContent)
                : '';
            $marks[] = ['subject' => $subject, 'period' => $period['label'], 'periodKey' => $period['value'], 'schoolYear' => $period['label'], 'teacher' => trim($cells->item(1)->textContent), 'coefficient' => str_replace(',', '.', $coefficient), 'credits' => str_replace(',', '.', $credits), 'evaluations' => $values];
        }
    }
    return $marks;
}

function fetchMygesAbsences(string $cookie): array {
    if ($cookie === '') return ['__upstream_status' => 401];
    $handle = curl_init('https://myges.fr/student/marks');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPGET => true, CURLOPT_HTTPHEADER => ['Accept: text/html', 'Cookie: ' . $cookie], CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true]);
    $page = (string) curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    if ($status < 200 || $status >= 300) return ['__upstream_status' => $status ?: 502];
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $page);
    foreach ($dom->getElementsByTagName('table') as $table) {
        $headers = [];
        foreach ($table->getElementsByTagName('th') as $header) $headers[] = trim(preg_replace('/\s+/', ' ', $header->textContent));
        if (!in_array('Date', $headers, true) || !in_array('Justifié', $headers, true)) continue;
        $absences = [];
        foreach ($table->getElementsByTagName('tr') as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 4) continue;
            $absences[] = ['date' => trim(preg_replace('/\s+/', ' ', $cells->item(0)->textContent)), 'course' => trim(preg_replace('/\s+/', ' ', $cells->item(1)->textContent)), 'type' => trim(preg_replace('/\s+/', ' ', $cells->item(2)->textContent)), 'justified' => trim(preg_replace('/\s+/', ' ', $cells->item(3)->textContent)) === 'Oui'];
        }
        return $absences;
    }
    return [];
}

function parseMygesDocumentLinks(string $html): array {
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $documents = [];
    foreach ($dom->getElementsByTagName('a') as $link) {
        $href = trim($link->getAttribute('href'));
        if (!str_starts_with($href, 'https://ges-dl.kordis.fr/private/')) continue;
        $title = trim(preg_replace('/\s+/', ' ', $link->textContent));
        if ($title === '' || in_array(strtolower($title), ['download', 'télécharger', 'telecharger'], true)) {
            for ($parent = $link->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode) {
                if (strtolower($parent->tagName) !== 'tr') continue;
                $cells = $parent->getElementsByTagName('td');
                $title = $cells->length >= 3 ? trim(preg_replace('/\s+/', ' ', $cells->item(2)->textContent)) : '';
                break;
            }
        }
        if ($title === '') $title = 'Document MyGES';
        $documents[$href] = ['title' => $title, 'url' => $href];
    }
    return array_values($documents);
}

function schoolYearFromLabel(string $label): string {
    return preg_match('/\b(20\d{2}-20\d{2})\b/', $label, $match) ? $match[1] : 'Autres documents';
}

function fetchMygesDocuments(string $cookie): array {
    if ($cookie === '') return ['__upstream_status' => 401];
    $handle = curl_init('https://myges.fr/common/student-documents');
    $jar = (string) ($_SESSION['myges_cookie_jar'] ?? '');
    $curlOptions = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPGET => true, CURLOPT_HTTPHEADER => ['Accept: text/html'], CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$cookie): int {
        if (stripos($header, 'Set-Cookie:') === 0 && preg_match('/^Set-Cookie:\s*([^=;]+)=([^;]*)/i', $header, $match)) {
            $cookieParts = [];
            foreach (explode(';', $cookie) as $part) {
                $part = trim($part);
                if (str_contains($part, '=')) [$name, $value] = explode('=', $part, 2); else continue;
                $cookieParts[trim($name)] = $value;
            }
            $cookieParts[trim($match[1])] = $match[2];
            $cookie = implode('; ', array_map(static fn (string $name, string $value): string => $name . '=' . $value, array_keys($cookieParts), $cookieParts));
        }
        return strlen($header);
    }, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true];
    if ($jar !== '' && is_readable($jar)) {
        $curlOptions[CURLOPT_COOKIEFILE] = $jar;
        $curlOptions[CURLOPT_COOKIEJAR] = $jar;
    } else {
        $curlOptions[CURLOPT_HTTPHEADER] = ['Accept: text/html', 'Cookie: ' . $cookie];
    }
    curl_setopt_array($handle, $curlOptions);
    $page = (string) curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    if ($status < 200 || $status >= 300) return ['__upstream_status' => $status ?: 502];
    $_SESSION['myges_cookie'] = $cookie;
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $page);
    $viewState = '';
    if (preg_match('/name="javax.faces.ViewState"[^>]+value="([^"]+)"/', $page, $viewStateMatch)) $viewState = html_entity_decode($viewStateMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $periods = [];
    $selectedPeriod = '';
    foreach ($dom->getElementsByTagName('select') as $select) {
        if (stripos($select->getAttribute('name'), 'periodSelect_input') === false) continue;
        foreach ($select->getElementsByTagName('option') as $option) {
            $value = trim($option->getAttribute('value'));
            $label = trim(preg_replace('/\s+/', ' ', $option->textContent));
            if ($value === '' || $label === '') continue;
            $periods[$value] = $label;
            if ($option->hasAttribute('selected')) $selectedPeriod = $value;
        }
        if ($selectedPeriod === '') $selectedPeriod = trim($select->getAttribute('value'));
        break;
    }
    if (!$periods) return [];
    $folders = [];
    $addDocuments = static function (string $periodLabel, array $documents) use (&$folders): void {
        $folders[$periodLabel] ??= ['period' => $periodLabel, 'documents' => []];
        foreach ($documents as $document) $folders[$periodLabel]['documents'][$document['url']] = $document;
    };
    $addDocuments($periods[$selectedPeriod] ?? reset($periods), parseMygesDocumentLinks($page));
    if ($viewState !== '' && count($periods) > 1) {
        foreach ($periods as $periodValue => $periodLabel) {
            if ($periodValue === $selectedPeriod) continue;
            $payload = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'documentForm:j_idt199:periodSelect',
                'javax.faces.partial.execute' => 'documentForm:j_idt199:periodSelect',
                'javax.faces.partial.render' => 'documentForm:annualDocWidget:yearDocumentsTable',
                'javax.faces.behavior.event' => 'valueChange',
                'javax.faces.partial.event' => 'change',
                'documentForm' => 'documentForm',
                'documentForm:j_idt199:periodSelect_focus' => '',
                'documentForm:j_idt199:periodSelect_input' => $periodValue,
                'javax.faces.ViewState' => $viewState,
            ];
            $handle = curl_init('https://myges.fr/common/student-documents');
            $headers = ['Accept: text/xml, */*;q=0.01', 'X-Requested-With: XMLHttpRequest', 'Faces-Request: partial/ajax', 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'Referer: https://myges.fr/common/student-documents'];
            $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($payload), CURLOPT_HTTPHEADER => $headers, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true];
            if ($jar !== '' && is_readable($jar)) {
                $options[CURLOPT_COOKIEFILE] = $jar;
                $options[CURLOPT_COOKIEJAR] = $jar;
            } else {
                $headers[] = 'Cookie: ' . $cookie;
                $options[CURLOPT_HTTPHEADER] = $headers;
            }
            curl_setopt_array($handle, $options);
            $response = (string) curl_exec($handle);
            $responseStatus = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
            if ($responseStatus < 200 || $responseStatus >= 300) continue;
            if (preg_match('/<update[^>]+id="[^"]*yearDocumentsTable"[^>]*><!\[CDATA\[(.*?)\]\]><\/update>/s', $response, $updateMatch)) {
                $addDocuments($periodLabel, parseMygesDocumentLinks($updateMatch[1]));
            }
        }
    }
    return array_map(static function (array $folder): array {
        $folder['documents'] = array_values($folder['documents']);
        return $folder;
    }, array_values($folders));
}

function parseMygesSupportPage(string $html): array {
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $subjects = [];
    foreach ($dom->getElementsByTagName('h3') as $header) {
        if ($header->getAttribute('role') !== 'tab') continue;
        $name = trim(preg_replace('/\s+/', ' ', $header->textContent));
        $name = preg_replace('/\s+\d+\s+fichier\(s\)$/u', '', $name) ?: $name;
        $files = [];
        $panel = $header->nextSibling;
        while ($panel && !($panel instanceof DOMElement)) $panel = $panel->nextSibling;
        if ($panel instanceof DOMElement) {
            foreach ($panel->getElementsByTagName('a') as $link) {
                $onclick = $link->getAttribute('onclick');
                if (!preg_match("/window\.open\('([^']+)'/", $onclick, $urlMatch)) continue;
                $title = trim(preg_replace('/\s+/', ' ', $link->textContent));
                if ($title === '') continue;
                $files[$urlMatch[1]] = ['title' => $title, 'url' => $urlMatch[1]];
            }
            foreach ($panel->getElementsByTagName('button') as $button) {
                if (!preg_match("/window\.open\('([^']+)'/", $button->getAttribute('onclick'), $urlMatch)) continue;
                $title = 'Document';
                $row = $button->parentNode?->parentNode;
                if ($row instanceof DOMElement) {
                    $cells = $row->getElementsByTagName('td');
                    if ($cells->length >= 2) $title = trim(preg_replace('/\s+/', ' ', $cells->item(1)->textContent));
                }
                $files[$urlMatch[1]] = ['title' => $title, 'url' => $urlMatch[1]];
            }
        }
        $subjects[] = ['name' => $name ?: 'Matière', 'files' => array_values($files)];
    }
    return $subjects;
}

function fetchMygesSupports(string $cookie): array {
    if ($cookie === '') return ['__upstream_status' => 401];
    $url = 'https://myges.fr/student/courses-files';
    $jar = (string) ($_SESSION['myges_cookie_jar'] ?? '');
    $fetch = static function (string $method, array $options = []) use ($url, $cookie, $jar): array {
        $handle = curl_init($url);
        $headers = $options['headers'] ?? ['Accept: text/html'];
        $curlOptions = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => true];
        foreach ($options as $key => $value) if ($key !== 'headers') $curlOptions[$key] = $value;
        if ($jar !== '' && is_readable($jar)) {
            $curlOptions[CURLOPT_COOKIEFILE] = $jar;
            $curlOptions[CURLOPT_COOKIEJAR] = $jar;
        } else $curlOptions[CURLOPT_HTTPHEADER][] = 'Cookie: ' . $cookie;
        curl_setopt_array($handle, $curlOptions);
        $body = (string) curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return [$status, $body];
    };
    [$status, $page] = $fetch('GET');
    if ($status < 200 || $status >= 300) return ['__upstream_status' => $status ?: 502];
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $page);
    $viewState = '';
    if (preg_match('/name="javax.faces.ViewState"[^>]+value="([^"]+)"/', $page, $match)) $viewState = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $periods = [];
    $selected = '';
    foreach ($dom->getElementsByTagName('select') as $select) {
        if (stripos($select->getAttribute('name'), 'periodSelect_input') === false) continue;
        foreach ($select->getElementsByTagName('option') as $option) {
            $value = trim($option->getAttribute('value'));
            $label = trim(preg_replace('/\s+/', ' ', $option->textContent));
            if ($value === '' || $label === '') continue;
            $periods[$value] = $label;
            if ($option->hasAttribute('selected')) $selected = $value;
        }
        break;
    }
    if (!$periods) return [];
    $result = [];
    foreach ($periods as $periodValue => $periodLabel) {
        $periodHtml = $periodValue === $selected ? $page : '';
        if ($periodHtml === '') {
            $payload = ['javax.faces.partial.ajax' => 'true', 'javax.faces.source' => 'coursesFilesForm:j_idt173:periodSelect', 'javax.faces.partial.execute' => 'coursesFilesForm:j_idt173:periodSelect', 'javax.faces.partial.render' => 'coursesFilesForm:coursesWidget', 'javax.faces.behavior.event' => 'valueChange', 'javax.faces.partial.event' => 'change', 'coursesFilesForm' => 'coursesFilesForm', 'coursesFilesForm:j_idt173:periodSelect_focus' => '', 'coursesFilesForm:j_idt173:periodSelect_input' => $periodValue, 'javax.faces.ViewState' => $viewState];
            [$periodStatus, $response] = $fetch('POST', [CURLOPT_POSTFIELDS => http_build_query($payload), 'headers' => ['Accept: text/xml, */*;q=0.01', 'X-Requested-With: XMLHttpRequest', 'Faces-Request: partial/ajax', 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'Referer: ' . $url]]);
            if ($periodStatus < 200 || $periodStatus >= 300) continue;
            $periodHtml = $response;
        }
        $subjects = parseMygesSupportPage($periodHtml);
        if (!$subjects && preg_match('/<update[^>]+id="[^"]*coursesWidget[^"]*"[^>]*><!\[CDATA\[(.*?)\]\]><\/update>/s', $periodHtml, $update)) $subjects = parseMygesSupportPage($update[1]);
        $result[] = ['period' => $periodLabel, 'subjects' => $subjects];
    }
    return $result;
}

function documentFilename(string $name, string $contentType, string $contentDisposition): string {
    $filename = '';
    if (preg_match("/filename\*=UTF-8''([^;]+)/i", $contentDisposition, $match)) {
        $filename = rawurldecode(trim($match[1], " \t\"'"));
    } elseif (preg_match('/filename="?([^";]+)"?/i', $contentDisposition, $match)) {
        $filename = trim($match[1]);
    }
    if ($filename === '') $filename = trim($name);
    $filename = preg_replace('/[\\\/:*?"<>|\x00-\x1F]+/', '-', $filename) ?: 'document-myges';
    $filename = trim($filename, " .-");
    if ($filename === '') $filename = 'document-myges';
    if (!str_contains($filename, '.')) {
        $extensions = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/zip' => 'zip',
        ];
        if (isset($extensions[$contentType])) $filename .= '.' . $extensions[$contentType];
    }
    return $filename;
}

function downloadMygesDocument(string $cookie, string $url, string $name = ''): never {
    $parts = parse_url($url);
    if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'ges-dl.kordis.fr' || !str_starts_with($parts['path'] ?? '', '/private/')) respond(['error' => 'Document refusé.'], 400);
    $contentDisposition = '';
    $handle = curl_init($url);
    $jar = (string) ($_SESSION['myges_cookie_jar'] ?? '');
    $curlOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => ['Accept: application/pdf, application/octet-stream;q=0.9, */*;q=0.8', 'Referer: https://myges.fr/'],
        CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$contentDisposition): int {
            if (stripos($header, 'Content-Disposition:') === 0) $contentDisposition = trim(substr($header, 20));
            return strlen($header);
        },
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ];
    if ($jar !== '' && is_readable($jar)) {
        $curlOptions[CURLOPT_COOKIEFILE] = $jar;
        $curlOptions[CURLOPT_COOKIEJAR] = $jar;
    } else {
        $curlOptions[CURLOPT_HTTPHEADER][] = 'Cookie: ' . $cookie;
    }
    curl_setopt_array($handle, $curlOptions);
    $content = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    curl_close($handle);
    if ($content === false || $status < 200 || $status >= 300) respond(['error' => 'Document MyGES indisponible.'], 502);
    $detectedType = function_exists('finfo_open') ? ((new finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: '') : '';
    $contentType = strtolower(trim(explode(';', $type ?: $detectedType, 2)[0]));
    if ($contentType === 'text/html' || $detectedType === 'text/html' || $contentType === 'application/json' || $detectedType === 'application/json') {
        respond(['error' => 'La session MyGES ne permet pas de télécharger ce document. Veuillez vous reconnecter.'], 401);
    }
    $filename = documentFilename($name, $contentType ?: $detectedType, $contentDisposition);
    header('Content-Type: ' . ($contentType ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . addcslashes($filename, "\\\"") . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}

$resource = $_GET['resource'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$routes = [
    'profile' => '/me/profile',
    'planning' => envValue('MYGES_PLANNING_PATH', '/planning'),
    'grades' => envValue('MYGES_GRADES_PATH', '/grades'),
    'absences' => envValue('MYGES_ABSENCES_PATH', '/absences'),
    'supports' => envValue('MYGES_SUPPORTS_PATH', '/me/courses'),
    'documents' => '/common/student-documents',
    'document' => '/private',
];

if ($resource === 'login' && $method === 'POST') {
    $body = requestBody();
    if (empty($body['username']) || empty($body['password'])) respond(['error' => 'Identifiant et mot de passe requis.'], 422);
    $token = authorizeUser((string) $body['username'], (string) $body['password']);
    session_regenerate_id(true);
    $_SESSION['access_token'] = $token;
    $webSession = authorizeMygesWeb((string) $body['username'], (string) $body['password']);
    $_SESSION['myges_cookie'] = $webSession['cookie'];
    $_SESSION['myges_cookie_jar'] = $webSession['cookie_jar'];
    $_SESSION['student'] = $webSession['student'];
    respond(['authenticated' => true, 'student' => $webSession['student']]);
}
if ($resource === 'logout' && $method === 'POST') {
    if (!empty($_SESSION['myges_cookie_jar'])) @unlink((string) $_SESSION['myges_cookie_jar']);
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
if ($resource === 'documents') {
    $payload = fetchMygesDocuments((string) ($_SESSION['myges_cookie'] ?? ''));
    if (isset($payload['__upstream_status'])) respond(['error' => 'Documents MyGES indisponibles.'], 502);
    respond($payload);
}
if ($resource === 'supports') {
    $payload = fetchMygesSupports((string) ($_SESSION['myges_cookie'] ?? ''));
    if (isset($payload['__upstream_status'])) respond(['error' => 'Supports de cours MyGES indisponibles.'], 502);
    respond($payload);
}
if ($resource === 'document') {
    $encoded = (string) ($_GET['file'] ?? '');
    $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
    $url = base64_decode(strtr($encoded, '-_', '+/'), true);
    if (!is_string($url)) respond(['error' => 'Document invalide.'], 400);
    downloadMygesDocument((string) ($_SESSION['myges_cookie'] ?? ''), $url, (string) ($_GET['name'] ?? ''));
}
if ($resource === 'profile') {
    $profile = upstream($routes['profile'], $_SESSION['access_token'], [], true);
    if (isset($profile['__upstream_status'])) respond(array_merge($_SESSION['student'] ?? [], ['offline' => true]));
    respond($profile);
}
$query = [];
if ($resource === 'planning') {
    $requestedDate = $_GET['date'] ?? gmdate('Y-m-d');
    $selectedDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $requestedDate, new DateTimeZone('UTC'));
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (!$selectedDate || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))) {
        $selectedDate = new DateTimeImmutable('today', new DateTimeZone('UTC'));
    }
    $schoolYear = (int) $selectedDate->format('n') >= 9
        ? (int) $selectedDate->format('Y')
        : (int) $selectedDate->format('Y') - 1;
    $start = new DateTimeImmutable($schoolYear . '-09-01 00:00:00', new DateTimeZone('UTC'));
    $end = $start->modify('+1 year');
    $query = [
        'start' => $start->format('Y-m-d\T00:00:00.000\Z'),
        'end' => $end->format('Y-m-d\T00:00:00.000\Z'),
    ];
}
if ($resource === 'grades' && !empty($_SESSION['myges_cookie'])) {
    $payload = fetchMygesMarks((string) $_SESSION['myges_cookie']);
    if (isset($payload['__upstream_status'])) {
        $authenticationPage = str_contains((string) ($payload['__upstream_body'] ?? ''), 'Authentification');
        if ($authenticationPage || in_array((int) $payload['__upstream_status'], [401, 403], true)) {
            if (!empty($_SESSION['myges_cookie_jar'])) @unlink((string) $_SESSION['myges_cookie_jar']);
            unset($_SESSION['myges_cookie'], $_SESSION['myges_cookie_jar']);
        }
    }
    else respond($payload);
}
if ($resource === 'absences' && !empty($_SESSION['myges_cookie'])) {
    $payload = fetchMygesAbsences((string) $_SESSION['myges_cookie']);
    if (!isset($payload['__upstream_status'])) respond($payload);
}
$path = str_replace('{year}', date('Y'), $routes[$resource]);
$retryable = in_array($resource, ['planning', 'grades', 'absences'], true);
$payload = upstream($path, $_SESSION['access_token'], $query, $retryable);
if ($resource === 'planning' && isset($payload['__upstream_status'])) {
    $start = isset($start) ? $start->getTimestamp() : strtotime('today UTC');
    $end = isset($end) ? $end->getTimestamp() : strtotime('+1 year', $start);
    $variants = [
        ['start' => (string) ($start * 1000), 'end' => (string) ($end * 1000)],
        ['start' => gmdate('Y-m-d\\TH:i:s\\Z', $start), 'end' => gmdate('Y-m-d\\TH:i:s\\Z', $end)],
        ['start' => gmdate('Y-m-d', $start), 'end' => gmdate('Y-m-d', $end)],
    ];
    foreach ($variants as $variant) {
        $candidate = upstream($path, $_SESSION['access_token'], $variant, true);
        if (!isset($candidate['__upstream_status'])) { $payload = $candidate; break; }
        $payload = $candidate;
    }
}
if ($resource === 'absences' && isset($payload['__upstream_status'])) {
    foreach ([str_replace('{year}', (string) ((int) date('Y') - 1), $routes[$resource]), '/me/absences'] as $candidatePath) {
        $candidate = upstream($candidatePath, $_SESSION['access_token'], [], true);
        if (!isset($candidate['__upstream_status'])) { $payload = $candidate; break; }
        $payload = $candidate;
    }
}
if ($resource === 'grades' && isset($payload['__upstream_status'])) {
    $year = (int) date('Y');
    $candidatePaths = [
        str_replace('{year}', (string) ($year - 1) . '-' . $year, $routes[$resource]),
        str_replace('{year}', (string) $year . '-' . ($year + 1), $routes[$resource]),
        str_replace('{year}', (string) ($year - 1), $routes[$resource]),
        '/me/grades',
        '/me/notes',
        '/me/marks',
        '/grades',
        '/notes',
        '/marks',
    ];
    foreach (array_unique($candidatePaths) as $candidatePath) {
        $candidate = upstream($candidatePath, $_SESSION['access_token'], [], true);
        if (!isset($candidate['__upstream_status'])) { $payload = $candidate; break; }
        $payload = $candidate;
    }
}
if (isset($payload['__upstream_status'])) {
    if ($resource === 'planning') respond([]);
    $message = $resource === 'planning'
        ? 'Le format de période du planning est refusé par MyGES.'
        : ($resource === 'grades'
            ? 'L’endpoint des notes est refusé par MyGES.'
            : ($resource === 'supports'
                ? 'L’endpoint des supports de cours est refusé par MyGES.'
                : 'L’endpoint des absences est refusé par MyGES.'));
    respond([
        'error' => $message,
        'diagnostic' => 'HTTP ' . (int) $payload['__upstream_status'] . ' après les variantes configurées.',
    ], 502);
}
respond($payload);
