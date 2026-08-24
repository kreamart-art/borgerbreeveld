<?php
/**
 * Controlepagina.
 *
 * Zet de website op de server, open dan dit bestand in je browser
 * (jouwadres.nl/controle.php). Hij kijkt na of alles goed staat en
 * zegt in gewone taal wat er nog moet gebeuren.
 *
 * Staat alles op groen? Verwijder dit bestand dan van de server,
 * je hebt het niet meer nodig.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

start_sessie();

// Deze pagina vertelt hoe de server erbij staat. Dat hoeft niet iedereen
// te weten, dus je moet eerst inloggen op het dashboard.
if (empty($_SESSION['beheer'])) {
    ?>
    <!doctype html>
    <html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Controle</title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="stylesheet" href="assets/style.css?v=3">
    </head>
    <body class="beheer-body">
        <main class="inlog">
            <h1>Controle</h1>
            <p class="hint">Log eerst in op het dashboard, dan kun je hier
                zien of alles goed op de server staat.</p>
            <p><a class="knop knop-groot" href="beheer.php">Naar het dashboard</a></p>
        </main>
    </body>
    </html>
    <?php
    exit;
}

zorg_voor_mappen();

$punten = [];

/** Voegt een regel toe aan de lijst. 'goed', 'let op' of 'moet nog'. */
function punt(&$punten, $stand, $wat, $uitleg = '')
{
    $punten[] = ['stand' => $stand, 'wat' => $wat, 'uitleg' => $uitleg];
}

/** Kan php echt schrijven in deze map? Niet gokken, gewoon proberen. */
function map_is_schrijfbaar($map)
{
    if (!is_dir($map)) {
        return false;
    }
    $proef = $map . '/proef-' . unieke_code(6) . '.tmp';
    $gelukt = @file_put_contents($proef, 'test') !== false;
    if ($gelukt) {
        @unlink($proef);
    }
    return $gelukt;
}

/* ------------------------------------------------------------------ */

// 1. De versie van php
if (PHP_VERSION_ID >= 70400) {
    punt($punten, 'goed', 'De server draait php ' . PHP_VERSION . '.');
} else {
    punt($punten, 'moet nog', 'De server draait php ' . PHP_VERSION . '.',
        'Deze website heeft php 7.4 of nieuwer nodig. Vraag je hosting om '
        . 'een nieuwere versie in te stellen.');
}

// 2. Schrijfrechten
foreach ([
    'uploads'        => UPLOAD_MAP,
    'uploads/thumbs' => THUMB_MAP,
    'data'           => DATA_MAP,
] as $naam => $map) {
    if (map_is_schrijfbaar($map)) {
        punt($punten, 'goed', 'De map ' . $naam . ' is beschrijfbaar.');
    } else {
        punt($punten, 'moet nog', 'De map ' . $naam . ' is niet beschrijfbaar.',
            'Zonder dit kan er niets worden opgeslagen. Zet in je '
            . 'FTP-programma de rechten van deze map op 755, en werkt dat '
            . 'niet, dan op 775. Zie stap 3 van LEES-MIJ.md.');
    }
}

// 3. Foto's verkleinen
if (function_exists('imagecreatetruecolor')) {
    punt($punten, 'goed', 'Foto\'s kunnen verkleind worden voor de galerij.');
} else {
    punt($punten, 'let op', 'De server kan geen foto\'s verkleinen.',
        'De uitbreiding GD ontbreekt. Ingestuurde foto\'s worden dan op '
        . 'volle grootte getoond, wat traag laadt. Vraag je hosting of GD '
        . 'aangezet kan worden.');
}

// 4. Foto's rechtop zetten
if (function_exists('exif_read_data')) {
    punt($punten, 'goed', 'Telefoonfoto\'s worden vanzelf rechtop gezet.');
} else {
    punt($punten, 'let op', 'Telefoonfoto\'s kunnen op hun kant komen te staan.',
        'De uitbreiding EXIF ontbreekt. Vraag je hosting of die aan kan.');
}

// 5. Hoe groot mag een bestand zijn
$grens = werkelijke_bestandslimiet();
if ($grens >= 20 * 1024 * 1024) {
    punt($punten, 'goed', 'Bestanden tot ' . leesbare_grootte($grens)
        . ' mogen ingestuurd worden.');
} else {
    punt($punten, 'moet nog', 'Er mag nu maar ' . leesbare_grootte($grens)
        . ' per bestand ingestuurd worden.',
        'Een filmpje van een telefoon is al snel groter. Zie stap 10 van '
        . 'LEES-MIJ.md: de bestanden .user.ini en .htaccess. Verandert er '
        . 'niets, vraag je hosting dan naar de PHP-instellingen.');
}

// 6. Bericht bij een nieuwe inzending
if (!MELD_NIEUWE_INZENDING) {
    punt($punten, 'let op', 'Er gaat geen bericht uit bij een nieuwe inzending.',
        'Dat staat zo ingesteld in config.php. Je moet dan zelf af en toe '
        . 'in het dashboard kijken.');
} elseif (function_exists('mail')) {
    punt($punten, 'goed', 'Bij een nieuwe inzending gaat er een mailtje naar '
        . CONTACT_EMAIL . '.');
} else {
    punt($punten, 'let op', 'Deze server kan geen mail versturen.',
        'Je krijgt dus geen bericht bij een nieuwe inzending. Kijk zelf af '
        . 'en toe in het dashboard, of vraag je hosting naar mail.');
}

// 6b. Meldingen op de telefoon
// Browsers eisen https voor meldingen, met een uitzondering: op je
// eigen computer (localhost) mag het ook zonder.
$gastheer = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$veiligeVerbinding = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/i', $gastheer);
if (!push_mogelijk()) {
    punt($punten, 'let op', 'Deze server kan geen meldingen naar de telefoon sturen.',
        push_waarom_niet() . ' De rest van de website werkt gewoon; je krijgt '
        . 'alleen geen berichtje op je telefoon.');
} elseif (!$veiligeVerbinding) {
    punt($punten, 'moet nog', 'De meldingen werken nog niet: er is geen https.',
        'Telefoons weigeren meldingen aan te zetten op een adres zonder het '
        . 'slotje. Zorg eerst voor een certificaat, daarna kun je ze in het '
        . 'dashboard aanzetten.');
} else {
    $toestellen = push_aantal_toestellen();
    if ($toestellen > 0) {
        punt($punten, 'goed', 'Meldingen staan aan op ' . $toestellen . ' '
            . ($toestellen === 1 ? 'toestel' : 'toestellen') . '.');
    } else {
        punt($punten, 'let op', 'Meldingen kunnen wel, maar staan nog nergens aan.',
            'Open het dashboard op je telefoon en tik bij "Meldingen op je '
            . 'telefoon" op Meldingen aanzetten.');
    }
}

// 7. De beveiliging van de mappen
$sloten = is_file(UPLOAD_MAP . '/.htaccess') && is_file(DATA_MAP . '/.htaccess');
if ($sloten) {
    punt($punten, 'goed', 'De beveiliging van de mappen uploads en data staat er.');
} else {
    punt($punten, 'moet nog', 'De beveiligingsbestanden ontbreken.',
        'In de mappen uploads en data hoort een bestand .htaccess te staan. '
        . 'Zet ze alsnog met FTP op de server. Ziet je FTP-programma ze niet, '
        . 'zet dan aan dat verborgen bestanden getoond worden.');
}

// 8. Het wachtwoord
if (BEHEER_WACHTWOORD === 'verander-dit-wachtwoord') {
    punt($punten, 'moet nog', 'Het wachtwoord van het dashboard staat nog op de '
        . 'standaardwaarde.',
        'Verander het in config.php, anders kan iedereen die deze website '
        . 'kent bij de inzendingen. Zie stap 4 van LEES-MIJ.md.');
} elseif (strlen(BEHEER_WACHTWOORD) < 10) {
    punt($punten, 'let op', 'Het wachtwoord is vrij kort.',
        'Neem er een van minstens tien tekens.');
} else {
    punt($punten, 'goed', 'Er staat een eigen wachtwoord op het dashboard.');
}

// 9. Het contactadres
if (CONTACT_EMAIL === 'herinneringen@voorbeeld.nl' || CONTACT_EMAIL === '') {
    punt($punten, 'moet nog', 'Het contactadres staat nog op het voorbeeld.',
        'Onderaan elke pagina staat nu een adres dat niet bestaat. '
        . 'Verander CONTACT_EMAIL in config.php.');
} else {
    punt($punten, 'goed', 'Het contactadres is ingevuld: ' . CONTACT_EMAIL . '.');
}

// 10. Staat er al iets in
$data = lees_data();
$aantal = 0;
foreach ($data['inzendingen'] as $inzending) {
    $aantal += count($inzending['bestanden']);
}
$aantalInzendingen = count($data['inzendingen']);
punt($punten, 'goed', 'Er ' . ($aantalInzendingen === 1 ? 'staat' : 'staan') . ' '
    . $aantalInzendingen . ' ' . ($aantalInzendingen === 1 ? 'inzending' : 'inzendingen')
    . ' in, met ' . $aantal . ' foto\'s en video\'s.');

/* ------------------------------------------------------------------ */

$moetNog = 0;
$letOp   = 0;
foreach ($punten as $p) {
    if ($p['stand'] === 'moet nog') {
        $moetNog++;
    } elseif ($p['stand'] === 'let op') {
        $letOp++;
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Controle</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body class="beheer-body">
<main class="binnen controle">

    <h1>Controle</h1>

    <?php if ($moetNog === 0 && $letOp === 0): ?>
        <div class="melding melding-goed">
            <h2>Alles staat goed</h2>
            <p>De website is klaar voor gebruik. Verwijder dit bestand
                (<code>controle.php</code>) nu van de server, je hebt het
                niet meer nodig.</p>
        </div>
    <?php elseif ($moetNog > 0): ?>
        <div class="melding melding-fout">
            <h2>Er moet nog iets gebeuren</h2>
            <p><?= (int) $moetNog ?> <?= $moetNog === 1 ? 'punt' : 'punten' ?>
                hieronder <?= $moetNog === 1 ? 'heeft' : 'hebben' ?> aandacht
                nodig voordat de site goed werkt.</p>
        </div>
    <?php else: ?>
        <div class="melding melding-goed">
            <h2>Het werkt</h2>
            <p>Er zijn alleen nog een paar kleine aandachtspunten.</p>
        </div>
    <?php endif; ?>

    <ul class="controlelijst">
        <?php foreach ($punten as $p): ?>
            <li class="stand-<?= $p['stand'] === 'goed' ? 'goed'
                    : ($p['stand'] === 'let op' ? 'letop' : 'fout') ?>">
                <span class="stand-merk"><?= h($p['stand']) ?></span>
                <p class="controle-wat"><?= h($p['wat']) ?></p>
                <?php if ($p['uitleg'] !== ''): ?>
                    <p class="controle-uitleg"><?= h($p['uitleg']) ?></p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="hint">
        <a href="index.php">Naar de website</a> &middot;
        <a href="beheer.php">Naar het dashboard</a>
    </p>

</main>
</body>
</html>
