# NEROZON Development

Status: DRAFT

## Grundsätze

- Änderungen werden nachvollziehbar versioniert.
- Neue Entwicklungsarbeit beginnt grundsätzlich auf Basis des aktuellen produktiven Stands, sofern für einen Arbeitsstrang kein anderer Branch ausdrücklich festgelegt wurde.
- KI-Worker entwickeln auf separaten Branches und liefern Änderungen kontrolliert zur Übernahme nach `main`.
- Vor Änderungen sind `/docs/` sowie die für den betroffenen Pfad geltenden lokalen `RULES.md` zu berücksichtigen.
- Widerspricht eine geplante Implementierung einer geltenden Regel, wird der Konflikt vor Umsetzung benannt und bewusst entschieden.
- Regeländerungen sind Architekturentscheidungen und keine implizite Folge einer Implementierung.

## Repository-Dokumentation

GitHub beschreibt **was und wie**. Google Drive beschreibt **warum und wofür**.

Projektweite technische Informationen gehören nach `/docs/`.
Lokale `RULES.md` bleiben nah am Code und enthalten nur Regeln, die für den jeweiligen Bereich zusätzlich gelten.
Dubletten zwischen `/docs/` und lokalen Regeldateien sind zu vermeiden.

## Regeldateien

Verbindliche lokale Regeldateien heißen `RULES.md` oder `*-RULES.md`.
Sie sind Repository-Metadaten und kein Bestandteil produktiver Deployment-Artefakte.
