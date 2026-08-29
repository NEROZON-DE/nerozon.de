# NEROZON Repository Architecture

Status: DRAFT

## Dokumentationsgrenze

Dieses Repository dokumentiert **was** die Software ist und **wie** sie gebaut und betrieben wird.
Fachliche Motivation, Markt-, Produkt- und Unternehmenskontext sowie das **warum** werden in der NEROZON-Wissensbasis in Google Drive gepflegt.

## Source of Truth

- Das Repository ist die führende Quelle für den reproduzierbaren Anwendungsstand.
- `main` repräsentiert den gewünschten produktiven Stand.
- Entwicklungsarbeit erfolgt auf separaten Branches und wird kontrolliert nach `main` übernommen.
- Funktionale Bereiche bleiben möglichst getrennt.
- Gemeinsame Komponenten entstehen nur bei tatsächlich gemeinsamer Verantwortung oder Wiederverwendung.

## Abhängigkeitsrichtung

Für Backend-Funktionalität gilt grundsätzlich:

Business Logic
→ NEROZON Data Model / fachliche Interfaces
→ Adapter / Persistenz
→ externe Infrastruktur

Business Logic arbeitet mit NEROZON-eigenen fachlichen Objekten und Begriffen und nicht direkt mit Datenbanktabellen, Framework-Models oder externen API-Ressourcen.
HTTP, Routing, Datenbank, Framework und Transportformate sind Infrastruktur.

## Abstraktion und Abhängigkeiten

Externe Frameworks und Libraries dürfen Infrastruktur bereitstellen, sollen aber nicht ungeprüft das fachliche Datenmodell oder Vokabular bestimmen.
Abstraktionsgrenzen werden dort eingesetzt, wo sie Austauschbarkeit, Testbarkeit, Zuverlässigkeit oder klare Verantwortlichkeiten verbessern.
Abstraktion ohne konkreten Nutzen ist zu vermeiden.

## Dokumentationshierarchie

`/docs/` enthält projektweite technische Dokumentation und Entscheidungen.
Lokale `RULES.md` liegen nah am Code und enthalten ausschließlich bereichsspezifische Ergänzungen oder Einschränkungen.
Lokale Regeln dürfen globale Regeln konkretisieren; bewusste Abweichungen müssen ausdrücklich dokumentiert werden.
