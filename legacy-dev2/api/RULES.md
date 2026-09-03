# NEROZON API – Local Rules

Status: DRAFT

Grundsätzlich gelten die projektweiten Dokumente unter `/docs/`, insbesondere `ARCHITECTURE.md`, `SECURITY.md`, `DATA-MODEL.md` und `DEVELOPMENT.md`.
Diese Datei enthält nur zusätzliche Regeln für `/api`.

## API-Grenze

- `/api/public` bildet die öffentliche HTTP-Grenze. Nicht öffentlich benötigter Backend-Code gehört nicht in den öffentlich adressierbaren Bereich.
- Externe Requests werden an der API-Grenze validiert und in NEROZON-eigene fachliche Strukturen übersetzt.
- Framework-, HTTP- und Transportobjekte werden nicht in die Business Logic durchgereicht.

## Berechtigungen

- Data Models dürfen deklarieren, welche Rollen oder Capabilities Felder bzw. Operationen lesen oder schreiben dürfen.
- Authentifizierung und Bestimmung des vertrauenswürdigen Ausführungskontexts erfolgen außerhalb des Data Models.
- Rollen, Capabilities oder Berechtigungen werden niemals ungeprüft aus einem externen Request übernommen.
- Bei sensiblen Informationen darf Zugriffsschutz nicht ausschließlich durch nachträgliches Ausblenden bereits geladener Daten erfolgen.

## Frameworks

Frameworks dürfen Routing, HTTP, Dependency Injection, Logging, Validierung, Datenbankzugriff und Migrationstechnik bereitstellen.
Framework-spezifische Objekte werden an den Grenzen zur NEROZON-Schicht übersetzt.
