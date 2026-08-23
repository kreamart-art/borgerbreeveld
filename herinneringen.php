<?php
/**
 * De pagina met de goedgekeurde geschreven herinneringen.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/inhoud.php';

// Deze pagina start bewust geen sessie: dan hoeft er ook geen
// cookie geplaatst te worden. Alleen insturen.php en beheer.php
// hebben er een nodig.

zorg_voor_mappen();

$data          = lees_data();
$herinneringen = zichtbare_herinneringen($data);

$PAGINA = [
    'titel'        => $INHOUD['herinneringen_titel'],
    'kop'          => $INHOUD['herinneringen_titel'],
    'omschrijving' => 'Herinneringen aan Borger Breeveld, in de woorden van '
        . 'familie, vrienden en publiek.',
];
require __DIR__ . '/kop.php';
?>

<section class="sectie sectie-zacht">
    <div class="binnen">
        <p class="sectie-intro"><?= h($INHOUD['herinneringen_intro']) ?></p>

        <?php if (!$herinneringen): ?>
            <p class="leeg"><?= h($INHOUD['herinneringen_leeg']) ?>
                <a href="insturen.php">Iets insturen</a>.</p>
        <?php else: ?>
            <div class="kaarten">
                <?php foreach ($herinneringen as $herinnering): ?>
                    <article class="kaart">
                        <svg class="kaart-teken" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M9 7H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h2v1a2 2 0 0 1-2 2H4v2h1a4 4 0 0 0 4-4V7zm11 0h-4a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h2v1a2 2 0 0 1-2 2h-1v2h1a4 4 0 0 0 4-4V7z"></path>
                        </svg>
                        <p class="kaart-tekst"><?= nl2br(h($herinnering['tekst'])) ?></p>
                        <p class="kaart-naam">
                            <?= h($herinnering['naam']) ?><?php if (!empty($herinnering['relatie'])): ?>
                                <span class="kaart-relatie"><?= h($herinnering['relatie']) ?></span>
                            <?php endif; ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="verder">
            <a class="knop" href="insturen.php">Deel jouw herinnering</a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/voet.php'; ?>
