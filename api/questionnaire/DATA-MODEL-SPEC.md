# Questionnaire Data Model – Specification

Status: DRAFT
Scope: dev3 questionnaire PoC

## Zweck

`questionnaire-data-model` definiert die fachlichen Objekte der anonymen Questionnaire-Submission unabhängig von HTTP und physischer Datenbankstruktur.

## QuestionnaireSubmission

Pflichtfelder:

- `submission_id`: serverseitig erzeugte technische UUID/gleichwertiger nicht bedeutungstragender Identifier.
- `questionnaire_id`: stabiler fachlicher Bezeichner, initial `20-fragen`.
- `questionnaire_version`: Version des fachlichen Fragenstands.
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

- `question_id`: Integer 1–20 für Version `20-fragen`.
- `type`: `single_choice`, `multi_choice`, `scale`, `short_text`.
- `value`: typabhängiger Wert.

Regeln:

- Eine Frage darf höchstens einmal im `answers`-Array vorkommen.
- Unbeantwortete Fragen werden weggelassen; sie sind kein Fehler.
- Ein vollständig leeres `answers`-Array ist zulässig.
- `single_choice`: genau ein erlaubter Optionswert.
- `multi_choice`: Liste eindeutiger erlaubter Optionswerte; leer wird wie unbeantwortet behandelt.
- `scale`: Integer 1–5.
- `short_text`: String; Whitespace-only wird wie unbeantwortet behandelt.
- Frage 20: maximal 4.000 Zeichen.
- Für Fragen 10 und 17 gilt im PoC ebenfalls maximal 4.000 Zeichen, solange kein engeres fachliches Limit spezifiziert ist.
- Unbekannte `question_id`, unbekannte Antworttypen und nicht erlaubte Optionswerte werden abgewiesen.

## Canonical Values

Persistiert werden stabile maschinenlesbare Optionswerte, nicht ausschließlich die angezeigten deutschen Texte. Die Zuordnung Text ↔ Wert wird versionsgebunden definiert. Beispiel: `q1_regular`, `q1_partial`, `q1_not_yet`, `q1_unknown`.

Devin darf die vollständigen canonical values aus `/www/q/20-FRAGEN-SPEC.md` deterministisch ableiten und in einer versionsgebundenen Definition ablegen. Die sichtbaren Texte bleiben durch die bestehende SPEC autoritativ.

## Versionierung

- `questionnaire_version` identifiziert Fragen, Optionen und Bedeutung.
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
- leere und partielle Submission.
- Typvalidierung.
- Dubletten von question_id.
- Versionsregeln.
- Trennung fachlicher Daten von technischer Telemetrie/Kontaktdaten.
- Reproduzierbarkeit der canonical values aus dem freigegebenen Fragenstand.
