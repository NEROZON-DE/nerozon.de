# NEROZON Deployment

Status: DRAFT

## Grundsätze

- Produktive Anwendungsbereiche müssen aus dem versionierten Repository reproduzierbar sein.
- Persistente Laufzeitdaten liegen außerhalb austauschbarer Deployment-Bäume.
- Deployment- und Runtime-Zugriffe folgen Least Privilege.
- Fehlerhafte versionierte Änderungen werden bevorzugt nachvollziehbar reverted.

## Nicht deploybare Repository-Inhalte

Folgende Inhalte dürfen niemals Bestandteil eines Web-Deployments sein:

- `/docs/**`
- `RULES.md`
- `**/RULES.md`
- `**/*-RULES.md`

Der Ausschluss von `/docs/**` ist zusätzlich zur Dateinamenskonvention technisch im Deployment umzusetzen.

## Aktuelles Hosting-Modell

Die Web- und API-Artefakte werden getrennt vorbereitet und veröffentlicht. Persistente Konfiguration, Secrets und Laufzeitdaten dürfen durch ein Deployment nicht überschrieben werden.

Die konkrete Deployment-Implementierung liegt unter `/.github/` und `/deploy/` und muss diese Regeln erfüllen.
