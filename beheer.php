<?php
/**
 * Het beheerpaneel voor de familie.
 *
 * Hier komt alles binnen wat mensen insturen. Niets staat automatisch op
 * de pagina: je zet per foto, per video en per verhaal zelf aan of het
 * zichtbaar mag zijn.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/inhoud.php';

start_sessie();
zorg_voor_mappen();

/* ------------------------------------------------------------------ */
/* Inloggen en uitloggen                                               */
/* ------------------------------------------------------------------ */

$loginfout = '';

// Iemand die het wachtwoord staat te raden, moet na een paar pogingen
// even wachten.
$wachten = inlog_wachttijd();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inloggen'])) {

    if ($wachten > 0) {
        $minuten = (int) ceil($wachten / 60);
        $loginfout = 'Te veel pogingen. Probeer het over '
            . $minuten . ' ' . ($minuten === 1 ? 'minuut' : 'minuten') . ' opnieuw.';
    } else {
        $ingevuld = (string) (isset($_POST['wachtwoord']) ? $_POST['wachtwoord'] : '');
        $rol = welke_rol($ingevuld);
        if ($rol !== '') {
            wis_mislukte_pogingen();
            session_regenerate_id(true);
            $_SESSION['beheer'] = true;
            $_SESSION['rol']    = $rol;
            $_SESSION['csrf']   = unieke_code(32);
            ga_naar('beheer.php');
        }

        noteer_mislukte_poging();
        $wachten = inlog_wachttijd();
        if ($wachten > 0) {
            $minuten = (int) ceil($wachten / 60);
            $loginfout = 'Dat wachtwoord klopt niet. Je hebt het te vaak geprobeerd, '
                . 'wacht ' . $minuten . ' ' . ($minuten === 1 ? 'minuut' : 'minuten') . '.';
        } else {
            $loginfout = 'Dat wachtwoord klopt niet.';
        }
        // Even wachten, zodat gokken sowieso traag wordt.
        usleep(400000);
    }
}

if (isset($_GET['uitloggen'])) {
    $_SESSION = [];
    session_destroy();
    ga_naar('beheer.php');
}

$ingelogd = !empty($_SESSION['beheer']);

/* ------------------------------------------------------------------ */
/* Het inlogscherm                                                     */
/* ------------------------------------------------------------------ */

if (!$ingelogd) {
    ?>
    <!doctype html>
    <html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Beheer</title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="manifest" href="beheer.webmanifest">
        <link rel="apple-touch-icon" href="assets/pwa/apple-touch-icon.png">
        <meta name="theme-color" content="#12402f">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Herinneringen">
        <link rel="stylesheet" href="assets/style.css?v=4">
    </head>
    <body class="beheer-body">
        <main class="inlog">
            <h1>Beheer</h1>
            <p class="hint">Deze pagina is voor de familie.</p>
            <?php if ($loginfout !== ''): ?>
                <div class="melding melding-fout" role="alert"><?= h($loginfout) ?></div>
            <?php endif; ?>
            <?php if ($wachten > 0): ?>
                <p class="hint">Het formulier komt vanzelf terug zodra de
                    wachttijd voorbij is.</p>
            <?php else: ?>
                <form method="post" action="beheer.php">
                    <div class="veld">
                        <label for="wachtwoord">Wachtwoord</label>
                        <input type="password" id="wachtwoord" name="wachtwoord"
                               autocomplete="current-password" required autofocus>
                    </div>
                    <button class="knop knop-groot" type="submit" name="inloggen" value="1">
                        Inloggen
                    </button>
                </form>
            <?php endif; ?>
            <p class="hint"><a href="index.php">Terug naar de pagina</a></p>
        </main>
        <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
        </script>
    </body>
    </html>
    <?php
    exit;
}

/* ------------------------------------------------------------------ */
/* Beheeracties                                                        */
/* ------------------------------------------------------------------ */

$gedaan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actie'])) {

    if (!csrf_klopt(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $gedaan = 'De actie is verlopen. Probeer het nog een keer.';
    } else {
        $actie       = (string) $_POST['actie'];
        $inzendingId = (string) (isset($_POST['inzending']) ? $_POST['inzending'] : '');
        $bestandId   = (string) (isset($_POST['bestand']) ? $_POST['bestand'] : '');

        if ($actie === 'bestand') {
            // Eén foto of video zichtbaar maken of juist weer verbergen.
            muteer_data(function ($data) use ($inzendingId, $bestandId, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    foreach ($inzending['bestanden'] as $j => $bestand) {
                        if ($bestand['id'] !== $bestandId) {
                            continue;
                        }
                        $nieuw = empty($bestand['zichtbaar']);
                        $data['inzendingen'][$i]['bestanden'][$j]['zichtbaar'] = $nieuw;
                        $gedaan = $nieuw
                            ? 'Dit bestand staat nu op de pagina.'
                            : 'Dit bestand is van de pagina gehaald.';
                    }
                }
                return $data;
            });

        } elseif ($actie === 'tekst') {
            // Het geschreven verhaal zichtbaar maken of verbergen.
            muteer_data(function ($data) use ($inzendingId, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    $nieuw = empty($inzending['tekst_zichtbaar']);
                    $data['inzendingen'][$i]['tekst_zichtbaar'] = $nieuw;
                    $gedaan = $nieuw
                        ? 'De herinnering staat nu op de pagina.'
                        : 'De herinnering is van de pagina gehaald.';
                }
                return $data;
            });

        } elseif ($actie === 'alles_aan' || $actie === 'alles_uit') {
            // Alles van deze inzending in één keer, handig bij een grote
            // verzameling foto's.
            $aan = ($actie === 'alles_aan');
            muteer_data(function ($data) use ($inzendingId, $aan, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    foreach ($inzending['bestanden'] as $j => $bestand) {
                        $data['inzendingen'][$i]['bestanden'][$j]['zichtbaar'] = $aan;
                    }
                    $aantal = count($inzending['bestanden']);
                    $gedaan = $aan
                        ? $aantal . ' bestanden staan nu op de pagina.'
                        : $aantal . ' bestanden zijn van de pagina gehaald.';
                }
                return $data;
            });

        } elseif ($actie === 'weggooien' && $bestandId !== '') {
            // Eén foto of video naar de prullenbak. Het bestand blijft
            // gewoon staan, het wordt alleen niet meer getoond.
            muteer_data(function ($data) use ($inzendingId, $bestandId, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    foreach ($inzending['bestanden'] as $j => $bestand) {
                        if ($bestand['id'] !== $bestandId) {
                            continue;
                        }
                        $data['inzendingen'][$i]['bestanden'][$j]['weg']       = true;
                        $data['inzendingen'][$i]['bestanden'][$j]['weg_op']    = time();
                        $data['inzendingen'][$i]['bestanden'][$j]['zichtbaar'] = false;
                        $gedaan = 'Weggegooid. Je vindt het terug in de prullenbak.';
                    }
                }
                return $data;
            });

        } elseif ($actie === 'terugzetten' && $bestandId !== '') {
            muteer_data(function ($data) use ($inzendingId, $bestandId, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    foreach ($inzending['bestanden'] as $j => $bestand) {
                        if ($bestand['id'] !== $bestandId) {
                            continue;
                        }
                        unset($data['inzendingen'][$i]['bestanden'][$j]['weg']);
                        unset($data['inzendingen'][$i]['bestanden'][$j]['weg_op']);
                        $gedaan = 'Teruggezet. Hij staat weer bij de inzending, '
                            . 'nog niet op de pagina.';
                    }
                }
                return $data;
            });

        } elseif ($actie === 'inzending_weg') {
            // Een hele inzending naar de prullenbak.
            muteer_data(function ($data) use ($inzendingId, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    $data['inzendingen'][$i]['weg']             = true;
                    $data['inzendingen'][$i]['weg_op']          = time();
                    $data['inzendingen'][$i]['tekst_zichtbaar'] = false;
                    foreach ($inzending['bestanden'] as $j => $bestand) {
                        $data['inzendingen'][$i]['bestanden'][$j]['zichtbaar'] = false;
                    }
                    $gedaan = 'De inzending ligt in de prullenbak.';
                }
                return $data;
            });

        } elseif ($actie === 'inzending_terug') {
            muteer_data(function ($data) use ($inzendingId, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    unset($data['inzendingen'][$i]['weg']);
                    unset($data['inzendingen'][$i]['weg_op']);
                    $gedaan = 'De inzending is teruggezet.';
                }
                return $data;
            });

        } elseif ($actie === 'bewerken') {
            // De naam, de relatie en de tekst van een inzending aanpassen.
            // Handig als iemand zijn naam vergeten is of verkeerd typte.
            $nieuweNaam    = trim((string) (isset($_POST['naam']) ? $_POST['naam'] : ''));
            $nieuweRelatie = trim((string) (isset($_POST['relatie']) ? $_POST['relatie'] : ''));
            $nieuweTekst   = trim((string) (isset($_POST['tekst']) ? $_POST['tekst'] : ''));

            if ($nieuweNaam === '') {
                $gedaan = 'Er moet wel een naam blijven staan.';
            } else {
                muteer_data(function ($data) use ($inzendingId, $nieuweNaam,
                        $nieuweRelatie, $nieuweTekst, &$gedaan) {
                    foreach ($data['inzendingen'] as $i => $inzending) {
                        if ($inzending['id'] !== $inzendingId) {
                            continue;
                        }
                        $data['inzendingen'][$i]['naam']    = mb_substr($nieuweNaam, 0, 80);
                        $data['inzendingen'][$i]['relatie'] = mb_substr($nieuweRelatie, 0, 120);
                        $data['inzendingen'][$i]['tekst']   = mb_substr($nieuweTekst, 0, MAX_TEKST_TEKENS);
                        // Staat er nu een naam, dan mag die ook onder de
                        // foto's komen te staan.
                        $data['inzendingen'][$i]['toon_inzender'] = true;
                        $gedaan = 'De gegevens zijn aangepast.';
                    }
                    return $data;
                });
            }

        } elseif ($actie === 'voorgoed_bestand' && $bestandId !== '') {
            // Eén bestand voorgoed van de schijf. Alleen de beheerder,
            // en alleen vanuit de prullenbak.
            if (!mag_verwijderen()) {
                $_SESSION['beheer_melding'] = 'Alleen de beheerder kan iets '
                    . 'voorgoed verwijderen.';
                ga_naar('beheer.php?filter=prullenbak');
            }
            muteer_data(function ($data) use ($inzendingId, $bestandId, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    foreach ($inzending['bestanden'] as $j => $bestand) {
                        if ($bestand['id'] !== $bestandId) {
                            continue;
                        }
                        verwijder_bestanden_van_schijf($bestand);
                        array_splice($data['inzendingen'][$i]['bestanden'], $j, 1);
                        $gedaan = 'Voorgoed verwijderd.';
                        break;
                    }
                }
                return $data;
            });

        } elseif ($actie === 'verwijderen') {
            if (!mag_verwijderen()) {
                $gedaan = 'Alleen de beheerder kan een inzending voorgoed '
                    . 'verwijderen. Je kunt hem wel van de pagina halen.';
                $_SESSION['beheer_melding'] = $gedaan;
                ga_naar('beheer.php?filter=' . urlencode(
                    isset($_POST['filter']) ? (string) $_POST['filter'] : 'alles'));
            }
            // Hele inzending weg, ook de bestanden op de schijf.
            muteer_data(function ($data) use ($inzendingId, &$gedaan) {
                foreach ($data['inzendingen'] as $i => $inzending) {
                    if ($inzending['id'] !== $inzendingId) {
                        continue;
                    }
                    foreach ($inzending['bestanden'] as $bestand) {
                        verwijder_bestanden_van_schijf($bestand);
                    }
                    array_splice($data['inzendingen'], $i, 1);
                    $gedaan = 'De inzending is verwijderd.';
                    break;
                }
                return $data;
            });
        }
    }

    // Ook hier: opslaan, dan doorsturen, zodat vernieuwen niets herhaalt.
    $_SESSION['beheer_melding'] = $gedaan;
    $filter = isset($_POST['filter']) ? (string) $_POST['filter'] : 'alles';
    ga_naar('beheer.php?filter=' . urlencode($filter));
}

$gedaan = isset($_SESSION['beheer_melding']) ? $_SESSION['beheer_melding'] : '';
unset($_SESSION['beheer_melding']);

/* ------------------------------------------------------------------ */
/* Overzicht opbouwen                                                  */
/* ------------------------------------------------------------------ */

$data   = lees_data();
$filter = isset($_GET['filter']) ? (string) $_GET['filter'] : 'alles';
if (!in_array($filter, ['alles', 'wacht', 'zichtbaar', 'prullenbak'], true)) {
    $filter = 'alles';
}
// De prullenbak is alleen voor de beheerder.
if ($filter === 'prullenbak' && !mag_verwijderen()) {
    $filter = 'alles';
}

$isBeheerder = (huidige_rol() === 'beheerder');
$prullenbak  = aantal_in_prullenbak($data);

// Meldingen op de telefoon. Standaard alleen voor de beheerder; met de
// schakelaar MELDINGEN_VOOR_FAMILIE in config.php krijgt de familie ze ook.
$magMeldingen = push_mogelijk()
    && ($isBeheerder || (defined('MELDINGEN_VOOR_FAMILIE') && MELDINGEN_VOOR_FAMILIE));
$pushSleutel    = $magMeldingen ? push_publieke_sleutel() : '';
$pushToestellen = $magMeldingen ? push_aantal_toestellen(huidige_rol()) : 0;

// Waar de pagina nu staat, zodat we kunnen zien of er iets bijkomt
// terwijl het dashboard openstaat.
$nuWachtend = wachtende_onderdelen($data, huidige_rol());
$nuLaatste  = laatste_inzending_tijd($data, huidige_rol());

// Bij een grote verzameling tonen we niet alles in één keer.
const BEHEER_EERSTE = 24;
$openId = isset($_GET['alles']) ? (string) $_GET['alles'] : '';

$inzendingen = $data['inzendingen'];
usort($inzendingen, function ($a, $b) {
    return $b['datum'] - $a['datum'];
});

/** Telt hoeveel onderdelen van een inzending nog wachten op beoordeling. */
function aantal_wachtend(array $inzending)
{
    $aantal = 0;
    foreach ($inzending['bestanden'] as $bestand) {
        if (!empty($bestand['weg'])) {
            continue;   // ligt in de prullenbak, telt niet mee
        }
        if (empty($bestand['zichtbaar'])) {
            $aantal++;
        }
    }
    if (trim($inzending['tekst']) !== '' && empty($inzending['tekst_zichtbaar'])) {
        $aantal++;
    }
    return $aantal;
}

/** Telt hoeveel onderdelen van een inzending op de pagina staan. */
function aantal_zichtbaar(array $inzending)
{
    $aantal = 0;
    foreach ($inzending['bestanden'] as $bestand) {
        if (!empty($bestand['weg'])) {
            continue;
        }
        if (!empty($bestand['zichtbaar'])) {
            $aantal++;
        }
    }
    if (trim($inzending['tekst']) !== '' && !empty($inzending['tekst_zichtbaar'])) {
        $aantal++;
    }
    return $aantal;
}

// Voor de knoppen bovenaan tellen we inzendingen, niet losse bestanden,
// want daaronder staat ook een lijst met inzendingen.
$totaalWacht     = 0;
$totaalZichtbaar = 0;
foreach ($inzendingen as $inzending) {
    if (!mag_zien($inzending) || !empty($inzending['weg'])) {
        continue;
    }
    if (aantal_wachtend($inzending) > 0) {
        $totaalWacht++;
    }
    if (aantal_zichtbaar($inzending) > 0) {
        $totaalZichtbaar++;
    }
}

// De cijfers voor het overzicht bovenaan.
$telFotos    = 0;
$telVideos   = 0;
$telVerhalen = 0;
$telZichtbaar = 0;
$laatste     = 0;
foreach ($inzendingen as $inzending) {
    if (!mag_zien($inzending) || !empty($inzending['weg'])) {
        continue;
    }
    foreach ($inzending['bestanden'] as $bestand) {
        if (!empty($bestand['weg'])) {
            continue;
        }
        if ($bestand['soort'] === 'video') {
            $telVideos++;
        } else {
            $telFotos++;
        }
        if (!empty($bestand['zichtbaar'])) {
            $telZichtbaar++;
        }
    }
    if (trim($inzending['tekst']) !== '') {
        $telVerhalen++;
    }
    if ($inzending['datum'] > $laatste) {
        $laatste = $inzending['datum'];
    }
}

$getoond = [];
foreach ($inzendingen as $inzending) {
    // De familie beoordeelt alleen de eigen verzameling. Wat bezoekers
    // insturen gaat langs de beheerder.
    if (!mag_zien($inzending)) {
        continue;
    }
    if ($filter === 'prullenbak') {
        // Alleen inzendingen met iets in de prullenbak.
        $ietsWeg = !empty($inzending['weg']);
        foreach ($inzending['bestanden'] as $bestand) {
            if (!empty($bestand['weg'])) {
                $ietsWeg = true;
            }
        }
        if (!$ietsWeg) {
            continue;
        }
        $getoond[] = $inzending;
        continue;
    }
    // Buiten de prullenbak tonen we niets dat helemaal weggegooid is.
    if (!empty($inzending['weg'])) {
        continue;
    }
    if ($filter === 'wacht' && aantal_wachtend($inzending) === 0) {
        continue;
    }
    if ($filter === 'zichtbaar' && aantal_zichtbaar($inzending) === 0) {
        continue;
    }
    $getoond[] = $inzending;
}

$token = csrf_token();
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Beheer</title>
<meta name="robots" content="noindex, nofollow">
    <link rel="manifest" href="beheer.webmanifest">
    <link rel="apple-touch-icon" href="assets/pwa/apple-touch-icon.png">
    <meta name="theme-color" content="#12402f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Herinneringen">
<link rel="stylesheet" href="assets/style.css?v=4">
</head>
<body class="beheer-body">

<!-- Schuift van boven in als er iets binnenkomt terwijl je het
     dashboard open hebt staan. -->
<div class="meld-balk" id="meld-balk" role="status" aria-live="polite"
     data-wacht="<?= (int) $nuWachtend ?>" data-laatste="<?= (int) $nuLaatste ?>">
    <div class="meld-balk-binnen">
        <svg class="meld-balk-teken" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M18 8a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7"></path>
            <path d="M10.5 20a1.8 1.8 0 0 0 3 0"></path>
        </svg>
        <span class="meld-balk-tekst">
            <span class="meld-balk-titel" id="meld-balk-titel">Nieuw</span>
            <span class="meld-balk-regel" id="meld-balk-regel"></span>
        </span>
        <a href="beheer.php?filter=wacht" id="meld-balk-link">Bekijken</a>
        <button type="button" class="meld-balk-sluit" id="meld-balk-sluit"
                aria-label="Deze melding sluiten">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M6 6l12 12M18 6L6 18"></path>
            </svg>
        </button>
    </div>
</div>


<header class="beheer-balk">
    <div class="binnen beheer-balk-binnen">
        <span class="beheer-titel">Beheer</span>
        <nav class="beheer-links">
            <span class="beheer-wie">Ingelogd als <?= h(rol_naam(huidige_rol())) ?></span>
            <a href="index.php">Bekijk de pagina</a>
            <a href="beheer.php?uitloggen=1">Uitloggen</a>
        </nav>
    </div>
</header>

<main class="binnen beheer-hoofd">

    <p class="beheer-uitleg">
        <?php if ($isBeheerder): ?>
            Alles wat hier binnenkomt staat nog niet op de pagina.
            Zet per foto, per video en per verhaal zelf aan wat zichtbaar
            mag zijn.
        <?php else: ?>
            Hier staan de foto's en video's van de familie. Zet zelf aan
            wat op de pagina mag komen. Niets is definitief: je kunt alles
            weer weghalen.
        <?php endif; ?>
    </p>

    <!-- Het overzicht in cijfers -->
    <ul class="cijfers">
        <?php if ($isBeheerder): ?>
            <li>
                <span class="cijfer"><?= count($inzendingen) ?></span>
                <span class="cijfer-bij">inzendingen</span>
            </li>
            <li class="<?= $totaalWacht > 0 ? 'cijfer-let-op' : '' ?>">
                <span class="cijfer"><?= (int) $totaalWacht ?></span>
                <span class="cijfer-bij">wacht op beoordeling</span>
            </li>
        <?php endif; ?>
        <li>
            <span class="cijfer"><?= (int) $telZichtbaar ?></span>
            <span class="cijfer-bij">op de pagina</span>
        </li>
        <li>
            <span class="cijfer"><?= (int) $telFotos ?></span>
            <span class="cijfer-bij">foto's</span>
        </li>
        <li>
            <span class="cijfer"><?= (int) $telVideos ?></span>
            <span class="cijfer-bij">video's</span>
        </li>
        <li>
            <span class="cijfer"><?= (int) $telVerhalen ?></span>
            <span class="cijfer-bij">verhalen</span>
        </li>
    </ul>

    <?php if ($laatste > 0 && $isBeheerder): ?>
        <p class="beheer-laatste">Laatste inzending:
            <?= h(nederlandse_datum($laatste)) ?>.</p>
    <?php endif; ?>

    <?php if ($gedaan !== ''): ?>
        <div class="melding melding-goed" role="status"><?= h($gedaan) ?></div>
    <?php endif; ?>

    <!-- Uitleg om het dashboard als app op je telefoon te zetten.
         Verdwijnt vanzelf als je hem al als app gebruikt, en blijft weg
         zodra je hem wegklikt. -->
    <div class="app-kaart" id="app-kaart" hidden>
        <button type="button" class="app-kaart-sluit" id="app-kaart-sluit"
                aria-label="Deze uitleg sluiten">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M6 6l12 12M18 6L6 18"></path>
            </svg>
        </button>
        <h2>Zet het dashboard op je telefoon</h2>
        <p>Dan open je het voortaan als app, zonder eerst naar de website
            te hoeven.</p>

        <button type="button" class="knop" id="app-installeer" hidden>
            Installeer als app
        </button>

        <div class="app-stappen" id="app-stappen-iphone" hidden>
            <p><strong>Op een iPhone of iPad</strong> (in Safari):</p>
            <ol>
                <li>Tik onderaan op de deelknop
                    <svg class="app-teken" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 15V4m0 0L8 8m4-4 4 4"></path>
                        <path d="M4 12v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7"></path>
                    </svg></li>
                <li>Kies <strong>Zet op beginscherm</strong></li>
                <li>Tik op <strong>Voeg toe</strong></li>
            </ol>
        </div>

        <div class="app-stappen" id="app-stappen-android" hidden>
            <p><strong>Op een Android-telefoon</strong> (in Chrome):</p>
            <ol>
                <li>Tik rechtsboven op de drie puntjes</li>
                <li>Kies <strong>App installeren</strong> of
                    <strong>Toevoegen aan startscherm</strong></li>
            </ol>
        </div>
    </div>

    <?php if ($magMeldingen): ?>
    <!-- Meldingen op de telefoon aanzetten. Wat hier gebeurt staat
         helemaal onderaan deze pagina in het script uitgelegd. -->
    <div class="meld-kaart" id="meld-kaart"
         data-sleutel="<?= h($pushSleutel) ?>"
         data-csrf="<?= h(csrf_token()) ?>"
         data-toestellen="<?= (int) $pushToestellen ?>" hidden>
        <div class="meld-kop">
            <svg class="meld-teken" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M18 8a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7"></path>
                <path d="M10.5 20a1.8 1.8 0 0 0 3 0"></path>
            </svg>
            <h2>Meldingen op je telefoon</h2>
        </div>

        <p id="meld-uitleg">Krijg een berichtje zodra er iets wordt
            ingestuurd, ook als het dashboard dichtstaat. En eens per dag
            een herinnering zolang er nog iets op beoordeling wacht.</p>

        <div class="meld-knoppen">
            <button type="button" class="knop" id="meld-aan" hidden>
                Meldingen aanzetten
            </button>
            <button type="button" class="knop knop-stil" id="meld-uit" hidden>
                Uitzetten op dit toestel
            </button>
            <button type="button" class="knop knop-stil knop-klein" id="meld-proef" hidden>
                Probeer er een
            </button>
            <span class="meld-stand" id="meld-stand" hidden>Staat uit</span>
        </div>

        <!-- Op een iPhone werkt dit alleen als het dashboard eerst als
             app op het beginscherm staat. Dat is een regel van Apple. -->
        <div class="meld-hulp" id="meld-iphone" hidden>
            <p><strong>Op een iPhone of iPad</strong> kan dit pas als het
                dashboard als app op je beginscherm staat:</p>
            <ol>
                <li>Tik onderaan in Safari op de deelknop
                    <svg class="app-teken" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 15V4m0 0L8 8m4-4 4 4"></path>
                        <path d="M4 12v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7"></path>
                    </svg></li>
                <li>Kies <strong>Zet op beginscherm</strong></li>
                <li>Open het dashboard vanaf je beginscherm en zet de
                    meldingen daar aan</li>
            </ol>
        </div>

        <div class="meld-hulp" id="meld-geweigerd" hidden>
            <p>De meldingen staan geblokkeerd voor deze website. Dat kun
                je alleen zelf terugzetten, in de instellingen van je
                telefoon of browser bij <strong>Meldingen</strong>.</p>
        </div>

        <div class="meld-hulp" id="meld-kan-niet" hidden>
            <p>Deze browser kan geen meldingen tonen. Probeer het op je
                telefoon, of gebruik Chrome, Edge of Safari.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="filters" role="group" aria-label="Filter">
        <a class="filter <?= $filter === 'alles' ? 'filter-aan' : '' ?>"
           href="beheer.php?filter=alles">Alles</a>
        <?php if ($isBeheerder): ?>
            <a class="filter <?= $filter === 'wacht' ? 'filter-aan' : '' ?>"
               href="beheer.php?filter=wacht">Wacht op beoordeling
                <span><?= (int) $totaalWacht ?></span></a>
        <?php endif; ?>
        <a class="filter <?= $filter === 'zichtbaar' ? 'filter-aan' : '' ?>"
           href="beheer.php?filter=zichtbaar">Op de pagina
            <span><?= (int) $telZichtbaar ?></span></a>
        <?php if ($isBeheerder): ?>
            <a class="filter <?= $filter === 'prullenbak' ? 'filter-aan' : '' ?>"
               href="beheer.php?filter=prullenbak">Prullenbak
                <span><?= (int) $prullenbak ?></span></a>
        <?php endif; ?>
    </div>

    <?php if ($filter === 'prullenbak'): ?>
        <p class="prullenbak-uitleg">
            Wat je weggooit komt hier terecht en staat niet meer op de
            pagina, maar is nog niet verdwenen. Zet het terug als je je
            vergist hebt. Alleen hier kun je iets voorgoed verwijderen.
        </p>
    <?php endif; ?>

    <?php if (!$getoond): ?>
        <p class="leeg">
            <?php if ($filter === 'prullenbak'): ?>
                De prullenbak is leeg.
            <?php elseif ($filter === 'wacht'): ?>
                Er wacht niets meer op beoordeling.
            <?php elseif ($filter === 'zichtbaar'): ?>
                Er staat nog niets op de pagina.
            <?php else: ?>
                Er is nog niets ingestuurd.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php foreach ($getoond as $inzending): ?>
        <article class="inzending" id="i<?= h($inzending['id']) ?>">

            <header class="inzending-kop">
                <div>
                    <h2><?= h($inzending['naam']) ?></h2>
                    <?php if (!empty($inzending['relatie'])): ?>
                        <p class="inzending-relatie"><?= h($inzending['relatie']) ?></p>
                    <?php endif; ?>
                    <p class="inzending-meta">
                        <?= h(nederlandse_datum($inzending['datum'])) ?>
                        <?php if (!empty($inzending['email'])): ?>
                            &middot; <a href="mailto:<?= h($inzending['email']) ?>"><?= h($inzending['email']) ?></a>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="inzending-telling">
                    <?php $wacht = aantal_wachtend($inzending); ?>
                    <?php if ($wacht > 0): ?>
                        <span class="merk merk-wacht"><?= (int) $wacht ?> wacht</span>
                    <?php endif; ?>
                    <?php $zicht = aantal_zichtbaar($inzending); ?>
                    <?php if ($zicht > 0): ?>
                        <span class="merk merk-aan"><?= (int) $zicht ?> zichtbaar</span>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (trim($inzending['tekst']) !== ''): ?>
                <div class="inzending-tekst <?= !empty($inzending['tekst_zichtbaar']) ? 'aan' : '' ?>">
                    <p><?= nl2br(h($inzending['tekst'])) ?></p>
                    <form method="post" action="beheer.php">
                        <input type="hidden" name="csrf" value="<?= h($token) ?>">
                        <input type="hidden" name="filter" value="<?= h($filter) ?>">
                        <input type="hidden" name="inzending" value="<?= h($inzending['id']) ?>">
                        <button class="knop knop-klein <?= !empty($inzending['tekst_zichtbaar']) ? 'knop-uit' : '' ?>"
                                type="submit" name="actie" value="tekst">
                            <?= !empty($inzending['tekst_zichtbaar'])
                                ? 'Herinnering van de pagina halen'
                                : 'Herinnering op de pagina zetten' ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Naam, relatie en tekst aanpassen. Handig als iemand zijn
                 naam vergeten is, of als de familie erbij wil zetten van
                 wie een foto komt. -->
            <details class="bewerken">
                <summary>Naam en tekst aanpassen</summary>
                <form method="post" action="beheer.php" class="bewerkform">
                    <input type="hidden" name="csrf" value="<?= h($token) ?>">
                    <input type="hidden" name="filter" value="<?= h($filter) ?>">
                    <input type="hidden" name="inzending" value="<?= h($inzending['id']) ?>">

                    <div class="veld">
                        <label for="naam-<?= h($inzending['id']) ?>">Naam, zoals hij onder
                            de foto's komt te staan</label>
                        <input type="text" id="naam-<?= h($inzending['id']) ?>"
                               name="naam" maxlength="80" required
                               value="<?= h($inzending['naam']) ?>">
                    </div>

                    <div class="veld">
                        <label for="relatie-<?= h($inzending['id']) ?>">Hoe kende hij of
                            zij hem</label>
                        <input type="text" id="relatie-<?= h($inzending['id']) ?>"
                               name="relatie" maxlength="120"
                               value="<?= h(isset($inzending['relatie']) ? $inzending['relatie'] : '') ?>">
                    </div>

                    <div class="veld">
                        <label for="tekst-<?= h($inzending['id']) ?>">De herinnering</label>
                        <textarea id="tekst-<?= h($inzending['id']) ?>" name="tekst"
                                  rows="4" maxlength="<?= (int) MAX_TEKST_TEKENS ?>"><?= h($inzending['tekst']) ?></textarea>
                    </div>

                    <button class="knop knop-klein" type="submit"
                            name="actie" value="bewerken">Opslaan</button>
                </form>
            </details>

            <?php if (!empty($inzending['bestanden'])): ?>
                <?php
                // In de prullenbak tonen we juist alleen het weggegooide,
                // daarbuiten juist alleen wat er nog is.
                $alle = [];
                foreach ($inzending['bestanden'] as $bestand) {
                    $ligtWeg = !empty($bestand['weg']) || !empty($inzending['weg']);
                    if ($filter === 'prullenbak' ? $ligtWeg : !$ligtWeg) {
                        $alle[] = $bestand;
                    }
                }
                $helemaal  = ($openId === $inzending['id']);
                $tonen     = $helemaal ? $alle : array_slice($alle, 0, BEHEER_EERSTE);
                $rest      = count($alle) - count($tonen);
                ?>

                <?php if (count($alle) > 1): ?>
                    <div class="alles-knoppen">
                        <form method="post" action="beheer.php">
                            <input type="hidden" name="csrf" value="<?= h($token) ?>">
                            <input type="hidden" name="filter" value="<?= h($filter) ?>">
                            <input type="hidden" name="inzending" value="<?= h($inzending['id']) ?>">
                            <button class="knop knop-klein" type="submit"
                                    name="actie" value="alles_aan">Alles op de pagina</button>
                            <button class="knop knop-klein knop-stil" type="submit"
                                    name="actie" value="alles_uit">Alles eraf</button>
                        </form>
                        <span class="alles-aantal"><?= count($alle) ?> bestanden</span>
                    </div>
                <?php endif; ?>

                <ul class="beheer-raster">
                    <?php foreach ($tonen as $bestand): ?>
                        <li class="beheer-item <?= !empty($bestand['zichtbaar']) ? 'aan' : '' ?> <?= (!empty($bestand['weg']) || !empty($inzending['weg'])) ? 'weg' : '' ?>">

                            <?php if ($bestand['soort'] === 'foto'): ?>
                                <a href="<?= h(bestand_url($bestand)) ?>" target="_blank"
                                   rel="noopener">
                                    <img src="<?= h(bestand_url($bestand, true)) ?>" loading="lazy"
                                         alt="Ingestuurd door <?= h($inzending['naam']) ?>">
                                </a>
                            <?php else: ?>
                                <video controls preload="metadata"
                                       src="<?= h(bestand_url($bestand)) ?>"></video>
                            <?php endif; ?>

                            <p class="beheer-item-meta">
                                <?= $bestand['soort'] === 'video' ? 'Video' : 'Foto' ?>,
                                <?= h(leesbare_grootte($bestand['bytes'])) ?>
                                <?php if (!empty($bestand['origineel'])): ?>
                                    <br><span class="beheer-orig"><?= h($bestand['origineel']) ?></span>
                                <?php endif; ?>
                            </p>

                            <form method="post" action="beheer.php" class="beheer-knoppen">
                                <input type="hidden" name="csrf" value="<?= h($token) ?>">
                                <input type="hidden" name="filter" value="<?= h($filter) ?>">
                                <input type="hidden" name="inzending" value="<?= h($inzending['id']) ?>">
                                <input type="hidden" name="bestand" value="<?= h($bestand['id']) ?>">

                                <?php if ($filter === 'prullenbak'): ?>
                                    <button class="knop knop-klein" type="submit"
                                            name="actie" value="terugzetten">Terugzetten</button>
                                    <?php if (mag_verwijderen()): ?>
                                        <button class="knop knop-klein knop-gevaar" type="submit"
                                                name="actie" value="voorgoed_bestand"
                                                onclick="return confirm('Deze foto of video voorgoed verwijderen? Dit kan niet ongedaan gemaakt worden.');">
                                            Voorgoed weg
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="knop knop-klein <?= !empty($bestand['zichtbaar']) ? 'knop-uit' : '' ?>"
                                            type="submit" name="actie" value="bestand">
                                        <?= !empty($bestand['zichtbaar'])
                                            ? 'Van de pagina halen'
                                            : 'Op de pagina zetten' ?>
                                    </button>
                                    <button class="knop knop-klein knop-stil" type="submit"
                                            name="actie" value="weggooien">Weggooien</button>
                                <?php endif; ?>

                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($rest > 0): ?>
                    <p class="meer-tonen">
                        <a class="knop knop-klein knop-stil"
                           href="beheer.php?filter=<?= h($filter) ?>&amp;alles=<?= h($inzending['id']) ?>#i<?= h($inzending['id']) ?>">
                            Toon alle <?= count($alle) ?> bestanden
                        </a>
                    </p>
                <?php elseif ($helemaal && count($alle) > BEHEER_EERSTE): ?>
                    <p class="meer-tonen">
                        <a class="knop knop-klein knop-stil"
                           href="beheer.php?filter=<?= h($filter) ?>#i<?= h($inzending['id']) ?>">
                            Toon er minder
                        </a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($filter === 'prullenbak' && !empty($inzending['weg'])): ?>
                <form method="post" action="beheer.php" class="verwijderform">
                    <input type="hidden" name="csrf" value="<?= h($token) ?>">
                    <input type="hidden" name="filter" value="<?= h($filter) ?>">
                    <input type="hidden" name="inzending" value="<?= h($inzending['id']) ?>">
                    <button class="knop knop-klein" type="submit"
                            name="actie" value="inzending_terug">Hele inzending terugzetten</button>
                </form>
            <?php elseif ($filter !== 'prullenbak' && $isBeheerder): ?>
                <form method="post" action="beheer.php" class="verwijderform">
                    <input type="hidden" name="csrf" value="<?= h($token) ?>">
                    <input type="hidden" name="filter" value="<?= h($filter) ?>">
                    <input type="hidden" name="inzending" value="<?= h($inzending['id']) ?>">
                    <button class="knop knop-klein knop-stil" type="submit"
                            name="actie" value="inzending_weg">Hele inzending weggooien</button>
                </form>
            <?php endif; ?>

            <?php if ($filter === 'prullenbak' && mag_verwijderen()): ?>
                <form method="post" action="beheer.php" class="verwijderform"
                      onsubmit="return confirm('Deze inzending voorgoed verwijderen, met alle foto\'s en video\'s erin? Dit kan niet ongedaan gemaakt worden.');">
                    <input type="hidden" name="csrf" value="<?= h($token) ?>">
                    <input type="hidden" name="filter" value="<?= h($filter) ?>">
                    <input type="hidden" name="inzending" value="<?= h($inzending['id']) ?>">
                    <button class="knop knop-klein knop-gevaar" type="submit"
                            name="actie" value="verwijderen">Voorgoed verwijderen, met alle bestanden</button>
                </form>
            <?php endif; ?>

        </article>
    <?php endforeach; ?>

</main>

<script>
(function () {
    'use strict';

    /* De service worker maakt het dashboard installeerbaar als app. */
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js');
    }

    var kaart = document.getElementById('app-kaart');
    if (!kaart) { return; }

    var alsApp = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    var weggeklikt = false;
    try {
        weggeklikt = localStorage.getItem('app-kaart-weg') === '1';
    } catch (e) { /* prive-modus, dan tonen we hem gewoon */ }

    /* Al als app geopend, of eerder weggeklikt? Dan niets tonen. */
    if (alsApp || weggeklikt) { return; }

    var isIphone  = /iPhone|iPad|iPod/i.test(navigator.userAgent);
    var isAndroid = /Android/i.test(navigator.userAgent);

    /* Op Android geeft de browser soms een echte installeerknop. */
    var installeerKnop = document.getElementById('app-installeer');
    var bewaardePrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        bewaardePrompt = e;
        installeerKnop.hidden = false;
        document.getElementById('app-stappen-android').hidden = true;
    });
    installeerKnop.addEventListener('click', function () {
        if (!bewaardePrompt) { return; }
        bewaardePrompt.prompt();
        bewaardePrompt = null;
        installeerKnop.hidden = true;
    });

    if (isIphone) {
        document.getElementById('app-stappen-iphone').hidden = false;
    } else if (isAndroid) {
        document.getElementById('app-stappen-android').hidden = false;
    } else {
        /* Op een computer beide korte uitleggen tonen. */
        document.getElementById('app-stappen-iphone').hidden = false;
        document.getElementById('app-stappen-android').hidden = false;
    }
    kaart.hidden = false;

    document.getElementById('app-kaart-sluit').addEventListener('click', function () {
        kaart.hidden = true;
        try { localStorage.setItem('app-kaart-weg', '1'); } catch (e) {}
    });
}());

/* ------------------------------------------------------------------
   Meldingen op je telefoon aan- en uitzetten
   ------------------------------------------------------------------
   Kort wat hier gebeurt: de browser maakt een eigen postadres aan waar
   meldingen naartoe mogen, samen met twee sleutels. Dat adres en die
   sleutels geven we door aan de website, die bewaart ze. Later stuurt de
   website daar een versleuteld berichtje naartoe. Het adres hoort bij
   deze ene telefoon, dus je zet de meldingen per toestel aan.
   ------------------------------------------------------------------ */
(function () {
    'use strict';

    var kaart = document.getElementById('meld-kaart');
    if (!kaart) { return; }
    kaart.hidden = false;

    var sleutel   = kaart.getAttribute('data-sleutel');
    var csrf      = kaart.getAttribute('data-csrf');
    var aanKnop   = document.getElementById('meld-aan');
    var uitKnop   = document.getElementById('meld-uit');
    var proefKnop = document.getElementById('meld-proef');
    var stand     = document.getElementById('meld-stand');
    var uitleg    = document.getElementById('meld-uitleg');

    function toon(id, ja) {
        var el = document.getElementById(id);
        if (el) { el.hidden = !ja; }
    }

    var kanHet = ('serviceWorker' in navigator)
              && ('PushManager' in window)
              && ('Notification' in window);
    var alsApp  = window.matchMedia('(display-mode: standalone)').matches
              || window.navigator.standalone === true;
    var isApple = /iPhone|iPad|iPod/i.test(navigator.userAgent);

    if (!kanHet || !sleutel) {
        /* Op een iPhone kan dit pas als het dashboard als app op het
           beginscherm staat. Dat is een regel van Apple, niet van ons. */
        toon(isApple && !alsApp ? 'meld-iphone' : 'meld-kan-niet', true);
        return;
    }
    if (Notification.permission === 'denied') {
        toon('meld-geweigerd', true);
        return;
    }

    /* De sleutel komt als tekst binnen, de browser wil er bytes van. */
    function sleutelNaarBytes(tekst) {
        var vulling = (4 - (tekst.length % 4)) % 4;
        var recht = (tekst + new Array(vulling + 1).join('='))
            .replace(/-/g, '+').replace(/_/g, '/');
        var ruw = window.atob(recht);
        var bytes = new Uint8Array(ruw.length);
        for (var i = 0; i < ruw.length; i++) {
            bytes[i] = ruw.charCodeAt(i);
        }
        return bytes;
    }

    function naarServer(velden) {
        var lijf = new FormData();
        Object.keys(velden).forEach(function (naam) {
            lijf.append(naam, velden[naam]);
        });
        lijf.append('csrf', csrf);
        return fetch('melding.php', {
            method: 'POST',
            body: lijf,
            credentials: 'same-origin'
        }).then(function (antwoord) { return antwoord.json(); });
    }

    var abonnement = null;

    function werkBij() {
        var aan = !!abonnement;
        aanKnop.hidden    = aan;
        uitKnop.hidden    = !aan;
        proefKnop.hidden  = !aan;
        stand.hidden      = false;
        stand.textContent = aan ? 'Staat aan op dit toestel' : 'Staat uit';
        stand.className   = 'meld-stand' + (aan ? ' meld-stand-aan' : '');
    }

    navigator.serviceWorker.register('sw.js').then(function () {
        return navigator.serviceWorker.ready;
    }).then(function (reg) {
        return reg.pushManager.getSubscription();
    }).then(function (huidig) {
        abonnement = huidig;
        werkBij();
    })['catch'](function () {
        toon('meld-kan-niet', true);
    });

    aanKnop.addEventListener('click', function () {
        aanKnop.disabled = true;
        Promise.resolve(Notification.requestPermission()).then(function (antwoord) {
            if (antwoord !== 'granted') {
                toon('meld-geweigerd', antwoord === 'denied');
                throw new Error('geen-toestemming');
            }
            return navigator.serviceWorker.ready;
        }).then(function (reg) {
            return reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: sleutelNaarBytes(sleutel)
            });
        }).then(function (nieuw) {
            var kopie = nieuw.toJSON();
            return naarServer({
                actie:    'aanmelden',
                endpoint: nieuw.endpoint,
                p256dh:   kopie.keys.p256dh,
                auth:     kopie.keys.auth
            }).then(function (uitkomst) {
                if (!uitkomst.ok) { throw new Error('aanmelden-mislukt'); }
                abonnement = nieuw;
                werkBij();
            });
        })['catch'](function (fout) {
            if (fout && fout.message !== 'geen-toestemming') {
                uitleg.textContent = 'Het aanzetten lukte niet. '
                    + 'Probeer het zo nog eens.';
            }
        }).then(function () {
            aanKnop.disabled = false;
        });
    });

    uitKnop.addEventListener('click', function () {
        if (!abonnement) { return; }
        uitKnop.disabled = true;
        var adres = abonnement.endpoint;
        abonnement.unsubscribe().then(function () {
            return naarServer({ actie: 'afmelden', endpoint: adres });
        }).then(function () {
            abonnement = null;
            werkBij();
        })['catch'](function () {
            /* niets aan te doen, de knop komt gewoon weer vrij */
        }).then(function () {
            uitKnop.disabled = false;
        });
    });

    proefKnop.addEventListener('click', function () {
        proefKnop.disabled = true;
        var oud = proefKnop.textContent;
        naarServer({ actie: 'test' }).then(function (uitkomst) {
            proefKnop.textContent = uitkomst.ok ? 'Onderweg' : 'Lukte niet';
        })['catch'](function () {
            proefKnop.textContent = 'Lukte niet';
        }).then(function () {
            setTimeout(function () {
                proefKnop.textContent = oud;
                proefKnop.disabled = false;
            }, 2500);
        });
    });
}());

/* ------------------------------------------------------------------
   De balk die inschuift als er iets binnenkomt terwijl je kijkt
   ------------------------------------------------------------------ */
(function () {
    'use strict';

    var balk = document.getElementById('meld-balk');
    if (!balk) { return; }

    var titelEl = document.getElementById('meld-balk-titel');
    var regelEl = document.getElementById('meld-balk-regel');
    var linkEl  = document.getElementById('meld-balk-link');
    var wacht   = parseInt(balk.getAttribute('data-wacht'), 10) || 0;
    var laatste = parseInt(balk.getAttribute('data-laatste'), 10) || 0;
    var klok    = null;

    function toonBalk(titel, tekst, adres) {
        titelEl.textContent = titel || 'Nieuw';
        regelEl.textContent = tekst || '';
        if (adres) { linkEl.setAttribute('href', adres); }
        balk.classList.add('meld-balk-aan');
        if (klok) { clearTimeout(klok); }
        klok = setTimeout(verberg, 14000);
    }
    function verberg() {
        balk.classList.remove('meld-balk-aan');
    }
    document.getElementById('meld-balk-sluit').addEventListener('click', verberg);

    /* Komt er een melding binnen terwijl deze pagina openstaat, dan geeft
       de service worker hier een seintje. Dan hoeven we niet te wachten
       tot de volgende controle. */
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', function (e) {
            if (e.data && e.data.soort === 'melding') {
                toonBalk(e.data.titel, e.data.tekst, e.data.adres);
            }
        });
    }

    /* En zelf af en toe navragen, zodat het ook werkt als de meldingen
       uitstaan of als je op de computer zit. */
    function kijk() {
        if (document.hidden) { return; }
        fetch('melding.php?actie=stand', { credentials: 'same-origin' })
            .then(function (antwoord) { return antwoord.json(); })
            .then(function (nu) {
                if (!nu || !nu.ok) { return; }
                if (nu.laatste > laatste) {
                    toonBalk('Nieuw ingestuurd',
                        'Er is zojuist iets ingestuurd.',
                        'beheer.php?filter=wacht');
                } else if (nu.wacht > wacht) {
                    toonBalk('Nieuw',
                        'Er wacht iets nieuws op beoordeling.',
                        'beheer.php?filter=wacht');
                }
                if (nu.laatste > laatste) { laatste = nu.laatste; }
                wacht = nu.wacht;
            })['catch'](function () {
                /* even geen verbinding, dan kijken we straks weer */
            });
    }

    setInterval(kijk, 45000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { kijk(); }
    });
}());
</script>
</body>
</html>
