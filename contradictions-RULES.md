# NEROZON Contradictions Register

Status: ACTIVE WORKING REGISTER

Diese Datei sammelt bekannte Widersprüche, Inkonsistenzen und noch nicht aufgelöste Konflikte, die während der Entwicklung in `Devin-exercise` entdeckt werden und nicht unverzüglich gelöst werden.

## Zweck

- Ein bereits bekannter Widerspruch wird hier einmal dokumentiert, statt bei jeder Analyse, jedem Test oder Review erneut als neuer Befund aufzutauchen.
- Solange ein Widerspruch hier eingetragen ist, sind daraus resultierende unstetige, widersprüchliche oder vorübergehend inkonsistente Ergebnisse im Arbeitsbranch akzeptiert.
- Der Eintrag ist keine fachliche Entscheidung und keine Aufhebung bestehender Regeln. Er dokumentiert ausschließlich einen bewusst tolerierten Zwischenzustand.
- Neue oder zusätzliche Auswirkungen eines bekannten Widerspruchs dürfen dem bestehenden Eintrag ergänzt werden, statt einen gleichartigen neuen Eintrag anzulegen.
- Sobald der Widerspruch aufgelöst ist, wird der Eintrag vollständig entfernt. Die Historie liegt in Git.

## Eintragsformat

Jeder offene Widerspruch sollte mindestens enthalten:

- eindeutige Kurzbezeichnung,
- betroffene Dateien, Regeln oder Bereiche,
- kurze Beschreibung des Konflikts,
- erwartete Auswirkungen bzw. welche unstetigen Ergebnisse dadurch toleriert werden,
- optional die vorgesehene Entscheidung oder der nächste Klärungsschritt.

## Promotion nach `specs`

Vor einem Pull Request zur Promotion von Spezifikationsänderungen nach `specs` muss dieses Register inhaltlich leer sein.

Ein PR nach `specs` darf keinen bewusst ungelösten Widerspruch als akzeptierten Referenzstand übertragen.

---

## Offene Widersprüche

Keine.
