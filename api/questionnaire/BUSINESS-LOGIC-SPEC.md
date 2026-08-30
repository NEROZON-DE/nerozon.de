# Questionnaire Business Logic – Specification

Status: DRAFT
Scope: dev3 questionnaire PoC

## Zweck

`questionnaire-business-logic` implementiert den fachlichen Use Case `SubmitQuestionnaire` unabhängig von HTTP, SQL und konkreter Persistenztechnik.

## Input

Ein interner `SubmitQuestionnaireCommand` mit:

- `questionnaire_id`
- `questionnaire_version`
- `answers`

Nicht vertrauenswürdige technische Client-Metadaten sind kein fachlicher Bestandteil des Commands.

## Ablauf

1. Prüfen, dass Questionnaire-ID und -Version unterstützt werden.
2. Eingehende Antworten über `questionnaire-data-model` normalisieren und validieren.
3. Server-seitig `submission_id` erzeugen.
4. Server-seitig `submitted_at` setzen.
5. `QuestionnaireSubmission` erzeugen.
6. Über `questionnaire-repository` atomar speichern.
7. Erfolgsresultat ohne Kontakt- oder Analyseverknüpfung zurückgeben.

## Fachliche Regeln

- Keine Frage ist Pflicht.
- Leere Submission ist zulässig.
- Unbekannte oder ungültige Antworten führen zur Ablehnung des Requests; gültige Teilmengen werden bei strukturell ungültigem Request nicht still gespeichert.
- Die Business Logic speichert keine E-Mail, persönliche Nachricht, IP oder User-Agent im fachlichen Payload.
- Sie erzeugt keine Referenz, die später vom optionalen Kontaktformular übernommen werden darf.
- Reporting/Auswertung ist kein synchroner Bestandteil dieses Use Cases.

## Resultat

Erfolg:

- `accepted: true`
- optional interne `submission_id` für technische Verarbeitung/Logging; der öffentliche HTTP-Contract muss sie nicht an den Browser zurückgeben.

Fehler mindestens unterscheidbar:

- `UnsupportedQuestionnaire`
- `UnsupportedQuestionnaireVersion`
- `InvalidQuestionnaireSubmission`
- `QuestionnairePersistenceFailed`

## Transaktionsgrenze

Eine Submission ist genau ein Primärdatensatz. Die Persistierung erfolgt vollständig oder gar nicht.

## Tests

- leere Submission erfolgreich.
- partielle Submission erfolgreich.
- vollständige Submission erfolgreich.
- ungültiger Wert wird vollständig abgewiesen.
- unbekannte Frage wird abgewiesen.
- Repository-Fehler liefert keinen falschen Erfolg.
- generierte IDs/Zeitpunkte stammen nicht aus dem Client.
- keine Contact-/PII-Daten werden in das Domainobjekt übernommen.

## Backstage

Entity: `component:default/questionnaire-business-logic`
Depends on:
- `component:default/questionnaire-data-model`
- `component:default/questionnaire-repository`
