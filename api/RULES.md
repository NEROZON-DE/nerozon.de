# NEROZON API Rules

Status: DRAFT

Zusätzlich gelten `/RULES.md`.

## Schichten und Verantwortlichkeiten

API-001
Die fachliche Abhängigkeitsrichtung lautet grundsätzlich:

Business Logic
→ NEROZON Data Model / fachliche Interfaces
→ Adapter / Persistenz
→ externe Infrastruktur

API-002
Business Logic arbeitet mit NEROZON-eigenen fachlichen Objekten und Begriffen.

API-003
Business Logic darf nicht direkt von Datenbanktabellen, ORM-Entities, Framework-Models oder externen API-Ressourcen abhängig sein.

API-004
Framework- und Infrastrukturcode darf fachliche Objekte verwenden oder übersetzen, aber nicht deren fachliche Definition bestimmen.

API-005
HTTP, Routing, Datenbank, Framework und Transportformat sind Infrastruktur und dürfen nicht mit der fachlichen Business Logic gleichgesetzt werden.

## Datenmodell

API-010
Jedes fachliche Objekt besitzt ein explizites NEROZON Data Model.

API-011
Das Data Model definiert mindestens:
- fachliche Felder
- Datentypen
- Validierungsregeln
- Konsistenzregeln
- Payload-/Schema-Version
- Persistenzabbildung
- operative Indexfelder, soweit vorhanden

API-012
Die Business Logic arbeitet gegen das aktuelle fachliche Objektmodell und nicht gegen historische Persistenzstrukturen.

API-013
Änderungen der Persistenzdarstellung sollen durch das Data Model bzw. Mapping gegenüber bestehender Business Logic soweit sinnvoll transparent bleiben.

API-014
Versionierte ältere Payloads müssen entweder lesbar bleiben oder über einen definierten Migrationspfad verfügen.

API-015
Gemeinsame elementare Prüfungen und Normalisierungen werden zentral bereitgestellt.
Sie dürfen nicht unnötig in einzelnen Data Models dupliziert werden.

API-016
Gemeinsame Hilfsfunktionen müssen fachlich gruppiert bleiben.
Eine unstrukturierte globale Utility-Sammlung ist zu vermeiden.

## Persistenz

API-020
Die generische Persistenzschicht kennt keine fachliche Bedeutung von Objekten wie Customer oder Questionnaire.

API-021
Das jeweilige Data Model bestimmt:
- Persistenzziel
- Payload
- Payload-Version
- benötigte Indexwerte
- Mapping zwischen Persistenz und fachlichem Objekt

API-022
Die Persistenzschicht standardisiert technische Funktionen wie Laden, Speichern, Suchen, Transaktionen, Prepared Statements, JSON-Encoding/-Decoding und Datenbankfehler.

API-023
Das relationale Schema muss nicht jedes fachliche Feld als eigene Spalte abbilden.

API-024
Operative Indexfelder werden nur eingeführt, wenn ein konkreter Zugriffs-, Such- oder Laufzeitbedarf besteht.

API-025
Indexfelder, die lediglich Projektionen des primären Payloads darstellen, müssen daraus reproduzierbar sein.

API-026
Aufwendige Auswertungsstrukturen sollen als separate Projection-/Read-Models aufgebaut werden können und aus Primärdaten reproduzierbar sein.

API-027
Performanceoptimierung erfolgt bevorzugt aufgrund gemessener Anforderungen und nicht vorsorglich.

## Context und experimentelle Daten

API-030
Noch nicht ausreichend verstandene Informationen dürfen vorübergehend in einem generischen Context-/Attribute-/Annotation-Modell abgelegt werden.

API-031
Generische Kontextdaten benötigen mindestens einen definierten Namespace/Key, Objekt- oder Actor-Bezug, Version und strukturierten Wert.

API-032
Der generische Context Store darf nicht zur dauerhaften Ersatzstruktur für fehlende fachliche Modelle werden.

## Berechtigungen

API-040
Data Models dürfen deklarieren, welche Rollen oder Capabilities Felder bzw. Operationen lesen oder schreiben dürfen.

API-041
Authentifizierung und Bestimmung des vertrauenswürdigen Ausführungskontexts erfolgen außerhalb des Data Models.

API-042
Rollen, Capabilities oder Berechtigungen dürfen niemals ungeprüft aus einem externen Request übernommen werden.

API-043
Bei sensiblen Informationen darf Zugriffsschutz nicht ausschließlich durch nachträgliches Ausblenden bereits geladener Daten erfolgen.

## Migrationen

API-050
Physische Datenbankänderungen werden explizit und versioniert durchgeführt.

API-051
Normale Runtime-Requests dürfen keine destruktiven Schemaänderungen durchführen.

API-052
Schemaänderungen werden fachlich in INIT, MIGRATION und gegebenenfalls CLEANUP/DROP getrennt.

API-053
Destruktive Migrationen benötigen eine bewusste administrative Ausführung.

## Frameworks

API-060
Frameworks dürfen insbesondere Routing, HTTP, Dependency Injection, Logging, Validierung, Datenbankzugriff und Migrationstechnik bereitstellen.

API-061
Ein Frameworkwechsel darf nicht erfordern, die fachliche Business Logic neu zu modellieren.

API-062
Framework-spezifische Objekte werden an den Grenzen zur NEROZON-Schicht übersetzt.
