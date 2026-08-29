# nerozon.de Deployment

Status: DRAFT

## Grundsätze

- Produktive Anwendungsbereiche müssen aus dem versionierten Repository reproduzierbar sein.
- Persistente Laufzeitdaten liegen außerhalb austauschbarer Deployment-Bäume.
- Deployment- und Runtime-Zugriffe folgen Least Privilege.
- Fehlerhafte versionierte Änderungen werden bevorzugt nachvollziehbar reverted.
- `specs` ist ein Informations- und Referenzbranch und niemals Quelle eines Runtime-Deployments.

## Nicht deploybare Repository-Inhalte

Folgende Inhalte dürfen niemals Bestandteil eines Web- oder API-Deployments sein:

- `/docs/**`
- `RULES.md`
- `**/RULES.md`
- `**/*-RULES.md`
- `SPEC.md`
- `**/SPEC.md`
- `**/*-SPEC.md`

Diese Ausschlüsse sind technisch beim Erzeugen des Deployment-Artefakts umzusetzen; die bloße Dateinamenskonvention reicht nicht aus.

## Aktuelles Hosting-Modell

Die Web- und API-Artefakte werden getrennt vorbereitet und veröffentlicht.
Persistente Konfiguration, Secrets und Laufzeitdaten dürfen durch ein Deployment nicht überschrieben werden.

Die konkrete Deployment-Implementierung liegt in den ausführbaren Branches unter `/.github/` und `/deploy/` und muss diese Regeln erfüllen.
