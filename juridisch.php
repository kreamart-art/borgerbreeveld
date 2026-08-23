<?php
/**
 * De teksten van de privacyverklaring en de voorwaarden.
 *
 * Deze staan apart van inhoud.php omdat het lange, juridische teksten
 * zijn. Je mag ze aanpassen, maar lees ze dan wel goed na: ze beschrijven
 * hoe de website werkt. Verandert er iets aan de website, pas deze
 * teksten dan mee aan.
 *
 * Twee dingen hoef je hier NIET in te vullen, die komen uit config.php:
 * het contactadres en de naam van de website.
 */

$JURIDISCH = [

/* ------------------------------------------------------------------ */
/* Wie is verantwoordelijk                                             */
/* ------------------------------------------------------------------ */

    // Vul hier in wie de pagina beheert. Dit hoort er wettelijk bij.
    'beheerder'      => 'De familie Breeveld',
    'beheerder_land' => 'Nederland',

    // Datum waarop deze teksten voor het laatst zijn aangepast.
    'bijgewerkt' => '22 augustus 2026',

/* ------------------------------------------------------------------ */
/* Privacyverklaring                                                   */
/* ------------------------------------------------------------------ */

    'privacy_titel' => 'Privacy',

    'privacy_intro' => 'Deze pagina is gemaakt om herinneringen aan Borger '
        . 'Breeveld te bewaren. Daarvoor verwerken we een klein beetje '
        . 'persoonsgegevens. Hieronder staat precies wat, waarom, en wat '
        . 'je rechten zijn. We houden het zo kort en gewoon mogelijk.',

    // Elk blok: een kop en een of meer alinea's.
    'privacy_blokken' => [

        [
            'kop' => 'Wie is verantwoordelijk',
            'tekst' => [
                'Deze pagina wordt beheerd door de familie Breeveld. Heb je '
                . 'een vraag over je gegevens, of wil je iets laten '
                . 'weghalen, mail dan naar het adres onderaan deze pagina. '
                . 'We reageren zo snel als we kunnen.',
            ],
        ],

        [
            'kop' => 'Wat we bewaren als je iets instuurt',
            'tekst' => [
                'Stuur je een foto, een video of een herinnering in, dan '
                . 'bewaren we: je naam, wat je eventueel invulde bij "hoe '
                . 'kende je hem", je e-mailadres als je dat achterliet, je '
                . 'geschreven herinnering, de bestanden die je meestuurde, '
                . 'en het moment waarop je het instuurde.',

                'Je naam verschijnt bij je bijdrage op de pagina, zodat '
                . 'anderen zien van wie hij komt. Je e-mailadres verschijnt '
                . 'nooit op de pagina. Dat zien alleen de familieleden die '
                . 'de inzendingen beoordelen, zodat zij je iets kunnen '
                . 'vragen of laten weten dat je bijdrage geplaatst is.',

                'We vragen niet meer dan dit. Er zit geen verplicht veld in '
                . 'het formulier behalve je naam.',
            ],
        ],

        [
            'kop' => 'Waarom we het mogen bewaren',
            'tekst' => [
                'Omdat je het zelf instuurt en daarbij aanvinkt dat je '
                . 'bijdrage op deze pagina getoond mag worden. Dat heet '
                . 'toestemming. Je kunt die toestemming altijd weer '
                . 'intrekken: stuur een mailtje en we halen je bijdrage weg.',
            ],
        ],

        [
            'kop' => 'Wie het te zien krijgt',
            'tekst' => [
                'Wat de familie goedkeurt, staat openbaar op deze pagina en '
                . 'is dus voor iedereen te zien. Wat niet is goedgekeurd, '
                . 'zien alleen de familieleden met toegang tot het beheer.',

                'We verkopen niets door, we delen niets met adverteerders, '
                . 'en er staat geen enkele meetdienst of sociaal netwerk op '
                . 'deze pagina. De enige partij die er verder bij kan is het '
                . 'hostingbedrijf waar de website staat, omdat de bestanden '
                . 'nu eenmaal op hun computers staan.',
            ],
        ],

        [
            'kop' => 'Hoe lang we het bewaren',
            'tekst' => [
                'Zolang deze herdenkingspagina bestaat. Vraag je om '
                . 'verwijdering, dan halen we je bijdrage en de bijbehorende '
                . 'bestanden weg. Gaat de pagina ooit offline, dan '
                . 'verdwijnen de gegevens mee.',
            ],
        ],

        [
            'kop' => 'Cookies',
            'tekst' => [
                'Deze pagina gebruikt geen cookies om je te volgen, en er '
                . 'staat geen enkele advertentie- of meetdienst op. Daarom '
                . 'zie je hier ook geen cookiebalk.',

                'Er is één uitzondering, en die is puur technisch. Ga je '
                . 'naar de pagina "Insturen", dan zet de website een klein '
                . 'cookie (PHPSESSID) op je apparaat. Dat is nodig om te '
                . 'controleren dat het formulier echt van deze website komt, '
                . 'en om je na het versturen het bedankje te kunnen tonen. '
                . 'Het bevat alleen een willekeurige code, geen gegevens '
                . 'over jou, en het verdwijnt zodra je je browser sluit.',

                'Op de andere pagina\'s van deze website wordt helemaal geen '
                . 'cookie geplaatst.',

                'Familieleden die inloggen op het beheer krijgen datzelfde '
                . 'cookie, plus een klein stukje opslag in hun browser dat '
                . 'onthoudt of ze de uitleg over de app hebben weggeklikt.',
            ],
        ],

        [
            'kop' => 'Bezoekgegevens en beveiliging',
            'tekst' => [
                'Zoals elke website houdt de server van het hostingbedrijf '
                . 'een logboek bij van bezoeken, met daarin onder meer '
                . 'IP-adressen. Dat gebeurt automatisch en is nodig om de '
                . 'website te laten werken en storingen op te lossen.',

                'Verder bewaren we tijdelijk mislukte inlogpogingen op het '
                . 'beheer, om te voorkomen dat iemand het wachtwoord gaat '
                . 'raden. Het IP-adres wordt daarbij versleuteld opgeslagen '
                . 'en na een kwartier weer weggegooid. Aan een gewone '
                . 'inzending koppelen we geen IP-adres.',
            ],
        ],

        [
            'kop' => 'Foto\'s van andere mensen',
            'tekst' => [
                'Op ingestuurde foto\'s staan vaak meer mensen dan alleen de '
                . 'inzender. Stuur daarom alleen materiaal in waarvan je '
                . 'denkt dat de mensen erop het goed zouden vinden. De '
                . 'familie kijkt alles na voordat het geplaatst wordt.',

                'Sta je zelf op een foto en wil je die niet op de pagina '
                . 'hebben? Mail ons, dan halen we hem weg. Daar hoef je geen '
                . 'reden voor te geven.',
            ],
        ],

        [
            'kop' => 'Je rechten',
            'tekst' => [
                'Je mag ons altijd vragen welke gegevens we van je hebben, '
                . 'ze laten aanpassen, of ze laten verwijderen. Ook mag je '
                . 'je toestemming intrekken. Eén mailtje is genoeg.',

                'Ben je het ergens niet mee eens en komen we er samen niet '
                . 'uit, dan kun je een klacht indienen bij de Autoriteit '
                . 'Persoonsgegevens, de Nederlandse toezichthouder.',
            ],
        ],
    ],

/* ------------------------------------------------------------------ */
/* Voorwaarden                                                         */
/* ------------------------------------------------------------------ */

    'voorwaarden_titel' => 'Voorwaarden',

    'voorwaarden_intro' => 'Een paar afspraken over deze pagina en over wat '
        . 'je instuurt. Geen kleine lettertjes, gewoon hoe we het bedoeld '
        . 'hebben.',

    'voorwaarden_blokken' => [

        [
            'kop' => 'Waar deze pagina voor is',
            'tekst' => [
                'Dit is een herdenkingspagina voor Borger Breeveld, gemaakt '
                . 'door zijn familie. Hij is bedoeld om herinneringen te '
                . 'verzamelen en te bewaren, meer niet. Er wordt niets '
                . 'verkocht en er wordt geen geld verdiend.',
            ],
        ],

        [
            'kop' => 'Wat je instuurt blijft van jou',
            'tekst' => [
                'De foto\'s, video\'s en teksten die je instuurt blijven van '
                . 'jou. Je geeft de familie alleen toestemming om ze op deze '
                . 'herdenkingspagina te tonen, en om er een kleinere versie '
                . 'van te maken zodat de pagina snel laadt. Verder gebruiken '
                . 'we ze nergens voor.',

                'Je kunt die toestemming altijd intrekken. Mail ons en we '
                . 'halen je bijdrage weg.',
            ],
        ],

        [
            'kop' => 'Wat je instuurt hoort van jou te zijn',
            'tekst' => [
                'Stuur alleen materiaal in dat je zelf hebt gemaakt, of '
                . 'waarvan je zeker weet dat je het mag delen. Stuur geen '
                . 'foto\'s van anderen zonder dat je denkt dat zij dat goed '
                . 'zouden vinden.',
            ],
        ],

        [
            'kop' => 'De familie bepaalt wat er komt te staan',
            'tekst' => [
                'Niets komt automatisch op de pagina. De familie kijkt alles '
                . 'na en kiest per foto, per video en per verhaal wat er '
                . 'geplaatst wordt. Er is geen recht op plaatsing, en de '
                . 'familie mag zonder opgaaf van reden iets weglaten of '
                . 'later weghalen.',

                'Beledigende, kwetsende of ongepaste inzendingen worden '
                . 'verwijderd.',
            ],
        ],

        [
            'kop' => 'Iets laten weghalen',
            'tekst' => [
                'Wil je je eigen bijdrage weg hebben, of sta je op een foto '
                . 'die je liever niet op de pagina ziet? Stuur een mailtje. '
                . 'Je hoeft niet uit te leggen waarom. We halen het weg.',
            ],
        ],

        [
            'kop' => 'Deze pagina zelf',
            'tekst' => [
                'We doen ons best om alles te laten kloppen, maar de familie '
                . 'is niet aansprakelijk voor eventuele fouten op deze '
                . 'pagina of voor het tijdelijk niet bereikbaar zijn ervan.',

                'De teksten over zijn leven zijn gebaseerd op de bronnen die '
                . 'onderaan de pagina staan.',
            ],
        ],
    ],
];
