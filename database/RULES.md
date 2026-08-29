# NEROZON Database – Local Rules

Status: DRAFT

Grundsätzlich gelten `/docs/DATA-MODEL.md`, `/docs/SECURITY.md`, `/docs/ARCHITECTURE.md` und `/docs/DEVELOPMENT.md`.
Diese Datei enthält nur zusätzliche Regeln für `/database`.

- `/database` enthält versionierte Datenbankschemata, Initialisierung und Migrationen, nicht produktive Daten oder Credentials.
- Produktive Datenbankzugänge und Secrets dürfen hier niemals gespeichert werden.
- Migrationen müssen reproduzierbar und in ihrer Reihenfolge eindeutig sein.
- Destruktive Änderungen müssen ausdrücklich als solche erkennbar sein und dürfen nicht automatisch durch normale Runtime-Requests ausgeführt werden.
- Datenbankartefakte dürfen keine fachliche Business Logic duplizieren.
