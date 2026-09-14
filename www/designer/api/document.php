<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../runtime/designer/storage.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo designer_json($payload);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $document = designer_with_lock(static function (array $paths): array {
            return designer_read_document($paths['document']);
        });
        respond(200, $document);
    }

    if ($method !== 'PUT') {
        header('Allow: GET, PUT');
        respond(405, ['error' => 'method_not_allowed']);
    }

    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > DESIGNER_MAX_BYTES) {
        respond(413, ['error' => 'payload_too_large']);
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        respond(400, ['error' => 'empty_request']);
    }
    if (strlen($raw) > DESIGNER_MAX_BYTES) {
        respond(413, ['error' => 'payload_too_large']);
    }

    $incoming = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($incoming)) {
        respond(400, ['error' => 'invalid_document']);
    }
    designer_validate_document($incoming);

    $saved = designer_with_lock(static function (array $paths) use ($incoming): array {
        $current = designer_read_document($paths['document']);
        $clientVersion = (int)$incoming['version'];
        $serverVersion = (int)($current['version'] ?? 0);

        if ($clientVersion !== $serverVersion) {
            return [
                '__conflict' => true,
                'server' => $current,
            ];
        }

        $next = $incoming;
        $next['version'] = $serverVersion + 1;
        $next['updatedAt'] = gmdate('c');

        designer_snapshot_current($paths, $current);
        designer_atomic_write($paths['document'], designer_json($next));
        designer_prune_history($paths['history']);

        return $next;
    });

    if (($saved['__conflict'] ?? false) === true) {
        respond(409, [
            'error' => 'version_conflict',
            'server' => $saved['server'],
        ]);
    }

    respond(200, $saved);
} catch (JsonException $e) {
    respond(400, ['error' => 'invalid_json']);
} catch (InvalidArgumentException $e) {
    respond(422, ['error' => 'validation_failed', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('designer API: ' . $e->getMessage());
    respond(500, ['error' => 'server_error']);
}
