# Dispatcher Rules

Der Dispatcher ist die kontrollierte Grenze zwischen internen NEROZON APIs und externen LLM-Anbietern.

## Zweck

- API-Meldungen annehmen.
- Requests persistieren und nachvollziehbar verarbeiten.
- Im ersten Wurf OpenAI über die Responses API bedienen.
- Work Orders aus einer technisch registrierten Queue in Fundamental-Role-Worker-Ausführungen überführen.
- Status, Queue, Fehler und Einstellungen über eine geschützte Control-Seite sichtbar machen.
- Telemetrie und technische Ausführungszustände persistieren.

## Persistenz

- Jede NEROZON-Umgebung besitzt eine eigene Datenbank. Der Dispatcher nutzt die Datenbank seiner aktuellen Umgebung.
- Innerhalb dieser Datenbank verwendet der Dispatcher den Tabellen-Namensraum `dispatcher_*`.
- Die Dispatcher-Tabellen sind die operative Wahrheit für Settings, Secrets, Queue, Ergebnisse, Retries, Cron-Läufe, Logs und technische Work-Order-Ausführungszustände.
- Runtime-Jobs werden nicht parallel im Dateisystem persistiert.
- Der Dispatcher ist nicht die Wahrheit für fachliche Arbeitsartefakte eines KI-Workers. CR, WO, Dateien, Commits, PRs oder andere Änderungen bleiben in ihrem jeweiligen Zielsystem.
- `dispatcher_workorders` enthält nur die für Orchestrierung erforderlichen Work-Order-Metadaten und Referenzen, nicht den vollständigen WO-Inhalt.
- Dispatcher-Ergebnisse dürfen Status, Zusammenfassungen, Fehler, Telemetrie und Referenzen auf externe Artefakte enthalten. Fachliche Artefakte sollen nicht dauerhaft als zweite Wahrheit im Dispatcher dupliziert werden.
- `/env-config/database.php` ist serverseitige Environment-Konfiguration und gehört nicht ins Repository.
- Die eigentlichen DB-Credentials dürfen außerhalb des Environment-Webroots in einer separaten Secret-Datei liegen; `/env-config/database.php` darf diese lediglich laden.
- Init muss idempotent sein: fehlende Strukturen und Defaults dürfen ergänzt, vorhandene Daten oder Settings aber nicht gelöscht oder überschrieben werden.
- Versionierte Schemaänderungen gehören nach `/database/init` bzw. `/database/migrations`.
- Destruktive Schemaänderungen gehören nicht in INIT und nicht in normale Runtime-Requests.

## Work Orders

- Im ersten PoC akzeptiert der Dispatcher Work Orders ausschließlich aus `NEROZON-DE/nerozon.de`, Branch `dev1`.
- GitHub Actions liefern dem Dispatcher faktische Angaben zu Repository, Branch, Pfad und Commit. Diese Angaben werden nicht aus dem WO-Text abgeleitet.
- Das WO-Target bezeichnet ausschließlich eine Fundamental Role.
- Das Mapping einer Fundamental Role auf `authority-*/main/ROLE.md` wird für den PoC deterministisch durch GitHub Actions aufgelöst und vom Dispatcher gegen die erwartete Zuordnung validiert.
- Der Dispatcher kennt keine Personal Agents und entscheidet nicht über eine konkrete Persona oder Spezialisierung.
- Registrierung erfolgt vor `request → queued`. Der Worker-Start erfolgt erst nach erfolgreichem Move/Push nach `queued`.
- Ein gestarteter Worker erhält nur die Authority- und Work-Referenzen als Bootstrap-Einstiegspunkte. Dauerhafte Regeln und Arbeitsinhalte werden nicht in den Dispatcher-Prompt dupliziert.
- OpenAI Worker werden im Background Mode gestartet. Die OpenAI `response_id` wird unmittelbar persistiert; die Lebensdauer einer Worker-Ausführung darf nicht an einen laufenden PHP-Request gekoppelt sein.
- Ein erneuter Start derselben bereits gestarteten WO darf nicht unbeabsichtigt eine zweite OpenAI-Ausführung erzeugen.

## Grenzen

- Keine API-Schlüssel, Tokens, Passwörter oder produktiven DB-Credentials im GitHub-Repo.
- Payloads werden nicht blind an Anbieter weitergereicht. Jeder Auftrag bekommt Metadaten, Status und Fehlerzustand.
- Provider-Zugriff erfolgt nur über dedizierte Adapter.
- Der Dispatcher orchestriert und protokolliert. Er benötigt keine Kenntnis von GitHub-Arbeitsinhalten oder Personal Agents.
- Der spätere GitHub-Zugriff eines Workers erfolgt über einen separaten kontrollierten NEROZON GitHub Adapter, nicht durch den Dispatcher selbst.

## Zugriff

- Control-Seite: Login geschützt; Credentials liegen als Benutzer + Passwort-Hash in den Dispatcher-Settings.
- Ingest-Endpunkt: kanonisch `Authorization: Bearer <token>`; Token liegt in den Dispatcher-Settings.
- Work-Order-Endpunkt: eigenes `worker_trigger_token`; dieses Secret darf nicht mit Ingest- oder Cron-Token wiederverwendet werden.
- Auf der verifizierten IONOS-CGI/FastCGI-Laufzeit wird der `Authorization`-Header per `.htaccess`/`SetEnvIf` nach `REDIRECT_HTTP_AUTHORIZATION` übernommen und von PHP dort gelesen.
- `X-Nerozon-Ingest-Token` ist in DEV als interner Fallback zulässig, aber nicht der bevorzugte externe Vertrag.
- Cron-Endpunkt: Cron Token Pflicht, außer CLI-Aufruf; Token liegt in den Dispatcher-Settings.
- Auf dem aktuellen IONOS-Altvertrag darf `init.php` per Browser ausgeführt werden, weil der bereitgestellte PHP-Shell-Aufruf als CGI arbeitet.
- Browser-Init ist ausschließlich erlaubt, wenn die serverseitige Datei `/env-config/admin-mode.php` exakt `true` zurückgibt. Fehlt die Datei oder gibt sie nicht `true` zurück, antwortet `init.php` mit HTTP 404.
- `admin-mode.php` gehört nicht ins Repository und muss außerhalb gezielter Administrationsarbeiten auf `false` stehen.
- Der von IONOS bereitgestellte DB-User besitzt technisch auch DDL-Rechte. Runtime-Code darf deshalb keine generischen DDL-Funktionen oder Schema-Endpunkte anbieten.

## Betrieb

- Der IONOS Cronjob ruft `cron.php` regelmäßig auf.
- Ein Cronlauf verarbeitet nur eine konfigurierte Anzahl generischer Jobs.
- Jeder Cronlauf wird in `dispatcher_cron_runs` protokolliert.
- Betriebsereignisse werden in `dispatcher_log` protokolliert.
- Fehler werden persistiert, aber nur bis zur konfigurierten Retry-Grenze automatisch wiederholt.
- Background-Worker-Zustände müssen später durch Webhook oder Reconciliation gegen OpenAI überprüfbar sein; ein dauerhaft laufender PHP-Prozess ist keine Voraussetzung.
- Temporäre Diagnose-Endpunkte müssen nach Abschluss einer Untersuchung wieder entfernt werden.

## Offene Punkte

- Der NEROZON GitHub Adapter für Worker-Toolzugriffe ist noch nicht implementiert.
- Webhook-/Reconciliation-Verarbeitung für laufende OpenAI Background Responses ist noch nicht implementiert.
- Rate Limits, Kostenlimits und Tenant-Trennung sind noch nicht implementiert.
- Provider-Routing ist vorbereitet, aber zunächst nur OpenAI implementiert.
- Retention für Rohantworten, Logs und technische Diagnosedaten muss vor Produktion verbindlich festgelegt werden.
