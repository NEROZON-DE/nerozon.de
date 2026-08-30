# Questionnaire Backend – Devin Execution Specification

Status: READY FOR DEVIN GO
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

## Feste Questionnaire-Identität und Canonical IDs

- `questionnaire_id = S092026`
- `questionnaire_version = 20260830`
- `schema_version = 1`
- Fragen: `S092026Qxx`, z. B. `S092026Q02`
- Auswahlantworten: `S092026QxxAyy`, z. B. `S092026Q02A03`

`Qxx` und `Ayy` sind zweistellig mit führender Null und folgen der verbindlichen Reihenfolge in `/www/q/20-FRAGEN-SPEC.md`. Freitextantworten erhalten keine künstliche `Axx`-ID.

Diese Werte werden nicht deploymentabhängig verändert.

## Runtime / Secrets

Die produktive Datenbank ist IONOS MariaDB 11.8. Die nicht geheimen Verbindungsparameter stehen in `/database/QUESTIONNAIRE-SCHEMA-SPEC.md`.

Devin MUSS für die Runtime-Konfiguration eine versionierte Beispiel-/Contract-Datei `secret.example.php` vorsehen, die ausschließlich die erwartete Struktur und sichere Platzhalter enthält.

Der echte Secret-Stand wird von Rainer manuell per SFTP als `Secret.php` unter `dev3.nerozon.de/config` angelegt. Die realen Zugangsdaten stammen aus seinem Vault.

Verbindlich:

- `Secret.php` wird niemals in GitHub erzeugt, committed oder als Build-/Deployment-Artefakt aus dem Repository bereitgestellt.
- `secret.example.php` enthält niemals echte Credentials.
- Runtime-Code liest DB-Credentials ausschließlich über die definierte Config-/Secret-Grenze.
- Fehlt `Secret.php` oder ein erforderlicher Wert, muss die Anwendung klar und sicher fehlschlagen; es gibt keinen Fallback auf Beispielwerte, Default-Credentials oder hardcodierte Zugangsdaten.
- Secrets dürfen nicht in Logs, Exceptions oder HTTP-Responses erscheinen.
- Deployments dürfen eine vorhandene serverseitige `Secret.php` nicht überschreiben oder löschen.
- Tests verwenden eigene Testkonfiguration/Fixtures und niemals Production-Secrets.

Devin dokumentiert nach Implementierung ausschließlich, welche Keys Rainer in `Secret.php` eintragen muss; die Werte selbst werden nicht angefordert oder dokumentiert.

## Erwartete Implementierungsartefakte

Mindestens:

- Runtime-Struktur unter `/api` mit öffentlichem Entry Point nur unter `/api/public`.
- `questionnaire-data-model` Implementierung.
- `questionnaire-business-logic` Implementierung.
- `questionnaire-repository` Implementierung.
- generischer `db-connector`.
- generischer `api-http-connector` / Routing-Grenze.
- versionierte `secret.example.php` und Config-/Secret-Loader gemäß obigem Contract.
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
11. fehlende/ungültige Runtime-Secrets führen zu sicherem Fehler ohne Credential-Leak und ohne Fallback.
12. Repository/Deployment enthält zu keinem Zeitpunkt echte DB-Credentials.
13. Fragen- und Auswahlantwort-IDs entsprechen deterministisch dem `S092026QxxAyy`-Modell.

## BLOCKER vor `GO`

Keine fachlich-technischen Build-Blocker offen.

### B-004 Ownership-Entity

Die Backstage-YAML verwendet vorläufig `group:default/nerozon-engineering`. Vor echtem Backstage-Import muss geklärt werden, welcher vorhandene GitHub-/Backstage-Group-Name Eigentümer dieser Komponenten ist. Dies blockiert weder PHP-Build noch Devin-GO.

## Erledigte ehemalige Blocker

### B-001 Datenbank-Engine / Runtime-Verfügbarkeit — RESOLVED

Festgelegt und in `/database/QUESTIONNAIRE-SCHEMA-SPEC.md` dokumentiert: IONOS Webhosting Pro, MariaDB 11.8, Port 3306, konkrete nicht geheime DB-Verbindungsparameter; Production-Secret ausschließlich serverseitig/Vault, niemals GitHub.

### B-002 Questionnaire-Version — RESOLVED

Festgelegt: `questionnaire_id = S092026`, `questionnaire_version = 20260830`.

### B-003 Canonical answer values — RESOLVED

Festgelegt ist das deterministische ID-Modell `S092026QxxAyy`. Fragen verwenden `S092026Qxx`; Auswahlantworten verwenden `S092026QxxAyy`. Die Nummerierung folgt der verbindlichen Reihenfolge der Fragen und Optionen in `/www/q/20-FRAGEN-SPEC.md`.

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

Prüfe Component Map und Implementierung auf richtige Abhängigkeitsrichtung, keine Infrastrukturkopplung in Business Logic/Data Model, sinnvolle Trennung wiederverwendbar vs. questionnaire-spezifisch, keine unnötige neue Abstraktion und saubere Secret-/Config-Grenze.

### Sina

Prüfe vollständiges Data Model gegenüber `20-FRAGEN-SPEC.md`, deterministische `S092026QxxAyy`-Zuordnung, Versionierung, Persistenzmapping/Indexfelder, Anonymität und Datentrennung.

### Tessa

Prüfe Testabdeckung der Abnahmekriterien, negative/Fehlerpfade, Testbarkeit der Component-Grenzen, Integrationstest ohne Produktions-Secrets sowie Missing-/Invalid-Secret-Verhalten.

## Ready-for-Devin-Regel für diesen PoC

Die fachlich-technischen Voraussetzungen für Devin sind erfüllt. B-004 ist ausschließlich vor einem echten Backstage-Import aufzulösen.
