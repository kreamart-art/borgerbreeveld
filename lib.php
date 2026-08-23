<?php
/**
 * Gereedschap voor de herdenkingspagina.
 *
 * Hier zit alles wat niet zichtbaar is: het bewaren van inzendingen,
 * het afhandelen van geüploade bestanden, het maken van kleinere foto's
 * en de beveiliging van de formulieren.
 *
 * In dit bestand hoef je normaal niets te veranderen.
 */

/* ------------------------------------------------------------------ */
/* Paden                                                               */
/* ------------------------------------------------------------------ */

define('BASIS_MAP',    __DIR__);
define('UPLOAD_MAP',   __DIR__ . '/uploads');
define('THUMB_MAP',    __DIR__ . '/uploads/thumbs');
define('DATA_MAP',     __DIR__ . '/data');
define('DATA_BESTAND', __DIR__ . '/data/inzendingen.json');
define('MAX_BESTAND_BYTES', MAX_BESTAND_MB * 1024 * 1024);

/* ------------------------------------------------------------------ */
/* Kleine hulpjes                                                      */
/* ------------------------------------------------------------------ */

/** Maakt tekst veilig om in de pagina te zetten. */
function h($tekst)
{
    return htmlspecialchars((string) $tekst, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Start de sessie, als dat nog niet gebeurd is. */
function start_sessie()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Cookie alleen voor deze site, en niet leesbaar voor javascript.
    $veilig = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(0, '/', '', $veilig, true);
    session_start();
}

/** Een korte, niet te raden code, bijvoorbeeld voor bestandsnamen. */
function unieke_code($lengte = 16)
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes((int) ceil($lengte / 2)));
    }
    return bin2hex(openssl_random_pseudo_bytes((int) ceil($lengte / 2)));
}

/** Toont een aantal bytes als leesbare tekst, bijvoorbeeld "12,4 MB". */
function leesbare_grootte($bytes)
{
    $bytes = (float) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 0, ',', '.') . ' kB';
    }
    $mb   = $bytes / (1024 * 1024);
    $uit  = number_format($mb, $mb < 10 ? 1 : 0, ',', '.');
    // "200,0 MB" leest raar, "200 MB" niet.
    if (substr($uit, -2) === ',0') {
        $uit = substr($uit, 0, -2);
    }
    return $uit . ' MB';
}

/** Datum in gewone taal, bijvoorbeeld "13 augustus 2026 om 14:05". */
function nederlandse_datum($tijdstip)
{
    static $maanden = [
        1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni',
        'juli', 'augustus', 'september', 'oktober', 'november', 'december',
    ];
    $oud = date_default_timezone_get();
    date_default_timezone_set(TIJDZONE);
    $dag   = date('j', $tijdstip);
    $maand = $maanden[(int) date('n', $tijdstip)];
    $jaar  = date('Y', $tijdstip);
    $tijd  = date('H:i', $tijdstip);
    date_default_timezone_set($oud);
    return $dag . ' ' . $maand . ' ' . $jaar . ' om ' . $tijd;
}

/** Stuurt de bezoeker door en stopt met de rest van de pagina. */
function ga_naar($adres)
{
    header('Location: ' . $adres, true, 303);
    exit;
}

/* ------------------------------------------------------------------ */
/* Mappen en beveiligingsbestanden                                     */
/* ------------------------------------------------------------------ */

/**
 * Maakt de mappen aan die nodig zijn, en zet er de .htaccess-bestanden
 * in die voorkomen dat er per ongeluk code wordt uitgevoerd.
 * Bestaat alles al, dan gebeurt er niets.
 */
function zorg_voor_mappen()
{
    foreach ([UPLOAD_MAP, THUMB_MAP, DATA_MAP] as $map) {
        if (!is_dir($map)) {
            @mkdir($map, 0775, true);
        }
    }

    // In uploads/ mag niets uitgevoerd worden, maar bestanden mogen wel
    // opgevraagd worden (anders zie je de foto's niet).
    //
    // php_flag staat bewust binnen <IfModule>: op servers waar php als
    // fpm of cgi draait geeft een losse php_flag-regel een foutmelding
    // op elke pagina. De regel met mod_rewrite doet het echte werk.
    $uploadRegels = "# Geen php of andere code uitvoeren in deze map.\n"
        . "<IfModule mod_php.c>\n  php_flag engine off\n</IfModule>\n"
        . "<IfModule mod_php7.c>\n  php_flag engine off\n</IfModule>\n"
        . "<IfModule mod_php5.c>\n  php_flag engine off\n</IfModule>\n"
        . "<IfModule mod_mime.c>\n"
        . "  AddType text/plain .php .php3 .php4 .php5 .php7 .php8 .phtml .phps .pl .py .cgi .sh\n"
        . "  RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phps .cgi .pl .py\n"
        . "</IfModule>\n"
        . "<IfModule mod_rewrite.c>\n  RewriteEngine On\n"
        . "  RewriteRule \\.(php|phtml|php3|php4|php5|php7|php8|phps|cgi|pl|py|sh)$ - [F,L]\n"
        . "</IfModule>\n"
        . "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|php8|phps|cgi|pl|py|sh)$\">\n"
        . "  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n"
        . "  <IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n  </IfModule>\n"
        . "</FilesMatch>\n"
        . "Options -ExecCGI -Indexes\n";
    schrijf_als_ontbreekt(UPLOAD_MAP . '/.htaccess', $uploadRegels);
    schrijf_als_ontbreekt(THUMB_MAP . '/.htaccess', $uploadRegels);

    // In data/ mag helemaal niets opgevraagd worden.
    $dataRegels = "# Deze map is niet bedoeld voor bezoekers.\n"
        . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n"
        . "<IfModule mod_php.c>\n  php_flag engine off\n</IfModule>\n"
        . "<IfModule mod_php7.c>\n  php_flag engine off\n</IfModule>\n"
        . "<IfModule mod_php5.c>\n  php_flag engine off\n</IfModule>\n"
        . "Options -ExecCGI -Indexes\n";
    schrijf_als_ontbreekt(DATA_MAP . '/.htaccess', $dataRegels);

    // Extra slot op de deur: wie de map wel kan bereiken, ziet geen lijst.
    schrijf_als_ontbreekt(DATA_MAP . '/index.html', '');
    schrijf_als_ontbreekt(UPLOAD_MAP . '/index.html', '');
    schrijf_als_ontbreekt(THUMB_MAP . '/index.html', '');
}

/** Schrijft een bestand alleen als het er nog niet is. */
function schrijf_als_ontbreekt($pad, $inhoud)
{
    if (!file_exists($pad)) {
        @file_put_contents($pad, $inhoud);
    }
}

/* ------------------------------------------------------------------ */
/* Opslag: één json-bestand, met een slot erop                         */
/* ------------------------------------------------------------------ */

/** Hoe een leeg gegevensbestand eruitziet. */
function lege_data()
{
    return [
        'versie'      => 1,
        'inzendingen' => [],
    ];
}

/** Leest alle inzendingen. */
function lees_data()
{
    if (!file_exists(DATA_BESTAND)) {
        return lege_data();
    }
    $fp = @fopen(DATA_BESTAND, 'rb');
    if (!$fp) {
        return lege_data();
    }
    flock($fp, LOCK_SH);
    $ruw = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $data = json_decode($ruw, true);
    if (!is_array($data) || !isset($data['inzendingen'])) {
        return lege_data();
    }
    return $data;
}

/**
 * Verandert de gegevens veilig, ook als twee mensen tegelijk insturen.
 * Geef een functie mee die de gegevens krijgt en de nieuwe versie teruggeeft.
 */
function muteer_data(callable $verander)
{
    zorg_voor_mappen();

    $fp = @fopen(DATA_BESTAND, 'c+b');
    if (!$fp) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    $ruw  = stream_get_contents($fp);
    $data = json_decode($ruw, true);
    if (!is_array($data) || !isset($data['inzendingen'])) {
        $data = lege_data();
    }

    $data = $verander($data);

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $data;
}

/* ------------------------------------------------------------------ */
/* Beveiliging van de formulieren                                      */
/* ------------------------------------------------------------------ */

/** Geeft het geheime formuliercodewoord van deze bezoeker. */
function csrf_token()
{
    start_sessie();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = unieke_code(32);
    }
    return $_SESSION['csrf'];
}

/** Controleert of het formulier van deze pagina komt. */
function csrf_klopt($ingestuurd)
{
    start_sessie();
    if (empty($_SESSION['csrf']) || !is_string($ingestuurd)) {
        return false;
    }
    return hash_equals($_SESSION['csrf'], $ingestuurd);
}

/**
 * Kijkt of de verzending groter was dan de server aankan.
 * In dat geval is alles leeg en zou je anders een witte pagina krijgen.
 */
function post_liep_over()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    if (!empty($_POST) || !empty($_FILES)) {
        return false;
    }
    $lengte = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    return $lengte > 0;
}

/** Zet een instelling zoals "200M" om naar bytes. */
function ini_naar_bytes($waarde)
{
    $waarde = trim((string) $waarde);
    if ($waarde === '') {
        return 0;
    }
    $laatste = strtolower($waarde[strlen($waarde) - 1]);
    $getal   = (float) $waarde;
    if ($laatste === 'g') {
        $getal *= 1024 * 1024 * 1024;
    } elseif ($laatste === 'm') {
        $getal *= 1024 * 1024;
    } elseif ($laatste === 'k') {
        $getal *= 1024;
    }
    return (int) $getal;
}

/**
 * Het kleinste van: onze eigen limiet en wat de server toestaat.
 * Dit getal gebruiken we ook in de melding aan de bezoeker.
 */
function werkelijke_bestandslimiet()
{
    $limieten = [MAX_BESTAND_BYTES];
    foreach (['upload_max_filesize', 'post_max_size'] as $instelling) {
        $bytes = ini_naar_bytes(ini_get($instelling));
        if ($bytes > 0) {
            $limieten[] = $bytes;
        }
    }
    return min($limieten);
}

/* ------------------------------------------------------------------ */
/* Geüploade bestanden                                                 */
/* ------------------------------------------------------------------ */

/**
 * Maakt van het rommelige $_FILES-formaat een nette lijst,
 * één rijtje per bestand.
 */
function upload_lijst($veld)
{
    $lijst = [];
    if (empty($_FILES[$veld]) || !isset($_FILES[$veld]['name'])) {
        return $lijst;
    }
    $bron = $_FILES[$veld];

    if (!is_array($bron['name'])) {
        $bron = [
            'name'     => [$bron['name']],
            'type'     => [$bron['type']],
            'tmp_name' => [$bron['tmp_name']],
            'error'    => [$bron['error']],
            'size'     => [$bron['size']],
        ];
    }

    foreach ($bron['name'] as $i => $naam) {
        if ($bron['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue; // leeg vakje, gewoon overslaan
        }
        $lijst[] = [
            'naam'  => (string) $naam,
            'tmp'   => $bron['tmp_name'][$i],
            'fout'  => (int) $bron['error'][$i],
            'bytes' => (int) $bron['size'][$i],
        ];
    }
    return $lijst;
}

/** Vertaalt een uploadfout van php naar gewone taal. */
function uploadfout_tekst($code, $bestandsnaam)
{
    $naam = $bestandsnaam !== '' ? '"' . $bestandsnaam . '"' : 'Een bestand';
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return $naam . ' is te groot voor deze server. '
                . 'De grens ligt nu op ' . leesbare_grootte(werkelijke_bestandslimiet()) . '.';
        case UPLOAD_ERR_PARTIAL:
            return $naam . ' is maar half aangekomen. Probeer het nog een keer.';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            return $naam . ' kon niet worden opgeslagen. Probeer het later nog eens.';
        default:
            return $naam . ' kon niet worden verwerkt.';
    }
}

/** De extensie van een bestandsnaam, in kleine letters. */
function extensie_van($bestandsnaam)
{
    $punt = strrpos($bestandsnaam, '.');
    if ($punt === false) {
        return '';
    }
    return strtolower(substr($bestandsnaam, $punt + 1));
}

/**
 * Kijkt of een bestand echt een afbeelding is.
 * Voor gewone webformaten gebruiken we getimagesize().
 * Foto's van een iPhone (heic en heif) herkent php vaak niet, die
 * controleren we op hun eigen kenmerk aan het begin van het bestand.
 */
function is_echte_afbeelding($pad, $ext)
{
    if ($ext === 'heic' || $ext === 'heif') {
        $fp = @fopen($pad, 'rb');
        if (!$fp) {
            return false;
        }
        $kop = fread($fp, 32);
        fclose($fp);
        return $kop !== false && strpos($kop, 'ftyp') !== false;
    }

    $info = @getimagesize($pad);
    if ($info === false || empty($info[2])) {
        return false;
    }

    $verwacht = [
        'jpg'  => [IMAGETYPE_JPEG],
        'jpeg' => [IMAGETYPE_JPEG],
        'png'  => [IMAGETYPE_PNG],
        'gif'  => [IMAGETYPE_GIF],
    ];
    if ($ext === 'webp') {
        // Oudere php-versies kennen IMAGETYPE_WEBP nog niet.
        if (defined('IMAGETYPE_WEBP')) {
            return $info[2] === IMAGETYPE_WEBP;
        }
        return true;
    }
    if (isset($verwacht[$ext])) {
        return in_array($info[2], $verwacht[$ext], true);
    }
    return false;
}

/**
 * Maakt een kleinere jpeg voor de galerij, met de juiste kant boven.
 * Lukt dat niet, bijvoorbeeld omdat GD ontbreekt, dan geven we ''
 * terug en gebruikt de pagina gewoon het origineel.
 */
function maak_verkleinde_versie($bronPad, $ext, $doelPad)
{
    if (!function_exists('imagecreatetruecolor')) {
        return ''; // GD is niet geïnstalleerd
    }

    $afbeelding = null;
    if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) {
        $afbeelding = @imagecreatefromjpeg($bronPad);
    } elseif ($ext === 'png' && function_exists('imagecreatefrompng')) {
        $afbeelding = @imagecreatefrompng($bronPad);
    } elseif ($ext === 'gif' && function_exists('imagecreatefromgif')) {
        $afbeelding = @imagecreatefromgif($bronPad);
    } elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
        $afbeelding = @imagecreatefromwebp($bronPad);
    }

    if (!$afbeelding) {
        return ''; // heic en heif kan GD niet openen, dat is geen ramp
    }

    // Telefoons bewaren de draairichting apart. Zonder deze stap
    // staan foto's op hun kant.
    if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('exif_read_data')) {
        $exif = @exif_read_data($bronPad);
        if (!empty($exif['Orientation'])) {
            $afbeelding = draai_volgens_exif($afbeelding, (int) $exif['Orientation']);
        }
    }

    $breedte = imagesx($afbeelding);
    $hoogte  = imagesy($afbeelding);
    if ($breedte < 1 || $hoogte < 1) {
        imagedestroy($afbeelding);
        return '';
    }

    $schaal = min(1, THUMB_MAX_ZIJDE / max($breedte, $hoogte));
    $nieuwB = max(1, (int) round($breedte * $schaal));
    $nieuwH = max(1, (int) round($hoogte * $schaal));

    $doel = imagecreatetruecolor($nieuwB, $nieuwH);
    // Doorzichtige delen worden wit, anders worden ze zwart in een jpeg.
    $wit = imagecolorallocate($doel, 255, 255, 255);
    imagefilledrectangle($doel, 0, 0, $nieuwB, $nieuwH, $wit);
    imagecopyresampled($doel, $afbeelding, 0, 0, 0, 0, $nieuwB, $nieuwH, $breedte, $hoogte);

    $gelukt = @imagejpeg($doel, $doelPad, THUMB_KWALITEIT);

    // Geheugen vrijgeven hoeft alleen nog op php 7. Vanaf php 8 doet dit
    // niets meer, en in php 8.5 geeft het zelfs een waarschuwing.
    if (PHP_VERSION_ID < 80000) {
        imagedestroy($doel);
        imagedestroy($afbeelding);
    }

    return $gelukt ? basename($doelPad) : '';
}

/** Draait een afbeelding volgens de exif-informatie van de camera. */
function draai_volgens_exif($afbeelding, $stand)
{
    switch ($stand) {
        case 2:
            imageflip($afbeelding, IMG_FLIP_HORIZONTAL);
            break;
        case 3:
            $afbeelding = imagerotate($afbeelding, 180, 0);
            break;
        case 4:
            imageflip($afbeelding, IMG_FLIP_VERTICAL);
            break;
        case 5:
            $afbeelding = imagerotate($afbeelding, -90, 0);
            imageflip($afbeelding, IMG_FLIP_HORIZONTAL);
            break;
        case 6:
            $afbeelding = imagerotate($afbeelding, -90, 0);
            break;
        case 7:
            $afbeelding = imagerotate($afbeelding, 90, 0);
            imageflip($afbeelding, IMG_FLIP_HORIZONTAL);
            break;
        case 8:
            $afbeelding = imagerotate($afbeelding, 90, 0);
            break;
    }
    return $afbeelding;
}

/**
 * Slaat één geüpload bestand op.
 * Geeft een rijtje gegevens terug, of een melding als het niet lukte.
 */
function bewaar_bestand(array $upload)
{
    $origineel = $upload['naam'];

    if ($upload['fout'] !== UPLOAD_ERR_OK) {
        return ['fout' => uploadfout_tekst($upload['fout'], $origineel)];
    }
    if (!is_uploaded_file($upload['tmp'])) {
        return ['fout' => 'Er ging iets mis bij het ontvangen van "' . $origineel . '".'];
    }

    $ext = extensie_van($origineel);
    if (!isset(TOEGESTANE_EXTENSIES[$ext])) {
        return ['fout' => '"' . $origineel . '" is een bestandstype dat we niet kunnen '
            . 'gebruiken. Stuur een foto (jpg, png, gif, webp, heic) of een video '
            . '(mp4, mov, m4v, webm, avi, 3gp).'];
    }

    $grens = werkelijke_bestandslimiet();
    if ($upload['bytes'] > $grens) {
        return ['fout' => '"' . $origineel . '" is ' . leesbare_grootte($upload['bytes'])
            . ' groot. Dat is te veel. De grens ligt op ' . leesbare_grootte($grens) . '.'];
    }
    if ($upload['bytes'] <= 0) {
        return ['fout' => '"' . $origineel . '" is leeg.'];
    }

    $soort = TOEGESTANE_EXTENSIES[$ext];

    if ($soort === 'foto' && !is_echte_afbeelding($upload['tmp'], $ext)) {
        return ['fout' => '"' . $origineel . '" lijkt geen echte foto te zijn. '
            . 'Probeer hem opnieuw vanaf je telefoon te versturen.'];
    }

    zorg_voor_mappen();

    // Nooit de naam van de inzender gebruiken, altijd een eigen naam.
    $nieuweNaam = unieke_code(20) . '.' . $ext;
    $doelPad    = UPLOAD_MAP . '/' . $nieuweNaam;

    if (!@move_uploaded_file($upload['tmp'], $doelPad)) {
        return ['fout' => '"' . $origineel . '" kon niet worden opgeslagen. '
            . 'Waarschijnlijk heeft de map uploads geen schrijfrechten.'];
    }
    @chmod($doelPad, 0644);

    $thumb = '';
    if ($soort === 'foto') {
        $thumbNaam = pathinfo($nieuweNaam, PATHINFO_FILENAME) . '.jpg';
        $thumb = maak_verkleinde_versie($doelPad, $ext, THUMB_MAP . '/' . $thumbNaam);
        if ($thumb !== '') {
            @chmod(THUMB_MAP . '/' . $thumb, 0644);
        }
    }

    return [
        'id'        => unieke_code(12),
        'bestand'   => $nieuweNaam,
        'thumb'     => $thumb,
        'soort'     => $soort,
        'origineel' => mb_substr($origineel, 0, 120),
        'bytes'     => $upload['bytes'],
        'zichtbaar' => false,
    ];
}

/** Haalt een bestand en de verkleinde versie van de schijf. */
function verwijder_bestanden_van_schijf(array $bestand)
{
    if (!empty($bestand['bestand'])) {
        $pad = UPLOAD_MAP . '/' . basename($bestand['bestand']);
        if (is_file($pad)) {
            @unlink($pad);
        }
    }
    if (!empty($bestand['thumb'])) {
        $pad = THUMB_MAP . '/' . basename($bestand['thumb']);
        if (is_file($pad)) {
            @unlink($pad);
        }
    }
}

/* ------------------------------------------------------------------ */
/* Wat er op de pagina komt                                            */
/* ------------------------------------------------------------------ */

/** Het webadres van een bestand, voor in de pagina. */
function bestand_url(array $bestand, $klein = false)
{
    if ($klein && !empty($bestand['thumb'])) {
        return 'uploads/thumbs/' . rawurlencode($bestand['thumb']);
    }
    return 'uploads/' . rawurlencode($bestand['bestand']);
}

/** Alle goedgekeurde foto's en video's, de nieuwste inzending eerst. */
function zichtbare_media(array $data)
{
    $uit = [];
    foreach ($data['inzendingen'] as $inzending) {
        if (!empty($inzending['weg'])) {
            continue;   // hele inzending zit in de prullenbak
        }
        foreach ($inzending['bestanden'] as $plek => $bestand) {
            if (empty($bestand['zichtbaar']) || !empty($bestand['weg'])) {
                continue;
            }
            $bestand['inzender'] = $inzending['naam'];
            $bestand['relatie']  = isset($inzending['relatie']) ? $inzending['relatie'] : '';
            // Bij foto's die de familie zelf heeft uitgekozen hoeft er geen
            // naam onder te staan.
            $bestand['toon_inzender'] = !isset($inzending['toon_inzender'])
                || !empty($inzending['toon_inzender']);
            $bestand['datum']    = $inzending['datum'];
            // Binnen één inzending houden we de volgorde aan waarin de
            // bestanden binnenkwamen.
            $bestand['plek'] = isset($bestand['volgorde']) ? $bestand['volgorde'] : $plek;
            $uit[] = $bestand;
        }
    }
    usort($uit, function ($a, $b) {
        if ($a['datum'] === $b['datum']) {
            return $a['plek'] - $b['plek'];
        }
        return $b['datum'] - $a['datum'];
    });
    return $uit;
}

/** Alle goedgekeurde geschreven herinneringen, de nieuwste eerst. */
function zichtbare_herinneringen(array $data)
{
    $uit = [];
    foreach ($data['inzendingen'] as $inzending) {
        if (!empty($inzending['weg'])) {
            continue;
        }
        if (empty($inzending['tekst_zichtbaar']) || trim($inzending['tekst']) === '') {
            continue;
        }
        $uit[] = $inzending;
    }
    usort($uit, function ($a, $b) {
        return $b['datum'] - $a['datum'];
    });
    return $uit;
}

/**
 * Het adres van een banner in de map media, of '' als die er niet is.
 *
 * Er zijn er twee:
 *   header         de brede collage, alleen op de hoofdpagina
 *   header-pagina  de rustiger foto, op alle andere pagina's
 *
 * Wil je een andere afbeelding, geef hem dan dezelfde naam.
 */
function banner_url($naam = 'header')
{
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $soort) {
        if (is_file(BASIS_MAP . '/media/' . $naam . '.' . $soort)) {
            return 'media/' . $naam . '.' . $soort;
        }
    }
    return '';
}

/** Bestaat er een verkleinde versie van deze banner? */
function banner_heeft_maten($naam)
{
    return is_file(BASIS_MAP . '/media/' . $naam . '-800.jpg');
}

/* ------------------------------------------------------------------ */
/* Bericht bij een nieuwe inzending                                    */
/* ------------------------------------------------------------------ */

/**
 * Laat de familie weten dat er iets is binnengekomen.
 * Lukt het versturen niet, dan gebeurt er verder niets: de inzending
 * is toch al opgeslagen.
 */
function meld_nieuwe_inzending($naam, $relatie, $tekst, $aantalBestanden, $email = '')
{
    if (!MELD_NIEUWE_INZENDING || !function_exists('mail')) {
        return false;
    }
    $naar = trim(CONTACT_EMAIL);
    if ($naar === '' || !filter_var($naar, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $onderwerp = 'Nieuwe bijdrage voor de herdenkingspagina';

    $regels = [];
    $regels[] = 'Er is zojuist iets ingestuurd.';
    $regels[] = '';
    $regels[] = 'Van: ' . $naam;
    if ($relatie !== '') {
        $regels[] = 'Kende hem als: ' . $relatie;
    }
    $regels[] = 'Bestanden: ' . (int) $aantalBestanden;
    $regels[] = 'Geschreven herinnering: ' . ($tekst !== '' ? 'ja' : 'nee');
    $regels[] = '';
    $regels[] = 'Het staat nog niet op de pagina. Log in op het dashboard om';
    $regels[] = 'te bekijken wat er zichtbaar mag worden:';
    $regels[] = beheer_adres();
    $regels[] = '';
    $regels[] = 'Dit bericht is automatisch verstuurd door de website.';

    $afzender = trim(AFZENDER_EMAIL) !== '' ? trim(AFZENDER_EMAIL) : $naar;

    $koppen = [
        'From: Herdenkingspagina <' . $afzender . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP',
    ];

    // Liet de inzender een adres achter, dan kun je meteen antwoorden.
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $koppen[] = 'Reply-To: ' . $email;
    }

    return @mail($naar, $onderwerp, implode("\n", $regels), implode("\r\n", $koppen));
}

/** Het volledige webadres van het dashboard, voor in een e-mail. */
function beheer_adres()
{
    $veilig = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $map    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return ($veilig ? 'https://' : 'http://') . $host . $map . '/beheer.php';
}

/* ------------------------------------------------------------------ */
/* Rem op het raden van het wachtwoord                                 */
/* ------------------------------------------------------------------ */

/** Waar we de mislukte pogingen bijhouden. */
function pogingen_bestand()
{
    return DATA_MAP . '/pogingen.json';
}

/** Wie probeert er in te loggen? */
function bezoeker_sleutel()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'onbekend';
    return substr(hash('sha256', $ip), 0, 16);
}

/**
 * Hoeveel seconden moet deze bezoeker nog wachten?
 * 0 betekent: gewoon proberen.
 */
function inlog_wachttijd()
{
    $bestand = pogingen_bestand();
    if (!is_file($bestand)) {
        return 0;
    }
    $lijst = json_decode((string) @file_get_contents($bestand), true);
    if (!is_array($lijst)) {
        return 0;
    }
    $sleutel = bezoeker_sleutel();
    if (empty($lijst[$sleutel])) {
        return 0;
    }
    $rij = $lijst[$sleutel];
    if ((int) $rij['aantal'] < MAX_INLOGPOGINGEN) {
        return 0;
    }
    $klaar = (int) $rij['laatste'] + INLOG_WACHTTIJD_MINUTEN * 60;
    return max(0, $klaar - time());
}

/** Een mislukte poging bijschrijven. */
function noteer_mislukte_poging()
{
    zorg_voor_mappen();
    $bestand = pogingen_bestand();
    $fp = @fopen($bestand, 'c+b');
    if (!$fp) {
        return;
    }
    flock($fp, LOCK_EX);
    $lijst = json_decode(stream_get_contents($fp), true);
    if (!is_array($lijst)) {
        $lijst = [];
    }

    // Oude rijen opruimen, zodat het bestandje klein blijft.
    $grens = time() - INLOG_WACHTTIJD_MINUTEN * 60;
    foreach ($lijst as $s => $rij) {
        if ((int) $rij['laatste'] < $grens) {
            unset($lijst[$s]);
        }
    }

    $sleutel = bezoeker_sleutel();
    $aantal  = isset($lijst[$sleutel]) ? (int) $lijst[$sleutel]['aantal'] : 0;
    $lijst[$sleutel] = ['aantal' => $aantal + 1, 'laatste' => time()];

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($lijst));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

/** Na een goede inlog is de teller weer schoon. */
function wis_mislukte_pogingen()
{
    $bestand = pogingen_bestand();
    if (!is_file($bestand)) {
        return;
    }
    $fp = @fopen($bestand, 'c+b');
    if (!$fp) {
        return;
    }
    flock($fp, LOCK_EX);
    $lijst = json_decode(stream_get_contents($fp), true);
    if (is_array($lijst)) {
        unset($lijst[bezoeker_sleutel()]);
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($lijst));
        fflush($fp);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
}

/* ------------------------------------------------------------------ */
/* Wie is er ingelogd op het dashboard                                 */
/* ------------------------------------------------------------------ */

/**
 * Kijkt welk wachtwoord er is ingevuld.
 * Geeft 'beheerder', 'familie' of '' terug.
 */
function welke_rol($ingevuld)
{
    if (!is_string($ingevuld) || $ingevuld === '') {
        return '';
    }
    if (hash_equals(BEHEER_WACHTWOORD, $ingevuld)) {
        return 'beheerder';
    }
    if (defined('FAMILIE_WACHTWOORD') && FAMILIE_WACHTWOORD !== ''
        && hash_equals(FAMILIE_WACHTWOORD, $ingevuld)) {
        return 'familie';
    }
    return '';
}

/** De rol van wie er nu is ingelogd, of '' als er niemand is. */
function huidige_rol()
{
    return isset($_SESSION['rol']) ? (string) $_SESSION['rol'] : '';
}

/** Mag deze persoon een hele inzending voorgoed verwijderen? */
function mag_verwijderen()
{
    return huidige_rol() === 'beheerder';
}

/** Hoe noemen we deze rol op het scherm? */
function rol_naam($rol)
{
    return $rol === 'beheerder' ? 'beheerder' : 'familie';
}

/**
 * Waar komt een inzending vandaan?
 * 'familie'  de verzameling die de familie zelf heeft uitgekozen
 * 'bezoeker' via het formulier op de website ingestuurd
 */
function herkomst_van(array $inzending)
{
    return isset($inzending['bron']) ? $inzending['bron'] : 'bezoeker';
}

/**
 * Welke inzendingen mag deze rol zien?
 * De familie beoordeelt alleen de eigen verzameling. Wat bezoekers
 * insturen gaat langs de beheerder.
 */
function mag_zien(array $inzending)
{
    if (huidige_rol() === 'beheerder') {
        return true;
    }
    return herkomst_van($inzending) === 'familie';
}

/* ------------------------------------------------------------------ */
/* De prullenbak                                                       */
/* ------------------------------------------------------------------ */

/**
 * Zit dit bestand in de prullenbak?
 * Weggegooide bestanden blijven gewoon op de schijf staan, ze worden
 * alleen niet meer getoond. Zo kun je een vergissing terugdraaien.
 */
function ligt_in_prullenbak(array $bestand)
{
    return !empty($bestand['weg']);
}

/** Hoeveel bestanden liggen er in de prullenbak? */
function aantal_in_prullenbak(array $data)
{
    $aantal = 0;
    foreach ($data['inzendingen'] as $inzending) {
        if (!empty($inzending['weg'])) {
            $aantal += count($inzending['bestanden']);
            continue;
        }
        foreach ($inzending['bestanden'] as $bestand) {
            if (!empty($bestand['weg'])) {
                $aantal++;
            }
        }
    }
    return $aantal;
}

/** Zit er in deze inzending iets dat niet in de prullenbak ligt? */
function heeft_iets_over(array $inzending)
{
    if (!empty($inzending['weg'])) {
        return false;
    }
    foreach ($inzending['bestanden'] as $bestand) {
        if (empty($bestand['weg'])) {
            return true;
        }
    }
    return trim($inzending['tekst']) !== '';
}
