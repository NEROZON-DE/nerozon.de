# NEROZON Web Application Rules

Status: DRAFT

Diese Regeln gelten für das gesamte Repository.
Untergeordnete RULES.md dürfen sie konkretisieren.
Abweichungen von übergeordneten Regeln müssen ausdrücklich dokumentiert werden.

## Architektur

ROOT-001
Das Repository ist die führende Quelle für den reproduzierbaren Anwendungsstand.

ROOT-002
`main` repräsentiert den gewünschten produktiven Stand.

ROOT-003
Neue Entwicklungsarbeit beginnt grundsätzlich auf Basis des aktuellen `main`.

ROOT-004
Funktionale Bereiche müssen so getrennt bleiben, dass Änderungen möglichst nur den betroffenen Bereich berühren.

ROOT-005
Gemeinsame Komponenten werden nur eingeführt, wenn tatsächlich gemeinsame Verantwortung oder Wiederverwendung besteht.
Abstraktion allein ist kein Grund für Shared Code.

## Entwicklung

ROOT-010
Änderungen müssen nachvollziehbar versioniert werden.

ROOT-011
KI-Worker entwickeln auf separaten Branches und liefern Änderungen per Pull Request.
Der Merge nach `main` erfolgt durch einen Menschen.

ROOT-012
Vor einer Änderung müssen die für den betroffenen Pfad geltenden RULES.md berücksichtigt werden.

ROOT-013
Ein Vorschlag, eine Implementierung oder eine Anforderung, die einer geltenden Regel widerspricht, muss vor Umsetzung ausdrücklich als Regelkonflikt benannt werden.

ROOT-014
Regeln dürfen geändert werden.
Eine Regeländerung ist jedoch eine bewusste Architekturentscheidung und keine implizite Folge einer Implementierung.

## Betrieb und Deployment

ROOT-020
Produktive Anwendungsbereiche müssen vollständig aus dem versionierten Repository reproduzierbar sein.

ROOT-021
Persistente Laufzeitdaten dürfen nicht von Dateien innerhalb eines austauschbaren Deployment-Baums abhängen.

ROOT-022
Secrets, Credentials und produktive Zugangsdaten dürfen nicht im Repository gespeichert werden.

ROOT-023
Deployment- und Runtime-Zugriffe folgen dem Least-Privilege-Prinzip.

ROOT-024
Fehlerhafte versionierte Änderungen werden bevorzugt durch einen nachvollziehbaren Revert korrigiert.

## Abhängigkeiten

ROOT-030
Externe Frameworks und Libraries dürfen Infrastruktur bereitstellen.
Sie dürfen nicht ohne bewusste Architekturentscheidung das fachliche Datenmodell oder Vokabular der Anwendung bestimmen.

ROOT-031
Externe Abhängigkeiten müssen hinter einer geeigneten Abstraktionsgrenze liegen, wenn ihre direkte Verwendung relevante Business-Logik dauerhaft an den Anbieter oder das Framework koppeln würde.

ROOT-032
Abstraktion wird dort eingesetzt, wo sie Austauschbarkeit, Testbarkeit, Zuverlässigkeit oder klare Verantwortungsgrenzen verbessert.
Abstraktion ohne konkreten Nutzen ist zu vermeiden.
