<?php
/**
 * De pagina met zijn levensverhaal.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/inhoud.php';

// Deze pagina start bewust geen sessie: dan hoeft er ook geen
// cookie geplaatst te worden. Alleen insturen.php en beheer.php
// hebben er een nodig.


$PAGINA = [
    'titel'        => $INHOUD['leven_titel'],
    'kop'          => $INHOUD['leven_titel'],
    'omschrijving' => 'Het leven van Borger Breeveld, van de AMS in '
        . 'Paramaribo tot Wan Pipel en de STVS.',
];
require __DIR__ . '/kop.php';
?>

<section class="sectie">
    <div class="binnen smal">
        <?php foreach ($INHOUD['leven_alineas'] as $alinea): ?>
            <p class="lopend"><?= h($alinea) ?></p>
        <?php endforeach; ?>

        <p class="verder">
            <a class="knop" href="tijdlijn.php">Bekijk de tijdlijn</a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/voet.php'; ?>
