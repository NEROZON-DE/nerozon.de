# NEROZON Web Frontend Rules

Status: DRAFT

Zusätzlich gelten `/RULES.md`.

WWW-001
`/www` enthält den vollständig reproduzierbaren öffentlichen Webstand.

WWW-002
Dateien innerhalb von `/www` sind Deployment-Artefakte bzw. deren versionierte Quellen und dürfen nicht als dauerhafte Laufzeitpersistenz verwendet werden.

WWW-003
Ein vollständiger Austausch von `/www` muss zulässig sein, ohne persistente Anwendungsdaten zu verlieren.

WWW-004
Secrets und serverseitige Credentials dürfen nicht Bestandteil von `/www` sein.

WWW-005
Die öffentliche Hauptseite soll technisch möglichst leichtgewichtig und unabhängig von funktionalen Anwendungen bleiben.

WWW-006
Funktionale Anwendungen innerhalb von `/www` müssen gegenüber der Hauptseite klar abgegrenzt sein.

WWW-007
Frontend-Code darf keine sicherheitsrelevanten Annahmen darüber treffen, dass vom Browser gelieferte Daten vertrauenswürdig sind.

WWW-008
Fachliche serverseitige Logik gehört grundsätzlich in die API bzw. eine dafür definierte Backend-Schicht und nicht in das öffentliche Frontend.

WWW-009
Gemeinsame Frontend-Ressourcen werden nur dann zentralisiert, wenn echte Wiederverwendung den zusätzlichen Kopplungsaufwand rechtfertigt.
