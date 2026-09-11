<?php
declare(strict_types=1);
require_once __DIR__ . '/_legal.php';

$legal = nerozon_legal_data();
$name = nerozon_legal_value($legal, 'operator_name');
$email = nerozon_legal_value($legal, 'email');
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

  <div class="notice"><strong>Entwurf / technische Vorlage.</strong> Die konkreten Angaben zu Hosting, Speicherdauer, Rechtsgrundlagen, Empfängern und Betroffenenrechten müssen vor produktiver Veröffentlichung fachlich und rechtlich vervollständigt werden.</div>

  <h2>Verantwortlicher</h2>
  <p>
    <?= $name !== '' ? nerozon_legal_escape($name) : 'Noch nicht über das Web-Secret konfiguriert.' ?>
    <?php if ($email !== ''): ?><br>E-Mail: <a href="mailto:<?= nerozon_legal_escape($email) ?>"><?= nerozon_legal_escape($email) ?></a><?php endif; ?>
  </p>

  <h2>Anonyme Befragung</h2>
  <p>Die Antworten der NEROZON-Research-Befragung werden fachlich getrennt von freiwillig übermittelten Kontaktdaten verarbeitet. Eine spätere Kontaktaufnahme wird nicht mit den Antworten der anonymen Befragung verknüpft.</p>

  <h2>Optionale Nachricht</h2>
  <p>Wenn Sie nach Abschluss der Befragung freiwillig eine Nachricht oder eine E-Mail-Adresse übermitteln, werden diese Angaben ausschließlich im separaten Kontaktvorgang verarbeitet. Die konkrete Speicherdauer und weitere Pflichtinformationen werden vor dem produktiven Start an dieser Stelle ergänzt.</p>

  <footer>
    <a href="/">Startseite</a>
    <a href="/impressum.php">Impressum</a>
  </footer>
</main>
</body>
</html>
