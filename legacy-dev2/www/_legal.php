<?php
declare(strict_types=1);

function nerozon_legal_data(): array
{
    $secretFile = getenv('NEROZON_LEGAL_SECRET_FILE') ?: ($_SERVER['NEROZON_LEGAL_SECRET_FILE'] ?? '');

    if (!is_string($secretFile) || $secretFile === '' || !is_readable($secretFile)) {
        return [];
    }

    $raw = file_get_contents($secretFile);
    if ($raw === false) {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function nerozon_legal_value(array $data, string $key): string
{
    $value = $data[$key] ?? '';
    return is_scalar($value) ? trim((string) $value) : '';
}

function nerozon_legal_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
