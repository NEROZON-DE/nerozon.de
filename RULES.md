# NEROZON Repository – General Rules

Status: DRAFT

Diese Datei definiert die projektweiten Regeln für Struktur, Spezifikationen und Regelpflege im Repository. Sie gilt für das gesamte Repository. Spezifischere `RULES.md` und `*-RULES.md` dürfen diese Regeln für ihren Bereich ergänzen, aber nicht stillschweigend widersprechen.

## Regelhierarchie

1. Diese Root-`RULES.md` definiert die projektweiten Meta-Regeln.
2. Projektweite fachliche und technische Grundlagen liegen unter `/docs/`.
3. Bereichsspezifische `RULES.md` gelten für ihren Verzeichnisbaum.
4. `*-RULES.md` dürfen einen enger abgegrenzten Themenbereich zusätzlich spezifizieren.
5. Je spezifischer eine Regel ist, desto enger ist ihr Geltungsbereich – nicht automatisch ihre Priorität.
6. Widersprüche zwischen geltenden Regeln werden nicht durch Interpretation aufgelöst. Sie müssen sichtbar gemacht und bewusst entschieden werden.

## Inhalt von Spezifikationen

- Spezifikationen beschreiben verbindlich, WAS erreicht werden muss und WARUM eine Anforderung besteht.
- Das WIE der konkreten Implementierung gehört nur dann in die Spezifikation, wenn die technische Form selbst eine verbindliche Architektur-, Sicherheits-, Daten- oder Schnittstellenanforderung ist.
- Implementierungsdetails, die frei austauschbar sein sollen, gehören nicht in die verbindliche Spezifikation.
- Beispiele, Datenmodelle, Schemas und Schnittstellenbeschreibungen gehören zur Spezifikation, wenn sie Anforderungen oder Verträge erklären bzw. maschinenprüfbar machen.
- Beispiele müssen erkennbar machen, ob sie normativ/verbindlich oder lediglich illustrativ sind.
- Offene oder noch nicht entschiedene Punkte werden ausdrücklich als `OPEN` oder `TBD` gekennzeichnet und dürfen nicht als implizit entschieden behandelt werden.

## Pflege von Regeln und Dokumentation

- Neue Regeln werden nicht allein deshalb eingeführt, weil eine bestehende Implementierung sich so verhält.
- Änderungen an Regeln sind bewusste Spezifikations- bzw. Architekturentscheidungen.
- Neue Erkenntnisse sollen bestehende Regeln konsolidieren und aktualisieren. Historische, überholte oder widersprüchliche Aussagen werden entfernt statt dauerhaft angehängt; die Historie liegt in Git.
- Unnötige Wiederholungen zwischen Root-Regeln, `/docs/` und lokalen Regeldateien sind zu vermeiden.
- Lokale Regeldateien enthalten nur die zusätzlichen Anforderungen ihres Bereichs.
- Vor einer Regeländerung sind betroffene übergeordnete und angrenzende Spezifikationen auf Auswirkungen und Widersprüche zu prüfen.

## Branch `specs`

Der Branch `specs` ist der bewusst geprüfte und freigegebene Referenzstand der NEROZON-Spezifikation.

Er enthält die Informations- und Verzeichnisstruktur, die zum Verständnis und zur Prüfung des Projekts notwendig ist, insbesondere:

- `/docs/` und andere verbindliche Dokumentation,
- alle geltenden `RULES.md` und `*-RULES.md`,
- relevante Verzeichnisstruktur,
- Referenz-Assets,
- Beispiele,
- Datenmodelle und Schemas,
- Schnittstellen- und API-Verträge.

`specs` enthält keinen ausführbaren Produktiv- oder Runtime-Code. Verzeichnisse ohne Spec-Inhalt dürfen durch `.gitkeep` abgebildet werden.

## Promotion nach `specs`

- `Devin-exercise` ist der aktive Arbeits- und Integrationsstand. Dort dürfen Spezifikation und Implementierung gemeinsam weiterentwickelt werden.
- Eine Promotion nach `specs` ist keine vollständige Branch-Merge-Operation, sondern eine kontrollierte Extraktion der spezifikationsrelevanten Änderungen.
- Vor der Promotion werden die Änderungen auf Widersprüche, Dubletten, fehlende Grundlagen und Auswirkungen auf bestehende Spezifikationen geprüft.
- Nach `specs` werden ausschließlich zulässige Spec-Inhalte übernommen; Ausführungscode und Deployment-Implementierung bleiben ausgeschlossen.
- Die Promotion erfolgt grundsätzlich über einen Pull Request, damit die Änderung gegenüber dem zuletzt akzeptierten Spec-Stand als Diff geprüft werden kann. Eine ausdrücklich vereinbarte Initialbefüllung darf davon abweichen.
- Der Merge nach `specs` bedeutet, dass der enthaltene Informationsstand bewusst als akzeptierte Referenz übernommen wurde.

## Deployment

`RULES.md` und `*-RULES.md` sind Repository-Metadaten und niemals Bestandteil produktiver Deployment-Artefakte.
Der Branch `specs` ist nicht deploybar und darf nicht als Quelle eines Produktionsdeployments verwendet werden.
