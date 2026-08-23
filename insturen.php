<?php
/**
 * De pagina waarop mensen een foto, een video of een herinnering
 * insturen, en waar die inzending ook verwerkt wordt.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/inhoud.php';

start_sessie();
zorg_voor_mappen();

/* ------------------------------------------------------------------ */
/* Een inzending verwerken                                             */
/* ------------------------------------------------------------------ */

/**
 * We werken met de regel: eerst opslaan, dan doorsturen naar dezelfde
 * pagina. Zo dient niemand per ongeluk twee keer in door te vernieuwen.
 * Meldingen gaan even via de sessie mee.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Was de hele verzending te groot voor de server? Dan is alles leeg.
    if (post_liep_over()) {
        $_SESSION['melding'] = [
            'fouten' => [
                'De verzending was in totaal te groot voor de server. '
                . 'Stuur de bestanden in kleinere groepjes, bijvoorbeeld '
                . 'een paar foto\'s tegelijk, of stuur een grote video apart.',
            ],
        ];
        ga_naar('insturen.php');
    }

    $fouten         = [];
    $waarschuwingen = [];

    // Komt het formulier wel van deze pagina?
    if (!csrf_klopt(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $fouten[] = 'Het formulier was verlopen. Probeer het nog een keer.';
    }

    // Onzichtbaar vakje tegen automatische spam. Mensen vullen dit nooit in.
    $isBot = !empty($_POST['website']);

    $naam    = trim((string) (isset($_POST['naam']) ? $_POST['naam'] : ''));
    $relatie = trim((string) (isset($_POST['relatie']) ? $_POST['relatie'] : ''));
    $email   = trim((string) (isset($_POST['email']) ? $_POST['email'] : ''));
    $tekst   = trim((string) (isset($_POST['tekst']) ? $_POST['tekst'] : ''));

    if ($naam === '') {
        $fouten[] = 'Vul je naam in, dan weten we van wie de bijdrage komt.';
    } elseif (mb_strlen($naam) > 80) {
        $fouten[] = 'Je naam is wel erg lang. Houd het bij maximaal 80 tekens.';
    }
    if (mb_strlen($relatie) > 120) {
        $fouten[] = 'Houd "hoe kende je hem" bij maximaal 120 tekens.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fouten[] = 'Dat e-mailadres klopt niet helemaal. Laat het gerust leeg.';
    }
    if (mb_strlen($tekst) > MAX_TEKST_TEKENS) {
        $fouten[] = 'Je herinnering is langer dan ' . MAX_TEKST_TEKENS
            . ' tekens. Kort hem iets in.';
    }

    $uploads = upload_lijst('bestanden');
    if (count($uploads) > MAX_BESTANDEN) {
        $fouten[] = 'Je koos ' . count($uploads) . ' bestanden. Er kunnen er '
            . MAX_BESTANDEN . ' tegelijk mee. Stuur de rest in een tweede bijdrage.';
        $uploads = [];
    }
    if (empty($_POST['akkoord'])) {
        $fouten[] = $INHOUD['fout_akkoord'];
    }
    if ($tekst === '' && count($uploads) === 0) {
        $fouten[] = 'Er zat nog niets in je bijdrage. Schrijf een herinnering '
            . 'of kies een foto of video.';
    }

    // Bestanden opslaan. Gaat er eentje mis, dan gaan de andere gewoon door.
    $bewaard = [];
    if (!$fouten && !$isBot) {
        foreach ($uploads as $upload) {
            $uitkomst = bewaar_bestand($upload);
            if (isset($uitkomst['fout'])) {
                $waarschuwingen[] = $uitkomst['fout'];
            } else {
                $bewaard[] = $uitkomst;
            }
        }
        if ($tekst === '' && count($bewaard) === 0) {
            $fouten[] = 'Er kon niets worden opgeslagen. Probeer het nog een keer.';
        }
    }

    if ($fouten) {
        $_SESSION['melding'] = [
            'fouten'         => $fouten,
            'waarschuwingen' => $waarschuwingen,
            'oud'            => [
                'naam'    => $naam,
                'relatie' => $relatie,
                'email'   => $email,
                'tekst'   => $tekst,
                'akkoord' => !empty($_POST['akkoord']),
            ],
        ];
        ga_naar('insturen.php');
    }

    // Een bot krijgt gewoon het bedankje te zien, maar we bewaren niets.
    if (!$isBot) {
        muteer_data(function ($data) use ($naam, $relatie, $email, $tekst, $bewaard) {
            $data['inzendingen'][] = [
                'id'              => unieke_code(12),
                'naam'            => mb_substr($naam, 0, 80),
                'relatie'         => mb_substr($relatie, 0, 120),
                'email'           => mb_substr($email, 0, 160),
                'tekst'           => mb_substr($tekst, 0, MAX_TEKST_TEKENS),
                'tekst_zichtbaar' => false,
                'datum'           => time(),
                // Waar komt dit vandaan? 'bezoeker' betekent: via het
                // formulier ingestuurd. Alleen de beheerder beoordeelt die.
                'bron'            => 'bezoeker',
                // Vastleggen dat er toestemming is gegeven, en wanneer.
                'akkoord_op'      => time(),
                'bestanden'       => $bewaard,
            ];
            return $data;
        });

        // De familie een seintje geven dat er iets ligt.
        meld_nieuwe_inzending($naam, $relatie, $tekst, count($bewaard), $email);
    }

    $_SESSION['melding'] = ['bedankt' => true, 'waarschuwingen' => $waarschuwingen];
    ga_naar('insturen.php?verzonden=1');
}

/* ------------------------------------------------------------------ */
/* De pagina opbouwen                                                  */
/* ------------------------------------------------------------------ */

$melding = isset($_SESSION['melding']) ? $_SESSION['melding'] : [];
unset($_SESSION['melding']);

$fouten         = isset($melding['fouten']) ? $melding['fouten'] : [];
$waarschuwingen = isset($melding['waarschuwingen']) ? $melding['waarschuwingen'] : [];
$bedankt        = !empty($melding['bedankt']);
$oud            = isset($melding['oud']) ? $melding['oud'] : [
    'naam' => '', 'relatie' => '', 'email' => '', 'tekst' => '', 'akkoord' => false,
];
$grens = werkelijke_bestandslimiet();

$PAGINA = [
    'titel'        => $INHOUD['insturen_titel'],
    'kop'          => $INHOUD['insturen_titel'],
    'omschrijving' => 'Stuur je foto\'s, video\'s en herinneringen aan '
        . 'Borger Breeveld in.',
];
require __DIR__ . '/kop.php';
?>

<section class="sectie" id="formulier-sectie">
    <div class="binnen smal">
        <p class="sectie-intro"><?= h($INHOUD['insturen_intro']) ?></p>

        <?php if ($bedankt): ?>
            <div class="melding melding-goed" role="status" tabindex="-1" id="melding">
                <h2><?= h($INHOUD['bedankt_titel']) ?></h2>
                <p><?= h($INHOUD['bedankt_tekst']) ?></p>
                <?php if ($waarschuwingen): ?>
                    <p>Een paar bestanden zijn niet meegekomen:</p>
                    <ul>
                        <?php foreach ($waarschuwingen as $w): ?>
                            <li><?= h($w) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <p class="melding-verder">
                    <a href="index.php">Terug naar de galerij</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($fouten): ?>
            <div class="melding melding-fout" role="alert" tabindex="-1" id="melding">
                <h2>Er ontbreekt nog iets</h2>
                <ul>
                    <?php foreach ($fouten as $f): ?>
                        <li><?= h($f) ?></li>
                    <?php endforeach; ?>
                    <?php foreach ($waarschuwingen as $w): ?>
                        <li><?= h($w) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="formulier" method="post" action="insturen.php"
              enctype="multipart/form-data" id="formulier"
              data-max-bytes="<?= (int) $grens ?>"
              data-max-bestanden="<?= (int) MAX_BESTANDEN ?>">

            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

            <!-- Onzichtbaar vakje tegen bots. Laat dit staan. -->
            <div class="honingpot" aria-hidden="true">
                <label for="website">Vul dit veld niet in</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="veld">
                <label for="naam"><?= h($INHOUD['label_naam']) ?>
                    <span class="verplicht">verplicht</span></label>
                <input type="text" id="naam" name="naam" required maxlength="80"
                       autocomplete="name" value="<?= h($oud['naam']) ?>">
            </div>

            <div class="veld">
                <label for="relatie"><?= h($INHOUD['label_relatie']) ?></label>
                <input type="text" id="relatie" name="relatie" maxlength="120"
                       aria-describedby="hint-relatie" value="<?= h($oud['relatie']) ?>">
                <p class="hint" id="hint-relatie"><?= h($INHOUD['hint_relatie']) ?></p>
            </div>

            <div class="veld">
                <label for="email"><?= h($INHOUD['label_email']) ?></label>
                <input type="email" id="email" name="email" maxlength="160"
                       autocomplete="email" aria-describedby="hint-email"
                       value="<?= h($oud['email']) ?>">
                <p class="hint" id="hint-email"><?= h($INHOUD['hint_email']) ?></p>
            </div>

            <div class="veld">
                <label for="tekst"><?= h($INHOUD['label_tekst']) ?></label>
                <textarea id="tekst" name="tekst" rows="6"
                          maxlength="<?= (int) MAX_TEKST_TEKENS ?>"
                          aria-describedby="hint-tekst"><?= h($oud['tekst']) ?></textarea>
                <p class="hint" id="hint-tekst"><?= h($INHOUD['hint_tekst']) ?></p>
            </div>

            <div class="veld">
                <label for="bestanden"><?= h($INHOUD['label_bestanden']) ?></label>
                <div class="sleepvlak" id="sleepvlak">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="sleep-teken">
                        <path d="M12 16V4m0 0L8 8m4-4 4 4"></path>
                        <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path>
                    </svg>
                    <p class="sleep-tekst">Sleep je foto's en video's hierheen,
                        of <span class="sleep-link">kies ze op je telefoon</span>.</p>
                    <p class="hint">Maximaal <?= (int) MAX_BESTANDEN ?> bestanden per keer,
                        elk tot <?= h(leesbare_grootte($grens)) ?>.</p>
                    <input type="file" id="bestanden" name="bestanden[]" multiple
                           accept="image/*,video/*"
                           aria-describedby="hint-bestanden">
                </div>
                <p class="hint" id="hint-bestanden">Toegestaan: jpg, jpeg, png, gif, webp,
                    heic, heif, mp4, mov, m4v, webm, avi en 3gp.</p>
                <ul class="bestandenlijst" id="bestandenlijst"></ul>
            </div>

            <div class="veld veld-akkoord">
                <label class="akkoord">
                    <input type="checkbox" name="akkoord" value="1"
                           <?= !empty($oud['akkoord']) ? 'checked' : '' ?>>
                    <span><?= h($INHOUD['label_akkoord']) ?></span>
                </label>
                <p class="hint"><?= h($INHOUD['hint_akkoord']) ?>
                    Lees hoe we met je gegevens omgaan in de
                    <a href="privacy.php">privacyverklaring</a> en de
                    <a href="voorwaarden.php">voorwaarden</a>.</p>
            </div>

            <button type="submit" class="knop knop-groot" id="verstuurknop">
                <?= h($INHOUD['knop_versturen']) ?>
            </button>
            <p class="hint hint-versturen" id="verstuurhint"></p>
        </form>
    </div>
</section>

<?php require __DIR__ . '/voet.php'; ?>
