<?php
declare(strict_types=1);

const NEROZON_ORIGIN = 'https://nerozon.de';
const NEROZON_FROM = 'noreply@nerozon.de';
const NEROZON_RESEARCH_TO = 'research@nerozon.de';
const NEROZON_CONTACT_TO = 'web.kontakt@nerozon.de';
const NEROZON_TOKEN_TTL = 900;
const NEROZON_MAX_BODY_BYTES = 65536;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_from_questionnaire(): bool
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (!is_string($referer) || $referer === '') {
        return false;
    }

    $parts = parse_url($referer);
    if (!is_array($parts)) {
        return false;
    }

    return ($parts['scheme'] ?? '') === 'https'
        && ($parts['host'] ?? '') === 'nerozon.de'
        && str_starts_with((string) ($parts['path'] ?? ''), '/q/');
}

function require_same_origin_post(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!is_string($origin) || !hash_equals(NEROZON_ORIGIN, $origin)) {
        respond(403, ['ok' => false, 'error' => 'origin_rejected']);
    }

    if (!request_from_questionnaire()) {
        respond(403, ['ok' => false, 'error' => 'source_rejected']);
    }

    $fetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? null;
    if (is_string($fetchSite) && $fetchSite !== '' && $fetchSite !== 'same-origin') {
        respond(403, ['ok' => false, 'error' => 'cross_site_rejected']);
    }
}

function issue_token(): never
{
    if (!request_from_questionnaire()) {
        respond(403, ['ok' => false, 'error' => 'source_rejected']);
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['mail_token'] = $token;
    $_SESSION['mail_token_expires'] = time() + NEROZON_TOKEN_TTL;

    respond(200, [
        'ok' => true,
        'token' => $token,
        'expiresIn' => NEROZON_TOKEN_TTL,
    ]);
}

function consume_token(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $stored = $_SESSION['mail_token'] ?? '';
    $expires = (int) ($_SESSION['mail_token_expires'] ?? 0);

    unset($_SESSION['mail_token'], $_SESSION['mail_token_expires']);

    if (!is_string($provided) || !is_string($stored) || $provided === '' || $stored === '') {
        respond(403, ['ok' => false, 'error' => 'token_missing']);
    }

    if ($expires < time() || !hash_equals($stored, $provided)) {
        respond(403, ['ok' => false, 'error' => 'token_invalid']);
    }
}

function read_json_body(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!is_string($contentType) || !str_starts_with(strtolower($contentType), 'application/json')) {
        respond(415, ['ok' => false, 'error' => 'json_required']);
    }

    $declaredLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($declaredLength > NEROZON_MAX_BODY_BYTES) {
        respond(413, ['ok' => false, 'error' => 'payload_too_large']);
    }

    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '' || strlen($raw) > NEROZON_MAX_BODY_BYTES) {
        respond(400, ['ok' => false, 'error' => 'invalid_body']);
    }

    try {
        $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        respond(400, ['ok' => false, 'error' => 'invalid_json']);
    }

    if (!is_array($data)) {
        respond(400, ['ok' => false, 'error' => 'invalid_payload']);
    }

    return $data;
}

function normalize_text(mixed $value, int $maxLength): string
{
    if (!is_string($value)) {
        return '';
    }

    $value = trim(str_replace("\0", '', $value));
    if (mb_strlen($value, 'UTF-8') > $maxLength) {
        respond(422, ['ok' => false, 'error' => 'field_too_long']);
    }

    return $value;
}

function normalize_answer(mixed $value): string|array|null
{
    if (is_string($value)) {
        return normalize_text($value, 4000);
    }

    if (is_array($value)) {
        if (count($value) > 12) {
            respond(422, ['ok' => false, 'error' => 'too_many_values']);
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                respond(422, ['ok' => false, 'error' => 'invalid_answer']);
            }
            $result[] = normalize_text($item, 1000);
        }
        return $result;
    }

    if ($value === null) {
        return null;
    }

    respond(422, ['ok' => false, 'error' => 'invalid_answer']);
}

function enforce_session_rate_limit(string $kind): void
{
    $cooldown = $kind === 'research' ? 30 : 10;
    $key = 'last_mail_' . $kind;
    $last = (int) ($_SESSION[$key] ?? 0);

    if ($last > 0 && (time() - $last) < $cooldown) {
        respond(429, ['ok' => false, 'error' => 'rate_limited']);
    }

    $_SESSION[$key] = time();
}

function send_plain_mail(string $to, string $subject, string $body, ?string $replyTo = null): void
{
    $headers = [
        'From: ' . NEROZON_FROM,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: NEROZON Web',
    ];

    if ($replyTo !== null) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    if (!mail($to, $subject, $body, implode("\r\n", $headers))) {
        respond(502, ['ok' => false, 'error' => 'mail_delivery_failed']);
    }
}

function answer_to_markdown(string|array|null $answer): string
{
    if (is_array($answer)) {
        if ($answer === []) {
            return '_Keine Antwort_';
        }
        return implode("\n", array_map(static fn(string $item): string => '- ' . $item, $answer));
    }

    if ($answer === null || $answer === '') {
        return '_Keine Antwort_';
    }

    return $answer;
}

function handle_research(array $data): never
{
    if (($data['questionnaireVersion'] ?? null) !== 'research-20-v1') {
        respond(422, ['ok' => false, 'error' => 'unsupported_questionnaire']);
    }

    $answersInput = $data['answers'] ?? null;
    if (!is_array($answersInput)) {
        respond(422, ['ok' => false, 'error' => 'answers_required']);
    }

    $questions = [
        1 => 'Wird generative KI in Ihrem Unternehmen heute bereits genutzt?',
        2 => 'In welchen Bereichen wird KI eingesetzt?',
        3 => 'Erfolgt diese Nutzung überwiegend offiziell freigegeben oder auch eigenständig durch Mitarbeitende?',
        4 => 'Gibt es bereits KI-Anwendungen, die regelmäßig in Geschäftsprozessen eingesetzt werden?',
        5 => 'Wie wichtig ist ein stärkerer KI-Einsatz für Ihr Unternehmen in den nächsten 12 Monaten?',
        6 => 'Was sind aktuell die größten Hindernisse für einen breiteren KI-Einsatz?',
        7 => 'Gibt es Daten oder Prozesse, bei denen Sie KI gerne einsetzen würden, es aus Sicherheits- oder Datenschutzgründen aber nicht tun?',
        8 => 'Wie sicher können Sie heute beurteilen, welche Unternehmensdaten an welche KI-Dienste übergeben werden dürfen?',
        9 => 'Wie groß ist die Sorge, durch KI die Kontrolle über Daten oder Geschäftsprozesse zu verlieren?',
        10 => 'Was müsste sich ändern, damit Sie KI deutlich breiter einsetzen würden?',
        11 => 'Wie wichtig wäre es Ihnen, unterschiedliche KI-Modelle je nach Aufgabe und Schutzbedarf einsetzen zu können?',
        12 => 'Wie wichtig ist Ihnen die Möglichkeit, sensible Aufgaben vollständig innerhalb Ihrer eigenen Infrastruktur auszuführen?',
        13 => 'Wie wichtig wäre eine zentrale Kontrolle darüber, welche Daten für welchen KI-Dienst verwendet werden dürfen?',
        14 => 'Wie wichtig ist die Nachvollziehbarkeit dessen, was eine KI mit Unternehmensdaten getan hat?',
        15 => 'Würden Sie KI mehr operative Aufgaben übertragen, wenn Berechtigungen, Datenzugriffe und Ergebnisse kontrollierbar wären?',
        16 => 'Bei welchen Aufgaben erwarten Sie aktuell den größten wirtschaftlichen Nutzen durch KI?',
        17 => 'Welche wiederkehrenden Tätigkeiten würden Sie am liebsten als Erstes automatisieren?',
        18 => 'Wäre für Sie eine Lösung interessanter, die vorhandene Systeme verbindet, statt weitere Insellösungen einzuführen?',
        19 => 'Was wäre für Sie der wichtigste messbare Erfolg eines KI-Projekts?',
        20 => 'Wenn ein sicherer produktiver KI-Einsatz möglich wäre: Wo würden Sie morgen anfangen?',
    ];

    $answers = [];
    foreach ($questions as $number => $_question) {
        $key = 'q' . $number;
        if (array_key_exists($key, $answersInput)) {
            $answers[$key] = normalize_answer($answersInput[$key]);
        }

        $otherKey = $key . '_other';
        if (array_key_exists($otherKey, $answersInput)) {
            $answers[$otherKey] = normalize_text($answersInput[$otherKey], 2000);
        }
    }

    $submittedAt = (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->format(DateTimeInterface::ATOM);
    $jsonPayload = [
        'questionnaireVersion' => 'research-20-v1',
        'submittedAt' => $submittedAt,
        'answers' => $answers,
    ];

    $jsonLine = json_encode($jsonPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $markdown = [
        '# NEROZON Research – 20 Fragen',
        '',
        '**Zeitpunkt:** ' . $submittedAt,
        '**Version:** research-20-v1',
        '',
    ];

    foreach ($questions as $number => $question) {
        $key = 'q' . $number;
        $markdown[] = '## ' . $number . '. ' . $question;
        $markdown[] = answer_to_markdown($answers[$key] ?? null);

        $otherKey = $key . '_other';
        if (($answers[$otherKey] ?? '') !== '') {
            $markdown[] = '';
            $markdown[] = '**Ergänzung:** ' . $answers[$otherKey];
        }

        $markdown[] = '';
    }

    send_plain_mail(
        NEROZON_RESEARCH_TO,
        '[NEROZON Research] Neue 20-Fragen-Antwort',
        $jsonLine . "\n\n" . implode("\n", $markdown)
    );

    respond(200, ['ok' => true]);
}

function handle_contact(array $data): never
{
    $privacyConsent = $data['privacyConsent'] ?? false;
    if ($privacyConsent !== true) {
        respond(422, ['ok' => false, 'error' => 'privacy_consent_required']);
    }

    $message = normalize_text($data['message'] ?? '', 8000);
    $email = normalize_text($data['email'] ?? '', 320);

    if ($message === '' && $email === '') {
        respond(422, ['ok' => false, 'error' => 'contact_empty']);
    }

    $replyTo = null;
    if ($email !== '') {
        if (preg_match('/[\r\n]/', $email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            respond(422, ['ok' => false, 'error' => 'invalid_email']);
        }
        $replyTo = $email;
    }

    $submittedAt = (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->format(DateTimeInterface::ATOM);
    $body = implode("\n", [
        'Zeitpunkt: ' . $submittedAt,
        'Absender: ' . ($email !== '' ? $email : 'nicht angegeben'),
        'Datenschutz: bestätigt',
        '',
        'Nachricht:',
        $message !== '' ? $message : '(keine Nachricht angegeben)',
    ]);

    send_plain_mail(
        NEROZON_CONTACT_TO,
        '[NEROZON Web] Neue Kontaktanfrage',
        $body,
        $replyTo
    );

    respond(200, ['ok' => true]);
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method === 'GET' && ($_GET['action'] ?? '') === 'token') {
    issue_token();
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

require_same_origin_post();
consume_token();
$data = read_json_body();

if (($data['website'] ?? '') !== '') {
    respond(400, ['ok' => false, 'error' => 'rejected']);
}

$kind = $data['type'] ?? null;
if (!is_string($kind) || !in_array($kind, ['research', 'contact'], true)) {
    respond(422, ['ok' => false, 'error' => 'invalid_type']);
}

enforce_session_rate_limit($kind);

if ($kind === 'research') {
    handle_research($data);
}

handle_contact($data);
