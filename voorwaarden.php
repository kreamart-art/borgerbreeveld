<?php
/**
 * De voorwaarden. De teksten staan in juridisch.php.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/inhoud.php';
require __DIR__ . '/juridisch.php';

// Ook deze pagina start geen sessie, dus er komt geen cookie.

$PAGINA = [
    'titel'        => $JURIDISCH['voorwaarden_titel'],
    'kop'          => $JURIDISCH['voorwaarden_titel'],
    'omschrijving' => 'De afspraken over deze herdenkingspagina en over '
        . 'wat je instuurt.',
];
require __DIR__ . '/kop.php';
?>

<section class="sectie">
    <div class="binnen smal juridisch">
        <p class="sectie-intro"><?= h($JURIDISCH['voorwaarden_intro']) ?></p>

        <?php foreach ($JURIDISCH['voorwaarden_blokken'] as $blok): ?>
            <h2><?= h($blok['kop']) ?></h2>
            <?php foreach ($blok['tekst'] as $alinea): ?>
                <p><?= h($alinea) ?></p>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <h2>Contact</h2>
        <p>
            <?= h($JURIDISCH['beheerder']) ?>, <?= h($JURIDISCH['beheerder_land']) ?>.<br>
            <a href="mailto:<?= h(CONTACT_EMAIL) ?>"><?= h(CONTACT_EMAIL) ?></a>
        </p>

        <p class="juridisch-datum">Laatst bijgewerkt op
            <?= h($JURIDISCH['bijgewerkt']) ?>.</p>

        <p class="verder">
            <a class="knop" href="privacy.php">Lees ook de privacyverklaring</a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/voet.php'; ?>
