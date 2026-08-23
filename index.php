<?php
/**
 * De hoofdpagina: de banner, de galerij met goedgekeurde foto's en
 * video's, en de oproep om zelf iets in te sturen.
 */

require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/inhoud.php';

// Deze pagina start bewust geen sessie: dan hoeft er ook geen
// cookie geplaatst te worden. Alleen insturen.php en beheer.php
// hebben er een nodig.

zorg_voor_mappen();

$data  = lees_data();
$media = zichtbare_media($data);

/* De galerij in stukken, anders wordt de pagina eindeloos lang. */
$totaal    = count($media);
$paginas   = max(1, (int) ceil($totaal / GALERIJ_PER_PAGINA));
$pagina    = isset($_GET['p']) ? (int) $_GET['p'] : 1;
if ($pagina < 1) {
    $pagina = 1;
}
if ($pagina > $paginas) {
    $pagina = $paginas;
}
$media = array_slice($media, ($pagina - 1) * GALERIJ_PER_PAGINA, GALERIJ_PER_PAGINA);

$PAGINA = [
    'titel' => '',
    'groot' => true,
];
require __DIR__ . '/kop.php';
?>

<!-- ---------------------------------------------------------------- -->
<!-- Galerij                                                          -->
<!-- ---------------------------------------------------------------- -->
<section class="sectie" id="galerij">
    <div class="binnen">
        <h2><?= h($INHOUD['galerij_titel']) ?></h2>
        <p class="sectie-intro"><?= h($INHOUD['intro']) ?></p>

        <?php if ($totaal > 0): ?>
            <p class="galerij-teller">
                <?= (int) $totaal ?> foto's en video's<?php if ($paginas > 1): ?>,
                    pagina <?= (int) $pagina ?> van <?= (int) $paginas ?><?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if (!$media): ?>
            <p class="leeg"><?= h($INHOUD['galerij_leeg']) ?>
                <a href="insturen.php">Iets insturen</a>.</p>
        <?php else: ?>
            <ul class="galerij" id="galerij-lijst">
                <?php foreach ($media as $i => $item): ?>
                    <?php
                    // Voor foto's tonen we de verkleinde versie, ook in het
                    // groot. Die is scherp genoeg en laadt veel sneller op
                    // een telefoon. Video's spelen af vanaf het origineel.
                    $groot = $item['soort'] === 'foto'
                        ? bestand_url($item, true)   // verkleinde versie: sneller
                        : bestand_url($item);        // video: altijd het origineel
                    ?>
                    <li>
                        <button type="button" class="tegel"
                                data-index="<?= (int) $i ?>"
                                data-soort="<?= h($item['soort']) ?>"
                                data-bron="<?= h($groot) ?>"
                                data-inzender="<?= $item['toon_inzender'] ? h($item['inzender']) : '' ?>">
                            <?php if ($item['soort'] === 'foto'): ?>
                                <img src="<?= h(bestand_url($item, true)) ?>" loading="lazy"
                                     alt="<?= $item['toon_inzender']
                                        ? 'Foto, ingestuurd door ' . h($item['inzender'])
                                        : 'Foto uit de verzameling van de familie' ?>">
                            <?php elseif (!empty($item['thumb'])): ?>
                                <!-- Video met een eigen beeld eruit. -->
                                <span class="tegel-video tegel-video-beeld">
                                    <img src="<?= h(bestand_url($item, true)) ?>" loading="lazy"
                                         alt="<?= $item['toon_inzender']
                                            ? 'Video, ingestuurd door ' . h($item['inzender'])
                                            : 'Video uit de verzameling van de familie' ?>">
                                    <span class="tegel-speel" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false">
                                            <path d="M8 5v14l11-7z"></path>
                                        </svg>
                                    </span>
                                </span>
                                <span class="label-video" aria-hidden="true">Video</span>
                            <?php else: ?>
                                <!-- Lukte het niet om een beeld uit de video te
                                     halen, dan tonen we een rustig vlak. -->
                                <span class="tegel-video">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M8 5v14l11-7z"></path>
                                    </svg>
                                    <span class="visueel-verborgen">Video<?php
                                        if ($item['toon_inzender']) {
                                            echo ', ingestuurd door ' . h($item['inzender']);
                                        } ?></span>
                                </span>
                                <span class="label-video" aria-hidden="true">Video</span>
                            <?php endif; ?>
                            <?php if ($item['toon_inzender']): ?>
                                <span class="tegel-onder">Ingestuurd door
                                    <?= h($item['inzender']) ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($paginas > 1): ?>
            <nav class="bladeren" aria-label="Meer foto's en video's">
                <?php if ($pagina > 1): ?>
                    <a class="knop knop-klein" href="index.php?p=<?= $pagina - 1 ?>#galerij">Vorige</a>
                <?php endif; ?>

                <span class="bladeren-nummers">
                    <?php for ($n = 1; $n <= $paginas; $n++): ?>
                        <?php if ($n === $pagina): ?>
                            <span class="bladeren-nu" aria-current="true"><?= $n ?></span>
                        <?php else: ?>
                            <a href="index.php?p=<?= $n ?>#galerij"><?= $n ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </span>

                <?php if ($pagina < $paginas): ?>
                    <a class="knop knop-klein" href="index.php?p=<?= $pagina + 1 ?>#galerij">Volgende</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>

<!-- ---------------------------------------------------------------- -->
<!-- De oproep om iets in te sturen                                   -->
<!-- ---------------------------------------------------------------- -->
<section class="sectie sectie-groen oproep">
    <div class="binnen smal">
        <svg class="oproep-teken" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M4 6h16v12H4z"></path>
            <path d="M4 7l8 6 8-6"></path>
        </svg>
        <h2><?= h($INHOUD['oproep_titel']) ?></h2>
        <p class="oproep-tekst"><?= h($INHOUD['oproep_tekst']) ?></p>
        <a class="knop knop-licht knop-groot" href="insturen.php">
            <?= h($INHOUD['oproep_knop']) ?>
        </a>
    </div>
</section>

<!-- Vergroting van foto's en video's -->
<div class="lichtbak" id="lichtbak" role="dialog" aria-modal="true"
     aria-label="Vergrote weergave" hidden>
    <button type="button" class="lichtbak-sluit" id="lichtbak-sluit" aria-label="Sluiten">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M6 6l12 12M18 6L6 18"></path>
        </svg>
    </button>
    <button type="button" class="lichtbak-pijl lichtbak-vorige" id="lichtbak-vorige"
            aria-label="Vorige">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M15 5l-7 7 7 7"></path>
        </svg>
    </button>
    <figure class="lichtbak-inhoud" id="lichtbak-inhoud">
        <figcaption class="lichtbak-onderschrift" id="lichtbak-onderschrift"></figcaption>
    </figure>
    <button type="button" class="lichtbak-pijl lichtbak-volgende" id="lichtbak-volgende"
            aria-label="Volgende">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M9 5l7 7-7 7"></path>
        </svg>
    </button>
</div>

<?php require __DIR__ . '/voet.php'; ?>
