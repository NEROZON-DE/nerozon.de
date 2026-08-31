# Dispatcher Rules

Der Dispatcher ist die kontrollierte Grenze zwischen internen NEROZON APIs und externen LLM-Anbietern.

## Zweck

- API-Meldungen annehmen.
- Requests persistieren und nachvollziehbar verarbeiten.
- Im ersten Wurf OpenAI über die Responses API bedienen.
- Status, Queue, Fehler und Einstellungen über eine geschützte Control-Seite sichtbar machen.
- Telemetrie und technische Ausführungszustände persistieren.

## Persistenz

- Jede NEROZON-Umgebung besitzt eine eigene Datenbank. Der Dispatcher nutzt die Datenbank seiner aktuellen Umgebung.
- Innerhalb dieser Datenbank verwendet der Dispatcher den Tabellen-Namensraum `dispatcher_*`.
- Die Dispatcher-Tabellen sind die operative Wahrheit für Settings, Secrets, Queue, Ergebnisse, Retries, Cron-Läufe und Logs.
- Runtime-Jobs werden nicht parallel im Dateisystem persistiert.
- Der Dispatcher ist nicht die Wahrheit für fachliche Arbeitsartefakte eines KI-Workers. Dateien, Commits, PRs oder andere Änderungen bleiben in ihrem jeweiligen Zielsystem.
- Dispatcher-Ergebnisse dürfen Status, Zusammenfassungen, Fehler, Telemetrie und Referenzen auf externe Artefakte enthalten. Fachliche Artefakte sollen nicht dauerhaft als zweite Wahrheit im Dispatcher dupliziert werden.
- `/env-config/database.php` ist serverseitige Environment-Konfiguration und gehört nicht ins Repository.
- Die eigentlichen DB-Credentials dürfen außerhalb des Environment-Webroots in einer separaten Secret-Datei liegen; `/env-config/database.php` darf diese lediglich laden.
- Init muss idempotent sein: fehlende Strukturen und Defaults dürfen ergänzt, vorhandene Daten oder Settings aber nicht gelöscht oder überschrieben werden.
- Destruktive Schemaänderungen gehören nicht in INIT und nicht in normale Runtime-Requests.

## Grenzen

- Keine API-Schlüssel, Tokens, Passwörter oder produktiven DB-Credentials im GitHub-Repo.
- Payloads werden nicht blind an Anbieter weitergereicht. Jeder Auftrag bekommt Metadaten, Status und Fehlerzustand.
- Provider-Zugriff erfolgt nur über dedizierte Adapter.
- Der Dispatcher orchestriert und protokolliert. Er benötigt keine Kenntnis von GitHub oder anderen fachlichen Zielsystemen, solange der jeweilige KI-Worker diese selbst bedient.

## Zugriff

- Control-Seite: Login geschützt; Credentials liegen als Benutzer + Passwort-Hash in den Dispatcher-Settings.
- Ingest-Endpunkt: kanonisch `Authorization: Bearer <token>`; Token liegt in den Dispatcher-Settings.
- Auf der verifizierten IONOS-CGI/FastCGI-Laufzeit wird der `Authorization`-Header per `.htaccess`/`SetEnvIf` nach `REDIRECT_HTTP_AUTHORIZATION` übernommen und von PHP dort gelesen.
- `X-Nerozon-Ingest-Token` ist in DEV als interner Fallback zulässig, aber nicht der bevorzugte externe Vertrag.
- Cron-Endpunkt: Cron Token Pflicht, außer CLI-Aufruf; Token liegt in den Dispatcher-Settings.
- Auf dem aktuellen IONOS-Altvertrag darf `init.php` per Browser ausgeführt werden, weil der bereitgestellte PHP-Shell-Aufruf als CGI arbeitet.
- Browser-Init ist ausschließlich erlaubt, wenn die serverseitige Datei `/env-config/admin-mode.php` exakt `true` zurückgibt. Fehlt die Datei oder gibt sie nicht `true` zurück, antwortet `init.php` mit HTTP 404.
- `admin-mode.php` gehört nicht ins Repository und muss außerhalb gezielter Administrationsarbeiten auf `false` stehen.
- Der von IONOS bereitgestellte DB-User besitzt technisch auch DDL-Rechte. Runtime-Code darf deshalb keine generischen DDL-Funktionen oder Schema-Endpunkte anbieten.

## Betrieb

- Der IONOS Cronjob ruft `cron.php` regelmäßig auf.
- Ein Cronlauf verarbeitet nur eine konfigurierte Anzahl Jobs.
- Jeder Cronlauf wird in `dispatcher_cron_runs` protokolliert.
- Betriebsereignisse werden in `dispatcher_log` protokolliert.
- Fehler werden persistiert, aber nur bis zur konfigurierten Retry-Grenze automatisch wiederholt.
- Temporäre Diagnose-Endpunkte müssen nach Abschluss einer Untersuchung wieder entfernt werden.

## Offene Punkte

- Rate Limits, Kostenlimits und Tenant-Trennung sind noch nicht implementiert.
- Provider-Routing ist vorbereitet, aber zunächst nur OpenAI implementiert.
- Retention für Rohantworten, Logs und technische Diagnosedaten muss vor Produktion verbindlich festgelegt werden.
