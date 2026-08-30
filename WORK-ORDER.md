# WORK ORDER

Status: READY
Executor: Devin
Branch: dev3
Scope: S092026 Questionnaire Backend

## Objective

Implementiere auf `dev3` den vollständigen Backend-Pfad für den anonymen Questionnaire `S092026` gemäß den bereits freigegebenen technischen Spezifikationen.

Dieser Work Order ist der branch-lokale Einstiegspunkt für die Ausführung. Er ersetzt keine RULES oder SPECs, sondern aktiviert den darin beschriebenen Arbeitsumfang.

## Authoritative Execution Spec

- `/api/questionnaire/DEVIN-EXECUTION-SPEC.md`

Devin liest vor Implementierungsbeginn die dort aufgeführten übergeordneten RULES, Architektur-/Datenmodell-Dokumente, Component-SPECs, Questionnaire-SPECs und Database-SPECs.

## Fixed Identity

- `questionnaire_id = S092026`
- `questionnaire_version = 20260830`
- `schema_version = 1`
- Fragen-ID: `S092026Qxx`
- Auswahlantwort-ID: `S092026QxxAyy`

## Required Build Scope

Devin baut mindestens:

- Questionnaire Data Model
- Questionnaire Business Logic
- Questionnaire Repository
- generischen DB Connector
- generische HTTP/API-Grenze
- Config-/Secret-Loader plus versionierte `secret.example.php`
- MariaDB-11.8-kompatible INIT-Migration
- Unit- und Integrationstests gemäß Execution Spec
- erforderliche Implementierungs-/Betriebsdokumentation
- Conformance-Nachweis für den implementierten Stand

## Runtime Secret Boundary

Produktive Secrets werden NICHT von Devin angelegt oder angefordert. Rainer legt die echte `Secret.php` nach Bereitstellung der erwarteten Struktur manuell per SFTP unter `dev3.nerozon.de/config` an. GitHub sieht diese Werte niemals.

## Required Reviews

Nach Implementierung müssen die in der Execution Spec definierten Review-Gates durchgeführt werden:

- Conrad – Architektur / Component Boundaries
- Sina – Data Model / Persistenz / Canonical IDs
- Tessa – Tests / Fehlerpfade / Integration

Ein Review-BLOCKER setzt den Work Order auf `BLOCKED` bzw. führt zurück in Implementierung. `DONE` ist erst zulässig, wenn alle erforderlichen Reviews ohne offenen BLOCKER abgeschlossen sind.

## Current Blocking Questions

Keine Build-Blocker offen.

Backstage Ownership (`B-004`) ist noch offen, blockiert diesen Build aber nicht.

## State Model

Erlaubte Zustände dieses Work Orders:

- `DRAFT`
- `READY`
- `IN_PROGRESS`
- `REVIEW`
- `BLOCKED`
- `DONE`

Devin setzt beim tatsächlichen Start `Status: IN_PROGRESS`. Nach abgeschlossener Implementierung und eigenen Tests setzt Devin `Status: REVIEW` und dokumentiert die für Conrad, Sina und Tessa prüfbaren Ergebnisse. `DONE` erst nach erfolgreichen Review-Gates.

## Completion

Der Work Order ist abgeschlossen, wenn:

1. der Scope der Execution Spec implementiert ist,
2. alle dortigen Abnahmekriterien erfüllt sind,
3. Tests erfolgreich sind,
4. keine echten Secrets im Repository/Deployment-Artefakt enthalten sind,
5. Conrad, Sina und Tessa ihre Review-Gates ohne offenen BLOCKER abgeschlossen haben,
6. der Conformance-Nachweis den finalen Stand dokumentiert.

## Instruction

Der Arbeitsauftrag ist freigegeben und ausführbar. Bei `GO` auf `dev3` diesen Work Order ausführen. Keine erneute Architekturentscheidung treffen, sofern die bestehenden SPECs den Fall eindeutig regeln. Neue echte Widersprüche oder nicht spezifizierte Entscheidungen als Blocker sichtbar machen, statt Annahmen still in die Implementierung einzubauen.
