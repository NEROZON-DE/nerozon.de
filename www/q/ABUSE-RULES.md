# ABUSE-RULES – /q

## Zweck

Diese Datei definiert die verbindlichen Abuse-/Spam-Schutzanforderungen für das Formular unter `/q`.

Ziel ist, automatisierten Missbrauch des Formulars als Mail-Relay bzw. Spamquelle deutlich zu erschweren, ohne normalen Besuchern CAPTCHA oder andere sichtbare Hürden aufzuerlegen.

## Grundprinzipien

- Alle sicherheitsrelevanten Prüfungen erfolgen serverseitig.
- Clientseitige JavaScript-Prüfungen allein gelten niemals als Sicherheitskontrolle.
- JavaScript darf für den regulären Formularablauf vorausgesetzt werden.
- Kein sichtbares CAPTCHA im Normalbetrieb.
- Schutz erfolgt mehrstufig (Defense in Depth).
- Bei zunehmendem Missbrauch kann später eine zusätzliche Challenge/CAPTCHA-Stufe ergänzt werden.

## Form-Session / Token

Beim Laden von `/q` fordert JavaScript über einen API-Endpunkt eine Form-Session an.

Der Server erzeugt dabei:

- einen kryptographisch zufälligen, nicht vorhersagbaren Token,
- einen serverseitigen Erstellungszeitpunkt (`created_at`),
- einen Status, mindestens `unused` / `used`,
- eine begrenzte Gültigkeitsdauer.

Der Startzeitpunkt darf nicht vom Browser vorgegeben oder als vertrauenswürdiger Wert übernommen werden.

Beim Absenden muss der Token mitgesendet werden. Der Server akzeptiert die Nachricht nur, wenn:

1. der Token serverseitig bekannt und gültig ist,
2. seit seiner Erstellung eine definierte Mindestzeit vergangen ist,
3. die maximale Gültigkeitsdauer noch nicht überschritten wurde,
4. der Token noch nicht benutzt wurde.

Nach erfolgreicher Verwendung wird der Token sofort invalidiert bzw. als `used` markiert. Ein Replay desselben Tokens darf keine weitere E-Mail auslösen.

Als Startwerte sind vorgesehen:

- Mindestalter: ca. 20–30 Sekunden,
- maximale Lebensdauer: ca. 30–60 Minuten.

Die konkreten Werte sollen konfigurierbar bleiben.

## JavaScript als zusätzliche Hürde

Ein regulärer Submit ohne vorherige JavaScript-basierte Form-Session ist nicht zulässig.

Damit reicht ein einfacher direkter POST auf den Mail-Endpunkt nicht aus. Ein Angreifer müsste mindestens den Session-Ablauf nachbilden oder JavaScript ausführen.

Dies ist ausdrücklich nur eine zusätzliche Hürde und kein Ersatz für serverseitige Prüfungen.

## Honeypot

Das Formular enthält mindestens ein für normale Benutzer unsichtbares Honeypot-Feld.

- Das Feld darf von einem normalen Benutzer nicht ausgefüllt werden.
- Ist es beim Submit befüllt, wird die Anfrage verworfen.
- Die Ablehnung soll einem Bot möglichst keine detaillierten Informationen über den Grund liefern.

## Rate Limiting

Der Mail-Endpunkt erhält ein serverseitiges Rate-Limit.

Startwert: maximal ca. 3–5 erfolgreich ausgelöste Nachrichten pro Stunde und IP-Adresse.

Das Limit soll konfigurierbar sein. Bei Überschreitung darf keine weitere E-Mail erzeugt werden.

Rate Limiting ergänzt Token und Honeypot; es ersetzt diese nicht.

## Eingabevalidierung

Alle Eingaben werden serverseitig validiert.

Insbesondere:

- maximale Nachrichtenlänge: 4.000 Zeichen,
- nur erwartete Felder akzeptieren,
- keine vom Client gelieferten Mail-Header übernehmen,
- keine frei steuerbaren Empfängeradressen zulassen,
- Schutz gegen Header Injection / CRLF Injection,
- Größenlimits für den gesamten Request setzen.

## Mailversand und Speicherung

Die über das Kontakt-/Nachrichtenfeld eingegebenen Daten werden von der Website ausschließlich zum Versand der Nachricht per E-Mail verarbeitet.

Die Anwendung soll:

- Nachricht und Kontaktdaten nicht in einer eigenen Datenbank speichern,
- keine dauerhaften Dateien mit dem Nachrichteninhalt erzeugen,
- den Inhalt nach erfolgreicher Übergabe an den Maildienst nicht für die Formularfunktion vorhalten.

Davon unberührt bleiben technisch erforderliche Server-/Security-Logs sowie die Speicherung der empfangenen Nachricht im E-Mail-System. In Logs sollen Nachrichteninhalte und andere unnötige personenbezogene Formulardaten nicht erscheinen.

## Datenschutz-Hinweis im Formular

Für das Absenden ist keine künstliche Einwilligungs-Checkbox vorgesehen.

Am Nachrichtenfeld bzw. vor dem Absenden soll transparent darauf hingewiesen werden, dass die Nachricht per E-Mail an NEROZON weitergeleitet wird und die Website den Nachrichteninhalt nicht dauerhaft speichert. Zusätzlich wird auf die Datenschutzerklärung verlinkt.

Die Formulierung darf nicht behaupten, dass überhaupt keine Speicherung stattfindet, da E-Mail-System und technische Serverlogs davon getrennt zu betrachten sind.

## Fehlerverhalten

Fehlgeschlagene Abuse-Prüfungen dürfen keine E-Mail auslösen.

Antworten des Endpunkts sollen keine unnötigen Details darüber verraten, welche konkrete Schutzregel angeschlagen hat. Insbesondere sollen Tokenvalidierung, Honeypot und Rate-Limit nicht als Anleitung für einen Angreifer offengelegt werden.

## Eskalationsstufe

Sollte der unsichtbare Schutz nicht ausreichen, kann als zusätzliche Stufe ein CAPTCHA bzw. eine vergleichbare Bot-Challenge eingeführt werden.

Dies ist nicht Teil des initialen Designs und soll erst bei tatsächlichem Bedarf aktiviert werden, um Benutzerfreundlichkeit, Abhängigkeiten und zusätzliche Datenschutzthemen gering zu halten.

## Kurzablauf

`Page Load -> JS fordert Form-Session an -> Server erzeugt Token + created_at -> Benutzer füllt /q aus -> Submit mit Token -> Server prüft Tokenalter + TTL + unused + Honeypot + Rate-Limit + Eingaben -> Token invalidieren -> Mail senden`

## Sicherheitsregel

Kein einzelner Mechanismus dieser Datei gilt für sich allein als ausreichender Schutz. Token, Zeitprüfung, Single-Use, Honeypot, Rate-Limit und serverseitige Eingabevalidierung sind gemeinsam umzusetzen.