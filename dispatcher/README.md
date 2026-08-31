# NEROZON Dispatcher

Der Dispatcher nimmt LLM-Jobs der API entgegen und verarbeitet sie asynchron. Im ersten Wurf ist OpenAI angebunden.

## Persistenz

Der Dispatcher nutzt die Datenbank der aktuellen NEROZON-Umgebung und legt dort ausschließlich Tabellen mit dem Namensraum `dispatcher_*` an.

Die Dispatcher-Tabellen enthalten:

- Dispatcher-Einstellungen
- OpenAI API-Key und Provider-Einstellungen
- Ingest-, Worker-Trigger- und Cron-Token
- Control-Login (Benutzer + Passwort-Hash)
- Queue, Status, Retries und Resultate der Jobs
- Work-Order-Registry und OpenAI-Execution-Referenzen
- Cron-Läufe und Dispatcher-Logs

## Environment-Konfiguration

Die Datenbankverbindung kommt ausschließlich aus:

`/env-config/database.php`

Diese Datei ist serverseitige Environment-Konfiguration, gehört nicht ins Repository und wird nicht deployed. Erwartete Rückgabe:

```php
return [
    'host' => '...',
    'database' => '...',
    'username' => '...',
    'password' => '...',
    'charset' => 'utf8mb4',
];
```

`/env-config/database.php` darf die eigentlichen Credentials aus einer separaten Secret-Datei laden.

## Init

Browser-Init ist auf dem aktuellen IONOS-Altvertrag ausschließlich bei aktivem serverseitigem `/env-config/admin-mode.php` erlaubt. Init ist idempotent und ergänzt fehlende Strukturen und Settings, ohne bestehende Werte zu überschreiben.

Beim ersten Anlegen eines Settings werden die erzeugten Secrets einmalig im Init-Output ausgegeben. Der neue GitHub-Action-Trigger verwendet `worker_trigger_token` und darf nicht mit dem normalen Ingest-Token identisch sein.

Die kanonische versionierte Schemaänderung für die Work-Order-Registry liegt zusätzlich unter `/database/migrations/`.

## Aktuelle Tabellen

- `dispatcher_settings`
- `dispatcher_jobs`
- `dispatcher_workorders`
- `dispatcher_cron_runs`
- `dispatcher_log`

## Work-Order-PoC

DEV1 ist zunächst die einzige zugelassene Work-Order-Quelle.

1. GitHub Actions erkennt eine neue `workorders/request/WO-*.md`.
2. Die Action löst das Fundamental-Role-Target deterministisch auf eine Authority `main:/ROLE.md` auf.
3. `POST /workorder.php?action=register` registriert die faktischen GitHub-Referenzen.
4. Erst nach erfolgreicher Registrierung verschiebt die Action die WO nach `workorders/queued/` und pusht den Commit.
5. `POST /workorder.php?action=start` aktualisiert Pfad/Commit und startet eine OpenAI Response mit `background: true`.
6. Die OpenAI `response_id` wird in `dispatcher_workorders` persistiert. Der PHP-Request kann danach enden; der Worker-Lebenszyklus ist nicht an die PHP-Laufzeit gebunden.

Bis der NEROZON GitHub Adapter verfügbar ist, kann der gestartete Worker seine übergebenen Authority- und Work-Order-Referenzen noch nicht selbst laden oder GitHub ändern. Dieser erwartete Abbruchpunkt ist Teil des ersten End-to-End-PoC.

## Endpunkte

- `GET /index.php` — Login-geschützte Control-Seite
- `GET /status.php` — maschinenlesbarer Status ohne sensitive Werte
- `POST /ingest.php` — nimmt allgemeine API-Payloads entgegen und legt Jobs an
- `POST /workorder.php?action=register` — registriert eine DEV1 Work Order
- `POST /workorder.php?action=start` — startet den Fundamental-Role-Worker im OpenAI Background Mode
- `GET /cron.php?token=...` — verarbeitet die bestehende generische Queue und protokolliert Cron-Läufe

## Architekturentscheidung

Die API ruft externe LLM-Anbieter nicht direkt auf. Sie gibt Arbeit an den Dispatcher. Routing, Kostenkontrolle, Retries, Providerwechsel, Audit und Monitoring bleiben damit an einer kontrollierten Grenze.

Der Dispatcher kennt keine Personal Agents und greift nicht selbst auf GitHub-Arbeitsinhalte zu. GitHub Actions liefern faktische Work-Order- und Authority-Referenzen. Der später gestartete Worker erhält nur diese Einstiegspunkte; sein kontrollierter GitHub-Zugriff erfolgt über den separaten NEROZON GitHub Adapter.
