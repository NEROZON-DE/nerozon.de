# Questionnaire Repository – Specification

Status: DRAFT
Scope: dev3 questionnaire PoC

## Zweck

`questionnaire-repository` ist der fachliche Persistenzadapter zwischen `questionnaire-data-model` und dem generischen `db-connector`.

## Verantwortung

Das Repository MUSS:

- `QuestionnaireSubmission` vollständig auf die definierte Persistenzstruktur abbilden.
- das fachliche JSON-Payload deterministisch erzeugen.
- erforderliche technische/operative Indexwerte aus dem Domainobjekt ableiten.
- ausschließlich den generischen `db-connector` für DB-Zugriff verwenden.
- Persistenzfehler in einen für die Business Logic stabilen Repository-Fehler übersetzen.

Das Repository DARF NICHT:

- HTTP kennen.
- fachliche Validierung aus dem Data Model duplizieren.
- Kontaktdaten oder technische Abuse-/Telemetry-Daten in den fachlichen Antwortdatensatz mischen.
- direkte PDO-/Connection-Objekte an höhere Schichten herausgeben.

## Initiale Operationen

- `save(QuestionnaireSubmission): void`

Read-/Search-Operationen werden erst eingeführt, wenn ein konkreter fachlicher oder administrativer Zugriff spezifiziert ist. Der PoC soll keine vorauseilende Repository-API erzeugen.

## Persistenzmapping

Initiale Spalten:

- `submission_id`
- `questionnaire_id`
- `questionnaire_version`
- `schema_version`
- `submitted_at`
- `payload_json`

`payload_json` enthält die fachliche Submission inklusive Antworten, aber keine IP, E-Mail, User-Agent oder Contact-Referenz.

## Tests

- vollständiges Mapping Domain → DB-Parameter.
- JSON round-trip ohne Bedeutungsverlust.
- Sonderzeichen/Unicode.
- DB-Fehlermapping.
- keine ungeplanten Felder/PII im Payload.

## Backstage

Entity: `component:default/questionnaire-repository`
Depends on:
- `component:default/questionnaire-data-model`
- `component:default/db-connector`
