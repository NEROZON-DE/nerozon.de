# IONOS Runtime

Dieses Dokument hält technisch verifiziertes Verhalten der aktuellen IONOS-Webhosting-Laufzeit fest. Es beschreibt keine allgemeine IONOS-Garantie, sondern den getesteten Stand der DEV2-Umgebung.

## PHP / Webserver

- PHP läuft als `cgi-fcgi`.
- Getestete PHP-Version: `8.4.24`.
- Der Webserver ist Apache; `GATEWAY_INTERFACE` meldet `CGI/1.1`.
- Der bereitgestellte Shell-Aufruf von `php` verhält sich nicht wie eine normale CLI-PHP-Installation. Insbesondere standen im Test übliche CLI-Optionen wie `-r` nicht zur Verfügung.

## HTTP Authorization

Der native HTTP-Header `Authorization` wird in dieser Laufzeit standardmäßig nicht direkt an PHP durchgereicht:

- `$_SERVER['HTTP_AUTHORIZATION']` ist ohne Gegenmaßnahme nicht gesetzt.
- `getallheaders()` und `apache_request_headers()` enthalten `Authorization` ebenfalls nicht.

Verifiziert funktionieren sowohl Apache `SetEnvIf` als auch eine Rewrite-Regel, um den Wert in die CGI-Umgebung zu übernehmen. Der Dispatcher verwendet:

```apache
SetEnvIf Authorization "^(.*)$" HTTP_AUTHORIZATION=$1
```

PHP erhält den Wert anschließend als `REDIRECT_HTTP_AUTHORIZATION`. Der Dispatcher prüft deshalb sowohl `HTTP_AUTHORIZATION` als auch `REDIRECT_HTTP_AUTHORIZATION`.

## Custom Headers

Normale `X-*`-Header werden unverändert an PHP weitergereicht. Verifiziert wurden unter anderem:

- `X-Authorization`
- `X-Nerozon-Probe`
- `X-Nerozon-Ingest-Token`

Sie stehen als entsprechende `HTTP_X_*`-Einträge in `$_SERVER` zur Verfügung und erscheinen auch in den Header-Funktionen.

Für den Dispatcher bleibt `Authorization: Bearer <token>` der kanonische Vertrag. `X-Nerozon-Ingest-Token` ist lediglich ein DEV/interner Fallback.

## Deployment-Hinweis

Root-Dotfiles wie `.htaccess` dürfen nicht implizit über ein `*`-Glob erwartet werden. Sie müssen in der Deployment-Pipeline explizit berücksichtigt werden. Das ist insbesondere für den Authorization-Workaround sicherheits- und funktionsrelevant.

## Diagnose

Die zur Verifikation verwendeten temporären HTTP-Diagnose-Endpunkte wurden nach Abschluss der Untersuchung wieder entfernt. Neue Diagnose-Endpunkte sind ebenfalls nur temporär zulässig und nach der Untersuchung zu löschen.
