# Conrad Architecture Review – S092026 Questionnaire Backend

Status: REVIEWED
Branch: `dev3`
Reviewer: Conrad
Scope: S092026 Questionnaire Backend

## Ergebnis

`PASS WITH FINDINGS`

Die implementierte Dependency-Richtung entspricht im Kern der vorgegebenen Architektur. Business Logic und Data Model bleiben frei von HTTP-, PDO-, SQL- und konkreten Tabellenabhängigkeiten. Das questionnaire-spezifische Repository besitzt das Persistenzmapping und verwendet ausschließlich den generischen DB Connector. Die öffentliche Composition Root liegt unter `/api/public`, und Runtime-Secrets werden über die zentrale Config-/Secret-Grenze aus der nicht versionierten `/config/Secret.php` geladen.

Es wurde kein Architektur-BLOCKER gefunden.

## BLOCKER

Keine.

## FINDINGS

### F-001 – `api-http-connector` ist trotz Wiederverwendungsziel questionnaire-spezifisch

**Betroffene Architekturgrenze:** wiederverwendbare Infrastrukturkomponente ↔ fachliche Questionnaire-Komponente

`api-http-connector` ist in `COMPONENT-MAP-SPEC.md` und seiner eigenen SPEC als wiederverwendbare technische Komponente vorgesehen. Die aktuelle Implementierung `api/src/Http/HttpConnector.php` hängt jedoch direkt von `SubmitQuestionnaire` sowie mehreren Questionnaire-spezifischen Exceptiontypen ab und enthält die konkrete Route `/v1/questionnaire/submissions`.

Damit bleibt zwar die geforderte Richtung HTTP → Business Logic erhalten, die technische HTTP-Komponente trägt aber konkrete Questionnaire-Semantik und müsste für eine weitere Backend-Capability geändert werden. Das widerspricht dem Work-Order-Intent, dass generische Connectoren keine Questionnaire-Fachsemantik enthalten sollen.

**Empfehlung:** Die generischen HTTP-Aufgaben (Request-Größe, Content-Type/JSON, Response-/Fehlerformat, technische HTTP-Grenze) von der questionnaire-spezifischen Route-/Use-Case-Adaption trennen. Die konkrete Form kann klein bleiben; es ist keine Framework- oder Controller-Hierarchie erforderlich. Entscheidend ist, dass der wiederverwendbare Connector nicht direkt `SubmitQuestionnaire` oder Questionnaire-Exceptions kennen muss.

**Freigaberelevanz:** FINDING, kein BLOCKER. Die aktuelle Implementierung wahrt die zentrale Dependency-Richtung und trägt keine Infrastruktur in Business Logic/Data Model. Das Problem betrifft die zugesagte Wiederverwendbarkeit und Component Boundary.

## NOTES

### N-001 – DB Connector erfüllt die generische Grenze

`DatabaseConnector`/`PdoDatabaseConnector` stellen technische DB-Primitiven, Transaktionen, Prepared Statements, Fehlerabbildung und JSON-Serialisierung bereit, ohne Questionnaire-Felder oder Questionnaire-Regeln zu kennen. Das konkrete SQL und der Tabellenname liegen korrekt im `DatabaseQuestionnaireRepository`.

### N-002 – Business Logic / Data Model sind infrastrukturfrei

`SubmitQuestionnaire` hängt vom `QuestionnaireRepository`-Interface und vom Questionnaire Data Model ab. HTTP, PDO, SQL, konkrete Tabellen und Runtime-Credentials treten dort nicht auf. ID und UTC-Zeitpunkt werden serverseitig erzeugt.

### N-003 – Repository-Grenze ist passend gesetzt

`DatabaseQuestionnaireRepository` besitzt das Domain→Persistenz-Mapping, verwendet den `DatabaseConnector` und übersetzt technische DB-Fehler in `QuestionnairePersistenceFailed`. Es reicht keine PDO-/Connection-Objekte nach oben weiter.

### N-004 – Config-/Secret-Grenze ist für den PoC belastbar

`SecretConfig` lädt ausschließlich eine explizit angegebene Runtime-Datei und schlägt bei fehlender/ungültiger Konfiguration sicher fehl. `api/public/index.php` lädt `/config/Secret.php`; das Deployment paketiert `/api`, nicht `/config`, sodass die serverseitige Secret-Datei nicht als Repository-Artefakt ausgeliefert wird. Nicht geheime DB-Ressourcenwerte und das Secret liegen zur Runtime gemeinsam im `database`-Config-Block. Für diesen PoC ist aus den autoritativen Quellen keine MUST-Regel ableitbar, die dafür getrennte Dateien oder getrennte Config-Objekte verlangt; daher kein Finding.

### N-005 – Öffentliche API-Grenze

Der Runtime-Entry-Point liegt unter `/api/public`. Interne Exceptions, SQL-/PDO-Details und Secrets werden vom öffentlichen Response-Format abgeschirmt.

### N-006 – Bekannte Widersprüche

`CONTRADICTIONS.md` enthält aktuell keine offenen Widersprüche. Der in `DEVIN-EXECUTION-SPEC.md` genannte B-004 Ownership-Entity-Punkt betrifft einen späteren Backstage-Import und blockiert diese Architekturfreigabe nicht.

## Geprüfte Quellen

- `/WORK-ORDER.md`
- `/RULES.md`
- `/CONTRADICTIONS.md`
- `/docs/ARCHITECTURE.md`
- `/docs/SECURITY.md`
- `/api/RULES.md`
- `/database/RULES.md`
- `/api/components/COMPONENT-MAP-SPEC.md`
- `/api/questionnaire/DEVIN-EXECUTION-SPEC.md`
- `/api/questionnaire/BUSINESS-LOGIC-SPEC.md`
- `/api/questionnaire/DATA-MODEL-SPEC.md`
- `/api/questionnaire/REPOSITORY-SPEC.md`
- `/api/questionnaire/CONFORMANCE.md`
- `/api/shared/db-connector/SPEC.md`
- `/api/shared/http-connector/SPEC.md`
- `/database/QUESTIONNAIRE-SCHEMA-SPEC.md`
- `/database/migrations/001_INIT_questionnaire_submissions.sql`
- `/api/src/Questionnaire/SubmitQuestionnaire.php`
- `/api/src/Questionnaire/QuestionnaireModel.php`
- `/api/src/Questionnaire/Repository.php`
- `/api/src/Database/DatabaseConnector.php`
- `/api/src/Database/PdoDatabaseConnector.php`
- `/api/src/Http/HttpConnector.php`
- `/api/src/Config/SecretConfig.php`
- `/api/bootstrap.php`
- `/api/public/index.php`
- `/config/secret.example.php`
- `/.github/workflows/deploy.yml`

## Abschluss

Keine architekturrelevanten Abweichungen außer F-001 gefunden. Der Branch ist aus Conrads Architekturreview nicht blockiert; F-001 sollte im Implementierungsprozess korrigiert oder durch eine explizite Architekturentscheidung neu eingeordnet werden. Separate Review-Gates von Sina und Tessa bleiben erforderlich.