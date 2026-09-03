# NEROZON Data Model

Status: DRAFT

## Fachliche Modelle

Jedes fachliche Objekt besitzt ein explizites NEROZON Data Model. Es definiert mindestens fachliche Felder, Datentypen, Validierungs- und Konsistenzregeln, Payload-/Schema-Version, Persistenzabbildung und erforderliche operative Indexfelder.

Business Logic arbeitet gegen das aktuelle fachliche Objektmodell und nicht gegen historische Persistenzstrukturen.
Ältere versionierte Payloads bleiben lesbar oder besitzen einen definierten Migrationspfad.

## Persistenz

Die generische Persistenzschicht kennt keine fachliche Bedeutung einzelner Objekttypen.
Das jeweilige Data Model bestimmt Persistenzziel, Payload, Payload-Version, benötigte Indexwerte und das Mapping zum fachlichen Objekt.

Die Persistenzschicht standardisiert technische Funktionen wie Laden, Speichern, Suchen, Transaktionen, Prepared Statements, JSON-Encoding/-Decoding und Datenbankfehler.
Das relationale Schema muss nicht jedes fachliche Feld als eigene Spalte abbilden.
Operative Indexfelder werden nur bei konkretem Zugriffs-, Such- oder Laufzeitbedarf eingeführt und müssen, soweit sie Projektionen des Payloads sind, daraus reproduzierbar bleiben.

## Kontextdaten

Noch nicht ausreichend verstandene Informationen dürfen vorübergehend in einem generischen Context-/Attribute-/Annotation-Modell abgelegt werden. Solche Daten benötigen Namespace/Key, Objekt- oder Actor-Bezug, Version und strukturierten Wert.
Der Context Store ist kein dauerhafter Ersatz für fehlende fachliche Modelle.

## Migrationen

Physische Datenbankänderungen erfolgen explizit und versioniert.
Normale Runtime-Requests führen keine destruktiven Schemaänderungen aus.
Schemaänderungen werden in INIT, MIGRATION und gegebenenfalls CLEANUP/DROP getrennt.
Destruktive Migrationen benötigen bewusste administrative Ausführung.

## Reporting

Aufwendige Analyse- und Read-Model-Strukturen sollen aus Primärdaten reproduzierbar sein. Performanceoptimierung erfolgt bevorzugt aufgrund gemessener Anforderungen.
