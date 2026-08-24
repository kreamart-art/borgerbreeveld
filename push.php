<?php
/**
 * Meldingen op de telefoon.
 *
 * Hiermee stuurt de website een berichtje naar het dashboard op je
 * telefoon, ook als je die app niet open hebt staan. Dat heet "web push"
 * en is iets wat de browser zelf regelt.
 *
 * Er is bewust geen enkele externe dienst en geen enkel extra pakket
 * nodig. Alles gebeurt met wat er standaard in php zit (openssl en curl).
 *
 * Hoe het werkt, in gewone woorden:
 *
 *  1. De website heeft een eigen sleutelpaar. Dat maakt hij de eerste
 *     keer zelf aan en bewaart hij in de map data/. Daarmee bewijst hij
 *     aan Google en Apple dat hij is wie hij zegt te zijn.
 *  2. Zet iemand in het dashboard de meldingen aan, dan geeft zijn
 *     telefoon een adres af waar berichtjes naartoe mogen. Dat adres
 *     bewaren we, samen met twee sleutels van die telefoon.
 *  3. Willen we iets melden, dan versleutelen we het bericht met die
 *     sleutels en sturen het naar dat adres. Onderweg kan niemand het
 *     lezen, ook Google en Apple niet.
 *
 * Je hoeft hier niets aan te veranderen.
 */

if (!defined('BORGER')) {
    define('BORGER', true);
}

/* ------------------------------------------------------------------ */
/* Kan deze server het uberhaupt?                                      */
/* ------------------------------------------------------------------ */

/**
 * Heeft deze server alles wat nodig is om meldingen te versturen?
 * Vrijwel elke hosting heeft dit, maar we controleren het netjes zodat
 * de rest van de site blijft werken als het er niet is.
 */
function push_mogelijk()
{
    static $antwoord = null;
    if ($antwoord !== null) {
        return $antwoord;
    }
    $antwoord = extension_loaded('openssl')
        && function_exists('openssl_pkey_derive')
        && function_exists('hash_hkdf')
        && function_exists('curl_init')
        && in_array('aes-128-gcm', openssl_get_cipher_methods(), true);
    return $antwoord;
}

/** Waarom het niet kan, in gewone taal, voor op het scherm. */
function push_waarom_niet()
{
    if (!extension_loaded('openssl')) {
        return 'Deze server mist de openssl-uitbreiding van php.';
    }
    if (!function_exists('openssl_pkey_derive')) {
        return 'Deze server draait een te oude versie van php (7.3 of nieuwer nodig).';
    }
    if (!function_exists('curl_init')) {
        return 'Deze server mist de curl-uitbreiding van php.';
    }
    return 'Deze server kan geen meldingen versturen.';
}

/* ------------------------------------------------------------------ */
/* Kleine hulpjes                                                      */
/* ------------------------------------------------------------------ */

/** Base64 zoals het web het wil: zonder +, / en = aan het eind. */
function push_b64url($ruw)
{
    return rtrim(strtr(base64_encode($ruw), '+/', '-_'), '=');
}

/** En weer terug. */
function push_van_b64url($tekst)
{
    $tekst = strtr((string) $tekst, '-_', '+/');
    $rest  = strlen($tekst) % 4;
    if ($rest) {
        $tekst .= str_repeat('=', 4 - $rest);
    }
    $ruw = base64_decode($tekst, true);
    return $ruw === false ? '' : $ruw;
}

/**
 * Een handtekening van openssl komt in een verpakking die "der" heet.
 * Web push wil hem kaal: eerst 32 bytes, dan nog eens 32 bytes.
 */
function push_der_naar_raw($der)
{
    $plek = 0;
    $lees = function ($aantal) use ($der, &$plek) {
        $stuk = substr($der, $plek, $aantal);
        $plek += $aantal;
        return $stuk;
    };

    if (strlen($der) < 8 || ord($der[$plek++]) !== 0x30) {
        return false;
    }
    $lengte = ord($der[$plek++]);
    if ($lengte & 0x80) {
        $plek += ($lengte & 0x7f);      // lange vorm, lengte slaan we over
    }

    $getallen = [];
    for ($i = 0; $i < 2; $i++) {
        if (!isset($der[$plek]) || ord($der[$plek++]) !== 0x02) {
            return false;
        }
        $n = ord($der[$plek++]);
        $getallen[] = ltrim($lees($n), "\x00");
    }

    if (count($getallen) !== 2) {
        return false;
    }
    return str_pad($getallen[0], 32, "\x00", STR_PAD_LEFT)
         . str_pad($getallen[1], 32, "\x00", STR_PAD_LEFT);
}

/**
 * De telefoon geeft zijn sleutel als 65 kale bytes. Openssl wil hem in
 * een nette verpakking. Die verpakking is voor deze soort sleutel altijd
 * hetzelfde rijtje bytes, dus die plakken we er gewoon voor.
 */
function push_punt_naar_pem($punt)
{
    $voorvoegsel = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
                 . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00";
    $der = $voorvoegsel . $punt;
    return "-----BEGIN PUBLIC KEY-----\n"
         . chunk_split(base64_encode($der), 64, "\n")
         . "-----END PUBLIC KEY-----\n";
}

/* ------------------------------------------------------------------ */
/* De sleutels van de website zelf                                     */
/* ------------------------------------------------------------------ */

/** Waar het sleutelpaar van de website staat. */
function push_sleutel_bestand()
{
    return DATA_MAP . '/push-sleutels.json';
}

/**
 * Het sleutelpaar van de website. De eerste keer maken we het aan.
 * Raakt dit bestand kwijt, dan moet iedereen de meldingen opnieuw
 * aanzetten; daarom staat het in data/ en niet ergens tijdelijks.
 */
function push_sleutels()
{
    static $bewaard = null;
    if ($bewaard !== null) {
        return $bewaard;
    }
    if (!push_mogelijk()) {
        return false;
    }

    zorg_voor_mappen();
    $pad = push_sleutel_bestand();

    if (file_exists($pad)) {
        $sleutels = json_decode((string) @file_get_contents($pad), true);
        if (is_array($sleutels) && !empty($sleutels['prive']) && !empty($sleutels['publiek'])) {
            $bewaard = $sleutels;
            return $bewaard;
        }
    }

    $paar = openssl_pkey_new([
        'curve_name'       => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);
    if (!$paar) {
        return false;
    }
    $prive = '';
    if (!openssl_pkey_export($paar, $prive)) {
        return false;
    }
    $details = openssl_pkey_get_details($paar);
    if (empty($details['ec']['x']) || empty($details['ec']['y'])) {
        return false;
    }
    $punt = "\x04"
          . str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT)
          . str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);

    $sleutels = ['prive' => $prive, 'publiek' => push_b64url($punt)];
    @file_put_contents($pad, json_encode($sleutels), LOCK_EX);
    @chmod($pad, 0600);

    $bewaard = $sleutels;
    return $bewaard;
}

/** De publieke sleutel, die de telefoon nodig heeft om zich aan te melden. */
function push_publieke_sleutel()
{
    $sleutels = push_sleutels();
    return $sleutels ? $sleutels['publiek'] : '';
}

/** Wie is de eigenaar van deze meldingen? Verplicht veld voor Google en Apple. */
function push_contact()
{
    $email = defined('CONTACT_EMAIL') ? trim(CONTACT_EMAIL) : '';
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'mailto:' . $email;
    }
    return 'mailto:webmaster@' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
}

/** Het pasje waarmee de website zich voorstelt bij Google of Apple. */
function push_pasje($endpoint, array $sleutels)
{
    $deel = parse_url($endpoint);
    if (empty($deel['scheme']) || empty($deel['host'])) {
        return false;
    }
    $voor = $deel['scheme'] . '://' . $deel['host'];

    $kop = ['typ' => 'JWT', 'alg' => 'ES256'];
    $eis = [
        'aud' => $voor,
        'exp' => time() + 11 * 3600,
        'sub' => push_contact(),
    ];
    $basis = push_b64url(json_encode($kop)) . '.' . push_b64url(json_encode($eis));

    $prive = openssl_pkey_get_private($sleutels['prive']);
    if (!$prive) {
        return false;
    }
    $der = '';
    if (!openssl_sign($basis, $der, $prive, OPENSSL_ALGO_SHA256)) {
        return false;
    }
    $kaal = push_der_naar_raw($der);
    if ($kaal === false) {
        return false;
    }
    return $basis . '.' . push_b64url($kaal);
}

/* ------------------------------------------------------------------ */
/* Het bericht versleutelen                                            */
/* ------------------------------------------------------------------ */

/**
 * Versleutelt de tekst zo dat alleen deze ene telefoon hem kan lezen.
 * Volgt de afspraak die alle browsers gebruiken (rfc 8291, aes128gcm).
 */
function push_versleutel($tekst, $p256dh, $authGeheim)
{
    $telefoonPunt = push_van_b64url($p256dh);
    $auth         = push_van_b64url($authGeheim);
    if (strlen($telefoonPunt) !== 65 || $telefoonPunt[0] !== "\x04" || strlen($auth) < 16) {
        return false;
    }

    // Voor elk bericht een vers sleutelpaar, dat gooien we daarna weg.
    $eigen = openssl_pkey_new([
        'curve_name'       => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);
    if (!$eigen) {
        return false;
    }
    $details = openssl_pkey_get_details($eigen);
    if (empty($details['ec']['x']) || empty($details['ec']['y'])) {
        return false;
    }
    $eigenPunt = "\x04"
        . str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT)
        . str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);

    // Het gedeelde geheim: alleen wij en die ene telefoon kunnen dit uitrekenen.
    // Zonder lengte meegeven: die is bij deze soort sleutel altijd 32 bytes,
    // en nieuwere php's klagen als je hem wel opgeeft.
    $gedeeld = @openssl_pkey_derive(push_punt_naar_pem($telefoonPunt), $eigen);
    if ($gedeeld === false || $gedeeld === null || $gedeeld === '') {
        return false;
    }

    $info = "WebPush: info\x00" . $telefoonPunt . $eigenPunt;
    $basis = hash_hkdf('sha256', $gedeeld, 32, $info, $auth);

    $zout   = random_bytes(16);
    $sleutel = hash_hkdf('sha256', $basis, 16, "Content-Encoding: aes128gcm\x00", $zout);
    $nonce   = hash_hkdf('sha256', $basis, 12, "Content-Encoding: nonce\x00", $zout);

    // De 0x02 aan het eind betekent: dit was het laatste stuk.
    $plat = $tekst . "\x02";
    $merk = '';
    $ver  = openssl_encrypt($plat, 'aes-128-gcm', $sleutel, OPENSSL_RAW_DATA, $nonce, $merk);
    if ($ver === false) {
        return false;
    }

    // De verpakking: zout, maximale stukgrootte, onze sleutel, en de brij.
    return $zout . pack('N', 4096) . chr(65) . $eigenPunt . $ver . $merk;
}

/* ------------------------------------------------------------------ */
/* Wie krijgt er meldingen: de aangemelde toestellen                   */
/* ------------------------------------------------------------------ */

/** Waar de aangemelde toestellen staan. */
function push_abonnee_bestand()
{
    return DATA_MAP . '/push-toestellen.json';
}

/** Alle aangemelde toestellen. */
function push_lees_abonnees()
{
    $pad = push_abonnee_bestand();
    if (!file_exists($pad)) {
        return [];
    }
    $data = json_decode((string) @file_get_contents($pad), true);
    return (is_array($data) && isset($data['toestellen']) && is_array($data['toestellen']))
        ? $data['toestellen']
        : [];
}

/** Verandert de lijst met toestellen, met een slot erop. */
function push_muteer_abonnees(callable $verander)
{
    zorg_voor_mappen();
    $pad = push_abonnee_bestand();

    $fp = @fopen($pad, 'c+b');
    if (!$fp) {
        return false;
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    $ruw  = stream_get_contents($fp);
    $data = json_decode($ruw, true);
    $lijst = (is_array($data) && isset($data['toestellen']) && is_array($data['toestellen']))
        ? $data['toestellen']
        : [];

    $lijst = $verander($lijst);

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode(
        ['toestellen' => array_values($lijst)],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($pad, 0600);
    return true;
}

/** Meldt een toestel aan. Was hij er al, dan werken we hem bij. */
function push_meld_aan($endpoint, $p256dh, $auth, $rol, $toestel = '')
{
    $endpoint = (string) $endpoint;
    if ($endpoint === '' || stripos($endpoint, 'https://') !== 0) {
        return false;
    }
    if (push_van_b64url($p256dh) === '' || push_van_b64url($auth) === '') {
        return false;
    }

    return push_muteer_abonnees(function ($lijst) use ($endpoint, $p256dh, $auth, $rol, $toestel) {
        $nu = time();
        foreach ($lijst as $i => $abonnee) {
            if (isset($abonnee['endpoint']) && hash_equals($abonnee['endpoint'], $endpoint)) {
                $lijst[$i]['p256dh']  = $p256dh;
                $lijst[$i]['auth']    = $auth;
                $lijst[$i]['rol']     = $rol;
                $lijst[$i]['toestel'] = $toestel;
                $lijst[$i]['fouten']  = 0;
                $lijst[$i]['bijgewerkt'] = $nu;
                return $lijst;
            }
        }
        $lijst[] = [
            'id'         => unieke_code(12),
            'endpoint'   => $endpoint,
            'p256dh'     => $p256dh,
            'auth'       => $auth,
            'rol'        => $rol,
            'toestel'    => $toestel,
            'aangemaakt' => $nu,
            'bijgewerkt' => $nu,
            'fouten'     => 0,
        ];
        return $lijst;
    });
}

/** Meldt een toestel af. */
function push_meld_af($endpoint)
{
    return push_muteer_abonnees(function ($lijst) use ($endpoint) {
        foreach ($lijst as $i => $abonnee) {
            if (isset($abonnee['endpoint']) && hash_equals($abonnee['endpoint'], (string) $endpoint)) {
                unset($lijst[$i]);
            }
        }
        return $lijst;
    });
}

/** Staat dit toestel al aangemeld? */
function push_is_aangemeld($endpoint)
{
    foreach (push_lees_abonnees() as $abonnee) {
        if (isset($abonnee['endpoint']) && hash_equals($abonnee['endpoint'], (string) $endpoint)) {
            return true;
        }
    }
    return false;
}

/** Hoeveel toestellen krijgen er meldingen, voor deze rol? */
function push_aantal_toestellen($rol = '')
{
    $aantal = 0;
    foreach (push_lees_abonnees() as $abonnee) {
        if ($rol === '' || (isset($abonnee['rol']) && $abonnee['rol'] === $rol)) {
            $aantal++;
        }
    }
    return $aantal;
}

/* ------------------------------------------------------------------ */
/* Versturen                                                           */
/* ------------------------------------------------------------------ */

/**
 * Stuurt een melding naar alle toestellen met een van deze rollen.
 * Geeft terug hoeveel er gelukt zijn.
 */
function push_stuur(array $rollen, $titel, $tekst, $adres = '', $merk = 'borger')
{
    if (!push_mogelijk()) {
        return 0;
    }
    $sleutels = push_sleutels();
    if (!$sleutels) {
        return 0;
    }
    $abonnees = push_lees_abonnees();
    if (!$abonnees) {
        return 0;
    }

    $inhoud = json_encode([
        'titel' => (string) $titel,
        'tekst' => (string) $tekst,
        'adres' => (string) $adres,
        'merk'  => (string) $merk,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $gelukt   = 0;
    $verlopen = [];

    foreach ($abonnees as $abonnee) {
        $rol = isset($abonnee['rol']) ? $abonnee['rol'] : '';
        if (!in_array($rol, $rollen, true)) {
            continue;
        }
        $code = push_verstuur_een($abonnee, $inhoud, $sleutels);
        if ($code >= 200 && $code < 300) {
            $gelukt++;
        } elseif ($code === 404 || $code === 410) {
            // De telefoon bestaat niet meer of heeft de app verwijderd.
            $verlopen[] = $abonnee['endpoint'];
        }
    }

    if ($verlopen) {
        push_muteer_abonnees(function ($lijst) use ($verlopen) {
            foreach ($lijst as $i => $abonnee) {
                if (isset($abonnee['endpoint']) && in_array($abonnee['endpoint'], $verlopen, true)) {
                    unset($lijst[$i]);
                }
            }
            return $lijst;
        });
    }

    return $gelukt;
}

/** Stuurt een melding naar een enkel toestel. Geeft de http-code terug. */
function push_verstuur_een(array $abonnee, $inhoud, array $sleutels)
{
    if (empty($abonnee['endpoint']) || empty($abonnee['p256dh']) || empty($abonnee['auth'])) {
        return 0;
    }
    $pasje = push_pasje($abonnee['endpoint'], $sleutels);
    if ($pasje === false) {
        return 0;
    }
    $brij = push_versleutel($inhoud, $abonnee['p256dh'], $abonnee['auth']);
    if ($brij === false) {
        return 0;
    }

    $ch = curl_init($abonnee['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $brij,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => [
            'Authorization: vapid t=' . $pasje . ', k=' . $sleutels['publiek'],
            'Content-Encoding: aes128gcm',
            'Content-Type: application/octet-stream',
            'TTL: 86400',
            'Urgency: normal',
            'Content-Length: ' . strlen($brij),
        ],
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // Vanaf php 8 ruimt php dit zelf op en klaagt nieuwere php als je het
    // toch doet. Op oudere versies moet het juist wel.
    if (PHP_VERSION_ID < 80000) {
        curl_close($ch);
    }
    return $code;
}

/* ------------------------------------------------------------------ */
/* De momenten waarop er iets gemeld wordt                             */
/* ------------------------------------------------------------------ */

/** Hoeveel uur er minstens tussen twee herinneringen zit. */
const PUSH_HERINNERING_UREN = 24;

/** Tussen welke uren we een herinnering sturen. Niemand wil dit 's nachts. */
const PUSH_VROEGSTE_UUR = 9;
const PUSH_LAATSTE_UUR  = 21;

/**
 * Er is zojuist iets ingestuurd via het formulier op de website.
 * Dat gaat naar de beheerder: wat bezoekers insturen komt niet bij de
 * familie in het dashboard terecht.
 */
function push_meld_inzending($naam, $aantalBestanden, $heeftTekst)
{
    $aantalBestanden = (int) $aantalBestanden;

    $delen = [];
    if ($aantalBestanden === 1) {
        $delen[] = 'een foto of video';
    } elseif ($aantalBestanden > 1) {
        $delen[] = $aantalBestanden . ' foto\'s en video\'s';
    }
    if ($heeftTekst) {
        $delen[] = 'een geschreven herinnering';
    }
    $wat = $delen ? implode(' en ', $delen) : 'iets';

    $naam = trim((string) $naam);
    if ($naam === '') {
        $naam = 'Iemand';
    }

    return push_stuur(
        ['beheerder'],
        'Nieuwe herinnering ingestuurd',
        $naam . ' stuurde ' . $wat . ' in. Het staat nog niet op de pagina.',
        'beheer.php?filter=wacht',
        'inzending'
    );
}

/** Waar we bijhouden wanneer de laatste herinnering is verstuurd. */
function push_herinnering_bestand()
{
    return DATA_MAP . '/push-herinnering.json';
}

/**
 * Stuurt hooguit eens per dag een herinnering als er nog iets op
 * beoordeling wacht.
 *
 * Op gewone webhosting draait er niets vanzelf op de achtergrond, dus we
 * kijken bij een gewoon paginabezoek even of het tijd is. Dat kost bijna
 * niets: meestal is het alleen dit ene kleine bestandje lezen.
 */
function push_herinnering_indien_nodig()
{
    if (!push_mogelijk()) {
        return false;
    }

    $pad = push_herinnering_bestand();
    $nu  = time();

    // Goedkoopste controle eerst: is het al weer tijd?
    if (file_exists($pad)) {
        $stand = json_decode((string) @file_get_contents($pad), true);
        if (is_array($stand) && isset($stand['laatste'])
            && ($nu - (int) $stand['laatste']) < PUSH_HERINNERING_UREN * 3600) {
            return false;
        }
    }

    // Niet midden in de nacht. Dan wachten we tot later op de dag.
    // Let op: php staat op de server meestal op wereldtijd, dus we
    // rekenen hier uitdrukkelijk met de klok uit config.php.
    try {
        $klok = new DateTime('now', new DateTimeZone(TIJDZONE));
        $uur  = (int) $klok->format('G');
    } catch (Exception $fout) {
        $uur = (int) date('G', $nu);
    }
    if ($uur < PUSH_VROEGSTE_UUR || $uur >= PUSH_LAATSTE_UUR) {
        return false;
    }

    // Krijgt iemand hier uberhaupt meldingen?
    $abonnees = push_lees_abonnees();
    if (!$abonnees) {
        return false;
    }

    // Meteen aftekenen, nog voor we versturen. Anders sturen twee
    // bezoekers die tegelijk langskomen allebei een herinnering.
    zorg_voor_mappen();
    @file_put_contents($pad, json_encode(['laatste' => $nu]), LOCK_EX);

    $data     = lees_data();
    $verstuurd = 0;

    foreach (['beheerder', 'familie'] as $rol) {
        // Heeft deze rol wel toestellen? Anders niet eens tellen.
        $heeft = false;
        foreach ($abonnees as $abonnee) {
            if (isset($abonnee['rol']) && $abonnee['rol'] === $rol) {
                $heeft = true;
                break;
            }
        }
        if (!$heeft) {
            continue;
        }

        $aantal = wachtende_onderdelen($data, $rol);
        if ($aantal < 1) {
            continue;
        }

        $verstuurd += push_stuur(
            [$rol],
            $aantal === 1 ? 'Er wacht nog iets op je' : 'Er wachten nog ' . $aantal . ' dingen op je',
            $aantal === 1
                ? 'Een foto, video of herinnering staat nog niet op de pagina.'
                : 'Ze staan nog niet op de pagina. Open het dashboard om ze te beoordelen.',
            'beheer.php?filter=wacht',
            'herinnering'
        );
    }

    return $verstuurd;
}
