<?php
declare(strict_types=1);
require_once __DIR__ . '/_legal.php';

$legal = nerozon_legal_data();
$configured = $legal !== [];
$organisation = nerozon_legal_value($legal, 'organisation');
$name = nerozon_legal_value($legal, 'name');
$street = nerozon_legal_value($legal, 'street');
$place = nerozon_legal_value($legal, 'place');
$country = nerozon_legal_value($legal, 'country');
$email = nerozon_legal_value($legal, 'email');
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#030507">
  <title>Impressum – NEROZON</title>
  <link rel="stylesheet" href="legal.css">
</head>
<body>
<main>
  <a class="brand" href="/">NEROZON</a>
  <div class="eyebrow">Rechtliche Informationen</div>
  <h1>Impressum</h1>

  <?php if (!$configured): ?>
    <div class="notice"><strong>Technischer Hinweis.</strong> Die Impressumsdaten sind auf diesem System noch nicht konfiguriert.</div>
  <?php else: ?>
    <h2>Angaben gemäß § 5 DDG</h2>
    <address>
      <?php if ($organisation !== ''): ?><?= nerozon_legal_escape($organisation) ?><br><?php endif; ?>
      <?php if ($name !== ''): ?>Inhaber: <?= nerozon_legal_escape($name) ?><br><?php endif; ?>
      <?php if ($street !== ''): ?><?= nerozon_legal_escape($street) ?><br><?php endif; ?>
      <?php if ($place !== ''): ?><?= nerozon_legal_escape($place) ?><br><?php endif; ?>
      <?php if ($country !== ''): ?><?= nerozon_legal_escape($country) ?><?php endif; ?>
    </address>

    <h2>Kontakt</h2>
    <p>
      <?php if ($email !== ''): ?>E-Mail: <a href="mailto:<?= nerozon_legal_escape($email) ?>"><?= nerozon_legal_escape($email) ?></a><?php endif; ?>
    </p>

    <h2>Hinweis zur Anschrift</h2>
    <p>Die vorstehende Anschrift wird zur Erfüllung gesetzlicher Informationspflichten und als ladungsfähige Anschrift veröffentlicht.</p>
    <p>Eine Nutzung der Anschrift für Werbung, Adresshandel, Profilbildung oder sonstige sachfremde kommerzielle Zwecke ist nicht gestattet, soweit hierfür keine gesetzliche Grundlage oder ausdrückliche Einwilligung besteht.</p>
  <?php endif; ?>

  <footer>
    <a href="/">Startseite</a>
    <a href="/datenschutz.php">Datenschutz</a>
  </footer>
</main>
</body>
</html>
