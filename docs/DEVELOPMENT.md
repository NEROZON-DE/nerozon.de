# NEROZON Development

Status: DRAFT

## Grundsätze

- Änderungen werden nachvollziehbar versioniert.
- Neue Entwicklungsarbeit beginnt grundsätzlich auf Basis des aktuellen produktiven Stands, sofern für einen Arbeitsstrang kein anderer Branch ausdrücklich festgelegt wurde.
- `Devin-exercise` ist aktuell der vereinbarte aktive Arbeits- und Integrationsbranch für die gemeinsame Weiterentwicklung.
- KI-Worker entwickeln auf dafür festgelegten Arbeitsbranches und liefern Änderungen kontrolliert zur Übernahme.
- Vor Änderungen sind die Root-`RULES.md`, `/docs/` sowie die für den betroffenen Pfad geltenden lokalen `RULES.md` und `*-RULES.md` zu berücksichtigen.
- Widerspricht eine geplante Implementierung einer geltenden Regel, wird der Konflikt vor Umsetzung benannt und bewusst entschieden.
- Regeländerungen sind Architektur- bzw. Spezifikationsentscheidungen und keine implizite Folge einer Implementierung.

## Repository-Dokumentation

GitHub enthält die verbindliche technische Projektbeschreibung, Regeln, Verträge und die dazugehörige Implementierung. Google Drive kann ergänzende fachliche, organisatorische oder explorative Informationen enthalten.

Projektweite technische Informationen gehören nach `/docs/`.
Lokale `RULES.md` bleiben nah am betroffenen Verzeichnis und enthalten nur Regeln, die für den jeweiligen Bereich zusätzlich gelten.
Dubletten zwischen Root-Regeln, `/docs/` und lokalen Regeldateien sind zu vermeiden.

## Regeldateien

Verbindliche Regeldateien heißen `RULES.md` oder `*-RULES.md`.
Für Aufbau, Hierarchie, Pflege und Konfliktbehandlung dieser Dateien gilt die Root-`RULES.md`.
Regeldateien sind Repository-Metadaten und kein Bestandteil produktiver Deployment-Artefakte.

## Branch `specs`

`specs` ist der geprüfte Referenzstand der Projektspezifikation und ausdrücklich kein zweiter ausführbarer Projektstand.

Der Branch enthält nur spezifikationsrelevante Informationen und Strukturen, insbesondere Dokumentation, Regeln, Referenz-Assets, Beispiele, Datenmodelle, Schemas und Schnittstellenverträge. Runtime-, Produktiv- und Deployment-Code wird nicht übernommen.

Änderungen werden aus dem aktiven Arbeitsstand gezielt extrahiert und grundsätzlich per Pull Request nach `specs` übernommen. Der PR dient als Review-Grenze: geprüft wird nur, was sich gegenüber dem zuletzt akzeptierten Spec-Stand geändert hat.

Ein Merge nach `specs` bedeutet, dass der enthaltene Informationsstand bewusst als Referenz akzeptiert wurde. Die detaillierten Promotion-Regeln stehen in der Root-`RULES.md`.
