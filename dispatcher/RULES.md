# Dispatcher Rules

Der Dispatcher ist die kontrollierte Grenze zwischen internen NEROZON APIs und externen LLM-Anbietern.

## Zweck

- API-Meldungen annehmen.
- Requests persistieren und nachvollziehbar verarbeiten.
- Im ersten Wurf OpenAI über die Responses API bedienen.
- Status, Queue, Fehler und Einstellungen über eine geschützte Control-Seite sichtbar machen.

## Grenzen

- Keine API-Schlüssel, Tokens, Passwörter oder personenbezogenen Betriebsdaten im GitHub-Repo.
- Konfiguration liegt außerhalb des Repos in `/config/dispatcher/config.php` oder in IONOS-Umgebungsvariablen.
- Payloads werden nicht blind an Anbieter weitergereicht. Jeder Auftrag bekommt Metadaten, Status und Fehlerzustand.
- Provider-Zugriff erfolgt nur über dedizierte Adapter.

## Zugriff

- Control-Seite: Login geschützt.
- Ingest-Endpunkt: Bearer Token Pflicht.
- Cron-Endpunkt: Cron Token Pflicht, außer CLI-Aufruf.

## Betrieb

- Der IONOS Cronjob ruft `cron.php` regelmäßig auf.
- Ein Cronlauf verarbeitet nur eine begrenzte Anzahl Jobs.
- Fehler werden persistiert, aber nicht endlos automatisch wiederholt.
- Secrets müssen vor Produktivbetrieb manuell auf dem Server gesetzt werden.

## Offene Punkte

- Persistenz zunächst Datei-basiert; später wahrscheinlich DB.
- Rate Limits, Kostenlimits und Tenant-Trennung sind im ersten Wurf nur vorbereitet.
- Provider-Routing ist vorbereitet, aber zunächst nur OpenAI implementiert.
