# NEROZON Questionnaire – Local Rules

Status: DRAFT

Für `/www/q` gelten zusätzlich die NEROZON-weiten Engineering-Regeln, die Root-`RULES.md`, `/docs/`, `/www/RULES.md` und `/www/q/ABUSE-RULES.md`.

Der konkrete funktionale und gestalterische Sollstand wird in den lokalen SPEC-Dateien beschrieben, insbesondere:

- `/www/q/QUESTIONNAIRE-SPEC.md`
- `/www/q/20-FRAGEN-SPEC.md`
- `/www/assets/backgrounds/BACKGROUNDS-SPEC.md`

Diese Datei enthält nur zusätzliche technische und datenbezogene Regeln für das Modul `/www/q`.

## Modulgrenze

Q-003
Questionnaire-spezifische Logik, Assets und Zustände bleiben innerhalb des Moduls, sofern keine tatsächlich gemeinsame Verantwortung besteht.
Zentrale Markenassets werden aus `/www/assets/brand/` verwendet.

Q-004
Die Kommunikation mit serverseitiger Business Logic erfolgt über definierte API-Verträge.

## Anonymität und Datentrennung

Q-040
Der Fragebogen wird als anonym angeboten und muss fachlich anonym bleiben.

Q-041
Fragebogenantworten dürfen nicht mit einer E-Mail-Adresse, persönlichen Nachricht oder anderen freiwillig angegebenen Kontaktdaten verknüpft werden.

Q-042
Eine technische Submission-ID dient ausschließlich der Verarbeitung des anonymen Fragebogens und darf nicht als Brücke zu später eingegebenen Kontaktdaten verwendet werden.

Q-043
Technische Betriebs- und Missbrauchsdaten werden getrennt vom fachlichen Antwort-Payload behandelt.
Sie dürfen zur Qualitätssicherung, Missbrauchserkennung und statistischen Bereinigung verwendet werden, aber nicht zur nachträglichen Verbindung mit dem optionalen Kontaktformular.

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
