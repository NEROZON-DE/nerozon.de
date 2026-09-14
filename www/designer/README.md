# NEROZON Designer PoC

## Runtime

- PHP 8.4 / Apache
- Public entry: `/designer/`
- API: `/designer/api/document.php`
- Persistent data: repository/server path `/runtime/designer/data/`
- React Flow: `@xyflow/react` 12.11.6 loaded as a pinned browser dependency

## Required permissions

The PHP/Apache user must be able to create and modify files in:

```text
/runtime/designer/data/
/runtime/designer/data/history/
```

The runtime creates `designer.json` and `designer.lock` itself.

## Persistence semantics

`PUT /designer/api/document.php` accepts the complete graph document. The submitted `version` must match the current server version. Otherwise the API returns HTTP 409 and does not overwrite the server document.

Writes use a temporary file plus `rename()` under an exclusive `flock()` lock. Before replacing a persisted document, the previous state is written to `/runtime/designer/data/history/`. PHP prunes the directory after every save and verifies that at most 10 JSON snapshots remain. Failure to delete an obsolete snapshot makes the save request fail rather than silently exceeding the configured history limit.

Runtime JSON and lock files are intentionally ignored by Git.
