# nerozon.de Repository Architecture

Status: DRAFT

## Dokumentationsgrenze

Dieses Repository dokumentiert **was** die Software ist und **wie** sie gebaut und betrieben wird.
Fachliche Motivation, Markt-, Produkt- und Unternehmenskontext sowie das **warum** können in der NEROZON-Wissensbasis in Google Drive gepflegt werden.

NEROZON-weite Engineering-Regeln bleiben die übergeordnete technische Grundlinie. Dieses Repository enthält die produkt- und projektspezifische technische Wahrheit für `nerozon.de`.

## Source of Truth

- Das Repository ist die führende Quelle für den reproduzierbaren Anwendungsstand von `nerozon.de`.
- `main` repräsentiert den gewünschten produktiven Stand.
- Entwicklungsarbeit erfolgt auf dafür definierten Branches und wird kontrolliert in verbindliche Stände übernommen.
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

Die NEROZON-weiten Engineering-Regeln bilden die übergeordnete Basis.
Die Root-`RULES.md` definiert die projektweiten Meta-Regeln von `nerozon.de`.
`/docs/` enthält projektweite technische Dokumentation und Entscheidungen.
Lokale `RULES.md` und `*-RULES.md` liegen nah am betroffenen Bereich und enthalten ausschließlich bereichsspezifische Präzisierungen oder zusätzliche Einschränkungen.
Lokale Regeln dürfen höhere Regeln präzisieren, aber keine höhere MUST-Regel abschwächen.
Konkrete aufgabenspezifische Sollvorgaben werden als `SPEC.md` oder `*-SPEC.md` nahe am betroffenen Bereich abgelegt.
