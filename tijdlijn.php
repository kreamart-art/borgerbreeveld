<?php
/**
 * De pagina met de tijdlijn.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/inhoud.php';

// Deze pagina start bewust geen sessie: dan hoeft er ook geen
// cookie geplaatst te worden. Alleen insturen.php en beheer.php
// hebben er een nodig.


$PAGINA = [
    'titel'        => $INHOUD['tijdlijn_titel'],
    'kop'          => $INHOUD['tijdlijn_titel'],
    'omschrijving' => 'De tijdlijn van Borger Breeveld, van 1944 tot 2026.',
];
require __DIR__ . '/kop.php';
?>

<section class="sectie sectie-groen">
    <div class="binnen smal">
        <ol class="tijdlijn">
            <?php foreach ($INHOUD['tijdlijn'] as $punt): ?>
                <li>
                    <span class="tijdlijn-stip" aria-hidden="true"></span>
                    <p class="tijdlijn-wanneer"><?= h($punt['wanneer']) ?></p>
                    <h2 class="tijdlijn-wat"><?= h($punt['wat']) ?></h2>
                    <p class="tijdlijn-tekst"><?= h($punt['tekst']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>

        <p class="verder">
            <a class="knop knop-licht" href="leven.php">Lees zijn verhaal</a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/voet.php'; ?>
