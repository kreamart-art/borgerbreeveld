/*
 * Service worker voor het dashboard.
 *
 * Twee taken:
 *
 *  1. Het dashboard installeerbaar maken als app op je telefoon. Er wordt
 *     bewust NIETS bewaard of gecachet: het dashboard toont privegegevens
 *     en moet altijd vers van de server komen. Zonder verbinding tonen we
 *     een nette melding in plaats van een kale foutpagina.
 *
 *  2. Meldingen opvangen. Dit stukje blijft ook draaien als de app dicht
 *     is; dat is precies waarom je op je telefoon een berichtje krijgt
 *     zonder dat je het dashboard open hebt staan.
 */

self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (e) {
    // Oude caches van eerdere versies opruimen, mochten die er ooit komen.
    e.waitUntil(
        caches.keys().then(function (namen) {
            return Promise.all(namen.map(function (naam) {
                return caches.delete(naam);
            }));
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (e) {
    if (e.request.mode !== 'navigate') {
        return; // gewone bestanden laat de browser zelf afhandelen
    }
    e.respondWith(
        fetch(e.request).catch(function () {
            return new Response(
                '<!doctype html><html lang="nl"><meta charset="utf-8">'
                + '<meta name="viewport" content="width=device-width, initial-scale=1">'
                + '<title>Geen verbinding</title>'
                + '<body style="font-family:-apple-system,sans-serif;background:#faf6ee;'
                + 'color:#23201b;display:flex;align-items:center;justify-content:center;'
                + 'min-height:100vh;margin:0;padding:2rem;text-align:center">'
                + '<div><h1 style="color:#12402f">Geen verbinding</h1>'
                + '<p>Het dashboard heeft internet nodig.<br>'
                + 'Probeer het zo weer.</p></div>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
        })
    );
});

/* ------------------------------------------------------------------ */
/* Meldingen                                                           */
/* ------------------------------------------------------------------ */

self.addEventListener('push', function (e) {
    var melding = {
        titel: 'Herinneringen',
        tekst: 'Er is iets nieuws in het dashboard.',
        adres: 'beheer.php',
        merk:  'borger'
    };

    if (e.data) {
        try {
            var binnen = e.data.json();
            if (binnen.titel) { melding.titel = binnen.titel; }
            if (binnen.tekst) { melding.tekst = binnen.tekst; }
            if (binnen.adres) { melding.adres = binnen.adres; }
            if (binnen.merk)  { melding.merk  = binnen.merk; }
        } catch (fout) {
            melding.tekst = e.data.text();
        }
    }

    e.waitUntil(Promise.all([
        self.registration.showNotification(melding.titel, {
            body:     melding.tekst,
            icon:     'assets/pwa/icon-192.png',
            badge:    'assets/pwa/badge-96.png',
            tag:      melding.merk,
            renotify: true,
            lang:     'nl',
            data:     { adres: melding.adres }
        }),

        /* Staat het dashboard op dit moment open? Dan laten we het daar
           ook meteen zien, zonder dat de pagina hoeft te wachten op de
           volgende controle. */
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(function (vensters) {
                vensters.forEach(function (venster) {
                    venster.postMessage({
                        soort: 'melding',
                        titel: melding.titel,
                        tekst: melding.tekst,
                        adres: melding.adres
                    });
                });
            })
    ]));
});

self.addEventListener('notificationclick', function (e) {
    e.notification.close();

    var adres = (e.notification.data && e.notification.data.adres) || 'beheer.php';
    var heel  = new URL(adres, self.registration.scope).href;

    e.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(function (vensters) {
                // Staat het dashboard al open? Dan dat venster naar voren
                // halen in plaats van een tweede te openen.
                for (var i = 0; i < vensters.length; i++) {
                    if (vensters[i].url.indexOf('beheer.php') !== -1) {
                        if (vensters[i].navigate) {
                            return vensters[i].navigate(heel).then(function (v) {
                                return (v || vensters[i]).focus();
                            });
                        }
                        return vensters[i].focus();
                    }
                }
                if (self.clients.openWindow) {
                    return self.clients.openWindow(heel);
                }
            })
    );
});
