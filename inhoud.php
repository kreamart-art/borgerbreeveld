<?php
/**
 * Alle teksten van de pagina staan hier bij elkaar.
 *
 * Je mag alles tussen de aanhalingstekens rustig aanpassen.
 * Let er alleen op dat je de aanhalingstekens, de komma's en de haakjes
 * laat staan zoals ze staan.
 */

/* ------------------------------------------------------------------ */
/* Naam, datums en korte teksten                                       */
/* ------------------------------------------------------------------ */

$INHOUD = [

    // Wat er in het tabblad van de browser komt te staan.
    'paginatitel' => 'Borger Breeveld 1944 – 2026',

    // Korte omschrijving voor zoekmachines en gedeelde links.
    'omschrijving' => 'Een pagina ter herinnering aan Borger Breeveld, '
        . 'Surinaams acteur, filmmaker en mediaman. Stuur je foto\'s, '
        . 'video\'s en herinneringen in.',

    'naam'   => 'Borger Breeveld',
    'datums' => '20 juli 1944 – 10 augustus 2026',

    // Beschrijving van de banner bovenaan, voor wie de pagina laat voorlezen.
    'banner_alt' => 'Borger Breeveld door de jaren heen: achter de filmcamera, '
        . 'als jonge man, met zijn fototoestel, en lachend in een hoed, '
        . 'tegen een achtergrond van Surinaams groen.',

    // Beschrijving van de banner op de andere pagina's.
    'banner_alt_pagina' => 'Borger Breeveld als jonge man, in wit overhemd, '
        . 'tegen een achtergrond van Surinaams groen met zonlicht door de '
        . 'bladeren.',

    // De tekst op de knop in de balk bovenaan. De korte versie is voor
    // een telefoon, daar past de lange niet naast het menu.
    'insturen_knop_balk' => 'Stuur een herinnering',
    'insturen_knop_kort' => 'Insturen',

    // De twee of drie regels boven de galerij op de hoofdpagina.
    'intro' => 'Acteur, filmmaker, zanger, gitarist en mediaduizendpoot. '
        . 'Voor velen voor altijd Roy uit Wan Pipel, voor anderen de stem '
        . 'en de hand achter jarenlange televisie. '
        . 'Op deze pagina bewaren we samen wat hij heeft achtergelaten.',

    // De pagina's in het menu bovenaan, in deze volgorde.
    // 'bestand' is de naam van het php-bestand, 'label' wat er in het
    // menu staat.
    'menu' => [
        ['bestand' => 'leven.php',         'label' => 'Zijn leven'],
        ['bestand' => 'tijdlijn.php',      'label' => 'Tijdlijn'],
        ['bestand' => 'index.php',         'label' => 'Galerij'],
        ['bestand' => 'herinneringen.php', 'label' => 'Herinneringen'],
        ['bestand' => 'insturen.php',      'label' => 'Insturen'],
    ],

/* ------------------------------------------------------------------ */
/* Zijn leven, vier alinea's                                           */
/* ------------------------------------------------------------------ */

    'leven_titel' => 'Zijn leven',

    'leven_alineas' => [

        'Borger Breeveld werd geboren op 20 juli 1944. In de jaren zestig '
        . 'zat hij op de AMS in Paramaribo, waar hij vooral opviel door '
        . 'sport, muziek en innemendheid, en wat minder door studiediscipline. '
        . 'Daar zette hij ook zijn eerste eigen producties op, waaronder '
        . 'One Night in Paradise in Theater Thalia.',

        'In Nederland volgde hij een praktijkopleiding film en camerawerk. '
        . 'In 1976 speelde hij Roy in Wan Pipel van regisseur Pim de la Parra, '
        . 'de eerste grote Surinaamse speelfilm, met Diana Gangaram Panday '
        . 'als Rubia. De film vertelt het verhaal van een in Nederland '
        . 'studerende Surinamer die terugkeert en moet kiezen tussen twee '
        . 'levens en twee liefdes. Aanvankelijk flopte de film, maar hij '
        . 'groeide uit tot een cultureel fenomeen. Mensen kennen de dialogen '
        . 'uit hun hoofd, en rond de Surinaamse onafhankelijkheidsdag wordt '
        . 'hij nog ieder jaar vertoond.',

        'Decennialang werkte hij bij de Surinaamse staatstelevisie STVS, als '
        . 'programmamaker, regisseur en mediamanager, onder meer aan het '
        . 'actualiteitenprogramma Mmanten Taki. Hij was medeoprichter van het '
        . 'Filminstituut Paramaribo, samen met Pim de la Parra en Arie '
        . 'Verkuijl, en in 2005 medeoprichter en secretaris van de Stichting '
        . 'Surinaamse Filmacademie.',

        'Later speelde hij ook in Sing Song en in Wiren, en in 2019 stond hij '
        . 'op de planken in het cabaretprogramma Switi Sranan. In 2021 '
        . 'ontving hij een Lifetime Media Award. Op 10 augustus 2026 '
        . 'overleed hij op 82-jarige leeftijd in een ziekenhuis in Nederland, '
        . 'na nierfalen bij een diabetesachtergrond. Hij laat zijn dochter '
        . 'na, actrice Manoushka Zeegelaar Breeveld, en zijn broers en zus '
        . 'Carl, Hans, Clarence en Lucia. Op 13 augustus 2026 namen we '
        . 'afscheid van hem in Nederland, met een livestream voor Suriname '
        . 'en daarbuiten. Na de crematie gaat zijn as naar Suriname.',
    ],

/* ------------------------------------------------------------------ */
/* De tijdlijn                                                         */
/* ------------------------------------------------------------------ */

    'tijdlijn_titel' => 'Tijdlijn',

    // Per gebeurtenis: 'wanneer', 'wat' en een korte toelichting.
    'tijdlijn' => [
        [
            'wanneer' => '20 juli 1944',
            'wat'     => 'Geboren',
            'tekst'   => 'Borger Breeveld wordt geboren.',
        ],
        [
            'wanneer' => 'Jaren zestig',
            'wat'     => 'De AMS in Paramaribo',
            'tekst'   => 'Hij valt op door sport, muziek en innemendheid, '
                . 'meer dan door studiediscipline, en zet er zijn eigen '
                . 'producties op, waaronder One Night in Paradise in '
                . 'Theater Thalia.',
        ],
        [
            'wanneer' => 'Nederland',
            'wat'     => 'Opleiding film en camerawerk',
            'tekst'   => 'Hij volgt een praktijkopleiding film en camerawerk.',
        ],
        [
            'wanneer' => '1976',
            'wat'     => 'Roy in Wan Pipel',
            'tekst'   => 'In de eerste grote Surinaamse speelfilm, van '
                . 'regisseur Pim de la Parra, met Diana Gangaram Panday als '
                . 'Rubia. De film flopt eerst en wordt daarna een cultureel '
                . 'fenomeen dat rond de onafhankelijkheidsdag jaarlijks '
                . 'wordt vertoond.',
        ],
        [
            'wanneer' => 'De STVS-jaren',
            'wat'     => 'Programmamaker, regisseur en mediamanager',
            'tekst'   => 'Decennialang bij de Surinaamse staatstelevisie '
                . 'STVS, onder meer aan het actualiteitenprogramma '
                . 'Mmanten Taki.',
        ],
        [
            'wanneer' => 'Filminstituut Paramaribo',
            'wat'     => 'Medeoprichter',
            'tekst'   => 'Samen met Pim de la Parra en Arie Verkuijl.',
        ],
        [
            'wanneer' => '2005',
            'wat'     => 'Stichting Surinaamse Filmacademie',
            'tekst'   => 'Medeoprichter en secretaris.',
        ],
        [
            'wanneer' => 'Later werk',
            'wat'     => 'Sing Song en Wiren',
            'tekst'   => 'Hij speelt in beide films.',
        ],
        [
            'wanneer' => '2019',
            'wat'     => 'Switi Sranan',
            'tekst'   => 'Hij staat op de planken in dit cabaretprogramma.',
        ],
        [
            'wanneer' => '2021',
            'wat'     => 'Lifetime Media Award',
            'tekst'   => 'Voor een leven in de media.',
        ],
        [
            'wanneer' => '10 augustus 2026',
            'wat'     => 'Overleden',
            'tekst'   => 'Op 82-jarige leeftijd, in een ziekenhuis in '
                . 'Nederland.',
        ],
        [
            'wanneer' => '13 augustus 2026',
            'wat'     => 'Afscheid',
            'tekst'   => 'In Nederland, met een livestream voor Suriname en '
                . 'daarbuiten. Na de crematie gaat zijn as naar Suriname.',
        ],
    ],

/* ------------------------------------------------------------------ */
/* Galerij en herinneringen                                            */
/* ------------------------------------------------------------------ */

    // De oproep onderaan de hoofdpagina.
    'oproep_titel' => 'Help mee zijn verhaal te bewaren',
    'oproep_tekst' => 'Heb je een foto van vroeger, een filmpje, of een '
        . 'herinnering die je bijgebleven is? Stuur hem in. Hoe kort ook. '
        . 'De familie kijkt alles rustig na en bepaalt wat er op deze '
        . 'pagina komt te staan.',
    'oproep_knop'  => 'Stuur je herinnering in',

    'galerij_titel' => 'Galerij',
    'galerij_intro' => 'Foto\'s en video\'s, ingestuurd door familie, '
        . 'vrienden en publiek.',
    'galerij_leeg'  => 'Hier komen de foto\'s en video\'s die jullie '
        . 'insturen. Wees de eerste.',

    'herinneringen_titel' => 'Herinneringen',
    'herinneringen_intro' => 'Wat mensen zich herinneren, in hun eigen woorden.',
    'herinneringen_leeg'  => 'Hier komen de verhalen die jullie insturen. '
        . 'Deel gerust de jouwe, hoe kort ook.',

/* ------------------------------------------------------------------ */
/* Het formulier                                                       */
/* ------------------------------------------------------------------ */

    'insturen_titel' => 'Stuur je herinnering in',
    'insturen_intro' => 'Heb je een foto, een filmpje of een verhaal? '
        . 'Stuur het hier in. De familie kijkt alles rustig na en bepaalt '
        . 'wat er op deze pagina komt te staan.',

    'label_naam'     => 'Je naam',
    'label_relatie'  => 'Hoe kende je hem?',
    'hint_relatie'   => 'Bijvoorbeeld oud-collega STVS, neef, of buurman.',
    'label_email'    => 'Je e-mailadres',
    'hint_email'     => 'Optioneel. Blijft privé en komt nooit op de pagina. '
        . 'Alleen voor als de familie je iets wil vragen.',
    'label_tekst'    => 'Je herinnering',
    'hint_tekst'     => 'Een zin is genoeg. Een heel verhaal mag ook.',
    'label_bestanden'=> 'Foto\'s en video\'s',
    // De toestemmingsvraag onder het formulier. Wettelijk verplicht:
    // zonder toestemming mogen we een bijdrage niet plaatsen.
    'label_akkoord' => 'Ik stuur dit zelf in en vind het goed dat de familie '
        . 'mijn bijdrage op deze pagina toont.',
    'hint_akkoord'  => 'Je kunt dit altijd weer intrekken. Eén mailtje en we '
        . 'halen je bijdrage weg.',
    'fout_akkoord'  => 'Zet nog even een vinkje bij de laatste vraag, dan '
        . 'weten we dat je bijdrage getoond mag worden.',

    'knop_versturen' => 'Versturen',
    'knop_bezig'     => 'Bezig met versturen',

    'bedankt_titel' => 'Dank je wel',
    'bedankt_tekst' => 'Je bijdrage is binnengekomen. De familie kijkt '
        . 'ernaar en zet hem daarna op de pagina.',

/* ------------------------------------------------------------------ */
/* Voettekst                                                           */
/* ------------------------------------------------------------------ */

    'rechten_tekst' => 'Wat je instuurt blijft van jou. Het wordt alleen '
        . 'op deze herdenkingspagina gebruikt en nergens anders voor.',

    'contact_tekst' => 'Vragen, of wil je iets laten weghalen? Mail naar',

    // Het onopvallende linkje onderaan, waarmee de familie inlogt.
    'beheer_link' => 'Voor de familie',

    'bronnen_titel' => 'Bronnen',

    'bronnen' => [
        [
            'naam' => 'NOS',
            'url'  => 'https://nos.nl/artikel/2626296-surinaamse-acteur-borger-breeveld-82-overleden-bekend-van-wan-pipel',
        ],
        [
            'naam' => 'Waterkant, overlijdensbericht',
            'url'  => 'https://www.waterkant.net/suriname/2026/08/10/surinaams-media-icoon-en-wan-pipel-acteur-borger-breeveld-overleden/',
        ],
        [
            'naam' => 'Starnieuws, Herinneringen aan Borger Breeveld',
            'url'  => 'https://www.starnieuws.com/index.php/welcome/index/nieuwsitem/93394',
        ],
        [
            'naam' => 'Waterkant, uitvaartverslag',
            'url'  => 'https://www.waterkant.net/suriname/2026/08/13/laatste-eer-voor-borger-breeveld-uitvaart-van-surinaams-film-en-media-icoon-vandaag-in-nederland/',
        ],
    ],
];
