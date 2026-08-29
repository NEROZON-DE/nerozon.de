# NEROZON Questionnaire Rules

Status: DRAFT

Zusätzlich gilt `/www/DESIGN-RULES.md`.

## Zweck und Modulgrenze

Q-001
`/www/q` ist eine eigenständige öffentliche Fragebogen-Anwendung innerhalb des NEROZON-Webauftritts.

Q-002
Der Fragebogen wird als geschlossener Nutzerfluss gestaltet: Einstieg → Einleitung → vier Fragenpakete → Absenden → optionaler persönlicher Kontakt.

Q-003
Questionnaire-spezifische Logik, Assets und Zustände bleiben innerhalb des Moduls, sofern keine tatsächlich gemeinsame Verantwortung besteht.
Zentrale Markenassets werden aus `/www/assets/brand/` verwendet.

Q-004
Die Kommunikation mit serverseitiger Business Logic erfolgt über definierte API-Verträge.

## Inhalt und Zeitvorgabe

Q-010
Der Fragebogen besteht aus genau 20 fachlichen Fragen.
Die fachliche Quelle ist das Dokument `nerozon/research/umfrage/20-fragen`.

Q-011
Die 20 Fragen werden in vier aufeinanderfolgende Pakete mit jeweils fünf Fragen gegliedert.
Die Paketstruktur und inhaltliche Reihenfolge der Quelle bleiben erhalten.

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

Q-016
Antworttypen und Antwortoptionen werden passend zur jeweiligen Frage gewählt.
Wo sinnvoll werden schnelle Einzel- oder Mehrfachauswahl, kompakte Skalen und kurze Freitexte verwendet.

Q-017
Antwortoptionen sollen bevorzugt als klar beschriftete Auswahlflächen mit unterstützenden Icons oder einfachen grafischen Symbolen erscheinen statt als technisch wirkende Standard-Bullets.

Q-018
Frage, Antwortoptionen und aktueller Abschnitt haben visuell Vorrang vor ergänzenden Erklärungen.

Q-019
Antwortoptionen werden als klickbare, großzügige Flächen gestaltet.
Sie dürfen keine gequetschte Pillen-Sammlung erzeugen, wenn dadurch Lesbarkeit oder Premiumwirkung leiden.

Q-019a
Die im Fragebogen verwendeten Antwortoptionen werden aus dem Drive-Dokument `20-fragen` übernommen bzw. dort gepflegt.
Änderungen an Antwortoptionen werden zuerst im Dokument oder parallel mit eindeutiger Rückführbarkeit dokumentiert.

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
Die konkrete visuelle Inszenierung der vier Pakete ist bewusst nicht festgelegt.
Sie soll innerhalb der NEROZON-Designsprache einen hochwertigen, überraschenden Produktseiten-Charakter erzeugen.

Q-026
Fünf Fragen eines Pakets dürfen nicht wie ein gedrängter Formularblock wirken.
Die Umsetzung soll entweder einzelne Fragen stärker fokussieren oder innerhalb des Pakets ausreichend Raum, Rhythmus und visuelle Staffelung schaffen.

Q-027
Hintergründe und Kapitelbilder unterstützen die Reise: Realität → Bremsen → Vertrauen/Kontrolle → Wert.
Sie werden als Atmosphäre und Orientierung eingesetzt, nicht als Dekoration ohne Zweck.

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

## Anonymität

Q-040
Der Fragebogen wird als anonym angeboten und muss fachlich anonym bleiben.

Q-041
Fragebogenantworten dürfen nicht mit einer E-Mail-Adresse, persönlichen Nachricht oder anderen freiwillig angegebenen Kontaktdaten verknüpft werden.

Q-042
Eine technische Submission-ID dient ausschließlich der Verarbeitung des anonymen Fragebogens und darf nicht als Brücke zu später eingegebenen Kontaktdaten verwendet werden.

Q-043
Technische Betriebs- und Missbrauchsdaten werden getrennt vom fachlichen Antwort-Payload behandelt.
Sie dürfen zur Qualitätssicherung, Missbrauchserkennung und statistischen Bereinigung verwendet werden, aber nicht zur nachträglichen Verbindung mit dem optionalen Kontaktformular.

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

## Client und Server

Q-060
Der Browser ist keine vertrauenswürdige Datenquelle.

Q-061
Clientseitige Validierung dient Benutzerführung und Komfort.
Verbindliche strukturelle Validierung erfolgt serverseitig, ohne unbeantwortete fachliche Fragen abzulehnen.

Q-062
Der Client enthält keine Secrets, administrativen Credentials oder privilegierten Funktionen.

Q-063
Manipulierbare Clientwerte werden serverseitig nicht ungeprüft als Autorisierung, Identität oder vertrauenswürdige Metadaten verwendet.

## Fragebogendaten und Telemetrie

Q-070
Die technische Darstellung des Fragebogens und das fachliche Questionnaire-Modell sind getrennte Verantwortlichkeiten.

Q-071
Die API validiert eingehende Fragebogendaten anhand eines definierten Data Contracts bzw. Data Models.

Q-072
Gespeicherte Antworten müssen einer definierten Questionnaire-/Schema-Version zugeordnet werden können.

Q-073
Änderungen an Fragen dürfen bestehende gespeicherte Antworten nicht mehrdeutig machen.

Q-074
Technische Identifikatoren wie IP-Adresse oder vergleichbare Metadaten sind nicht Bestandteil des fachlichen Antwort-Payloads.

Q-075
Technische Missbrauchssignale dürfen genutzt werden, um Einreichungen für die Auswertung zu markieren, zu gruppieren oder auszuschließen.
Die ursprüngliche anonyme Einreichung bleibt davon als Primärdatensatz nachvollziehbar, solange ihre Aufbewahrung vorgesehen ist.

## Auswertung

Q-080
Die operative Erfassung der Antworten bleibt von späteren Analyse- und Reporting-Strukturen getrennt genug, dass Auswertungen die Teilnahme nicht unnötig koppeln oder verlangsamen.

Q-081
Ableitbare Analyse- oder Reporting-Strukturen sollen aus den primären Fragebogendaten reproduzierbar sein.
