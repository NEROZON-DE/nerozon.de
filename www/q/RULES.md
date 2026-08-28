# NEROZON Questionnaire Rules

Status: DRAFT

Zusätzlich gelten:
- `/RULES.md`, sobald diese Regeln nach `main` übernommen wurden
- `/www/RULES.md`, sobald diese Regeln nach `main` übernommen wurden
- `/www/DESIGN-RULES.md`

## Zweck und Modulgrenze

Q-001
`/www/q` ist eine eigenständige öffentliche Fragebogen-Anwendung innerhalb des NEROZON-Webauftritts.

Q-002
Der Fragebogen wird als geschlossener Nutzerfluss gestaltet: Einstieg → vier Fragenpakete → Absenden → optionaler persönlicher Kontakt.

Q-003
Questionnaire-spezifische Logik, Assets und Zustände bleiben innerhalb des Moduls, sofern keine tatsächlich gemeinsame Verantwortung besteht.
Zentrale Markenassets werden aus `/www/assets/brand/` verwendet.

Q-004
Die Kommunikation mit serverseitiger Business Logic erfolgt über definierte API-Verträge.

## Inhaltlicher Aufbau

Q-010
Der Fragebogen besteht aus genau 20 fachlichen Fragen.

Q-011
Die 20 Fragen werden in vier aufeinanderfolgende Pakete mit jeweils fünf Fragen gegliedert.

Q-012
Vor dem ersten Fragenpaket steht eine kurze Einleitung, die Zweck, erwartete Dauer und grundlegenden Charakter der Befragung verständlich erklärt.

Q-013
Jedes Fragenpaket muss als eigener, klar erkennbarer Abschnitt wahrnehmbar sein und zugleich Teil eines durchgehenden Gesamtflusses bleiben.

Q-014
Frage, Antwortoptionen und aktueller Abschnitt haben visuell Vorrang vor ergänzenden Erklärungen.

Q-015
Antwortoptionen müssen eindeutig auswählbar sein und ihren gewählten Zustand unmittelbar sichtbar machen.

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

## Nutzerführung und Zustand

Q-030
Der Nutzer muss jederzeit erkennen können, in welchem der vier Fragenpakete er sich befindet und wie weit der Fragebogen fortgeschritten ist.

Q-031
Bereits beantwortete Fragen dürfen beim Vor- und Zurückscrollen nicht unbeabsichtigt ihren Zustand verlieren.

Q-032
Der Nutzer darf frühere Fragen vor dem Absenden erneut aufrufen und seine Antworten ändern.

Q-033
Fehlende Pflichtantworten werden vor dem endgültigen Absenden verständlich kenntlich gemacht und müssen gezielt erreichbar sein.

## Absenden und optionaler Kontakt

Q-040
Nach dem vierten Fragenpaket wird eine eindeutige Aktion zum Absenden der Fragebogenantworten angeboten.

Q-041
Das Absenden der fachlichen Antworten ist ein eigener abgeschlossener Vorgang.
Eine spätere Entscheidung für oder gegen persönlichen Kontakt darf die bereits erfolgte Übermittlung nicht verändern.

Q-042
Nach erfolgreichem Absenden wird ein optionales Kontaktformular angeboten.
Es darf nicht den Eindruck erwecken, für die Teilnahme am Fragebogen erforderlich zu sein.

Q-043
Das Kontaktformular bietet die Möglichkeit, eine persönliche Nachricht zu übermitteln.
Eine E-Mail-Adresse kann optional angegeben werden.

Q-044
Wird keine E-Mail-Adresse angegeben, darf das Kontaktformular keine Antwortmöglichkeit vortäuschen, die technisch nicht besteht.
Der Nutzen einer Nachricht ohne Rückkanal muss in der Oberfläche verständlich sein.

Q-045
Fragebogenantworten und optionale Kontaktdaten werden fachlich getrennt behandelt.
Eine Verknüpfung darf nur bewusst und für einen definierten Zweck erfolgen.

## Client und Server

Q-050
Der Browser ist keine vertrauenswürdige Datenquelle.

Q-051
Clientseitige Validierung dient Benutzerführung und Komfort.
Verbindliche Validierung erfolgt serverseitig.

Q-052
Der Client enthält keine Secrets, administrativen Credentials oder privilegierten Funktionen.

Q-053
Manipulierbare Clientwerte werden serverseitig nicht ungeprüft als Autorisierung, Identität oder vertrauenswürdige Metadaten verwendet.

## Fragebogendaten und Telemetrie

Q-060
Die technische Darstellung des Fragebogens und das fachliche Questionnaire-Modell sind getrennte Verantwortlichkeiten.

Q-061
Die API validiert eingehende Fragebogendaten anhand eines definierten Data Contracts bzw. Data Models.

Q-062
Gespeicherte Antworten müssen einer definierten Questionnaire-/Schema-Version zugeordnet werden können.

Q-063
Änderungen an Fragen dürfen bestehende gespeicherte Antworten nicht mehrdeutig machen.

Q-064
Daten werden nur erhoben oder dauerhaft gespeichert, wenn sie für einen definierten Zweck benötigt werden.

Q-065
Anonymität bzw. Personenbezug des Fragebogens ist eine bewusste fachliche Eigenschaft und darf nicht zufällig aus der technischen Implementierung entstehen.

Q-066
Technische Telemetrie und fachliche Fragebogenantworten werden als getrennte Datenarten behandelt, dürfen aber über definierte technische Referenzen miteinander verknüpft werden, wenn dies für Missbrauchserkennung, Qualitätssicherung oder Betrieb erforderlich ist.

Q-067
Technische Identifikatoren wie IP-Adresse oder vergleichbare Metadaten sind nicht Bestandteil des fachlichen Antwort-Payloads.
Sie werden separat gespeichert und über eine Submission- oder Event-ID referenziert.

## Auswertung

Q-070
Die operative Erfassung der Antworten bleibt von späteren Analyse- und Reporting-Strukturen getrennt genug, dass Auswertungen die Teilnahme nicht unnötig koppeln oder verlangsamen.

Q-071
Ableitbare Analyse- oder Reporting-Strukturen sollen aus den primären Fragebogendaten reproduzierbar sein.

Q-072
Technische Missbrauchssignale dürfen genutzt werden, um Einreichungen für die Auswertung zu markieren, zu gruppieren oder auszuschließen.
Die ursprüngliche Einreichung bleibt davon als Primärdatensatz nachvollziehbar, solange ihre Aufbewahrung vorgesehen ist.
