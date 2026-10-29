# Changelog

## [2.9.1] – 2026-10-29

### Javítás
- A fejlécbe szúrt inline szkriptek (consent default, GTM betöltő) `data-cfasync="false" data-nowprocket data-cookieconsent="ignore"` attribútumot kapnak. Cloudflare Rocket Loader mellett a consent default korábban csak az oldal betöltése után futott le, a GTM pedig consent jelek nélkül indult, így a Tag Assistant nem látott beleegyezés jeleket.

## [2.9.0] – 2026-10-21

### Átállás normál pluginra, automatikus frissítés
- A plugin többé nem `mu-plugins`, hanem normál pluginként települ (`wp-content/plugins/wp-consent-mode-v2-mu-plugin/`). A WordPress az MU pluginokat nem frissíti, így az előző frissítő nem működött.
- Frissítés GitHub Release-ekből a plugin-update-checker segítségével (új `CMV2_Updater` osztály): frissítésértesítés, "Update now" gomb és automatikus frissítés kapcsoló a Bővítmények oldalon.
- Új GitHub Actions workflow (`.github/workflows/release.yml`): `v*` tagre ellenőrzi a verzióegyezést, buildeli a zipet és létrehozza a Release-t.
- Ha a régi MU példány még aktív, az új plugin nem tölt be duplán, hanem admin figyelmeztetést mutat.
- Plugin header: `Update URI` hozzáadva, a név már nem "MU plugin".
- A `CMV2_VERSION` konstans és a plugin header verziója újra egyezik (korábban 2.8.12 és 2.8.15).

### Új admin felület
- Oldalsó menüs, kártyás beállítóoldal (Általános, Szövegek, Megjelenés, Integrációk, Régiók, Állapot és mentés) új `CMV2_Admin_Page` osztályban.
- **Élő előnézet:** a banner szöveg-, szín-, forma-, pozíció- és gombváltoztatásra azonnal frissül; egyszerű és részletes nézet.
- Kapcsolók a jelölőnégyzetek helyett, vizuális pozícióválasztó, kattintható színséma-kártyák, lekerekítés-csúszka.
- Ragadós mentősáv "mentetlen változás" jelzéssel és figyelmeztetéssel oldal elhagyásakor; mentés újratöltés nélkül (AJAX, hibánál natív űrlapküldésre esik vissza).
- Feltételes mezők (pl. a Zaraz Purpose ID csak bekapcsolt Zaraz mellett látszik).
- Az "Állapot" kártya valódi ellenőrzéseket mutat (GTM, Zaraz, országheader, oldalcache) a korábbi statikus "Aktív" doboz helyett.
- A jQuery már csak a WordPress színválasztóhoz kell, a többi admin JS vanilla.
- Reszponzív elrendezés (előnézet a tartalom alá kerül kisebb képernyőn, a menü vízszintes sáv lesz).

### Script blokkoló (új)
- A jelölt szkriptek és iframe-ek a megfelelő kategória (`analytics` / `ads`) elfogadásáig nem futnak le.
- **Kézi jelölés:** `<script type="text/plain" data-cmv2-category="analytics">` és `<iframe data-cmv2-category="ads" data-cmv2-src="...">`.
- **Automatikus szabályok:** soronként `kategória: minta`; a `script_loader_tag` szűrő átírja a WordPress által betöltött (enqueue) szkripteket, az inline előtte/utána részekkel együtt. A `type="module"` és a JSON/JSON-LD blokkok kezelve vannak.
- Az aktiválás dokumentum-sorrendben történik: egy külső szkript betöltődése után indul a következő.
- Régiószűrésnél a banner-régión kívüli látogatók mindent megkapnak. Alapból kikapcsolt.

### Hozzájárulási statisztika (új)
- Névtelen napi számlálók: banner megjelenés (munkamenetenként egyszer), első döntés (mindent elfogad / egyedi / csak szükséges) és későbbi módosítás. Nem tárol IP-t, sütit, azonosítót vagy user agentet.
- Publikus REST végpont (`cmv2/v1/event`), `sendBeacon`-nel, nonce nélkül, így oldalcache mellett is működik. Atomi `ON DUPLICATE KEY UPDATE` számláló.
- Admin "Statisztika" szekció: 7 / 30 / 90 napos nézet, összegző kártyák és halmozott napi oszlopdiagram (inline SVG).
- 180 napnál régebbi adat automatikus törlése (napi cron), "Statisztika törlése" gomb, `uninstall.php` az eltávolításkor törli a táblát és a beállításokat. Alapból bekapcsolt, az Általános szekcióban kikapcsolható.
- **A végpont védelme:** same-origin ellenőrzés (`Sec-Fetch-Site` / `Origin`, kikapcsolható a `cmv2_stats_verify_origin` szűrővel), napi forgó HMAC token (a mai és a tegnapi érvényes), valamint napi plausibility plafonok (a döntések nem szaladhatnak el a megjelenésektől, abszolút napi limit a `cmv2_stats_daily_cap` szűrővel). A limiten túli kérést a szerver csendben eldobja. Ha az oldal cache-e 2 napnál régebbi HTML-t szolgál ki, annak eseményei elvesznek.
- Nem jogi bizonyíték a hozzájárulásról; elszánt hamisítást a védelem sem zár ki, ezért a számok tájékoztató jellegűek.

### Javítva
- **Egyedi szövegek nem jelentek meg a frontenden:** a nyelvi alapszövegek minden lekéréskor felülírták a mentett szövegeket. Mostantól: alapérték < nyelvi szöveg < mentett érték.
- **Export:** a letöltés `admin_init`-ben fut, így nem ütközik a már elküldött fejlécekkel.
- **Mentés/import:** Post/Redirect/Get, F5-re nem megy el újra az űrlap.
- **Oldalcache-biztos consent:** a consent cookie olvasását és a `wait_for_update` kiválasztását a böngésző végzi, így a `<head>` HTML minden látogatónak azonos.
- **Régiókezelés:** az EEA-only banner ellenőrzés is a `custom_regions` listát használja, az EEA lista egy helyen van.
- A színmező-validáció csak 3 vagy 6 jegyű hexet fogad el.
- Az `ads_data_redaction` leírása egyezik a tényleges (`false`) értékkel.
- A sütinév (`CMV2_LS_KEY`) a JS-nek is átadódik, nincs beégetve.

### Belső változások
- Új `CMV2_Options` osztály: alapértékek, fordítások, régiók, kérésszintű cache. A korábbi `cmv2_get_*` függvények kompatibilis burkolóként megmaradtak.
- A GTMKIT `remove_from_cart` javítás külön `CMV2_Integrations` osztályba került, a `cmv2_enable_gtmkit_fix` filterrel kikapcsolható.

### Átállás a régi MU példányról
1. Töröld a `wp-content/mu-plugins/` alól a plugin fájljait.
2. Telepítsd a Release zip-jét normál pluginként, majd aktiváld. A beállítások (`cmv2_settings`) megmaradnak.
3. A Bővítmények oldalon kapcsold be az automatikus frissítést.
