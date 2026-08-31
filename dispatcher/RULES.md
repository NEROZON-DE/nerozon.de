# Dispatcher Rules

Der Dispatcher ist die kontrollierte Grenze zwischen internen NEROZON APIs und externen LLM-Anbietern.

## Zweck

- API-Meldungen annehmen.
- Requests persistieren und nachvollziehbar verarbeiten.
- Im ersten Wurf OpenAI über die Responses API bedienen.
- Status, Queue, Fehler und Einstellungen über eine geschützte Control-Seite sichtbar machen.

## Persistenz

- Jede NEROZON-Umgebung besitzt eine eigene Datenbank. Der Dispatcher nutzt die Datenbank seiner aktuellen Umgebung.
- Innerhalb dieser Datenbank verwendet der Dispatcher den Tabellen-Namensraum `dispatcher_*`.
- Die Dispatcher-Tabellen sind die operative Wahrheit für Settings, Secrets, Queue, Ergebnisse, Retries, Cron-Läufe und Logs.
- Runtime-Jobs werden nicht parallel im Dateisystem persistiert.
- `/env-config/database.php` ist serverseitige Environment-Konfiguration und gehört nicht ins Repository.
- Die eigentlichen DB-Credentials dürfen außerhalb des Environment-Webroots in einer separaten Secret-Datei liegen; `/env-config/database.php` darf diese lediglich laden.
- Init muss idempotent sein: fehlende Strukturen und Defaults dürfen ergänzt, vorhandene Daten oder Settings aber nicht gelöscht oder überschrieben werden.
- Destruktive Schemaänderungen gehören nicht in INIT und nicht in normale Runtime-Requests.

## Grenzen

- Keine API-Schlüssel, Tokens, Passwörter oder produktiven DB-Credentials im GitHub-Repo.
- Payloads werden nicht blind an Anbieter weitergereicht. Jeder Auftrag bekommt Metadaten, Status und Fehlerzustand.
- Provider-Zugriff erfolgt nur über dedizierte Adapter.

## Zugriff

- Control-Seite: Login geschützt; Credentials liegen als Benutzer + Passwort-Hash in den Dispatcher-Settings.
- Ingest-Endpunkt: Bearer Token Pflicht; Token liegt in den Dispatcher-Settings.
- Cron-Endpunkt: Cron Token Pflicht, außer CLI-Aufruf; Token liegt in den Dispatcher-Settings.
- Init darf nur über CLI ausgeführt werden. Ein HTTP-Aufruf von `init.php` wird nicht angeboten.
- Der von IONOS bereitgestellte DB-User besitzt technisch auch DDL-Rechte. Runtime-Code darf deshalb keine generischen DDL-Funktionen oder Schema-Endpunkte anbieten.

## Betrieb

- Der IONOS Cronjob ruft `cron.php` regelmäßig auf.
- Ein Cronlauf verarbeitet nur eine konfigurierte Anzahl Jobs.
- Jeder Cronlauf wird in `dispatcher_cron_runs` protokolliert.
- Betriebsereignisse werden in `dispatcher_log` protokolliert.
- Fehler werden persistiert, aber nur bis zur konfigurierten Retry-Grenze automatisch wiederholt.

## Offene Punkte

- Rate Limits, Kostenlimits und Tenant-Trennung sind noch nicht implementiert.
- Provider-Routing ist vorbereitet, aber zunächst nur OpenAI implementiert.
