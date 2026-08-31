# NEROZON Dispatcher

`dispatcher.nerozon.de -> /dispatcher/`

Der Dispatcher nimmt LLM-Jobs der API entgegen und verarbeitet sie asynchron per IONOS Cronjob. Im ersten Wurf ist OpenAI angebunden.

## Persistenz

Der Dispatcher besitzt eine eigene Datenbank. Die Datenbank ist die operative Wahrheit für:

- Dispatcher-Einstellungen
- OpenAI API-Key und Provider-Einstellungen
- Ingest- und Cron-Token
- Control-Login (Benutzer + Passwort-Hash)
- Queue, Status, Retries und Resultate der Jobs
- Cron-Läufe
- Dispatcher-/Cron-Logs

Im Dateisystem liegen keine Runtime-Jobs oder Dispatcher-Secrets mehr.

## Bootstrap-Konfiguration

Secrets liegen nicht im Repo. Auf IONOS liegt:

`/config/dispatcher/config.php`

Sie enthält nur, was benötigt wird, um die Dispatcher-Datenbank überhaupt zu erreichen bzw. initial zu provisionieren:

- DB Host/Port
- DB Name
- DB Benutzer/Passwort
- temporärer Init-Key
- optional Provisioning-Credentials für `CREATE DATABASE` / `CREATE USER`

Siehe `dispatcher/config/dispatcher.config.example.php`.

## Init

`/dispatcher/init.php`

Init ist absichtlich wiederholt ausführbar:

- `CREATE DATABASE IF NOT EXISTS` (wenn konfiguriert/erlaubt)
- `CREATE USER IF NOT EXISTS` und GRANT (wenn Provisioning aktiviert und vom Hosting erlaubt)
- `CREATE TABLE IF NOT EXISTS`
- fehlende Settings via `INSERT IGNORE`
- keine Tabellen, Daten, Settings oder Credentials werden gelöscht oder überschrieben

Erstmalig erzeugte Admin-/Ingest-/Cron-Credentials werden nur beim erstmaligen Anlegen im Init-Output ausgegeben.

Browser-Aufruf:

`https://dispatcher.nerozon.de/init.php?key=<INIT_KEY>`

CLI-Aufruf benötigt keinen Init-Key.

Hinweis: Falls IONOS im Hosting-Paket `CREATE DATABASE` oder `CREATE USER` nicht erlaubt, werden Datenbank und DB-Benutzer einmalig im IONOS Panel angelegt. Init übernimmt danach Tabellen und Dispatcher-Inhalte.

## Endpunkte

- `GET /index.php` — Login-geschützte Control-Seite mit Status und DB-basierten Einstellungen
- `GET /status.php` — maschinenlesbarer Status ohne sensitive Werte
- `POST /ingest.php` — nimmt API-Payloads entgegen und schreibt Jobs in die DB
- `GET /cron.php?token=...` — verarbeitet DB-Queue und protokolliert jeden Cron-Lauf
- `GET /init.php?key=...` — idempotente Initialisierung

## IONOS Cronjob

`https://dispatcher.nerozon.de/cron.php?token=<CRON_TOKEN>`

Empfehlung erster Wurf: alle 5 Minuten. Laufintervall und Jobs pro Lauf sind getrennte Stellgrößen.

## Beispiel Ingest

```bash
curl -X POST https://dispatcher.nerozon.de/ingest.php \
  -H "Authorization: Bearer <INGEST_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"source":"api","type":"llm.request","payload":{"input":"Sag kurz Hallo"}}'
```

## Architekturentscheidung

Die API ruft OpenAI nicht direkt auf. Sie gibt Arbeit an den Dispatcher. Dadurch liegen Routing, Kostenkontrolle, Retry, Providerwechsel, Audit und Monitoring an einer kontrollierten Stelle.
