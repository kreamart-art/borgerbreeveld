<?php
/**
 * De onderkant van elke pagina: de voettekst met de bronnen.
 */
?>
</main>

<footer class="voet">
    <div class="binnen">
        <p class="voet-rechten"><?= h($INHOUD['rechten_tekst']) ?></p>
        <p class="voet-contact"><?= h($INHOUD['contact_tekst']) ?>
            <a href="mailto:<?= h(CONTACT_EMAIL) ?>"><?= h(CONTACT_EMAIL) ?></a>.</p>

        <nav class="voet-juridisch" aria-label="Juridische informatie">
            <a href="privacy.php">Privacy</a>
            <a href="voorwaarden.php">Voorwaarden</a>
        </nav>

        <nav class="voet-menu" aria-label="Pagina's van deze website">
            <?php foreach ($INHOUD['menu'] as $punt): ?>
                <a href="<?= h($punt['bestand']) ?>"><?= h($punt['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <h2 class="voet-kop"><?= h($INHOUD['bronnen_titel']) ?></h2>
        <ul class="bronnen">
            <?php foreach ($INHOUD['bronnen'] as $bron): ?>
                <li><a href="<?= h($bron['url']) ?>" rel="noopener noreferrer"
                       target="_blank"><?= h($bron['naam']) ?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="voet-onder">
            <p class="voet-naam"><?= h($INHOUD['naam']) ?>, <?= h($INHOUD['datums']) ?></p>

            <!-- Onopvallend, maar wel te vinden: hier logt de familie in. -->
            <a class="voet-beheer" href="beheer.php" rel="nofollow">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M7 10V7a5 5 0 0 1 10 0v3"></path>
                    <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                </svg>
                <?= h($INHOUD['beheer_link']) ?>
            </a>
        </div>
    </div>
</footer>

<script src="assets/app.js?v=2"></script>
</body>
</html>
