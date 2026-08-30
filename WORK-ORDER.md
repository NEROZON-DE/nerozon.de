# WORK ORDER

Status: REVIEW
Executor: Conrad
Branch: dev3
Scope: S092026 Questionnaire Backend – Architecture Review

## Objective

Prüfe die auf `dev3` implementierte Questionnaire-Backend-Lösung auf Architekturkonformität. Dies ist ein Review-Auftrag, kein Implementierungsauftrag.

Bewerte insbesondere Component Boundaries, Dependency-Richtung, Trennung von Fachlichkeit und Infrastruktur, Wiederverwendbarkeit der Connectoren sowie Config-/Secret-Grenzen.

## Authoritative Sources

Ausgangspunkt ist die bestehende Implementierung auf `dev3` einschließlich der für den Questionnaire freigegebenen RULES und SPECs.

Besonders relevant:

- `/api/questionnaire/DEVIN-EXECUTION-SPEC.md`
- `/api/questionnaire/DATA-MODEL-SPEC.md`
- `/api/questionnaire/BUSINESS-LOGIC-SPEC.md`
- `/api/questionnaire/REPOSITORY-SPEC.md`
- `/api/shared/db-connector/SPEC.md`
- `/api/shared/http-connector/SPEC.md`
- `/api/components/COMPONENT-MAP-SPEC.md`
- `/database/QUESTIONNAIRE-SCHEMA-SPEC.md`
- alle übergeordneten und lokal einschlägigen `RULES.md`

Die tatsächliche Implementierung ist gegen diese Vorgaben zu prüfen. Allgemeine Best Practices dürfen zur Bewertung herangezogen werden, ersetzen aber keine NEROZON-Regel.

## Fixed Architecture Intent

Die vorgesehene Dependency-Richtung ist:

HTTP
→ API HTTP Connector
→ Questionnaire Business Logic
→ Questionnaire Data Model
→ Questionnaire Repository
→ DB Connector
→ Questionnaire Database

Business Logic darf nicht direkt von HTTP, PDO, SQL, konkreten Tabellen oder externen Infrastrukturdetails abhängen.

Generische Connectoren dürfen keine Questionnaire-Fachsemantik enthalten.

## Review Scope

Conrad prüft mindestens:

- Component Boundaries und Dependency-Richtung
- Business Logic vs. Data Model vs. Repository vs. Infrastruktur
- Wiederverwendbarkeit von DB- und HTTP-Connector
- Persistenzkopplung und konkrete DB-Abhängigkeiten
- Config-/Secret-Grenzen und Verantwortlichkeiten
- ob Secrets, technische Resource-Konfiguration und fachliche Konfiguration sauber getrennt sind
- API-Grenzen und öffentlich erreichbare Komponenten
- versteckte Framework-/PDO-/HTTP-Kopplungen
- unnötige oder fehlende Abstraktionen
- Übereinstimmung zwischen SPECs und Implementierung
- neue stille Architekturentscheidungen, die nicht aus den zulässigen Quellen folgen

## Review Rules

Conrad implementiert oder refaktoriert im Rahmen dieses Work Orders nicht selbst.

Bestehende RULES oder SPECs werden nicht still verändert, um die Implementierung passend zu machen.

Nicht eindeutig spezifizierte notwendige Entscheidungen werden als UNKNOWN sichtbar gemacht. Ein UNKNOWN ist nur dann BLOCKER, wenn ohne seine Klärung keine belastbare Architekturfreigabe möglich ist.

Findings werden klassifiziert als:

- `BLOCKER` – verhindert Architekturfreigabe
- `FINDING` – sollte korrigiert werden, verhindert aber nicht zwingend die weitere Arbeit
- `NOTE` – Hinweis oder Verbesserung ohne Freigaberelevanz

## Required Output

Conrad dokumentiert das Review nachvollziehbar im Repository, ohne produktiven Code zu verändern.

Der Review-Abschluss enthält mindestens:

- Ergebnis: `PASS`, `PASS WITH FINDINGS` oder `BLOCKED`
- BLOCKER
- FINDINGS
- NOTES
- geprüfte Quellen bzw. relevante Implementierungsbereiche
- bei jedem Finding eine kurze Begründung und die betroffene Architekturgrenze

Wenn keine architekturrelevanten Abweichungen gefunden werden, ist dies ausdrücklich festzuhalten.

## Completion

Dieser Conrad-Work-Order ist abgeschlossen, wenn die Architekturreview vollständig dokumentiert ist.

Bei `BLOCKED` oder offenen Findings wird keine eigenständige Implementierung vorgenommen. Die Ergebnisse gehen zurück an den Implementierungsprozess bzw. an Rainer zur Entscheidung.

Nach Conrads Review bleiben die separaten Review-Gates von Sina und Tessa erforderlich.

## Instruction

Bei `GO auf dev3` diesen Work Order unmittelbar ausführen. Nicht nach Auftrag, Ziel oder Scope fragen. `WORK-ORDER.md` ist der branch-lokale Einstiegspunkt und dieser Work Order ist bereits im Zustand `REVIEW` für Conrad freigegeben.
