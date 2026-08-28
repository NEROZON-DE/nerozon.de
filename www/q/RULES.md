# NEROZON Questionnaire Rules

Status: DRAFT

Zusätzlich gelten:
- `/RULES.md`
- `/www/RULES.md`

## Modulgrenze

Q-001
`/www/q` ist eine eigenständige funktionale Anwendung innerhalb des öffentlichen Webbereichs.

Q-002
Änderungen am Fragebogen sollen die Hauptseite nicht unnötig beeinflussen.

Q-003
Questionnaire-spezifische Logik, Assets und Zustände bleiben innerhalb des Moduls, sofern keine tatsächlich gemeinsame Verantwortung besteht.

Q-004
Die Kommunikation mit serverseitiger Business Logic erfolgt über definierte API-Verträge.

## Client und Server

Q-010
Der Browser ist keine vertrauenswürdige Datenquelle.

Q-011
Clientseitige Validierung dient Benutzerführung und Komfort.
Verbindliche Validierung erfolgt serverseitig.

Q-012
Der Client darf keine Secrets, administrativen Credentials oder privilegierten Funktionen enthalten.

Q-013
Manipulierbare Clientwerte dürfen serverseitig nicht ohne Prüfung als Autorisierung, Identität oder vertrauenswürdige Metadaten verwendet werden.

## Fragebogen und Daten

Q-020
Die technische Darstellung des Fragebogens und das fachliche Questionnaire-Modell sind getrennte Verantwortlichkeiten.

Q-021
Die API muss eingehende Fragebogendaten anhand eines definierten Data Contracts bzw. Data Models validieren.

Q-022
Gespeicherte Antworten müssen einer definierten Questionnaire-/Schema-Version zugeordnet werden können.

Q-023
Änderungen an Fragen dürfen bestehende gespeicherte Antworten nicht mehrdeutig machen.

Q-024
Daten werden nur erhoben oder dauerhaft gespeichert, wenn sie für einen definierten Zweck benötigt werden.

Q-025
Anonymität bzw. Personenbezug des Fragebogens muss eine bewusste fachliche Eigenschaft sein und darf nicht zufällig aus der technischen Implementierung entstehen.

Q-026
Technische Telemetrie und fachliche Fragebogenantworten werden logisch getrennt behandelt.

## Auswertung

Q-030
Die spätere Auswertung darf die operative Datenerfassung nicht unnötig koppeln oder verlangsamen.

Q-031
Ableitbare Analyse- oder Reporting-Strukturen sollen aus den primären Fragebogendaten reproduzierbar sein.
