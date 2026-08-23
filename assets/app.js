/* ------------------------------------------------------------------
   Kleine hulpjes in de browser:
   1. het slepen en tonen van gekozen bestanden
   2. de vergroting van foto's en video's in de galerij
   3. het uitklapmenu in de balk bovenaan
   ------------------------------------------------------------------ */

(function () {
    'use strict';

    /* --------------------------------------------------------------
       1. Bestanden kiezen
       -------------------------------------------------------------- */

    var formulier = document.getElementById('formulier');
    var invoer    = document.getElementById('bestanden');
    var sleepvlak = document.getElementById('sleepvlak');
    var lijst     = document.getElementById('bestandenlijst');
    var knop      = document.getElementById('verstuurknop');
    var knophint  = document.getElementById('verstuurhint');

    function leesbareGrootte(bytes) {
        if (bytes < 1024) { return bytes + ' B'; }
        if (bytes < 1024 * 1024) { return Math.round(bytes / 1024) + ' kB'; }
        var mb = bytes / (1024 * 1024);
        var tekst = mb < 10 ? mb.toFixed(1).replace('.', ',') : Math.round(mb) + '';
        if (tekst.slice(-2) === ',0') { tekst = tekst.slice(0, -2); }
        return tekst + ' MB';
    }

    if (formulier && invoer && lijst) {

        var maxBytes     = parseInt(formulier.getAttribute('data-max-bytes'), 10) || 0;
        var maxBestanden = parseInt(formulier.getAttribute('data-max-bestanden'), 10) || 10;

        function toonBestanden() {
            lijst.innerHTML = '';
            var bestanden = invoer.files;
            var teGroot = false;

            for (var i = 0; i < bestanden.length; i++) {
                var bestand = bestanden[i];
                var regel = document.createElement('li');

                var naam = document.createElement('span');
                naam.className = 'naam';
                naam.textContent = bestand.name;

                var grootte = document.createElement('span');
                grootte.className = 'grootte';
                grootte.textContent = leesbareGrootte(bestand.size);

                if (maxBytes > 0 && bestand.size > maxBytes) {
                    regel.className = 'tegroot';
                    teGroot = true;
                    var waarschuwing = document.createElement('span');
                    waarschuwing.className = 'waarschuwing';
                    waarschuwing.textContent = 'Te groot, deze komt niet mee. '
                        + 'De grens ligt op ' + leesbareGrootte(maxBytes) + '.';
                    naam.appendChild(waarschuwing);
                }

                regel.appendChild(naam);
                regel.appendChild(grootte);
                lijst.appendChild(regel);
            }

            if (bestanden.length > maxBestanden) {
                var melding = document.createElement('li');
                melding.className = 'tegroot';
                melding.textContent = 'Je koos ' + bestanden.length + ' bestanden. '
                    + 'Er kunnen er ' + maxBestanden + ' tegelijk mee.';
                lijst.appendChild(melding);
            }

            if (knophint) {
                knophint.textContent = teGroot
                    ? 'De gemarkeerde bestanden zijn te groot. Haal ze weg of stuur ze apart.'
                    : '';
            }
        }

        invoer.addEventListener('change', toonBestanden);

        /* Slepen en neerzetten */
        if (sleepvlak) {
            ['dragenter', 'dragover'].forEach(function (soort) {
                sleepvlak.addEventListener(soort, function (e) {
                    e.preventDefault();
                    sleepvlak.classList.add('sleep-actief');
                });
            });
            ['dragleave', 'drop'].forEach(function (soort) {
                sleepvlak.addEventListener(soort, function (e) {
                    e.preventDefault();
                    sleepvlak.classList.remove('sleep-actief');
                });
            });
            sleepvlak.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                    // Werkt in alle moderne browsers, ook op de telefoon.
                    try {
                        invoer.files = e.dataTransfer.files;
                    } catch (fout) {
                        return;
                    }
                    toonBestanden();
                }
            });
        }

        /* Tijdens het versturen de knop uitzetten */
        formulier.addEventListener('submit', function () {
            if (knop) {
                knop.disabled = true;
                knop.textContent = 'Bezig met versturen';
            }
            if (knophint) {
                knophint.textContent = 'Even geduld. Grote video\'s kunnen een paar '
                    + 'minuten duren. Sluit dit venster niet.';
            }
        });
    }

    /* --------------------------------------------------------------
       2. De lichtbak van de galerij
       -------------------------------------------------------------- */

    var lichtbak = document.getElementById('lichtbak');
    if (!lichtbak) { return; }

    var tegels = Array.prototype.slice.call(document.querySelectorAll('.tegel'));
    if (!tegels.length) { return; }

    /* Sommige telefoonfoto's (heic) kan niet elke browser tonen. In plaats
       van een kapot plaatje laten we dan een rustig vlak met een teken zien. */
    function vervangDoorTeken(afbeelding) {
        var vlak = document.createElement('span');
        vlak.className = 'tegel-video';
        vlak.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
            + '<path d="M4 5h16v14H4zM8 11a1.6 1.6 0 1 0 0-3.2A1.6 1.6 0 0 0 8 11z'
            + 'M4 17l5-5 3 3 3-3 5 5z"></path></svg>';
        if (afbeelding.parentNode) {
            afbeelding.parentNode.insertBefore(vlak, afbeelding);
            afbeelding.parentNode.removeChild(afbeelding);
        }
    }

    Array.prototype.forEach.call(document.querySelectorAll('.tegel img'),
        function (afbeelding) {
            afbeelding.addEventListener('error', function () {
                vervangDoorTeken(afbeelding);
            });
        });

    var vak        = document.getElementById('lichtbak-inhoud');
    var onderschrift = document.getElementById('lichtbak-onderschrift');
    var sluitKnop  = document.getElementById('lichtbak-sluit');
    var vorigeKnop = document.getElementById('lichtbak-vorige');
    var volgendeKnop = document.getElementById('lichtbak-volgende');
    var huidige = 0;
    var vanaf = null;

    function toon(index) {
        if (index < 0) { index = tegels.length - 1; }
        if (index >= tegels.length) { index = 0; }
        huidige = index;

        var tegel = tegels[index];
        var soort = tegel.getAttribute('data-soort');
        var bron  = tegel.getAttribute('data-bron');
        var wie   = tegel.getAttribute('data-inzender');

        // Oude foto of video weghalen, onderschrift laten staan.
        var oud = vak.querySelector('img, video');
        if (oud) { vak.removeChild(oud); }

        var element;
        if (soort === 'video') {
            element = document.createElement('video');
            element.src = bron;
            element.controls = true;
            element.playsInline = true;
            element.autoplay = true;
        } else {
            element = document.createElement('img');
            element.src = bron;
            element.alt = wie ? 'Ingestuurd door ' + wie : 'Foto van Borger Breeveld';
            element.addEventListener('error', function () {
                onderschrift.textContent = 'Deze foto kan je browser niet tonen. '
                    + 'Probeer hem te openen op je telefoon.';
            });
        }
        vak.insertBefore(element, onderschrift);

        var telling = '(' + (index + 1) + ' van ' + tegels.length + ')';
        onderschrift.textContent = wie
            ? 'Ingestuurd door ' + wie + '  ' + telling
            : telling;

        var meerdere = tegels.length > 1;
        vorigeKnop.hidden = !meerdere;
        volgendeKnop.hidden = !meerdere;
    }

    function open(index) {
        vanaf = document.activeElement;
        lichtbak.hidden = false;
        document.body.style.overflow = 'hidden';
        toon(index);
        sluitKnop.focus();
    }

    function sluit() {
        var video = vak.querySelector('video');
        if (video) { video.pause(); }
        lichtbak.hidden = true;
        document.body.style.overflow = '';
        var oud = vak.querySelector('img, video');
        if (oud) { vak.removeChild(oud); }
        if (vanaf && vanaf.focus) { vanaf.focus(); }
    }

    tegels.forEach(function (tegel, index) {
        tegel.addEventListener('click', function () { open(index); });
    });

    sluitKnop.addEventListener('click', sluit);
    vorigeKnop.addEventListener('click', function () { toon(huidige - 1); });
    volgendeKnop.addEventListener('click', function () { toon(huidige + 1); });

    // Klikken naast de foto sluit ook.
    lichtbak.addEventListener('click', function (e) {
        if (e.target === lichtbak) { sluit(); }
    });

    document.addEventListener('keydown', function (e) {
        if (lichtbak.hidden) { return; }
        if (e.key === 'Escape') { sluit(); }
        else if (e.key === 'ArrowLeft') { toon(huidige - 1); }
        else if (e.key === 'ArrowRight') { toon(huidige + 1); }
        else if (e.key === 'Tab') {
            // De aandacht binnen de vergroting houden.
            var knoppen = [sluitKnop, vorigeKnop, volgendeKnop].filter(function (k) {
                return !k.hidden;
            });
            var plek = knoppen.indexOf(document.activeElement);
            e.preventDefault();
            var volgende = e.shiftKey ? plek - 1 : plek + 1;
            if (volgende < 0) { volgende = knoppen.length - 1; }
            if (volgende >= knoppen.length) { volgende = 0; }
            knoppen[volgende].focus();
        }
    });

    // Vegen op de telefoon.
    var startX = null;
    lichtbak.addEventListener('touchstart', function (e) {
        startX = e.changedTouches[0].clientX;
    }, { passive: true });
    lichtbak.addEventListener('touchend', function (e) {
        if (startX === null) { return; }
        var verschil = e.changedTouches[0].clientX - startX;
        if (Math.abs(verschil) > 60) {
            toon(verschil < 0 ? huidige + 1 : huidige - 1);
        }
        startX = null;
    }, { passive: true });

}());

/* ------------------------------------------------------------------
   3. Het uitklapmenu in de balk bovenaan
   ------------------------------------------------------------------ */

(function () {
    'use strict';

    var knop  = document.getElementById('menu-knop');
    var lijst = document.getElementById('menu-lijst');
    if (!knop || !lijst) { return; }

    function open() {
        lijst.hidden = false;
        knop.setAttribute('aria-expanded', 'true');
    }

    function sluit(terugNaarKnop) {
        lijst.hidden = true;
        knop.setAttribute('aria-expanded', 'false');
        if (terugNaarKnop) { knop.focus(); }
    }

    knop.addEventListener('click', function (e) {
        e.stopPropagation();
        if (lijst.hidden) { open(); } else { sluit(false); }
    });

    /* Kies je een sectie, dan klapt het menu weer dicht. */
    lijst.addEventListener('click', function (e) {
        if (e.target.tagName === 'A') { sluit(false); }
    });

    /* Klikken naast het menu sluit het ook. */
    document.addEventListener('click', function (e) {
        if (lijst.hidden) { return; }
        if (!lijst.contains(e.target) && e.target !== knop) { sluit(false); }
    });

    document.addEventListener('keydown', function (e) {
        if (lijst.hidden) { return; }
        if (e.key === 'Escape') { sluit(true); }
    });

    /* Met de pijltjestoetsen door het menu lopen. */
    lijst.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') { return; }
        var links = Array.prototype.slice.call(lijst.querySelectorAll('a'));
        var plek  = links.indexOf(document.activeElement);
        e.preventDefault();
        var volgende = e.key === 'ArrowDown' ? plek + 1 : plek - 1;
        if (volgende < 0) { volgende = links.length - 1; }
        if (volgende >= links.length) { volgende = 0; }
        links[volgende].focus();
    });

    knop.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            open();
            var eerste = lijst.querySelector('a');
            if (eerste) { eerste.focus(); }
        }
    });
}());
