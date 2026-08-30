# Questionnaire Data Model – Specification

Status: DRAFT
Scope: dev3 questionnaire PoC

## Zweck

`questionnaire-data-model` definiert die fachlichen Objekte der anonymen Questionnaire-Submission unabhängig von HTTP und physischer Datenbankstruktur.

## QuestionnaireSubmission

Pflichtfelder:

- `submission_id`: serverseitig erzeugte technische UUID/gleichwertiger nicht bedeutungstragender Identifier.
- `questionnaire_id`: stabiler fachlicher Bezeichner, initial `S092026`.
- `questionnaire_version`: Version des fachlichen Fragenstands, initial `20260830`.
- `schema_version`: Version dieses Payload-Schemas, initial `1`.
- `submitted_at`: serverseitiger UTC-Zeitpunkt.
- `answers`: Liste von `QuestionnaireAnswer`, darf leer sein.

Nicht Bestandteil des fachlichen Modells:

- E-Mail-Adresse
- Name
- persönliche Nachricht
- IP-Adresse
- User-Agent
- Contact-/Mail-Referenz
- Browser-Session-ID als fachlicher Identifier

## QuestionnaireAnswer

Felder:

- `question_id`: stabiler technischer Fragen-Identifier nach dem Schema `S092026Qxx`, z. B. `S092026Q02`.
- `type`: `single_choice`, `multi_choice`, `scale`, `short_text`.
- `value`: typabhängiger Wert; Auswahlantworten verwenden stabile Answer-IDs nach dem Schema `S092026QxxAyy`, z. B. `S092026Q02A03`.

Regeln:

- Eine Frage darf höchstens einmal im `answers`-Array vorkommen.
- Unbeantwortete Fragen werden weggelassen; sie sind kein Fehler.
- Ein vollständig leeres `answers`-Array ist zulässig.
- `single_choice`: genau eine erlaubte Answer-ID der jeweiligen Frage.
- `multi_choice`: Liste eindeutiger erlaubter Answer-IDs der jeweiligen Frage; leer wird wie unbeantwortet behandelt.
- `scale`: die erlaubten Skalenwerte werden ebenfalls versionsgebunden eindeutig der jeweiligen Frage zugeordnet; sofern als Auswahl modelliert, gilt dasselbe Answer-ID-Schema.
- `short_text`: String; Whitespace-only wird wie unbeantwortet behandelt und benötigt keine Answer-ID.
- Frage 20: maximal 4.000 Zeichen.
- Für Fragen 10 und 17 gilt im PoC ebenfalls maximal 4.000 Zeichen, solange kein engeres fachliches Limit spezifiziert ist.
- Unbekannte `question_id`, unbekannte Antworttypen und nicht erlaubte Answer-IDs werden abgewiesen.

## Canonical IDs

Für Questionnaire `S092026` gilt ein deterministisches technisches ID-Modell:

- Questionnaire: `S092026`
- Frage 1: `S092026Q01`
- Frage 2: `S092026Q02`
- Antwort 1 auf Frage 2: `S092026Q02A01`
- Antwort 3 auf Frage 2: `S092026Q02A03`

`Qxx` und `Ayy` sind zweistellig mit führender Null. Die Nummerierung folgt der verbindlichen Reihenfolge in `/www/q/20-FRAGEN-SPEC.md`.

Persistiert werden diese stabilen IDs, nicht ausschließlich die angezeigten deutschen Texte. Die sichtbaren Texte bleiben durch die bestehende SPEC autoritativ und sind versionsgebunden den IDs zugeordnet.

Freitextantworten besitzen keine künstliche `Axx`-ID; ihr `question_id` identifiziert die Frage und `value` enthält den Text.

Damit ist die technische Ableitung der Auswahlwerte festgelegt und nicht mehr Devin zur freien Benennung überlassen.

## Versionierung

- `questionnaire_id = S092026` identifiziert diesen Fragebogen fachlich stabil.
- `questionnaire_version = 20260830` identifiziert den am 30.08.2026 festgelegten Fragen-, Options- und Bedeutungsstand.
- `schema_version` identifiziert die technische Payload-Struktur.
- Bestehende gespeicherte Payloads dürfen durch spätere Änderungen nicht mehrdeutig werden.
- Eine Änderung nur der sichtbaren Darstellung ohne Bedeutungsänderung erzwingt nicht automatisch eine neue Schema-Version.
- Änderung von Fragebedeutung, erlaubten Werten oder Zuordnung erfordert eine neue `questionnaire_version`.

## Persistenzabbildung

Das Data Model liefert für das Repository:

- technische ID
- Questionnaire-ID/-Version
- Schema-Version
- Submitted-at
- vollständigen fachlichen JSON-Payload

Operative Indexfelder dürfen nur aufgenommen werden, wenn ein konkreter Zugriff dies benötigt. Für den initialen PoC sind keine einzelnen Antworten als DB-Spalten erforderlich.

## Tests / Sina Review

Sina prüft mindestens:

- vollständige Abbildung aller 20 Fragetypen/Optionen.
- korrekte deterministische Zuordnung `S092026QxxAyy` zur Reihenfolge der freigegebenen Fragen und Optionen.
- leere und partielle Submission.
- Typvalidierung.
- Dubletten von question_id.
- Versionsregeln.
- Trennung fachlicher Daten von technischer Telemetrie/Kontaktdaten.
- Reproduzierbarkeit der canonical IDs aus dem freigegebenen Fragenstand.
