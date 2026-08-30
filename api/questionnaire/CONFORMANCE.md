# S092026 Backend Conformance

Status: IMPLEMENTED – pending required review gates

Implemented boundaries: public HTTP only under `/api/public`; HTTP translation is separate from business logic; business logic has no HTTP/PDO/SQL dependency; questionnaire repository owns domain-to-persistence mapping; DB connector is questionnaire-neutral; runtime secrets are loaded only from unversioned `/config/Secret.php`; migration is explicit and versioned.

Data model: fixed questionnaire `S092026`, questionnaire version `20260830`, schema version `1`; deterministic `S092026Qxx` and `S092026QxxAyy` validation for all 20 questions; optional/empty answers accepted; unknown questions/options, wrong types, duplicate questions and overlong short text rejected; domain payload excludes IP, User-Agent, email, message and contact references.

Persistence: one atomic row per submission with UUID, questionnaire/version/schema fields, UTC timestamp and deterministic JSON payload. MariaDB INIT provides primary key, version/time index, positive schema constraint and JSON validity constraint.

Tests: unit runner covers empty submission, canonical partial mapping, invalid/duplicate answers, valid HTTP mapping, malformed JSON and method rejection. Database integration test exercises HTTP → business → repository → DB when a dedicated test database is supplied. Production secrets are neither required nor accepted by tests.

Required reviews outstanding: Conrad (architecture/component boundaries), Sina (data model/persistence/canonical IDs), Tessa (tests/error paths/integration). DONE requires all three without open BLOCKER.
