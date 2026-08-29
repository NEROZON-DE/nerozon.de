# NEROZON Questionnaire – Specification

Status: DRAFT
Type: Implementation specification

Diese Spezifikation beschreibt den konkreten Nutzerfluss und die Interaktion der öffentlichen Fragebogen-Anwendung unter `/www/q`.

Die fachlichen Fragen, Antworttypen und Antwortoptionen werden ausschließlich durch `/www/q/20-FRAGEN-SPEC.md` definiert.
Technische Sicherheits-, Datenschutz- und Datenregeln stehen in `/www/q/RULES.md` und `/www/q/ABUSE-RULES.md`.

## Zweck und Nutzerfluss

Q-001
`/www/q` ist eine eigenständige öffentliche Fragebogen-Anwendung innerhalb des NEROZON-Webauftritts.

Q-002
Der Fragebogen wird als geschlossener Nutzerfluss gestaltet: Einstieg → Einleitung → vier Fragenpakete → Absenden → optionaler persönlicher Kontakt.

## Inhalt und Zeitvorgabe

Q-010
Fragen, Antworttypen, Antwortoptionen, Paketstruktur und Reihenfolge werden aus `/www/q/20-FRAGEN-SPEC.md` übernommen.
Eine zweite fachliche Kopie der Fragen wird nicht gepflegt.

Q-012
Vor dem ersten Fragenpaket steht eine kurze Einleitung, die Zweck, Anonymität und die erwartete Bearbeitungsdauer verständlich erklärt.

Q-013
Die Zielvorgabe für den vollständigen Fragebogen beträgt ungefähr zwei Minuten.
Die Interaktion wird deshalb auf schnelles Erfassen und möglichst direkte Auswahl ausgelegt.

Q-013a
Die Zwei-Minuten-Zeit beginnt mit der bewussten Aktion `Los geht’s`.
Nach Ablauf von zwei Minuten wird einmalig ein freundlicher Hinweis eingeblendet, der sowohl das weitere Beantworten als auch das sofortige Absenden der bis dahin gegebenen Antworten anbietet.
Der Hinweis darf keine Antwort erzwingen und darf nach `Weiter beantworten` nicht erneut erscheinen.

Q-014
Keine der 20 Fragen ist verpflichtend.
Der Nutzer darf einzelne Fragen oder ganze Bereiche unbeantwortet lassen und den Fragebogen trotzdem absenden.

Q-015
Unbeantwortete Fragen werden nicht als Fehler behandelt und lösen keine drängenden Warnungen oder Bestätigungsdialoge aus.

Q-017
Antwortoptionen sollen bevorzugt als klar beschriftete Auswahlflächen mit unterstützenden Icons oder einfachen grafischen Symbolen erscheinen statt als technisch wirkende Standard-Bullets.

Q-018
Frage, Antwortoptionen und aktueller Abschnitt haben visuell Vorrang vor ergänzenden Erklärungen.

Q-019
Antwortoptionen werden als klickbare, großzügige Flächen gestaltet.
Sie dürfen keine gequetschte Pillen-Sammlung erzeugen, wenn dadurch Lesbarkeit oder Premiumwirkung leiden.

## Einstieg und Scroll-Dramaturgie

Q-020
Der Einstieg nutzt einen bildfüllenden Hero-Bereich mit dominanter NEROZON-Marke.

Q-021
Im Hero zeigt ein Pfeil innerhalb eines Kreises eindeutig an, dass der Nutzer nach unten fortfahren kann.

Q-022
Der Scroll-Hinweis darf sich subtil bewegen, um die mögliche Interaktion anzudeuten.
Die Bewegung muss ruhig bleiben und reduzierte Bewegung berücksichtigen.

Q-023
Ein Klick oder Tap auf den Scroll-Hinweis führt weich zum nächsten inhaltlichen Abschnitt.
Normales Scrollen bleibt jederzeit gleichwertig möglich.

Q-024
Die vier Fragenpakete werden beim Scrollen als inszenierte Abfolge präsentiert.
Übergänge dürfen mit Bewegung, Tiefenwirkung, Fixierung, Überblendung oder vergleichbaren Effekten arbeiten, solange Fragen und Antworten jederzeit eindeutig bedienbar bleiben.

Q-025
Die konkrete visuelle Inszenierung der vier Pakete ist bewusst nicht vollständig festgelegt.
Sie soll innerhalb der NEROZON-Designsprache einen hochwertigen, überraschenden Produktseiten-Charakter erzeugen.

Q-026
Fünf Fragen eines Pakets dürfen nicht wie ein gedrängter Formularblock wirken.
Die Umsetzung soll entweder einzelne Fragen stärker fokussieren oder innerhalb des Pakets ausreichend Raum, Rhythmus und visuelle Staffelung schaffen.

Q-027
Hintergründe und Kapitelbilder unterstützen die Reise: Realität → Bremsen → Vertrauen/Kontrolle → Wert.
Ihre konkrete Zuordnung wird in `/www/assets/backgrounds/BACKGROUNDS-SPEC.md` beschrieben.

## Nutzerführung und Zustand

Q-030
Der Nutzer muss jederzeit erkennen können, in welchem der vier Fragenpakete er sich befindet und wie weit der Fragebogen fortgeschritten ist.

Q-031
Bereits beantwortete Fragen dürfen beim Vor- und Zurückscrollen nicht unbeabsichtigt ihren Zustand verlieren.

Q-032
Der Nutzer darf frühere Fragen vor dem Absenden erneut aufrufen und seine Antworten ändern.

Q-033
Der Fragebogen darf den Nutzer nicht durch Pflichtfelder, künstliche Sperren oder wiederholte Aufforderungen zu Antworten drängen.

Q-034
Die Fortschrittsanzeige muss wahrnehmbar sein.
Ein stärkerer oberer Fortschrittsbalken oder eine Kapitelanzeige `01–04` ist einem kaum sichtbaren dünnen Balken vorzuziehen.

Q-035
Bei Fragen mit genau einer möglichen Auswahl führt die Auswahl nach kurzer visueller Rückmeldung sanft zur nächsten Frage.
Mehrfachauswahlen bleiben am aktuellen Ort, damit mehrere Optionen gewählt werden können.

Q-036
Bei kurzen Freitextfragen führt `Return` nach der Eingabe sanft zur nächsten Frage, sofern die Frage nicht bewusst als abschließender Freitext ausgelegt ist.
Frage 20 bleibt davon ausgenommen.

Q-037
Frage 20 ist auf 4.000 Zeichen begrenzt.
Ab 3.000 Zeichen wird ein sichtbarer Zeichenzähler eingeblendet, der den aktuellen Stand bis zum Limit anzeigt.

Q-038
Antwortoptionen wie `Weitere` oder `Anderes`, die eine individuelle Ergänzung vorsehen, blenden bei Aktivierung ein zugehöriges Freitextfeld innerhalb derselben Frage ein.
Bei einer Einzelauswahl mit solcher Ergänzung wird beim Aktivieren dieser Option nicht automatisch zur nächsten Frage gescrollt.
`Return` bzw. `Enter` innerhalb eines eingeblendeten Ergänzungsfelds erzeugt einen normalen Zeilenumbruch und löst keinen automatischen Wechsel oder Scroll zur nächsten Frage aus.
Wird die zugehörige Option deaktiviert, wird das Ergänzungsfeld nicht als Antwort übermittelt.

## Absenden und optionaler Kontakt

Q-050
Nach dem vierten Fragenpaket wird eine eindeutige Aktion zum Absenden der bis dahin gegebenen Antworten angeboten.
Auch ein nur teilweise oder vollständig unbeantworteter Fragebogen darf abgesendet werden.

Q-051
Mit erfolgreichem Absenden ist die anonyme Fragebogen-Submission abgeschlossen.

Q-052
Erst nach erfolgreichem Abschluss wird ein separates optionales Kontaktformular angeboten.
Es darf nicht den Eindruck erwecken, für die Teilnahme am Fragebogen erforderlich zu sein.

Q-053
Das Kontaktformular bietet die Möglichkeit, eine persönliche Nachricht zu übermitteln.
Eine E-Mail-Adresse kann optional angegeben werden.

Q-054
Das Kontaktformular erzeugt einen eigenständigen Vorgang ohne fachliche oder technische Referenz auf die zuvor abgegebene Fragebogen-Submission.

Q-055
Wird keine E-Mail-Adresse angegeben, darf die Oberfläche keine persönliche Rückantwort versprechen.

Q-056
Für den separaten Kontaktvorgang ist keine künstliche Pflicht-Einwilligungscheckbox vorgesehen.
Vor dem Absenden wird transparent darauf hingewiesen, dass die angegebenen Kontaktdaten und die Nachricht zur Bearbeitung per E-Mail an NEROZON weitergeleitet werden.
Die Datenschutzerklärung wird unmittelbar erreichbar verlinkt.
Die Formulierung darf nicht behaupten, dass außerhalb der Website – insbesondere im E-Mail-System oder in technisch erforderlichen Logs – keinerlei Speicherung stattfindet.
