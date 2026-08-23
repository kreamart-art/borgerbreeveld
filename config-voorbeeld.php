<?php
/**
 * Instellingen van de herdenkingspagina.
 *
 * Dit is het enige bestand waarin je normaal iets hoeft te veranderen,
 * samen met inhoud.php (daar staan alle teksten).
 */

/* ------------------------------------------------------------------ */
/* Beheer                                                              */
/* ------------------------------------------------------------------ */

/**
 * De twee wachtwoorden voor het dashboard.
 *
 * Met het beheerderswachtwoord kun je alles, inclusief een hele
 * inzending voorgoed verwijderen.
 *
 * Met het familiewachtwoord kun je foto's, video's en verhalen op de
 * pagina zetten of eraf halen, en de namen en teksten aanpassen. Alleen
 * definitief verwijderen kan daar niet, zodat er nooit per ongeluk iets
 * voorgoed weg is.
 */
/*
 * Staat de website op een server met Coolify of Docker? Dan kun je deze
 * waarden ook als omgevingsvariabele meegeven. Die gaan dan vóór op wat
 * hier staat, zodat je wachtwoorden niet in de code hoeven te staan.
 *
 *   BORGER_BEHEER_WACHTWOORD
 *   BORGER_FAMILIE_WACHTWOORD
 *   BORGER_CONTACT_EMAIL
 */
define('BEHEER_WACHTWOORD',  getenv('BORGER_BEHEER_WACHTWOORD')  ?: 'verander-dit-wachtwoord');
define('FAMILIE_WACHTWOORD', getenv('BORGER_FAMILIE_WACHTWOORD') ?: 'verander-dit-ook');

/** Contactadres dat onderaan de pagina staat. */
define('CONTACT_EMAIL', getenv('BORGER_CONTACT_EMAIL') ?: 'info@voorbeeld.nl');

/**
 * Stuur een berichtje naar het contactadres zodra iemand iets instuurt.
 * Zet op false als je liever zelf af en toe in het dashboard kijkt.
 */
const MELD_NIEUWE_INZENDING = true;

/**
 * Het adres waar die melding vandaan lijkt te komen. Neem een adres van
 * je eigen domein, anders belandt de mail vaak in de spam. Laat je het
 * leeg, dan gebruiken we het contactadres hierboven.
 */
const AFZENDER_EMAIL = '';

/* ------------------------------------------------------------------ */
/* Beveiliging van het dashboard                                       */
/* ------------------------------------------------------------------ */

/** Zoveel keer mag iemand het wachtwoord fout typen. */
const MAX_INLOGPOGINGEN = 5;

/** En daarna zoveel minuten wachten. */
const INLOG_WACHTTIJD_MINUTEN = 15;

/* ------------------------------------------------------------------ */
/* Limieten voor inzendingen                                           */
/* ------------------------------------------------------------------ */

/** Hoeveel bestanden mag iemand in één keer insturen. */
const MAX_BESTANDEN = 10;

/** Grootste toegestane bestand, in megabytes. */
const MAX_BESTAND_MB = 200;

/** Hoeveel foto's en video's per pagina in de galerij. */
const GALERIJ_PER_PAGINA = 48;

/** Maximale lengte van een geschreven herinnering, in tekens. */
const MAX_TEKST_TEKENS = 5000;

/* ------------------------------------------------------------------ */
/* Foto's verkleinen voor de galerij                                   */
/* ------------------------------------------------------------------ */

/** Langste zijde van de verkleinde versie, in pixels. */
const THUMB_MAX_ZIJDE = 1400;

/** JPEG-kwaliteit van de verkleinde versie (1 tot 100). */
const THUMB_KWALITEIT = 82;

/* ------------------------------------------------------------------ */
/* Overig                                                              */
/* ------------------------------------------------------------------ */

/** Tijdzone voor de datums in het beheerpaneel. */
const TIJDZONE = 'Europe/Amsterdam';

/**
 * Welke bestandstypen mogen binnenkomen.
 * De extensie links, het soort rechts ('foto' of 'video').
 */
const TOEGESTANE_EXTENSIES = [
    'jpg'  => 'foto',
    'jpeg' => 'foto',
    'png'  => 'foto',
    'gif'  => 'foto',
    'webp' => 'foto',
    'heic' => 'foto',
    'heif' => 'foto',
    'mp4'  => 'video',
    'mov'  => 'video',
    'm4v'  => 'video',
    'webm' => 'video',
    'avi'  => 'video',
    '3gp'  => 'video',
];
