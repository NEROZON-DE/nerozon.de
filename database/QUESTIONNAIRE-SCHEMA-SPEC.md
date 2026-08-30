# Questionnaire Database Schema – Specification

Status: DRAFT
Scope: dev3 questionnaire PoC

## Ziel

Die Datenbank speichert anonyme Questionnaire-Primärdaten versioniert und reproduzierbar. Fachliche Antworten liegen primär als JSON-Payload vor; nur konkret benötigte operative Felder werden relational geführt.

## Initiales Schema

Tabelle: `questionnaire_submissions`

| Spalte | Typische Anforderung | Regel |
| --- | --- | --- |
| `submission_id` | UUID/CHAR äquivalent | Primary Key, serverseitig erzeugt |
| `questionnaire_id` | kurzer String | NOT NULL |
| `questionnaire_version` | kurzer String | NOT NULL |
| `schema_version` | Integer | NOT NULL |
| `submitted_at` | UTC Timestamp | NOT NULL |
| `payload_json` | JSON/Text mit validem JSON | NOT NULL |

Index initial:

- Primary Key auf `submission_id`.
- Index auf `(questionnaire_id, questionnaire_version, submitted_at)` für versionsbezogene spätere Auswertung.

Keine einzelnen Fragen/Antworten als Spalten im initialen Schema.
Keine E-Mail, Nachricht, IP-Adresse, User-Agent oder Contact-ID in dieser Tabelle.

## Migrationen

Devin erstellt reproduzierbare Dateien unter `/database` mit eindeutiger Reihenfolge:

- INIT für erstmalige Erstellung.
- spätere additive/ändernde Schritte als MIGRATION.
- CLEANUP/DROP separat und niemals im normalen Runtime-Request.

Die konkrete SQL-Dialektwahl richtet sich nach der tatsächlich verfügbaren IONOS-Datenbank und ist derzeit ein BLOCKER für ausführbaren SQL-Code, nicht für das logische Schema.

## Constraints

- `schema_version > 0`.
- leeres fachliches `answers`-Array bleibt gültiger Payload.
- DB-seitige Constraints dürfen die Business-Validierung ergänzen, aber keine Questionnaire-Fachlogik duplizieren.

## Datenschutz/Trennung

Questionnaire-Primärdaten und technische Betriebs-/Abuse-Daten müssen logisch getrennt bleiben. Eine spätere technische Logging-/Telemetry-Struktur erhält keine fachliche Contact-Verknüpfung.

## Tests

- INIT auf leerer DB ausführbar.
- erneute kontrollierte Ausführung erzeugt keinen unbemerkten inkonsistenten Stand.
- Insert einer gültigen leeren und vollständigen Submission.
- Constraint-/Duplicate-ID-Verhalten definiert.
- gespeicherter JSON-Payload bleibt unverändert lesbar.
