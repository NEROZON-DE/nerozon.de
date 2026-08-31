# NEROZON GitHub Adapter

This directory contains the central GitHub Adapter used by NEROZON workers.

The first PREP version is intentionally read-only. It supports `read_file` and `list_path` and rejects every repository that is not explicitly allowlisted.

## Runtime configuration

The public document root contains no credentials. The adapter expects two PHP files outside the document root:

`/env-config/github.php`

```php
<?php
return [
    'github_api_base' => 'https://api.github.com',
    'allowed_repositories' => [
        'NEROZON-DE/nerozon.de',
        'NEROZON-DE/authority-engineering',
        'NEROZON-DE/authority-solution',
        'NEROZON-DE/authority-implementation',
        'NEROZON-DE/authority-quality-assurance',
    ],
];
```

`/._secrets/github.php`

```php
<?php
return [
    'adapter_token' => '<random adapter bearer token>',
    'github_token' => '<GitHub credential with only the required repository read permissions>',
];
```

The exact filesystem root is derived as the parent directory of the deployed adapter document root.

## Request contract

`POST /index.php`

```json
{
  "operation": "read_file",
  "repository": "NEROZON-DE/authority-implementation",
  "ref": "main",
  "path": "ROLE.md",
  "execution_id": "EX-...",
  "wo": "WO-..."
}
```

Authorization:

`Authorization: Bearer <adapter_token>`

`execution_id` and `wo` are accepted as request metadata for the future telemetry layer; version 0.1 does not persist them.

## Deployment model

PREP endpoint: `github-prep.nerozon.de`

Production endpoint: `github.nerozon.de`

DEV application environments consume the central endpoint. They do not receive separate adapter instances.
