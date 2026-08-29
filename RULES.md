# nerozon.de Repository – General Rules

Status: DRAFT

Diese Datei definiert die projektweiten Meta-Regeln für Struktur, Spezifikationen und Regelpflege im Repository `nerozon.de`.

Die NEROZON-weiten Engineering-Regeln sind die übergeordnete Basis. Diese Repository-Regeln und alle darunterliegenden Regeln dürfen sie präzisieren, aber nicht still abschwächen.

## Regelhierarchie

1. NEROZON-weite Engineering-Regeln bilden die übergeordnete technische Grundlinie.
2. Diese Root-`RULES.md` definiert die projektweiten Meta-Regeln für `nerozon.de`.
3. Projektweite technische Grundlagen liegen unter `/docs/`.
4. Bereichsspezifische `RULES.md` gelten für ihren Verzeichnisbaum.
5. `*-RULES.md` dürfen einen enger abgegrenzten Themenbereich zusätzlich präzisieren.
6. Je spezifischer eine Regel ist, desto enger ist ihr Geltungsbereich – nicht automatisch ihre Priorität.
7. Eine niedrigere Ebene darf eine höhere MUST-Regel nicht abschwächen. Widersprüche werden sichtbar gemacht und nach dem definierten Ausnahme- bzw. Klärungsprozess behandelt.

## Spezifikationen

- Konkrete fachliche, funktionale oder gestalterische Sollvorgaben werden als `SPEC.md` oder `*-SPEC.md` nahe am betroffenen Bereich abgelegt.
- Spezifikationen können Funktionen, Interaktionen, Texte, Datenstrukturen, Schnittstellen, Beispiele, Assets oder Designelemente verbindlich beschreiben.
- Eine SPEC ist keine RULES-Datei und erweitert oder reduziert allein durch ihre Bezeichnung keine Engineering-, Conformance- oder Freigaberegel.
- Widerspricht eine SPEC einer geltenden Regel, wird der Konflikt vor Umsetzung geklärt.
- `DRAFT` kennzeichnet einen Arbeitsstand; `APPROVED` kennzeichnet freigegebenen Entwicklungsinput.

## Branch `specs`

`specs` ist der gemeinsame Referenz- und Übergabestand für die Informationsstruktur von `nerozon.de`.

Er enthält insbesondere:

- Root- und lokale RULES-Dateien,
- `/docs/`,
- `SPEC.md` und `*-SPEC.md`,
- relevante Verzeichnisstruktur,
- Referenz-Assets,
- Beispiele,
- Datenmodelle und Schemas,
- Schnittstellen- und API-Verträge.

`specs` enthält keinen ausführbaren Produktiv- oder Runtime-Code. Verzeichnisse ohne Spec-Inhalt dürfen durch `.gitkeep` abgebildet werden.

## Synchronisation gemeinsamer RULES und SPEC

- Änderungen an `RULES.md`, `*-RULES.md`, `SPEC.md` oder `*-SPEC.md`, die nicht ausdrücklich für einen bestimmten Branch bestimmt sind, werden zuerst in `specs` eingebracht.
- Solche gemeinsamen Änderungen werden in `dev1`, `dev2` und `dev3` mit demselben Inhalt übernommen.
- Unbeabsichtigte Unterschiede der gemeinsamen RULES- und SPEC-Stände zwischen `dev1`, `dev2` und `dev3` sind nicht zulässig.
- Eine branch-spezifische Abweichung muss ausdrücklich als solche benannt sein.
- `Devin-exercise` ist von dieser automatischen bzw. gemeinsamen Synchronisation ausgenommen. Der Branch bleibt unabhängig und übernimmt benötigte Änderungen aus `specs` selbst.

## Bekannte Widersprüche im Arbeitsstand

Arbeitsbranches dürfen für bekannte, vorübergehend tolerierte Widersprüche ein branch-lokales Register `/CONTRADICTIONS.md` führen.

- Das Register ist keine Regeldatei und verändert keine geltende Anforderung.
- Ein Eintrag dokumentiert ausschließlich einen bekannten Zwischenzustand.
- Gelöste Einträge werden entfernt; die Historie liegt in Git.
- Bekannte, nicht durch eine autorisierte NEROZON-Ausnahme gedeckte Widersprüche gegen geltende MUST-Regeln oder verbindliche Spezifikationen müssen vor einer produktiven Freigabe aufgelöst sein.
- `specs` darf keinen bewusst ungelösten Widerspruch als akzeptierten Referenzstand enthalten.

## Pflege von Regeln und Dokumentation

- Neue Regeln werden nicht allein deshalb eingeführt, weil eine bestehende Implementierung sich so verhält.
- Änderungen an Regeln sind bewusste Spezifikations- bzw. Architekturentscheidungen.
- Neue Erkenntnisse konsolidieren und aktualisieren bestehende Regeln; historische, überholte oder widersprüchliche Aussagen werden entfernt statt dauerhaft angehängt.
- Unnötige Wiederholungen zwischen Root-Regeln, `/docs/`, lokalen RULES und SPECs sind zu vermeiden.
- Vor einer Regeländerung sind betroffene übergeordnete und angrenzende Spezifikationen auf Auswirkungen und Widersprüche zu prüfen.

## Deployment

`RULES.md`, `*-RULES.md`, `SPEC.md` und `*-SPEC.md` sind Repository-Metadaten bzw. Entwicklungsinput und kein Bestandteil produktiver Laufzeitartefakte.

Der Branch `specs` ist nicht deploybar und darf nicht als Quelle eines Runtime-Deployments verwendet werden.
