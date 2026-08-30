# DB Connector – Specification

Status: DRAFT
Scope: dev3 questionnaire PoC; intended reusable backend component

## Zweck

`db-connector` kapselt den technischen relationalen Datenbankzugriff. Er stellt fachlich neutralisierte Persistenzprimitive bereit und verhindert, dass Business Logic oder Data Models direkt an PDO, konkrete SQL-Verbindungsdetails oder Runtime-Credentials gekoppelt werden.

## Verantwortung

Der Connector MUSS:

- Verbindungen aus externer Runtime-Konfiguration beziehen; keine Credentials im Repository.
- Prepared Statements für variable Werte verwenden.
- Transaktionen mit begin/commit/rollback bereitstellen.
- technische DB-Fehler in stabile NEROZON-Fehlertypen übersetzen.
- JSON-Werte deterministisch kodieren/dekodieren und Fehler dabei sichtbar machen.
- Zeichensatz/Connection-Modus explizit setzen.
- Connection-Lifecycle zentral verwalten.

Der Connector DARF NICHT:

- Questionnaire-Felder oder fachliche Regeln kennen.
- HTTP-Requests/Responses kennen.
- Tabellen dynamisch aus ungeprüften Requestwerten wählen.
- Schemaänderungen während normaler Runtime-Requests ausführen.
- Secrets loggen.

## Contract

Mindestens folgende technische Operationen müssen als stabile interne Schnittstelle verfügbar sein:

- `execute(statement, parameters): WriteResult`
- `fetchOne(statement, parameters): ?Row`
- `fetchAll(statement, parameters): Row[]`
- `transaction(callback): T`

Konkrete PHP-Signaturen dürfen idiomatisch umgesetzt werden, solange die fachlichen Konsumenten keine PDO-Objekte erhalten.

## Fehlerklassen

Mindestens unterscheidbar:

- `DatabaseUnavailable`
- `DatabaseConstraintViolation`
- `DatabaseQueryFailure`
- `DatabaseSerializationFailure`

Interne Treiberdetails dürfen geloggt werden, sofern keine Secrets oder fachlichen Payloads unnötig offengelegt werden. Öffentliche API-Fehler erhalten keine SQL-/Treiberinformationen.

## Konfiguration

Konkrete Variablennamen/Secret-Namen sind nicht Bestandteil dieser SPEC. Die Runtime-Konfiguration muss mindestens Host/Endpoint, Datenbankname, Benutzer, Secret und notwendige Connection-Optionen liefern können.

## Tests

Pflichtnachweise:

- Prepared-Statement-Nutzung gegen Injection-Versuche.
- erfolgreiche Read/Write-Operation.
- Rollback bei Fehler innerhalb einer Transaktion.
- Mapping mindestens eines Connection- und eines Constraint-Fehlers.
- JSON-Encoding/-Decoding inklusive ungültiger Daten.
- keine Credentials oder Payloads in für Clients sichtbaren Fehlermeldungen.

## Backstage

Entity: `component:default/db-connector`
Typ: `library`
Abhängigkeit: `resource:default/questionnaire-database` für diesen PoC; spätere Datenbankressourcen dürfen denselben Connector verwenden.
