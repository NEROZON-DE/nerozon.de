# NEROZON Dispatcher

Der Dispatcher nimmt LLM-Jobs der API entgegen und verarbeitet sie asynchron. Im ersten Wurf ist OpenAI angebunden.

## Persistenz

Der Dispatcher nutzt die Datenbank der aktuellen NEROZON-Umgebung und legt dort ausschließlich Tabellen mit dem Namensraum `dispatcher_*` an.

Die Dispatcher-Tabellen enthalten:

- Dispatcher-Einstellungen
- OpenAI API-Key und Provider-Einstellungen
- Ingest- und Cron-Token
- Control-Login (Benutzer + Passwort-Hash)
- Queue, Status, Retries und Resultate der Jobs
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

`dispatcher/init.php` ist ausschließlich über PHP CLI ausführbar. HTTP-Aufrufe liefern 404.

Beispiel aus dem Environment-Root:

```bash
php dispatcher/init.php
```

Init ist idempotent:

- `CREATE TABLE IF NOT EXISTS`
- fehlende Settings via `INSERT IGNORE`
- keine bestehenden Tabellen, Daten, Settings oder Credentials werden gelöscht oder überschrieben

Beim ersten Lauf werden Admin-Passwort, Ingest-Token und Cron-Token erzeugt und einmalig im CLI-Output ausgegeben. Diese Werte müssen direkt gesichert werden.

## Aktuelle Tabellen

- `dispatcher_settings`
- `dispatcher_jobs`
- `dispatcher_cron_runs`
- `dispatcher_log`

## Endpunkte

- `GET /index.php` — Login-geschützte Control-Seite
- `GET /status.php` — maschinenlesbarer Status ohne sensitive Werte
- `POST /ingest.php` — nimmt API-Payloads entgegen und legt Jobs an
- `GET /cron.php?token=...` — verarbeitet die Queue und protokolliert Cron-Läufe

## Architekturentscheidung

Die API ruft externe LLM-Anbieter nicht direkt auf. Sie gibt Arbeit an den Dispatcher. Routing, Kostenkontrolle, Retries, Providerwechsel, Audit und Monitoring bleiben damit an einer kontrollierten Grenze.
