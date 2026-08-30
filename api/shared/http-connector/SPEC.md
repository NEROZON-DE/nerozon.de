# HTTP API Connector – Specification

Status: DRAFT
Scope: dev3 questionnaire PoC; intended reusable backend component

## Zweck

`api-http-connector` bildet die öffentliche HTTP-Grenze von `/api/public`. Er übernimmt Transport, Routing und Übersetzung und hält HTTP-/Framework-Konzepte aus Business Logic und Data Models heraus.

## Verantwortung

Der Connector MUSS:

- nur explizit definierte Routen/Methoden annehmen.
- Request-Größe begrenzen.
- JSON strukturell lesen und ungültiges JSON ablehnen.
- externe Daten in interne Request-/Command-Strukturen übersetzen.
- Business-Ergebnisse in stabile HTTP-Responses übersetzen.
- technische Fehler in generische öffentliche Fehlerantworten übersetzen.
- Content-Type und HTTP-Status explizit setzen.
- Request-/Correlation-Kennung für technische Nachvollziehbarkeit unterstützen, ohne sie als fachliche Identität zu verwenden.

Der Connector DARF NICHT:

- fachliche Questionnaire-Regeln duplizieren.
- SQL/PDO direkt verwenden.
- externe Rollen, IDs oder Metadaten ungeprüft als vertrauenswürdig übernehmen.
- Stacktraces, SQL, Secrets oder interne Pfade an Clients ausgeben.

## Standard-Fehlerformat

```json
{
  "error": {
    "code": "invalid_request",
    "message": "Request could not be processed."
  }
}
```

`code` ist stabil und maschinenlesbar. `message` ist für öffentliche Clients geeignet und darf keine internen Details leaken.

## Questionnaire Route

Für diesen PoC stellt der Connector mindestens bereit:

- `POST /v1/questionnaire/submissions`

Der fachliche Contract steht in `/api/questionnaire/openapi.yaml` und den Questionnaire-SPECs.

## Tests

Pflichtnachweise:

- falsche HTTP-Methode wird abgewiesen.
- falscher Content-Type / ungültiges JSON wird abgewiesen.
- unbekannte Route wird abgewiesen.
- gültiger Request erreicht Business Logic ohne HTTP-Objekte weiterzureichen.
- Business-/Validation-/DB-Fehler werden korrekt auf HTTP gemappt.
- interne Exceptiondetails erscheinen nicht in Responses.

## Backstage

Entity: `component:default/api-http-connector`
Typ: `service`
Provides API: `api:default/questionnaire-api`
