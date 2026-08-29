# NEROZON Background Assets

Status: DRAFT

Diese Datei beschreibt die Hintergrundbilder für `/www/q`, ihren Einsatzzweck und ihre feste Zuordnung.
Die produktiv verwendeten Assets liegen zentral unter `/www/assets/backgrounds/` und werden von `/www/q` nur referenziert.

Die aktuelle V2 verwendet SVG-Hintergründe. Dadurch bleiben die Flächen scharf, leichtgewichtig und ohne zusätzliche Raster-Artefakte skalierbar. Die visuelle Richtung orientiert sich an den zuvor erzeugten NEROZON-Hintergrundstudien: dunkler Datenraum, Partikel-/Netzstrukturen, kontrollierte Cyan-/Türkis-Akzente und viel negative Fläche.

## Asset-Satz

### `hero-void.svg`
Einsatz: Hero-Bereich von `/q`.
Zweck: Sehr dunkler, ruhiger NEROZON-Raum mit viel negativer Fläche für das Original-Logo.
Charakter: Hochwertig, reduziert, technisch, nicht bunt.

### `section-reality.svg`
Einsatz: Einleitung und Paket 1 „Wo steht KI heute?“.
Zweck: Nüchterner Datenraum; KI ist bereits da, aber noch nicht dramatisch inszeniert.
Charakter: Kühle Ordnung, dezente Raster-/Partikelstruktur, klare Tiefe.

### `section-friction.svg`
Einsatz: Paket 2 „Was bremst den produktiven Einsatz?“.
Zweck: Sichtbare Spannung und Unterbrechung im Datenfluss.
Charakter: Bruch, Fragmentierung und Reibung – ohne Alarm- oder Cyberpunk-Optik.

### `section-control.svg`
Einsatz: Paket 3 „Was müsste eine vertrauenswürdige Lösung können?“.
Zweck: Struktur, Kontrolle, Nachvollziehbarkeit und Governance andeuten.
Charakter: Geordnetes Netzwerk, technische Präzision, ruhiger Fokus.

### `section-flow.svg`
Einsatz: Abschluss des Fragebogens und mögliche Übergänge.
Zweck: Kontrollierter Datenfluss und Verbindung vorhandener Systeme.
Charakter: Leuchtender gerichteter Datenstrom, ruhig und zielgerichtet.

### `section-value.svg`
Einsatz: Paket 4 „Wo entsteht echter Wert?“.
Zweck: Von Kontrolle zu Nutzen; steigende Bewegung und wirtschaftlicher Wert.
Charakter: Blau-Türkis mit zurückhaltendem Grünanteil und klarer Aufwärtsbewegung.

## Einbindungsregeln

BG-001
Hintergrundbilder unterstützen die Kapitelreise und werden nicht beliebig zwischen Kapiteln ausgetauscht.

BG-002
Texte und Antwortflächen liegen immer auf ausreichend dunklen Masken bzw. Kontrastflächen.

BG-003
Die Bilder ersetzen keine semantische Kapitelstruktur. Die Seite bleibt auch ohne Bildwirkung vollständig verständlich und bedienbar.

BG-004
Farbe kommt primär aus Licht, Datenstrukturen und aktiven Interaktionen. Große vollflächige bunte UI-Flächen werden vermieden.

BG-005
Der Hero verwendet das bestehende Original-Logo `/www/nerozon-logo.png`. Es wird nicht durch nachgesetzte HTML-Typografie oder eine lokal rekonstruierte Wortmarke ersetzt.

BG-006
Bei späterem Austausch eines Hintergrundbildes bleibt Dateiname bzw. Einsatzzweck stabil, sofern sich die gestalterische Funktion nicht ändert.
