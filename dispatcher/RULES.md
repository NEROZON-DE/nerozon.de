# Dispatcher Rules

Der Dispatcher ist die kontrollierte Grenze zwischen internen NEROZON APIs und externen LLM-Anbietern.

## Zweck

- API-Meldungen annehmen.
- Requests persistieren und nachvollziehbar verarbeiten.
- Im ersten Wurf OpenAI über die Responses API bedienen.
- Status, Queue, Fehler und Einstellungen über eine geschützte Control-Seite sichtbar machen.

## Persistenz

- Der Dispatcher besitzt eine eigene Datenbank.
- Die Dispatcher-Datenbank ist die operative Wahrheit für Settings, Secrets, Queue, Ergebnisse, Retries, Cron-Läufe und Logs.
- Runtime-Jobs werden nicht parallel im Dateisystem persistiert.
- `/config/dispatcher/config.php` enthält nur Bootstrap-/Provisioning-Zugang zur Dispatcher-Datenbank und keine normale Dispatcher-Konfiguration.
- Init muss idempotent sein: fehlende Strukturen und Defaults dürfen ergänzt, vorhandene Daten oder Settings aber nicht gelöscht oder überschrieben werden.
- Destruktive Schemaänderungen gehören nicht in INIT und nicht in normale Runtime-Requests.

## Grenzen

- Keine API-Schlüssel, Tokens, Passwörter oder produktiven DB-Credentials im GitHub-Repo.
- Payloads werden nicht blind an Anbieter weitergereicht. Jeder Auftrag bekommt Metadaten, Status und Fehlerzustand.
- Provider-Zugriff erfolgt nur über dedizierte Adapter.

## Zugriff

- Control-Seite: Login geschützt; Credentials liegen als Benutzer + Passwort-Hash in der Dispatcher-Datenbank.
- Ingest-Endpunkt: Bearer Token Pflicht; Token liegt in der Dispatcher-Datenbank.
- Cron-Endpunkt: Cron Token Pflicht, außer CLI-Aufruf; Token liegt in der Dispatcher-Datenbank.
- Init-Endpunkt benötigt außerhalb CLI einen separaten Bootstrap-Init-Key aus `/config`.

## Betrieb

- Der IONOS Cronjob ruft `cron.php` regelmäßig auf.
- Ein Cronlauf verarbeitet nur eine konfigurierte Anzahl Jobs.
- Jeder Cronlauf wird in `dispatcher_cron_runs` protokolliert.
- Betriebsereignisse werden in `dispatcher_log` protokolliert.
- Fehler werden persistiert, aber nur bis zur konfigurierten Retry-Grenze automatisch wiederholt.

## Offene Punkte

- Rate Limits, Kostenlimits und Tenant-Trennung sind noch nicht implementiert.
- Provider-Routing ist vorbereitet, aber zunächst nur OpenAI implementiert.
