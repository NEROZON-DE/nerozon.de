# NEROZON Backend Component Map – Questionnaire PoC

Status: DRAFT
Scope: dev3 branch PoC

## Ziel

Dieser Stand zerlegt die Capability `questionnaire-submission` erstmals in wiederverwendbare technische Komponenten. Der 20-Fragen-Fragebogen ist Referenzimplementierung für spätere Backend-Funktionen. Eine spätere fachliche Erweiterung (z. B. 25 Fragen) soll keine erneute Grundsatzentscheidung zu HTTP, Persistenz oder Datenbankzugriff benötigen, solange keine neue technische Anforderung entsteht.

## Komponenten

| Entity | Backstage kind | Verantwortung | Wiederverwendbar | Build |
| --- | --- | --- | --- | --- |
| `api-http-connector` | Component/service | Öffentliche HTTP-Grenze, Routing, Request/Response-Übersetzung | ja | Devin |
| `questionnaire-business-logic` | Component/library | fachlicher Use Case Submission | fachlich erweiterbar | Devin |
| `questionnaire-data-model` | Component/library | fachliche Objekte, Typen, Validierung, Versionierung | questionnaire-spezifisch | Devin |
| `questionnaire-repository` | Component/library | Mapping Domain ↔ Persistenz | questionnaire-spezifisch | Devin |
| `db-connector` | Component/library | generischer DB-Zugriff, Transaktionen, Prepared Statements, Fehlerabbildung | ja | Devin |
| `questionnaire-database` | Resource/database | physische Persistenz der anonymen Primärdaten | Infrastruktur | Devin |
| `questionnaire-api` | API/openapi | öffentlicher Contract für Submission | fachlich erweiterbar | Devin |

## Abhängigkeitsrichtung

`questionnaire-api` wird von `api-http-connector` bereitgestellt.

`api-http-connector`
→ `questionnaire-business-logic`
→ `questionnaire-data-model`
→ `questionnaire-repository`
→ `db-connector`
→ `questionnaire-database`

Die Business Logic kennt keine HTTP-, PDO-, SQL- oder Tabellenobjekte. Das Data Model kennt keine HTTP- oder DB-Verbindungsdetails. Der DB Connector kennt keine Questionnaire-Fachsemantik.

## Nicht Teil dieses Build-Auftrags

- optionales Kontaktformular und Mailversand
- dessen Abuse-Session/Token/Rate-Limit
- Reporting-/Analysemodelle
- Backstage-Installation
- Produktions-Credentials oder Runtime-Secrets

Diese Trennung ist bewusst: der optionale Kontaktvorgang muss gemäß `/www/q/QUESTIONNAIRE-SPEC.md` unabhängig von der anonymen Submission bleiben.

## Build-Reihenfolge

1. `questionnaire-data-model`
2. `db-connector`
3. `questionnaire-database` Schema/INIT
4. `questionnaire-repository`
5. `questionnaire-business-logic`
6. `api-http-connector`
7. `questionnaire-api` integrieren
8. Unit-/Integrationstests und Conformance-Prüfung

## Review-Gates

- Conrad: Architekturgrenzen, Dependency-Richtung, Wiederverwendbarkeit, Component Graph.
- Sina: Data Model, Contracts, Validierung, Versionierung, Persistenzabbildung und Indexfelder.
- Tessa: Testbarkeit, Unit-/Integrationstests, Fehlerfälle, Akzeptanzkriterien.
- Devin: Implementierung und technische Nachweise; keine stillen SPEC-/RULES-Änderungen.

## Statusmodell dieses PoC

- `DRAFT`: beschrieben, aber noch nicht freigegeben.
- `READY`: keine für die Implementierung blockierende fachliche/technische Frage offen.
- `IMPLEMENTED`: Code und Migration vorhanden.
- `REVIEWED`: zuständige Reviews bestanden.
- `DONE`: integriert, getestet und Conformance erfüllt.

Das Statusmodell ist vorläufig und darf nicht als NEROZON-weites Statusmodell interpretiert werden.

## Offene Punkte

Siehe `DEVIN-EXECUTION-SPEC.md`. Offene Entscheidungen werden dort ausdrücklich als BLOCKER oder NON-BLOCKER klassifiziert.
