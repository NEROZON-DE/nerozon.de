# NEROZON Security

Status: DRAFT

## Grundprinzipien

- Secrets, Credentials und produktive Zugangsdaten dürfen nicht im Repository gespeichert werden.
- Client-Code enthält keine Secrets, administrativen Credentials oder privilegierten Funktionen.
- Browser und externe Requests sind nicht vertrauenswürdig.
- Manipulierbare Clientwerte werden serverseitig nicht ungeprüft als Identität, Autorisierung oder vertrauenswürdige Metadaten verwendet.
- Verbindliche strukturelle Validierung erfolgt serverseitig.
- Zugriffe folgen Least Privilege.

## Datenschutz und Datentrennung

Personenbezogene Daten werden nur erhoben und verarbeitet, wenn sie für den jeweiligen Zweck erforderlich sind.
Fachliche Nutzdaten, technische Betriebs-/Missbrauchsdaten und freiwillige Kontaktdaten werden nach Verantwortlichkeit getrennt behandelt.
Eine technische Kennung darf nicht ohne definierten fachlichen Zweck zur nachträglichen Verknüpfung getrennter Datenbereiche verwendet werden.

## Abuse Protection

Missbrauchsschutz wird als serverseitige Verantwortung betrachtet. Clientseitige Signale können ergänzen, sind aber nicht allein vertrauenswürdig.
Technische Missbrauchssignale dürfen zur Erkennung, Markierung, Gruppierung oder Abweisung missbräuchlicher Requests verwendet werden, ohne dadurch fachlich getrennte Datenbestände unnötig zu verknüpfen.

## Secrets

Secrets werden außerhalb des versionierten und öffentlich erreichbaren Webroots gespeichert oder über eine gleichwertig geschützte Runtime-Konfiguration bereitgestellt.
Der Zugriff erfolgt über eine zentrale, klar begrenzte Schnittstelle. Anwendungen erhalten nur die Secrets, die sie tatsächlich benötigen.
Konkrete Secret-Namen oder produktive Secret-Werte gehören nicht in Repository-Dokumentation, Logs oder Fehlermeldungen.

Weitere konkrete Anforderungen werden mit der Implementierung des Secret-Subsystems ergänzt.
