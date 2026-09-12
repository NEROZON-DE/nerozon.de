<?php
declare(strict_types=1);
require_once __DIR__ . '/_legal.php';

$responsible = nerozon_responsible_data();
$organisation = nerozon_legal_value($responsible, 'organisation');
$name = nerozon_legal_value($responsible, 'name');
$street = nerozon_legal_value($responsible, 'street');
$place = nerozon_legal_value($responsible, 'place');
$country = nerozon_legal_value($responsible, 'country');
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#030507">
  <title>Datenschutz – NEROZON</title>
  <link rel="stylesheet" href="legal.css">
</head>
<body>
<main>
  <a class="brand" href="/">NEROZON</a>
  <div class="eyebrow">Datenschutz</div>
  <h1>Datenschutzerklärung</h1>

  <p>Diese Datenschutzerklärung informiert darüber, welche personenbezogenen Daten beim Besuch dieser Website und bei der Nutzung der NEROZON-Research-Befragung sowie des Kontaktformulars verarbeitet werden.</p>

  <h2>1. Verantwortlicher</h2>
  <?php if ($responsible !== []): ?>
    <address>
      <?php if ($organisation !== ''): ?><?= nerozon_legal_escape($organisation) ?><br><?php endif; ?>
      <?php if ($name !== ''): ?><?= nerozon_legal_escape($name) ?><br><?php endif; ?>
      <?php if ($street !== ''): ?><?= nerozon_legal_escape($street) ?><br><?php endif; ?>
      <?php if ($place !== ''): ?><?= nerozon_legal_escape($place) ?><br><?php endif; ?>
      <?php if ($country !== ''): ?><?= nerozon_legal_escape($country) ?><?php endif; ?>
    </address>
  <?php else: ?>
    <p>Die Angaben zum Verantwortlichen konnten auf diesem System nicht geladen werden.</p>
  <?php endif; ?>

  <h2>2. Grundsatz der Datenverarbeitung</h2>
  <p>NEROZON erhebt über diese Website keine Daten zu Werbe-, Tracking-, Analyse- oder Profilingzwecken. NEROZON verarbeitet bei der Research-Befragung ausschließlich die von Ihnen eingegebenen Antworten und beim Kontaktformular ausschließlich die von Ihnen dort freiwillig eingegebenen Angaben.</p>
  <p>Die Daten aus der Research-Befragung und die Daten aus dem Kontaktformular werden getrennt verarbeitet. NEROZON verknüpft diese Datensätze nicht miteinander und versucht nicht, Teilnehmer der Research-Befragung anhand technischer Betriebsdaten zu identifizieren.</p>

  <h2>3. Hosting und technische Bereitstellung</h2>
  <p>Diese Website wird bei der IONOS SE, Elgendorfer Str. 57, 56410 Montabaur, Deutschland, betrieben. IONOS verarbeitet im Rahmen der technischen Bereitstellung der Website und der Kommunikationsdienste Daten, die für Betrieb, Sicherheit und Stabilität technisch erforderlich sind. Dazu können insbesondere IP-Adresse, Zeitpunkt des Zugriffs, angeforderte Ressource, übertragene Datenmenge, Browser- und Systeminformationen sowie technische Protokolldaten gehören.</p>
  <p>Die Verarbeitung dieser technischen Daten erfolgt zur sicheren und funktionsfähigen Bereitstellung der Website. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO. Das berechtigte Interesse besteht im sicheren, stabilen und störungsfreien Betrieb des Internetangebots. Soweit IONOS Daten im Auftrag von NEROZON verarbeitet, erfolgt dies im Rahmen einer Auftragsverarbeitung gemäß Art. 28 DSGVO.</p>
  <p>NEROZON führt die technischen Hostingdaten nicht mit den Inhalten der Research-Befragung oder des Kontaktformulars zusammen.</p>

  <h2>4. Technisch notwendige Session</h2>
  <p>Für den geschützten Versand der Research-Befragung und des Kontaktformulars wird eine technisch notwendige Session verwendet. Sie dient ausschließlich dazu, einmalig nutzbare Sicherheitstoken bereitzustellen und missbräuchliche Mehrfach- oder Fremdaufrufe der Versandfunktion zu erschweren.</p>
  <p>Die Session enthält keine Antworten aus der Befragung und keine Inhalte des Kontaktformulars. Die zugehörigen Sicherheitstoken sind nur kurzzeitig gültig und werden nach Verwendung ungültig. Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO zum Schutz der Website und der Kommunikationsfunktion vor Missbrauch.</p>

  <h2>5. NEROZON-Research-Befragung</h2>
  <p>Die Research-Befragung ist so gestaltet, dass keine Kontaktdaten abgefragt werden. Übermittelt werden ausschließlich die Antworten, die Sie selbst in der Befragung auswählen oder eingeben.</p>
  <p>Freitextfelder können grundsätzlich Angaben enthalten, die einen Personenbezug ermöglichen. Bitte geben Sie dort keine personenbezogenen oder vertraulichen Angaben ein, die für die Beantwortung der jeweiligen Frage nicht erforderlich sind.</p>
  <p>Die Verarbeitung erfolgt auf Grundlage Ihrer Einwilligung gemäß Art. 6 Abs. 1 lit. a DSGVO. Die Einwilligung wird mit dem Absenden der Antworten erteilt. Sie können Ihre Einwilligung jederzeit mit Wirkung für die Zukunft widerrufen. Die Rechtmäßigkeit der bis zum Widerruf erfolgten Verarbeitung bleibt hiervon unberührt.</p>
  <p>Die Antworten werden ausschließlich für die Auswertung der NEROZON-Research-Befragung verwendet. Eine Zuordnung zu einer Person oder zu später übermittelten Kontaktdaten findet durch NEROZON nicht statt.</p>

  <h2>6. Kontaktformular</h2>
  <p>Das Kontaktformular ist technisch und fachlich von der Research-Befragung getrennt. Verarbeitet werden ausschließlich die von Ihnen eingegebene Nachricht, eine gegebenenfalls freiwillig angegebene E-Mail-Adresse, der Zeitpunkt der Übermittlung sowie die Bestätigung, dass Sie die Datenschutzerklärung zur Kenntnis genommen und der Verarbeitung zugestimmt haben.</p>
  <p>Die Verarbeitung erfolgt grundsätzlich auf Grundlage Ihrer Einwilligung gemäß Art. 6 Abs. 1 lit. a DSGVO. Soweit Ihre Nachricht auf die Anbahnung oder Durchführung eines Vertrags gerichtet ist, kann zusätzlich Art. 6 Abs. 1 lit. b DSGVO einschlägig sein.</p>
  <p>Die Angaben werden ausschließlich zur Bearbeitung Ihrer Nachricht und einer gegebenenfalls erforderlichen Antwort verwendet. Eine Verknüpfung mit Antworten aus der Research-Befragung findet nicht statt.</p>

  <h2>7. Empfänger</h2>
  <p>Empfänger der von Ihnen übermittelten Daten ist NEROZON. Für Hosting und technische Kommunikationsleistungen wird IONOS als Dienstleister eingesetzt. Eine Weitergabe an weitere Empfänger erfolgt nicht, sofern hierfür keine gesetzliche Verpflichtung besteht oder Sie einer solchen Weitergabe nicht ausdrücklich zugestimmt haben.</p>

  <h2>8. Speicherdauer</h2>
  <p>Antworten aus der Research-Befragung werden nur so lange in ihrer eingegangenen Form gespeichert, wie dies für Auswertung und Dokumentation der Befragung erforderlich ist. Anschließend werden sie gelöscht oder nur noch in einer Form weiterverwendet, die keinen Personenbezug mehr ermöglicht.</p>
  <p>Daten aus dem Kontaktformular werden bis zur abschließenden Bearbeitung Ihres Anliegens gespeichert und anschließend gelöscht, sofern keine gesetzlichen Aufbewahrungspflichten oder andere zulässige Gründe für eine weitere Speicherung bestehen.</p>
  <p>Technische Betriebs- und Protokolldaten beim Hosting werden nach den für den technischen Betrieb und die Sicherheit erforderlichen Fristen verarbeitet und gelöscht.</p>

  <h2>9. Ihre Rechte</h2>
  <p>Soweit die gesetzlichen Voraussetzungen vorliegen, haben Sie insbesondere das Recht auf Auskunft über die zu Ihrer Person gespeicherten Daten, auf Berichtigung unrichtiger Daten, auf Löschung, auf Einschränkung der Verarbeitung, auf Datenübertragbarkeit sowie auf Widerspruch gegen Verarbeitungen, die auf Art. 6 Abs. 1 lit. f DSGVO beruhen.</p>
  <p>Eine erteilte Einwilligung können Sie jederzeit mit Wirkung für die Zukunft widerrufen. Bei tatsächlich anonymen Research-Antworten kann eine nachträgliche Zuordnung zu einer bestimmten Person und damit auch eine personenbezogene Auskunft oder Löschung einzelner Antworten technisch nicht möglich sein, weil NEROZON bewusst keine Zuordnung herstellt.</p>

  <h2>10. Beschwerderecht</h2>
  <p>Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu beschweren, wenn Sie der Ansicht sind, dass die Verarbeitung Ihrer personenbezogenen Daten gegen die Datenschutz-Grundverordnung verstößt.</p>

  <h2>11. Änderungen dieser Datenschutzerklärung</h2>
  <p>Diese Datenschutzerklärung kann angepasst werden, wenn sich die Website, die eingesetzten technischen Verfahren oder die rechtlichen Anforderungen ändern. Es gilt die jeweils auf dieser Website veröffentlichte Fassung.</p>

  <footer>
    <a href="/">Startseite</a>
    <a href="/impressum.php">Impressum</a>
  </footer>
</main>
</body>
</html>
