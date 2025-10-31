# 🍪 Consent Mode V2 - Gyors Üzembe Helyezés

## 📦 Telepítés (1 perc)

1. **Másold a fájlt:**
   ```
   wp-content/mu-plugins/consent-mode-v2.php
   ```

2. **Ellenőrizd:**
   - Nyisd meg az oldaladat
   - Látnod kell a cookie bannert
   - Kattints "Elfogadok mindent"-re

3. **Admin felület:**
   - WordPress Admin → **Beállítások** → **Consent Mode V2**
   - Állítsd be a szövegeket és színeket
   - Mentés

✅ Kész! A plugin működik.

---

## 🎨 Gyors Színbeállítás

### Előre Beállított Témák

A **Színek** tabon válassz egy sémát:

- 🖤 **Alapértelmezett** - Fekete-fehér klasszikus
- 🔵 **Modern Kék** - Professzionális kék tónus
- 🟢 **Eco Zöld** - Friss, természetes hatás
- 🟣 **Elegáns Lila** - Kreatív, egyedi megjelenés
- ⚫ **Dark Mode** - Sötét téma (light szöveg)
- 🟠 **Meleg Narancs** - Barátságos, meleg tónus

**Egy kattintás** és az összes szín beállítódik!

---

## 📝 Angol Nyelvre Váltás (30 másodperc)

**Szövegek** tab:

```
Banner címsor: Cookie Settings
Leírás: We use cookies to improve functionality, analytics, and advertising.
Elfogad mindent: Accept All Cookies
Elutasít: Essential Only
Mentés: Save Preferences
```

**Mentés** → Kész!

---

## 🎯 Google Tag Manager Beállítás

### 1. GTM Trigger (Analitika)

```
Trigger név: CM - Analytics Granted
Trigger Type: Custom Event
Event name: cm_update
Feltétel: cmv2_analytics equals granted
```

### 2. GTM Trigger (Hirdetés)

```
Trigger név: CM - Ads Granted
Trigger Type: Custom Event
Event name: cm_update
Feltétel: cmv2_ads equals granted
```

### 3. GA4 Tag

```
Tag neve: GA4 - Main
Tag Type: Google Analytics: GA4 Configuration
Measurement ID: G-XXXXXXXXXX
Trigger: CM - Analytics Granted (vagy cm_update)
```

✅ Most már csak akkor tüzel a GA4, ha a felhasználó elfogadta!

---

## 🔧 Gyakori Beállítások

### Banner Mindig Megjelenik? (Teszt után)

1. **Browser DevTools** (F12)
2. **Application** tab
3. **Local Storage** → domain
4. Töröld a `cmv2_state` kulcsot
5. Refresh (F5)

### Szöveg Nem Változik?

1. **Cache törlése:**
   - Browser cache: Ctrl+Shift+Del
   - WP cache plugin: Tisztítás
   
2. **Hard refresh:**
   - Windows: `Ctrl + Shift + R`
   - Mac: `Cmd + Shift + R`

### Színek Nem Jelennek Meg?

1. Mentsd a beállításokat
2. Nyisd meg inkognito/private módban
3. Ha ott működik → cache probléma

---

## 💾 Export/Import

### Beállítások Mentése Másik Oldalra

1. **Haladó** tab
2. Kattints: **📥 Beállítások exportálása**
3. Letöltődik egy `.json` fájl

### Beállítások Betöltése

1. **Haladó** tab
2. Válaszd ki a `.json` fájlt
3. Kattints: **📤 Beállítások importálása**
4. Megerősítés

✅ Minden beállítás átmásolódik!

---

## 🐛 Hibaelhárítás (30 másodperc)

### 1. Banner Nem Jelenik Meg

```javascript
// Console-ba (F12):
console.log(document.getElementById('cmv2-modal'));
// Ha null → JavaScript hiba van
```

**Megoldás:**
- Nézd meg a Console tab-ot
- Valószínű más plugin ütközik

### 2. GTM Nem Kapja Az Eseményeket

```javascript
// Console-ba:
console.log(window.dataLayer);
// Keresd: {event: "cm_update"}
```

**Megoldás:**
- Ellenőrizd, hogy a GTM container be van-e ágyazva
- Használd a GTM Preview Mode-ot

### 3. Beállítások Nem Mentődnek

- Ellenőrizd a **fájl jogosultságokat** (chmod 644)
- Nézd meg a **PHP error log**-ot
- Próbálj **admin userrel** menteni

---

## 📊 Ellenőrzés

### ✅ Minden Működik, Ha:

1. **Banner megjelenik** első látogatáskor
2. **Választás után eltűnik** és nem jön elő újra
3. **Megnyitó gomb** (bal alsó sarokban) működik
4. **Console-ban látod** a `cm_default` és `cm_update` eseményeket
5. **localStorage-ban van** a `cmv2_state` kulcs
6. **GTM Preview Mode-ban látod** az eseményeket

### 🔍 Tesztelés

1. **Private/Incognito módban** nyisd meg az oldalt
2. Kattints **"Elfogadok mindent"**-re
3. **F12** → Console tab
4. Írd be: `console.log(window.CM.get())`
5. Látnod kell:
   ```json
   {
     version: "2025-10-08",
     ts: 1234567890,
     choices: {
       analytics: true,
       ads: true
     }
   }
   ```

✅ Ha ezt látod → minden tökéletes!

---

## 🚀 Tippek

### 1. Többnyelvű Oldal?

Használj WPML-t vagy Polylang-ot, és add hozzá a `functions.php`-hoz:

```php
add_filter('cmv2_get_options', function($opts) {
    if (ICL_LANGUAGE_CODE == 'en') {
        $opts['title'] = 'Cookie Settings';
        // stb...
    }
    return $opts;
});
```

### 2. Egyedi Gomb Helyett Az Alapértelmezett?

**Haladó** tab → Kapcsold **ki** a "Megnyitó gomb megjelenítése" opciót.

Aztán add hozzá saját gombhoz:

```html
<button onclick="window.CM.open()">
    🍪 Sütibeállítások
</button>
```

### 3. Süti Időtartam Csökkentése

Ha gyakrabban szeretnéd újrakérdezni:

**Haladó** tab → **Süti élettartam**: `30` nap (helyett 180)

---

## 📞 Quick Support Checklist

Ha valami nem működik, ellenőrizd:

- [ ] A plugin aktív? (MU plugin esetén automatikus)
- [ ] Van JavaScript hiba a Console-ban?
- [ ] Cache törölve? (WP + browser)
- [ ] Private/incognito módban is probléma van?
- [ ] Más cookie pluginok ki vannak kapcsolva?
- [ ] PHP verzió minimum 7.4?
- [ ] WordPress verzió minimum 5.0?

Ha minden OK → valószínű téma/plugin konfliktus.

---

## 🎓 Hasznos Parancsok (Console)

```javascript
// Banner megnyitása
window.CM.open()

// Jelenlegi állapot
window.CM.get()

// Reset (töröl mindent)
window.CM.reset()

// dataLayer ellenőrzés
console.log(window.dataLayer)

// localStorage ellenőrzés
console.log(localStorage.getItem('cmv2_state'))
```

---

## 📄 Fájlok

```
wp-consent-mode-v2-mu-plugin/
├── consent-mode-v2.php     # Fő plugin fájl
├── README.md               # Teljes dokumentáció (angol)
├── HASZNALAT.md           # Részletes útmutató (magyar)
└── GYORS-START.md         # Ez a fájl
```

---

**Verzió:** 2.0.0  
**Frissítve:** 2025-10-08  
**Fejlesztő:** Custom WordPress Development

🎉 **Boldog cookie compliance-t!** 🍪
