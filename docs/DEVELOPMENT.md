# nerozon.de Development

Status: DRAFT

## Grundsätze

- Änderungen werden nachvollziehbar versioniert.
- Vor Änderungen sind die NEROZON-weiten Engineering-Regeln, die Root-`RULES.md`, `/docs/` sowie die für den betroffenen Pfad geltenden lokalen RULES und SPECs zu berücksichtigen.
- Widerspricht eine geplante Implementierung einer geltenden Regel oder Spezifikation, wird der Konflikt vor Umsetzung benannt und bewusst entschieden.
- Regeländerungen sind Architektur- bzw. Governance-Entscheidungen und keine implizite Folge einer Implementierung.
- Spezifikationsänderungen sind bewusste Änderungen des Sollstands und werden nicht still aus bestehendem Code abgeleitet.

## Dokumentationsebenen

NEROZON-weite Engineering-Regeln definieren die produktübergreifenden technischen Leitplanken.

Dieses Repository enthält die verbindliche technische Projektbeschreibung für `nerozon.de`:
- `/docs/` für projektweite technische Grundlagen,
- Root- und lokale RULES für projektweite bzw. bereichsspezifische Regeln,
- `SPEC.md` und `*-SPEC.md` für konkrete fachliche, funktionale oder gestalterische Sollvorgaben,
- Code und technische Artefakte für die Implementierung in ausführbaren Branches.

Dubletten zwischen diesen Ebenen sind zu vermeiden. Niedrigere Ebenen präzisieren höhere, ohne deren MUST-Regeln abzuschwächen.

## Branch-Modell

### `specs`

`specs` ist der gemeinsame Referenz- und Übergabestand für die Informationsstruktur des Projekts.
Der Branch enthält Regeln, Spezifikationen, technische Dokumentation, Referenz-Assets, Beispiele, Datenmodelle, Schemas und Schnittstellenverträge, aber keinen ausführbaren Produktiv- oder Runtime-Code.

Nicht branch-spezifische Änderungen an RULES und SPEC werden zuerst in `specs` vorgenommen.

### `dev1`, `dev2`, `dev3`

Die regulären DEV-Branches dürfen Implementierungen unabhängig entwickeln und testen.

Gemeinsame RULES- und SPEC-Änderungen aus `specs` werden mit demselben Inhalt in alle drei DEV-Branches übernommen.
Dadurch dürfen sich Implementierungen unterscheiden, der gemeinsame normative und spezifizierte Sollstand jedoch nicht unbeabsichtigt auseinanderlaufen.

Branch-spezifische RULES- oder SPEC-Abweichungen müssen ausdrücklich als solche benannt sein.

### `Devin-exercise`

`Devin-exercise` bleibt ein unabhängiger Arbeits- und Experimentierbranch.
Er wird nicht automatisch mit gemeinsamen RULES- oder SPEC-Änderungen synchronisiert und ist selbst dafür verantwortlich, benötigte Änderungen aus `specs` zu übernehmen.

## Widersprüche im Arbeitsstand

Ein ausführbarer Arbeitsbranch darf bekannte, vorübergehend tolerierte Widersprüche in `/CONTRADICTIONS.md` dokumentieren.

Der Registereintrag hebt keine Regel oder Spezifikation auf.
Bekannte, nicht durch eine autorisierte NEROZON-Ausnahme gedeckte Widersprüche gegen geltende MUST-Regeln oder verbindliche Spezifikationen müssen vor produktiver Freigabe aufgelöst sein.

## Regel- und Spezifikationsdateien

Verbindliche lokale Regeldateien heißen `RULES.md` oder `*-RULES.md`.
Konkrete Entwicklungsaufträge und Sollvorgaben heißen `SPEC.md` oder `*-SPEC.md`.

RULES und SPECs sind Repository-Metadaten bzw. Entwicklungsinput und kein Bestandteil produktiver Laufzeitartefakte.
