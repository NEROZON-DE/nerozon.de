<?php
declare(strict_types=1);
require_once __DIR__ . '/_legal.php';

$legal = nerozon_legal_data();
$configured = $legal !== [];
$name = nerozon_legal_value($legal, 'operator_name');
$street = nerozon_legal_value($legal, 'street');
$postalCode = nerozon_legal_value($legal, 'postal_code');
$city = nerozon_legal_value($legal, 'city');
$email = nerozon_legal_value($legal, 'email');
$phone = nerozon_legal_value($legal, 'phone');
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
    <div class="notice"><strong>Technischer Platzhalter.</strong> Die Impressumsdaten sind auf diesem System noch nicht über das Web-Secret konfiguriert.</div>
  <?php else: ?>
    <h2>Anbieter</h2>
    <address>
      <?= nerozon_legal_escape($name) ?><br>
      <?= nerozon_legal_escape($street) ?><br>
      <?= nerozon_legal_escape(trim($postalCode . ' ' . $city)) ?>
    </address>

    <h2>Kontakt</h2>
    <p>
      <?php if ($email !== ''): ?>E-Mail: <a href="mailto:<?= nerozon_legal_escape($email) ?>"><?= nerozon_legal_escape($email) ?></a><?php endif; ?>
      <?php if ($email !== '' && $phone !== ''): ?><br><?php endif; ?>
      <?php if ($phone !== ''): ?>Telefon: <?= nerozon_legal_escape($phone) ?><?php endif; ?>
    </p>
  <?php endif; ?>

  <footer>
    <a href="/">Startseite</a>
    <a href="/datenschutz.php">Datenschutz</a>
  </footer>
</main>
</body>
</html>
