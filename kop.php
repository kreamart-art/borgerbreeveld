<?php
/**
 * De bovenkant van elke pagina: de balk met het menu, en de banner.
 *
 * Elke pagina zet eerst een paar dingen klaar en laadt dan dit bestand:
 *
 *   $PAGINA = [
 *       'titel' => 'Zijn leven',   // in het tabblad van de browser
 *       'kop'   => 'Zijn leven',   // de kop over de banner
 *       'groot' => false,          // true alleen op de hoofdpagina
 *   ];
 *   require __DIR__ . '/kop.php';
 */

if (!isset($PAGINA)) {
    $PAGINA = [];
}
$paginaTitel = isset($PAGINA['titel']) ? $PAGINA['titel'] : '';
$paginaKop   = isset($PAGINA['kop']) ? $PAGINA['kop'] : $paginaTitel;
$paginaGroot = !empty($PAGINA['groot']);
$paginaOmschrijving = isset($PAGINA['omschrijving'])
    ? $PAGINA['omschrijving']
    : $INHOUD['omschrijving'];

// De hoofdpagina heeft de brede collage, de andere pagina's een
// rustiger foto in dezelfde sfeer.
$bannerNaam = $paginaGroot ? 'header' : 'header-pagina';
$banner     = banner_url($bannerNaam);

// Staat de tweede banner er niet, dan gebruiken we gewoon de eerste.
if ($banner === '') {
    $bannerNaam = 'header';
    $banner     = banner_url($bannerNaam);
}

$bannerAlt = $paginaGroot || $bannerNaam === 'header'
    ? $INHOUD['banner_alt']
    : $INHOUD['banner_alt_pagina'];

// Zonder banner blijft het gewoon een groen vlak. Met banner is hij groot
// op de hoofdpagina en een smalle band op de andere pagina's.
$heroKlasse = 'hero';
if ($banner !== '') {
    $heroKlasse .= $paginaGroot ? ' hero-groot' : ' hero-klein';
}

// Welk bestand staat de bezoeker nu op? Daarmee zetten we het menu goed.
$huidigeBestand = basename($_SERVER['SCRIPT_NAME']);

$volledigeTitel = $paginaTitel !== ''
    ? $paginaTitel . ' | ' . $INHOUD['naam']
    : $INHOUD['paginatitel'];
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($volledigeTitel) ?></title>
<meta name="description" content="<?= h($paginaOmschrijving) ?>">
<meta name="theme-color" content="#12402f">

<?php
// Het plaatje en de tekst die verschijnen als iemand de link deelt,
// bijvoorbeeld in WhatsApp. Het adres moet volledig zijn, met domein.
$veilig = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$basisAdres = ($veilig ? 'https://' : 'http://')
    . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost')
    . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= h($INHOUD['naam']) ?>">
<meta property="og:title" content="<?= h($volledigeTitel) ?>">
<meta property="og:description" content="<?= h($paginaOmschrijving) ?>">
<meta property="og:image" content="<?= h($basisAdres) ?>/media/deel.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= h($INHOUD['banner_alt']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($volledigeTitel) ?>">
<meta name="twitter:description" content="<?= h($paginaOmschrijving) ?>">
<meta name="twitter:image" content="<?= h($basisAdres) ?>/media/deel.jpg">

<link rel="icon" href="assets/pwa/site-icon.png" type="image/png">
<link rel="apple-touch-icon" href="assets/pwa/site-apple-touch-icon.png">
<link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>

<a class="overslaan" href="#inhoud">Ga naar de inhoud</a>

<header class="balk">
    <div class="balk-binnen">
        <a class="balk-naam" href="index.php"><?= h($INHOUD['naam']) ?></a>

        <div class="balk-rechts">

            <!-- Het menu klapt uit als je erop klikt. -->
            <div class="menu-wikkel">
                <button type="button" class="menu-knop" id="menu-knop"
                        aria-expanded="false" aria-controls="menu-lijst"
                        aria-haspopup="true">
                    <span>Menu</span>
                    <svg class="menu-pijl" viewBox="0 0 24 24" aria-hidden="true"
                         focusable="false">
                        <path d="M6 9l6 6 6-6"></path>
                    </svg>
                </button>
                <nav class="menu-lijst" id="menu-lijst"
                     aria-label="Pagina's van deze website" hidden>
                    <?php foreach ($INHOUD['menu'] as $punt): ?>
                        <a href="<?= h($punt['bestand']) ?>"
                           <?= $punt['bestand'] === $huidigeBestand ? 'aria-current="page"' : '' ?>>
                            <?= h($punt['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <a class="knop knop-klein balk-cta" href="insturen.php">
                <span class="balk-cta-lang"><?= h($INHOUD['insturen_knop_balk']) ?></span>
                <span class="balk-cta-kort"><?= h($INHOUD['insturen_knop_kort']) ?></span>
            </a>
        </div>
    </div>
</header>

<main id="inhoud">

<!-- ---------------------------------------------------------------- -->
<!-- De banner bovenaan                                               -->
<!-- ---------------------------------------------------------------- -->
<section class="<?= h($heroKlasse) ?>" id="top">

    <?php if ($banner !== ''): ?>
        <div class="banner">
          <div class="banner-beeld">
            <picture>
                <?php if (banner_heeft_maten($bannerNaam)): ?>
                    <source type="image/webp"
                            srcset="media/<?= h($bannerNaam) ?>-800.webp 800w,
                                    media/<?= h($bannerNaam) ?>.webp 1519w"
                            sizes="(min-width: 1080px) 1048px, 100vw">
                    <img src="media/<?= h($bannerNaam) ?>.jpg"
                         srcset="media/<?= h($bannerNaam) ?>-800.jpg 800w,
                                 media/<?= h($bannerNaam) ?>.jpg 1519w"
                         sizes="(min-width: 1080px) 1048px, 100vw" width="1519" height="1035" fetchpriority="high"
                         alt="<?= h($bannerAlt) ?>">
                <?php else: ?>
                    <img src="<?= h($banner) ?>" fetchpriority="high"
                         alt="<?= h($bannerAlt) ?>">
                <?php endif; ?>
            </picture>
            <div class="banner-vervaging" aria-hidden="true"></div>
          </div>
        </div>
    <?php endif; ?>

    <div class="hero-binnen">
        <?php if ($paginaGroot): ?>
            <h1><?= h($INHOUD['naam']) ?></h1>
            <p class="datums"><?= h($INHOUD['datums']) ?></p>
        <?php else: ?>
            <p class="hero-boven"><?= h($INHOUD['naam']) ?></p>
            <h1><?= h($paginaKop) ?></h1>
        <?php endif; ?>
    </div>
</section>
