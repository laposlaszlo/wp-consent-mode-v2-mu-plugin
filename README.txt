CM V2 – Minimal WordPress MU Plugin (Consent Mode v2)
=====================================================

Mi ez?
------
Egy minimális, saját fejlesztésű consent banner WordPress-hez, amely a Google Consent Mode v2 (GCM v2)
jeleit (default/update) küldi, és a felhasználó választását localStorage-ben tárolja.

Fő funkciók
-----------
- GCM v2 `default` = denied a <head> legelején
- GCM v2 `update` a felhasználói döntésnél (analytics + ads/marketing csoportok)
- Egyszerű modal UI (Elfogadok mindent / Csak szükséges / Mentés)
- 'Sütibeállítások' lebegő gomb (bármikor módosítható a döntés)
- GTM kompatibilis: dataLayer eventek (`cm_default`, `cm_update`)

Telepítés
---------
1) Csomagold ki a ZIP-et, majd a mappát tedd ide: `wp-content/mu-plugins/`
   - MU plugin esetén a fájlok automatikusan betöltődnek (nincs aktiválás gomb).
2) Ellenőrizd, hogy a sablonod `<head>` részében **a wp_head() hívás a GTM/gtag előtt** történik,
   így a `default` jel legelsőként fusson.
3) A láblécben megjelenik egy "Sütibeállítások" gomb, amivel a modal bármikor megnyitható.
   (Fejléchez/lábléchez saját linket is tehetsz és hívd: `window.CM.open()`)

GTM/Gtag beállítás
------------------
- **GTM**: Használd a Consent Overview-t. A GA4/GAds tagekhez kapcsold be a tag-szintű consentet
  (Analytics/Ads "Granted" esetén fusson). A plugin már küldi a GCM v2 jeleket.
- **gtag.js**: Ha nem GTM-et használsz, a plugin default/update hívásai elegendőek.

Meta/Bing/egyéb tagek
---------------------
- Ezeket töltsd GTM-ből **csak akkor**, ha a megfelelő csoport engedélyezett (pl. Ads/Marketing).
- Példa: Triggerelj marketing consentre, vagy feltételes Custom HTML tag-ben ellenőrizd a
  `cmv2_ads` dataLayer változót.

Testreszabás
------------
- A tájékoztató URL-jét filterrel módosíthatod a `functions.php`-ban:
  add_filter('cmv2_policy_url', fn() => '/adatkezelesi-tajekoztato/');
- A verziót (ÉS a TTL-t) a JS-ben tudod változtatni; verzióváltásnál a banner újra megjelenik.
- Stílusok az inline CSS-ben – igény esetén tedd külön fájlba.

Megjegyzések
------------
- Ez a plugin **nem** IAB TCF-kompatibilis CMP. Google Ads méréshez elég a GCM v2.
  Ha **publisherként** személyre szabott hirdetést szolgálsz ki az EEA/UK/CH felé, a TCF-követelmény miatt
  Google-tanúsított CMP szükséges.
- Fegyelmezett GTM-governance szükséges: minden nem-szükséges tag csak consent után fusson.