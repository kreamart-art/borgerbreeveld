# De herdenkingspagina voor Borger Breeveld

Deze map is een complete website. Je zet hem op je webhosting en hij werkt.
Er is geen speciale software voor nodig, geen database en geen account bij
een andere dienst. Alleen gewone webhosting met PHP, zoals bijna elke
hostingpartij die levert.

Deze uitleg gaat ervan uit dat je geen programmeur bent. Neem hem rustig
van boven naar beneden door.

---

## 1. Wat er in de map zit

De website bestaat uit deze pagina's:

| Pagina | Waarvoor |
|---|---|
| `index.php` | De hoofdpagina: de galerij en de oproep om iets in te sturen |
| `leven.php` | Zijn levensverhaal |
| `tijdlijn.php` | De tijdlijn |
| `herinneringen.php` | De verhalen die mensen instuurden |
| `insturen.php` | Het formulier |
| `privacy.php` | De privacyverklaring |
| `voorwaarden.php` | De voorwaarden |

En verder:

| Bestand of map | Waarvoor |
|---|---|
| `beheer.php` | Het dashboard waarin de familie bepaalt wat zichtbaar wordt |
| `controle.php` | Kijkt na of alles goed op de server staat. Weghalen als het klaar is |
| `beheer.webmanifest`, `sw.js`, `assets/pwa/` | Maken van het dashboard een app voor op de telefoon |
| `kop.php` | De balk met het menu en de banner, op elke pagina hetzelfde |
| `voet.php` | De voettekst, op elke pagina hetzelfde |
| `config.php` | Het wachtwoord, het contactadres en de limieten |
| `inhoud.php` | Alle teksten van de website |
| `juridisch.php` | De teksten van de privacyverklaring en de voorwaarden |
| `lib.php` | De techniek eronder. Hier hoef je niets in te doen |
| `assets/` | De vormgeving en een klein beetje javascript |
| `uploads/` | Hier komen de ingestuurde foto's en video's |
| `data/` | Hier komen de inzendingen zelf. Niet zichtbaar voor bezoekers |
| `media/` | Hier staan de twee banners |

---

## 2. Alles op de server zetten

Je hebt de inloggegevens van je hosting nodig en een FTP-programma.
Gratis en gewoon: **FileZilla** (Windows, Mac) of **Cyberduck** (Mac).

1. Open je FTP-programma en log in met de gegevens van je hostingpartij:
   servernaam, gebruikersnaam en wachtwoord.
2. Aan de rechterkant zie je de server. Ga naar de map waarin de website
   hoort. Die heet meestal `public_html`, `httpdocs`, `www` of `htdocs`.
3. Aan de linkerkant zie je je eigen computer. Ga naar deze map.
4. Sleep **alle** bestanden en mappen van links naar rechts.
   Ook de bestanden die met een punt beginnen (`.htaccess`, `.user.ini`).
   Ziet je programma die niet, zet dan in de instellingen aan dat
   verborgen bestanden getoond worden.
5. Wacht tot alles is overgezet en ga naar je webadres. De pagina hoort er
   nu te staan.

> **Let op de bestanden met een punt.** `uploads/.htaccess` en
> `data/.htaccess` zijn belangrijk voor de veiligheid. Worden ze niet
> meegenomen, dan maakt de pagina ze zelf opnieuw aan zodra iemand hem
> bezoekt, maar het is beter als ze er meteen staan.

---

## 3. Schrijfrechten instellen

De website moet zelf bestanden kunnen opslaan. Daarvoor hebben twee
mappen schrijfrechten nodig: `uploads` (met `uploads/thumbs` erin) en
`data`. Zonder dat komt er geen enkele inzending binnen.

**In FileZilla:**

1. Klik met de rechtermuisknop op de map `uploads`.
2. Kies **Bestandsrechten** (of *File permissions*).
3. Vul bij het getal **755** in.
4. Zet een vinkje bij **Toepassen op onderliggende mappen**, zodat
   `uploads/thumbs` het ook krijgt.
5. Doe hetzelfde met de map `data`.

**In Cyberduck:** klik met de rechtermuisknop op de map, kies **Info**,
dan het tabblad **Permissions**, en vul daar 755 in.

**In het klantenpaneel van je hosting:** de meeste hebben een
bestandsbeheerder. Zoek daar naar "rechten", "permissions" of "chmod"
bij de map.

Werkt het opslaan daarna nog niet, probeer dan **775**. Helpt dat ook
niet, vraag je hosting dan welke waarde bij hen nodig is. Ga niet zomaar
naar 777, dat is onnodig ruim.

### Zo weet je zeker dat het goed staat

Er zit een controlepagina bij. Open na het uploaden

```
jouwadres.nl/controle.php
```

Die kijkt zelf na of de mappen beschrijfbaar zijn, of foto's verkleind
kunnen worden, hoe groot bestanden mogen zijn, of er mail verstuurd kan
worden, en of het wachtwoord en het contactadres al zijn ingevuld. Bij
elk punt dat nog niet goed staat, staat erbij wat je moet doen.

Staat alles op groen? Verwijder `controle.php` dan van de server, je
hebt het niet meer nodig.

---

## 4. De twee wachtwoorden

Dit is het belangrijkste dat je moet doen voordat je de pagina deelt.

Er zijn twee manieren om in te loggen op het dashboard, met elk een
eigen wachtwoord. Ze staan allebei in `config.php`:

```php
define('BEHEER_WACHTWOORD',  getenv('BORGER_BEHEER_WACHTWOORD')  ?: 'jouw-wachtwoord');
define('FAMILIE_WACHTWOORD', getenv('BORGER_FAMILIE_WACHTWOORD') ?: 'wachtwoord-familie');
```

De wachtwoorden die nu zijn ingesteld krijg je apart doorgegeven, ze
staan met opzet niet in deze handleiding.

| Wachtwoord | Wie | Wat je ermee kunt |
|---|---|---|
| `BEHEER_WACHTWOORD` | de beheerder | alles, ook een inzending voorgoed verwijderen |
| `FAMILIE_WACHTWOORD` | de familie | foto's en verhalen op de pagina zetten of eraf halen, en namen en teksten aanpassen |

Het verschil zit alleen in dat verwijderen. Zo kan er nooit per ongeluk
iets voorgoed weg zijn. Wil je dat de familie ook mag verwijderen, geef
ze dan het beheerderswachtwoord.

Staat de website op een server met Coolify of Docker, dan kun je ze ook
als omgevingsvariabele zetten (`BORGER_BEHEER_WACHTWOORD` en
`BORGER_FAMILIE_WACHTWOORD`). Die gaan vóór op wat er in het bestand
staat, zodat de wachtwoorden nergens in de code terechtkomen.

Aanpassen in het bestand doe je zo:

1. Open `config.php` in een tekstbewerker (Kladblok, TextEdit,
   Notepad++). Dus **niet** in Word.
2. Zet een ander wachtwoord tussen de aanhalingstekens.

3. Verander in hetzelfde bestand ook het contactadres:

   ```php
   const CONTACT_EMAIL = 'herinneringen@voorbeeld.nl';
   ```

4. Sla het bestand op en zet het opnieuw op de server.

Ga daarna naar `jouwadres.nl/beheer.php` en log in met het nieuwe
wachtwoord. Deel dat wachtwoord alleen binnen de familie.

---

## 5. De banner bovenaan

Er zijn twee banners, allebei in de map `media`:

- `header.jpg` staat boven de hoofdpagina, de brede collage
- `header-pagina.jpg` staat boven de andere pagina's

Van allebei staan er kleinere en modernere versies naast (`-800.jpg` en
`.webp`), zodat de pagina snel laadt op een telefoon.

Wil je een andere banner? Vervang dan alle bestanden van die naam. Haal
je ze allemaal weg, dan blijft het gewoon een groen vlak.

---

## 6. De galerij

De galerij toont 48 foto's per pagina, met knoppen om te bladeren. Wil je
er meer of minder per pagina, verander dan deze regel in `config.php`:

```php
const GALERIJ_PER_PAGINA = 48;
```

---

## 7. Het dashboard voor de familie

Ga naar `jouwadres.nl/beheer.php` en log in met het wachtwoord uit punt 4.
Zonder dat wachtwoord komt niemand erin.

Bovenaan zie je in één oogopslag hoeveel er is ingestuurd, hoeveel er nog
op beoordeling wacht, en hoeveel er op de pagina staat.

- Alles wat mensen insturen komt hier binnen en staat **nog niet** op de
  pagina. Niemand ziet het behalve jullie.
- Je beslist **per foto en per video**. Klik op **Op de pagina zetten** en
  precies die ene foto verschijnt in de galerij. Klik op **Van de pagina
  halen** om hem weer weg te halen.
- Het geschreven verhaal heeft een eigen knop. Je kunt dus de foto's van
  iemand tonen en zijn tekst nog even laten liggen, of andersom.
- **Weggooien** zet een foto in de **prullenbak**. Hij staat dan niet
  meer op de pagina, maar hij is nog niet weg. In de prullenbak (alleen
  zichtbaar voor de beheerder) kun je hem terugzetten als je je vergist
  hebt, of hem voorgoed verwijderen. Zo raak je nooit per ongeluk iets
  kwijt.
- De familie ziet alleen de verzameling die de familie zelf heeft
  uitgekozen. Wat bezoekers via het formulier insturen komt bij de
  beheerder terecht.
- Bovenin kun je filteren: **alles**, **wacht op beoordeling** of
  **zichtbaar**.
- Bij een grote verzameling zie je eerst 24 foto's, met een knop om ze
  allemaal te tonen. Met **Alles op de pagina** en **Alles eraf** doe je
  een hele inzending in één keer.
- Onder **Naam en tekst aanpassen** kun je de naam, de relatie en de
  herinnering van een inzending wijzigen. Handig als iemand zijn naam
  vergeet, verkeerd typt, of als jullie er zelf bij willen zetten van wie
  een foto komt. Zodra je hier iets opslaat, komt die naam ook onder de
  foto's te staan.
- Het e-mailadres van een inzender zie je alleen hier. Het komt nooit op
  de openbare pagina.

### Het dashboard als app op je telefoon

Het dashboard kun je op je telefoon zetten als een echte app, met een
eigen icoontje op je beginscherm. Je hoeft dan nooit meer eerst naar de
website.

Open `jouwadres.nl/beheer.php` op je telefoon en log in. Bovenaan staat
een kaart met de uitleg, die is per telefoon net iets anders:

- **iPhone of iPad** (in Safari): tik onderaan op de deelknop, kies
  **Zet op beginscherm** en tik op **Voeg toe**.
- **Android** (in Chrome): tik op de knop **Installeer als app** als die
  er staat, of anders rechtsboven op de drie puntjes en dan
  **App installeren**.

Daarna staat er een groen icoontje met "BB" tussen je apps. De app werkt
gewoon via internet: er wordt niets op de telefoon bewaard, dus de
inzendingen blijven net zo prive als op de website.

Heb je de kaart weggeklikt en wil je hem terug? Hij hoort bij de browser
op dat toestel; open het dashboard in een andere browser en hij staat er
weer. De stappen hierboven werken ook zonder de kaart.

---

## 8. Bericht bij een nieuwe inzending

Stuurt iemand iets in, dan gaat er automatisch een mailtje naar het
contactadres uit `config.php`. Zo hoef je niet steeds zelf te kijken.
In dat mailtje staat wie het instuurde, hoeveel bestanden erbij zaten,
en een link naar het dashboard. Antwoorden gaat rechtstreeks naar de
inzender, als die een adres achterliet.

Komt de mail in de spam terecht? Zet dan in `config.php` bij
`AFZENDER_EMAIL` een adres van je eigen domein:

```php
const AFZENDER_EMAIL = 'website@jouwdomein.nl';
```

Wil je helemaal geen mail, zet dan `MELD_NIEUWE_INZENDING` op `false`.

Let op: sommige hostingpakketten versturen geen mail vanaf de website.
Werkt het niet, vraag dan bij je hosting of `mail()` aanstaat. De
inzending zelf komt hoe dan ook gewoon binnen.

---

## 9. Als iemand het wachtwoord probeert te raden

Na vijf mislukte pogingen kan er een kwartier lang niet meer worden
ingelogd vanaf dat adres. Daarna mag het weer. Die twee getallen staan
in `config.php`:

```php
const MAX_INLOGPOGINGEN = 5;
const INLOG_WACHTTIJD_MINUTEN = 15;
```

Heb je jezelf buitengesloten? Verwijder dan het bestand
`data/pogingen.json` en je kunt meteen weer inloggen.

---

## 10. Grote video's toestaan

Standaard staan veel servers maar 2 MB per bestand toe. Een filmpje van
een telefoon is al snel groter. Daarom zitten er twee bestanden in de map
die dat verhogen. Welke van de twee werkt, hangt af van je server.

**Manier 1: `.user.ini`** (werkt op de meeste hostings van nu)

Open `.user.ini` en haal de puntkomma's aan het begin van de regels weg,
zodat er staat:

```ini
upload_max_filesize = 200M
post_max_size = 220M
max_execution_time = 600
max_input_time = 600
```

Zet het bestand terug op de server. Het kan tot vijf minuten duren voor
de server de wijziging oppikt.

**Manier 2: `.htaccess`**

Werkt manier 1 niet, open dan `.htaccess` en haal de `#` weg voor de vier
regels binnen `<IfModule mod_php.c>`, zodat er staat:

```apache
<IfModule mod_php.c>
  php_value upload_max_filesize 200M
  php_value post_max_size 220M
  php_value max_execution_time 600
  php_value max_input_time 600
</IfModule>
```

Wat betekenen ze:

- `upload_max_filesize`: het grootste bestand dat iemand mag insturen.
- `post_max_size`: de hele inzending bij elkaar. Zet deze altijd wat
  **hoger** dan de vorige.
- `max_execution_time` en `max_input_time`: hoe lang de server mag doen
  over het ontvangen. Grote video's hebben tijd nodig.

> **Krijg je ineens op elke pagina een foutmelding** (meestal "500
> Internal Server Error")? Dan begrijpt jouw server die `php_value`-regels
> niet. Zet de `#` er weer voor, of haal `.htaccess` weg, en gebruik
> manier 1. Veel hostings hebben hier ook een knop voor in hun eigen
> beheerscherm, vaak onder "PHP-instellingen".

Wil je een andere grens dan 200 MB? Verander dan ook `MAX_BESTAND_MB` in
`config.php`, zodat de melding op de pagina klopt met de werkelijkheid.

---

## 11. De teksten aanpassen

Alle zichtbare tekst staat in `inhoud.php`, van alle vijf de pagina's. Je hoeft daar alleen tekst
tussen de aanhalingstekens te veranderen. Laat de aanhalingstekens, de
komma's en de haakjes staan zoals ze staan.

Zo verander je bijvoorbeeld de regels bovenaan:

```php
'intro' => 'Acteur, filmmaker, zanger, gitarist en mediaduizendpoot. ...',
```

Sla op, zet het bestand terug op de server, en ververs de pagina.

Staat er per ongeluk een aanhalingsteken te veel of te weinig, dan wordt
de pagina wit. Zet dan de oude versie terug. Maak daarom altijd eerst een
kopie van het bestand voordat je iets verandert.

---

## 12. Veiligheid, kort samengevat

- Ingestuurde bestanden krijgen een nieuwe, willekeurige naam. De
  originele naam wordt alleen in het beheer getoond.
- Alleen foto's en video's komen erdoor. Van een foto wordt gecontroleerd
  of het echt een foto is, niet alleen of de naam goed staat.
- In de map `uploads` kan geen code worden uitgevoerd. Dat regelt
  `uploads/.htaccess`.
- De map `data`, waar de inzendingen en de e-mailadressen staan, is voor
  bezoekers helemaal afgesloten.
- `beheer.php` is zonder wachtwoord niet te openen, en na vijf mislukte
  pogingen gaat de deur een kwartier op slot.

---

## 13. Als er iets misgaat

**De pagina is wit.**
Er staat een typefout in een bestand dat je hebt aangepast. Zet de kopie
terug. Heb je niets aangepast, vraag dan bij je hosting of PHP aanstaat
en welke versie draait. Deze pagina werkt vanaf PHP 7.4.

**Insturen lukt niet, er komt een melding dat er niets opgeslagen kon
worden.**
De mappen `uploads` en `data` hebben nog geen schrijfrechten. Zie punt 3.

**Een video komt niet aan.**
Het bestand is groter dan de server toestaat. Zie punt 10. In de melding
op de pagina staat welke grens er nu geldt.

**Foto's van een iPhone verschijnen niet in de galerij.**
iPhones sturen soms het bestandstype `heic`, dat niet elke server en niet
elke browser kan tonen. De inzending komt wel binnen en je ziet hem in het
beheer. Je kunt de inzender vragen om op de telefoon bij
*Instellingen > Camera > Indelingen* te kiezen voor **Meest compatibel**,
dan sturen ze gewone jpg-bestanden.

**Ik wil helemaal opnieuw beginnen.**
Verwijder het bestand `data/inzendingen.json` en alles in `uploads` behalve
`.htaccess`, `index.html` en de map `thumbs`. Alle inzendingen zijn dan weg.

---

## 14. Nog even dit

Onderaan de pagina staat dat ingestuurd materiaal van de inzender blijft
en alleen op deze pagina wordt gebruikt. Houd je daaraan. Vraagt iemand
om iets weg te halen, doe dat dan gewoon: in het beheer met één klik.
