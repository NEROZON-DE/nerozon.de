# S092026 Questionnaire Backend

The public entry point is `/api/public/index.php`; backend classes remain outside the public directory. Runtime expects `/config/Secret.php`, which is deliberately not versioned. Use `/config/secret.example.php` only as the structure contract and provide `database.host`, `database.port`, `database.database`, `database.username`, and `database.password` manually on the server. There is no fallback to example/default credentials.

Initialize MariaDB 11.8 with `/database/migrations/001_INIT_questionnaire_submissions.sql`. Runtime requests never execute schema changes.

Run unit tests with `php api/tests/run.php`. The DB integration test is `php api/tests/integration.php` and requires `TEST_DB_HOST`, `TEST_DB_PORT`, `TEST_DB_NAME`, `TEST_DB_USER`, and `TEST_DB_PASSWORD` for a dedicated test database with the INIT migration applied.

Empty: `{"questionnaire_id":"S092026","questionnaire_version":"20260830","answers":[]}`

Partial: `{"questionnaire_id":"S092026","questionnaire_version":"20260830","answers":[{"question_id":"S092026Q01","type":"single_choice","value":"S092026Q01A01"}]}`

A full submission uses the same canonical `S092026Qxx` / `S092026QxxAyy` identifiers. Questions 10, 17, and 20 use `short_text` values instead of answer IDs.
