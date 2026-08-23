/*
 * Service worker voor het dashboard.
 *
 * Bewust zo eenvoudig mogelijk: er wordt NIETS bewaard of gecachet.
 * Het dashboard toont privegegevens en moet altijd vers van de server
 * komen. Dit bestand bestaat alleen zodat het dashboard als app op een
 * telefoon geinstalleerd kan worden, en om zonder verbinding een nette
 * melding te tonen in plaats van een kale foutpagina.
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
