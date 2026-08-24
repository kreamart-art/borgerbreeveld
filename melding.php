<?php
/**
 * Het loket voor de meldingen.
 *
 * Het dashboard praat met dit bestand om de meldingen aan of uit te
 * zetten, om er eentje te proberen, en om te kijken of er intussen iets
 * nieuws is binnengekomen.
 *
 * Alles hier vereist dat je bent ingelogd. Er komt geen enkel gegeven
 * uit dit bestand als je dat niet bent.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';

start_sessie();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

/** Antwoordt en stopt. */
function antwoord(array $velden, $code = 200)
{
    http_response_code($code);
    echo json_encode($velden, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rol = huidige_rol();
if ($rol === '') {
    antwoord(['ok' => false, 'fout' => 'niet-ingelogd'], 401);
}

$actie = isset($_GET['actie']) ? (string) $_GET['actie'] : '';
if ($actie === '' && isset($_POST['actie'])) {
    $actie = (string) $_POST['actie'];
}

/* ------------------------------------------------------------------ */
/* Kijken of er iets nieuws is (terwijl het dashboard openstaat)       */
/* ------------------------------------------------------------------ */

if ($actie === 'stand') {
    $data = lees_data();
    antwoord([
        'ok'      => true,
        'wacht'   => wachtende_onderdelen($data, $rol),
        'laatste' => laatste_inzending_tijd($data, $rol),
    ]);
}

/* ------------------------------------------------------------------ */
/* Alles hieronder verandert iets, dus alleen via het formulier        */
/* ------------------------------------------------------------------ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    antwoord(['ok' => false, 'fout' => 'verkeerde-methode'], 405);
}
if (!csrf_klopt(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
    antwoord(['ok' => false, 'fout' => 'verlopen'], 403);
}
if (!push_mogelijk()) {
    antwoord(['ok' => false, 'fout' => 'kan-niet', 'uitleg' => push_waarom_niet()], 501);
}

/** Een korte omschrijving van het toestel, zodat je ze uit elkaar houdt. */
function toestel_omschrijving()
{
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if (preg_match('/iPhone/i', $ua))  { return 'iPhone'; }
    if (preg_match('/iPad/i', $ua))    { return 'iPad'; }
    if (preg_match('/Android/i', $ua)) { return 'Android-telefoon'; }
    if (preg_match('/Macintosh/i', $ua)) { return 'Mac'; }
    if (preg_match('/Windows/i', $ua)) { return 'Windows-computer'; }
    return 'toestel';
}

if ($actie === 'aanmelden') {
    $endpoint = isset($_POST['endpoint']) ? (string) $_POST['endpoint'] : '';
    $p256dh   = isset($_POST['p256dh'])   ? (string) $_POST['p256dh']   : '';
    $auth     = isset($_POST['auth'])     ? (string) $_POST['auth']     : '';

    if (!push_meld_aan($endpoint, $p256dh, $auth, $rol, toestel_omschrijving())) {
        antwoord(['ok' => false, 'fout' => 'aanmelden-mislukt'], 400);
    }
    antwoord([
        'ok'        => true,
        'aangemeld' => true,
        'toestellen' => push_aantal_toestellen($rol),
    ]);
}

if ($actie === 'afmelden') {
    $endpoint = isset($_POST['endpoint']) ? (string) $_POST['endpoint'] : '';
    push_meld_af($endpoint);
    antwoord([
        'ok'        => true,
        'aangemeld' => false,
        'toestellen' => push_aantal_toestellen($rol),
    ]);
}

if ($actie === 'test') {
    $gelukt = push_stuur(
        [$rol],
        'De meldingen staan aan',
        'Zo ziet het eruit als er iets nieuws binnenkomt.',
        'beheer.php',
        'proef'
    );
    antwoord(['ok' => $gelukt > 0, 'verstuurd' => $gelukt]);
}

antwoord(['ok' => false, 'fout' => 'onbekende-actie'], 400);
