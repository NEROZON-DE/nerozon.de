# Questionnaire Backend – Devin Execution Specification

Status: DRAFT / NOT READY FOR GO
Scope: dev3 branch PoC

## Auftrag nach Freigabe

Devin implementiert den Backend-Pfad für die anonyme 20-Fragen-Submission entsprechend:

- `/RULES.md`
- `/docs/ARCHITECTURE.md`
- `/docs/DATA-MODEL.md`
- `/docs/SECURITY.md`
- `/docs/DEVELOPMENT.md`
- `/api/RULES.md`
- `/database/RULES.md`
- `/www/q/RULES.md`
- `/www/q/20-FRAGEN-SPEC.md`
- `/www/q/QUESTIONNAIRE-SPEC.md`
- den Component-SPECs unter `/api/components`, `/api/shared` und `/api/questionnaire`
- `/database/QUESTIONNAIRE-SCHEMA-SPEC.md`

Devin ändert keine RULES oder SPEC still, um eine Implementierung passend zu machen. Unauflösbare Konflikte werden als Blocker gemeldet.

## Erwartete Implementierungsartefakte

Mindestens:

- Runtime-Struktur unter `/api` mit öffentlichem Entry Point nur unter `/api/public`.
- `questionnaire-data-model` Implementierung.
- `questionnaire-business-logic` Implementierung.
- `questionnaire-repository` Implementierung.
- generischer `db-connector`.
- generischer `api-http-connector` / Routing-Grenze.
- INIT-Migration für `questionnaire_submissions`.
- Unit-Tests für Model, Business Logic, Repository-Mapping, DB Connector und HTTP-Mapping.
- Integrationstest HTTP → Business → Repository → Testdatenbank.
- dokumentierter Testaufruf/Fixture für leere, partielle und vollständige Submission.
- Conformance-Nachweis für die geltenden Regeln/SPECs vor PR in einen verbindlichen Stand.

## Abnahmekriterien

Der Build ist für diesen Scope technisch vollständig, wenn:

1. `POST /v1/questionnaire/submissions` eine leere, partielle und vollständige gültige Submission akzeptiert.
2. ungültige oder unbekannte Antworten serverseitig abgewiesen werden.
3. erfolgreiche Requests genau einen anonymen Primärdatensatz persistieren.
4. DB-/Runtime-Fehler keinen falschen Erfolg erzeugen.
5. Business Logic keine HTTP-/PDO-/SQL-Abhängigkeit besitzt.
6. der DB Connector keine Questionnaire-Fachsemantik besitzt.
7. der persistierte fachliche Payload keine IP, E-Mail, User-Agent, Nachricht oder Contact-Referenz enthält.
8. bestehende Submissionen eindeutig Questionnaire- und Schema-Version zugeordnet sind.
9. Tests die spezifizierten Fehler- und Trennungsfälle abdecken.
10. Conrad, Sina und Tessa ihre jeweiligen Review-Gates ohne offenen BLOCKER abschließen.

## BLOCKER vor `GO`

### B-001 Datenbank-Engine / Runtime-Verfügbarkeit

Für ausführbare Migration und Connector-Konfiguration muss feststehen, welche relationale Datenbank im IONOS-Setup tatsächlich verwendet wird (z. B. MySQL/MariaDB inklusive verfügbarer Version). Das logische Schema ist davon unabhängig beschrieben.

### B-002 Questionnaire-Version

Der freigegebene Fragenstand benötigt einen stabilen maschinenlesbaren `questionnaire_version` Wert. Dieser darf nicht bei jedem Deployment wechseln.

### B-003 Canonical answer values

Für alle Auswahloptionen müssen stabile maschinenlesbare Werte verbindlich festgelegt werden, damit Frontend, API und gespeicherte historische Daten dieselbe Semantik verwenden. Die sichtbaren Texte in `20-FRAGEN-SPEC.md` sind bereits fachlich freigegeben; die technischen Keys fehlen noch.

### B-004 Ownership-Entity

Die Backstage-YAML verwendet vorläufig `group:default/nerozon-engineering`. Vor echtem Backstage-Import muss geklärt werden, welcher vorhandene GitHub-/Backstage-Group-Name Eigentümer dieser Komponenten ist. Dies blockiert nicht den PHP-Build, aber den validen Katalogimport.

## NON-BLOCKER / Review-Entscheidungen

### N-001 PHP-Struktur

Devin darf Namespaces, Dateinamen und interne Klassenstruktur idiomatisch wählen, solange Component-Grenzen und Dependency-Richtung erhalten bleiben.

### N-002 Library-Auswahl

Kleine Infrastruktur-Libraries dürfen verwendet werden, wenn sie die NEROZON-Grenzen nicht in die Business Logic tragen. Neue große Framework-Bindungen sind Conrad zur Review vorzulegen.

### N-003 Öffentliche Submission-ID

Initial soll die API nur `{ "accepted": true }` zurückgeben. Eine Submission-ID ist für den Browser nicht erforderlich und soll wegen Q-042/Q-054 nicht unnötig als Brücke verfügbar gemacht werden.

### N-004 Kurztextlimit Fragen 10/17

Bis zu einer engeren Vorgabe gilt serverseitig ein defensives Maximum von 4.000 Zeichen analog Frage 20. Sina kann ein engeres Modell empfehlen; dies ist für den ersten Build kein Architekturblocker.

## Review-Aufträge

### Conrad

Prüfe Component Map und Implementierung auf:

- richtige Abhängigkeitsrichtung.
- keine Infrastrukturkopplung in Business Logic/Data Model.
- sinnvolle Trennung wiederverwendbar vs. questionnaire-spezifisch.
- keine unnötige neue Abstraktion.

### Sina

Prüfe:

- vollständiges Data Model gegenüber `20-FRAGEN-SPEC.md`.
- canonical values und Versionierung.
- Persistenzmapping/Indexfelder.
- Anonymität und Datentrennung.

### Tessa

Prüfe:

- Testabdeckung der Abnahmekriterien.
- negative/Fehlerpfade.
- Testbarkeit der Component-Grenzen.
- Integrationstest ohne Produktions-Secrets.

## Ready-for-Devin-Regel für diesen PoC

`GO` ist sinnvoll, sobald B-001 bis B-003 entschieden sind. B-004 muss spätestens vor Backstage-Import aufgelöst sein.
