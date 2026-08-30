# Questionnaire Database Schema – Specification

Status: DRAFT
Scope: dev3 questionnaire PoC

## Ziel

Die Datenbank speichert anonyme Questionnaire-Primärdaten versioniert und reproduzierbar. Fachliche Antworten liegen primär als JSON-Payload vor; nur konkret benötigte operative Felder werden relational geführt.

## Runtime / Datenbankplattform

Für `nerozon.de` ist die produktive relationale Datenbank festgelegt:

- Provider: IONOS Webhosting Pro
- Engine: MariaDB 11.8
- Host: `db5021309120.hosting-data.io`
- Port: `3306`
- Datenbankname: `dbs16070822`
- Benutzername: `dbu4674652`

Das Passwort/Secret wird nicht im Repository dokumentiert und ausschließlich über geschützte Runtime-Konfiguration bereitgestellt.

Diese Werte beschreiben die aktuelle Produktionsressource. Fachliche Komponenten dürfen nicht direkt von diesen konkreten Verbindungswerten abhängen; der Zugriff erfolgt über `db-connector`.

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

Devin erstellt reproduzierbare MariaDB-11.8-kompatible Dateien unter `/database` mit eindeutiger Reihenfolge:

- INIT für erstmalige Erstellung.
- spätere additive/ändernde Schritte als MIGRATION.
- CLEANUP/DROP separat und niemals im normalen Runtime-Request.

Der ausführbare SQL-Dialekt ist MariaDB 11.8. MariaDB-spezifische Features dürfen verwendet werden, wenn sie einen konkreten Nutzen haben; unnötige Plattformkopplung ist zu vermeiden.

## Constraints

- `schema_version > 0`.
- leeres fachliches `answers`-Array bleibt gültiger Payload.
- DB-seitige Constraints dürfen die Business-Validierung ergänzen, aber keine Questionnaire-Fachlogik duplizieren.

## Datenschutz/Trennung

Questionnaire-Primärdaten und technische Betriebs-/Abuse-Daten müssen logisch getrennt bleiben. Eine spätere technische Logging-/Telemetry-Struktur erhält keine fachliche Contact-Verknüpfung.

## Tests

- INIT auf leerer MariaDB-11.8-kompatibler DB ausführbar.
- erneute kontrollierte Ausführung erzeugt keinen unbemerkten inkonsistenten Stand.
- Insert einer gültigen leeren und vollständigen Submission.
- Constraint-/Duplicate-ID-Verhalten definiert.
- gespeicherter JSON-Payload bleibt unverändert lesbar.
