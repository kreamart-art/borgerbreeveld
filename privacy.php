<?php
/**
 * De privacyverklaring. De teksten staan in juridisch.php.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/inhoud.php';
require __DIR__ . '/juridisch.php';

// Ook deze pagina start geen sessie, dus er komt geen cookie.

$PAGINA = [
    'titel'        => $JURIDISCH['privacy_titel'],
    'kop'          => $JURIDISCH['privacy_titel'],
    'omschrijving' => 'Hoe deze herdenkingspagina omgaat met je gegevens.',
];
require __DIR__ . '/kop.php';
?>

<section class="sectie">
    <div class="binnen smal juridisch">
        <p class="sectie-intro"><?= h($JURIDISCH['privacy_intro']) ?></p>

        <?php foreach ($JURIDISCH['privacy_blokken'] as $blok): ?>
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
            <a class="knop" href="voorwaarden.php">Lees ook de voorwaarden</a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/voet.php'; ?>
