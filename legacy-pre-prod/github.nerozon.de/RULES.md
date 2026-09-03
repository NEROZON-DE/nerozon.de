# GitHub Adapter Rules

The GitHub Adapter is a central NEROZON platform service. It is not an application-environment component and MUST NOT be duplicated for DEV1, DEV2, DEV3, or other workload environments.

## Responsibility

The adapter provides controlled GitHub access to authorized NEROZON workers. It translates a small, explicit NEROZON tool contract into GitHub API calls.

The adapter MUST NOT perform orchestration, model routing, Work Order lifecycle decisions, role selection, or Dispatcher responsibilities.

## Access

Every adapter request that can access GitHub data MUST be authenticated with the adapter bearer token.

GitHub credentials MUST NOT be stored in this repository or below the public document root. Runtime secrets are loaded from `/_secrets` or the server-specific equivalent outside the document root.

The adapter MUST apply an allowlist before contacting GitHub. A worker MUST NOT be able to use the adapter as an unrestricted GitHub proxy.

## Operations

Operations MUST be explicit and narrow. The initial PREP implementation supports only read operations:

- `read_file`
- `list_path`

Write, commit, move, branch, pull-request, or administrative operations require a later explicit design and authorization decision.

## Observability

Requests SHOULD carry execution and Work Order correlation metadata when available. Credentials and authorization headers MUST never be logged or returned.

## Environment model

`github-prep.nerozon.de` is the PREP instance for developing and validating the central adapter itself.

`github.nerozon.de` is the production instance.

Application environments such as DEV1 or DEV2 consume one of these central services; they do not receive their own GitHub Adapter instance.
