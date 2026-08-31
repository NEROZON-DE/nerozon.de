# NEROZON Dispatcher

`dispatcher.nerozon.de -> /dispatcher/`

Der Dispatcher nimmt Meldungen der API entgegen, legt daraus LLM-Jobs an und verarbeitet sie asynchron per IONOS Cronjob. Im ersten Wurf ist OpenAI angebunden.

## Endpunkte

- `GET /index.php` — Login-geschützte Control-Seite
- `GET /status.php` — maschinenlesbarer Status ohne sensitive Details
- `POST /ingest.php` — nimmt API-Payloads entgegen
- `GET /cron.php?token=...` — verarbeitet Queue via IONOS Cronjob

## Server-Konfiguration

Secrets liegen nicht im Repo. Auf IONOS muss eine Datei angelegt werden:

`/config/dispatcher/config.php`

Siehe `config/dispatcher.config.example.php`.

Pflichtwerte:

- `OPENAI_API_KEY`
- `DISPATCHER_INGEST_TOKEN`
- `DISPATCHER_CRON_TOKEN`
- `DISPATCHER_ADMIN_PASSWORD_HASH`

Passworthash lokal erzeugen:

```php
php -r "echo password_hash('dein-passwort', PASSWORD_DEFAULT), PHP_EOL;"
```

## IONOS Cronjob

Ziel-URL:

```text
https://dispatcher.nerozon.de/cron.php?token=<DISPATCHER_CRON_TOKEN>
```

Empfehlung erster Wurf: alle 5 Minuten.

## Beispiel Ingest

```bash
curl -X POST https://dispatcher.nerozon.de/ingest.php \
  -H "Authorization: Bearer <DISPATCHER_INGEST_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"source":"api","type":"llm.request","payload":{"input":"Sag kurz Hallo"}}'
```

## Architekturentscheidung

Die eigentliche API ruft OpenAI nicht direkt auf. Sie gibt Arbeit an den Dispatcher. Dadurch bekommen wir später Routing, Kostenkontrolle, Retry-Logik, Providerwechsel, Audit und Monitoring an einer Stelle.
