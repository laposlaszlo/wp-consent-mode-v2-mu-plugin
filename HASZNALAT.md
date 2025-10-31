# 🍪 Consent Mode V2 - WordPress Plugin

## Gyors Telepítési Útmutató

### 1. Telepítés (MU Plugin)
1. Másold a `consent-mode-v2.php` fájlt ide: `wp-content/mu-plugins/`
2. Ha nincs ilyen mappa, hozd létre
3. Kész! A plugin automatikusan betöltődik

### 2. Beállítások
1. WordPress admin → **Beállítások** → **Consent Mode V2**
2. Állítsd be a szövegeket, színeket
3. Kattints a **"💾 Beállítások mentése"** gombra

### 3. Tesztelés
1. Nyisd meg az oldaladat
2. Látni fogod a cookie banner-t
3. Teszteld a gombokat
4. Ellenőrizd, hogy a beállítások elmentődnek

## 🎨 Színek Beállítása

### Alap Színséma (Jelenleg)
- **Elsődleges szín**: `#111111` (fekete)
- **Háttérszín**: `#ffffff` (fehér)

### Példa: Modern Kék Téma
```
Elsődleges szín: #0066cc
Elsődleges szövegszín: #ffffff
Háttérszín: #ffffff
Szövegszín: #000000
Szegélyszín: #e0e0e0
Link szín: #0066cc
```

### Példa: Dark Mode
```
Elsődleges szín: #ffffff
Elsődleges szövegszín: #000000
Háttérszín: #1a1a1a
Szövegszín: #ffffff
Szegélyszín: #333333
Link szín: #4a9eff
```

### Példa: Zöld Eco Téma
```
Elsődleges szín: #2ecc71
Elsődleges szövegszín: #ffffff
Háttérszín: #ffffff
Szövegszín: #2c3e50
Szegélyszín: #95a5a6
Link szín: #27ae60
```

## 📝 Szövegek Angol Nyelvre

Ha angol nyelvű az oldalad, használd ezeket:

```
Title: Cookie Settings
Description: We use cookies and similar technologies to improve functionality, provide analytics, and deliver personalized content. Non-essential cookies require your consent.
Privacy Link Text: Privacy Policy
Necessary Label: Necessary
Analytics Label: Analytics (GA4)
Ads Label: Advertising & Marketing
Accept All: Accept All Cookies
Reject All: Essential Only
Save: Save Preferences
Open Button: Cookie Settings
```

## 📝 Szövegek Német Nyelvre

```
Title: Cookie-Einstellungen
Description: Wir verwenden Cookies und ähnliche Technologien für Funktionalität, Analyse und Werbung. Nicht unbedingt erforderliche Elemente werden erst nach Ihrer Zustimmung aktiviert.
Privacy Link Text: Datenschutzerklärung
Necessary Label: Notwendig
Analytics Label: Analyse (GA4)
Ads Label: Werbung & Marketing
Accept All: Alle akzeptieren
Reject All: Nur notwendige
Save: Speichern
Open Button: Cookie-Einstellungen
```

## 📝 Szövegek Spanyol Nyelvre

```
Title: Configuración de cookies
Description: Utilizamos cookies y tecnologías similares para funcionalidad, análisis y publicidad. Los elementos no esenciales solo se activan con tu consentimiento.
Privacy Link Text: Política de privacidad
Necessary Label: Necesarias
Analytics Label: Análisis (GA4)
Ads Label: Publicidad y Marketing
Accept All: Aceptar todas
Reject All: Solo necesarias
Save: Guardar
Open Button: Configuración de cookies
```

## 🎯 Google Tag Manager Beállítás

### Alapvető GTM Integráció

1. **GTM Container kód beillesztése** (ha még nincs):
   - Másold be a GTM kódot a `<head>` és `<body>` tag-ek után

2. **GA4 Tag beállítás GTM-ben**:
   - Tag Type: Google Analytics: GA4 Configuration
   - Measurement ID: `G-XXXXXXXXXX` (a saját GA4 ID-d)
   - **Trigger:** Custom Event = `cm_update`
   - **Condition:** `cmv2_analytics` equals `granted`

3. **Google Ads Conversion Tag**:
   - Tag Type: Google Ads Conversion Tracking
   - Conversion ID: `AW-XXXXXXXXXX`
   - **Trigger:** Custom Event = `cm_update`
   - **Condition:** `cmv2_ads` equals `granted`

### GTM Variables (Változók)

Hozd létre ezeket a Data Layer változókat:

1. **cmv2_analytics**
   - Variable Type: Data Layer Variable
   - Data Layer Variable Name: `cmv2_analytics`

2. **cmv2_ads**
   - Variable Type: Data Layer Variable
   - Data Layer Variable Name: `cmv2_ads`

3. **cmv2_version**
   - Variable Type: Data Layer Variable
   - Data Layer Variable Name: `cmv2_version`

## 🔧 Haladó Testreszabás

### CSS Felülírás

Ha további CSS módosításokat szeretnél, add hozzá a child theme `style.css` fájljához:

```css
/* Banner átlátszóság */
#cmv2-modal .cmv2-window {
    opacity: 0.95;
}

/* Animáció */
#cmv2-modal .cmv2-window {
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Megnyitó gomb jobbra pozicionálása */
.cmv2-open {
    left: auto !important;
    right: 16px !important;
}
```

### JavaScript Események Kezelése

```javascript
// Figyelj a consent változásra
window.addEventListener('storage', function(e) {
    if (e.key === 'cmv2_state') {
        console.log('Consent változás:', JSON.parse(e.newValue));
    }
});

// Modal megnyitása egyedi gombból
document.getElementById('my-custom-button').addEventListener('click', function() {
    window.CM.open();
});

// Consent állapot ellenőrzése
const consent = window.CM.get();
if (consent && consent.choices.analytics) {
    console.log('Analytics engedélyezve');
}
```

### PHP Hook-ok

```php
// Szövegek dinamikus módosítása
add_filter('cmv2_get_options', function($options) {
    // Egyedi logika alapján módosítsd a szövegeket
    if (is_page('english')) {
        $options['title'] = 'Cookie Settings';
    }
    return $options;
});

// Privacy URL dinamikus beállítása
add_filter('cmv2_policy_url', function($url) {
    return get_permalink(get_page_by_path('privacy-policy'));
});
```

## ⚡ Teljesítmény Optimalizálás

### 1. Script Betöltés Optimalizálása

A plugin a wp_footer hook-ba van kötve (priority 99), így:
- ✅ Nem blokkolja a page load-ot
- ✅ Az összes más script után töltődik be
- ✅ Aszinkron módon fut

### 2. localStorage Cache

A felhasználói választás localStorage-ban tárolódik:
- ✅ Nincs szerver oldali kérés
- ✅ Azonnali betöltés
- ✅ 180 napos (állítható) cache

### 3. Minimális Méret

- JavaScript: ~3KB (minified)
- CSS: ~2KB (minified)
- Összesen: ~5KB extra méret

## 🐛 Gyakori Problémák Megoldása

### A banner mindig megjelenik (nem menti a választást)

**Megoldás 1**: localStorage engedélyezése
```javascript
// Console-ban futtasd le:
localStorage.setItem('test', '1');
console.log(localStorage.getItem('test'));
// Ha nem működik, a browser blokkolja
```

**Megoldás 2**: Cookie-k engedélyezése a böngészőben
- Chrome: Settings → Privacy and Security → Allow cookies
- Firefox: Settings → Privacy & Security → Custom → Accept cookies

### A színek nem jelennek meg

1. **Cache törlése**:
   - WP plugin cache törlése (WP Super Cache, W3 Total Cache, stb.)
   - Browser cache törlése (Ctrl+Shift+Delete)

2. **CSS konfliktek ellenőrzése**:
   - Nyisd meg a DevTools-t (F12)
   - Elements tab → válaszd ki a `.cmv2-window` elemet
   - Nézd meg, hogy milyen CSS szabályok aktívak

3. **Hard refresh**:
   - Windows: `Ctrl + Shift + R`
   - Mac: `Cmd + Shift + R`

### GTM nem kapja meg az eseményeket

1. **dataLayer ellenőrzése**:
```javascript
console.log(window.dataLayer);
// Látnod kell: {event: "cm_default"} és {event: "cm_update"}
```

2. **GTM Preview Mode**:
   - GTM → Preview gomb
   - Reload az oldalad
   - Nézd meg a Tags és Events tabokat

3. **Consent signals ellenőrzése**:
```javascript
console.log(gtag('get', 'G-XXXXXXXXXX', 'consent'));
```

## 📱 Mobil Optimalizálás

A banner automatikusan reszponzív:
- **Desktop**: Központosított modal ablak
- **Tablet**: Kisebb margin
- **Mobile**: 
  - Teljes szélesség (kevesebb padding)
  - Gombok teljes szélességben
  - Függőleges elrendezés

### Tesztelés

Chrome DevTools → Toggle device toolbar (Ctrl+Shift+M)

## 🔐 Adatvédelem & GDPR

### Megfelelőség Checklist

✅ **Előzetes tájékoztatás**: Banner megjelenik első látogatáskor  
✅ **Explicit consent**: Felhasználónak kell választania  
✅ **Granular control**: Külön kategóriák (Analytics, Ads)  
✅ **Easy withdraw**: Bármikor megváltoztatható (megnyitó gomb)  
✅ **Default denied**: Alapértelmezés szerint minden tracking ki van kapcsolva  
✅ **Privacy policy link**: Közvetlen link az adatvédelmi oldalra  
✅ **No tracking before consent**: Google Consent Mode V2 miatt  

### Adatvédelmi Nyilatkozatban Említendő

```
Süti (Cookie) Használat

Weboldalunk sütiket használ a következő célokra:
- Szükséges: Weboldal működéséhez nélkülözhetetlenek
- Analitika: Google Analytics 4 (GA4) látogatási statisztikák
- Hirdetés: Google Ads, Meta Pixel remarketing

A sütik használatát a látogatás első alkalommal egyszer kell engedélyezni.
A beállítások bármikor módosíthatók a "Sütibeállítások" gombra kattintva.

További információ: [Linkelj a teljes cookie policy-re]
```

## 📊 Analytics & Tracking

### Google Analytics 4 Beállítás

1. **GA4 Admin → Data Streams → Configure tag settings → Show more**
2. **Google signals**: Kapcsold be (ha consent van)
3. **Ads Personalization**: Kapcsold be (ha consent van)

### Consent Mode Riportok GA4-ben

GA4-ben nézd meg:
- Admin → Data display → Reporting identity → Observe users
- Reports → Life cycle → Acquisition → Traffic acquisition
  - Modeled = consent nélküli látogatók becslése
  - Observed = consent-tal rendelkező látogatók

## 🎓 Tippek & Trükkök

### 1. A/B Tesztelés

Próbálj ki különböző szövegeket:
```
A verzió: "Elfogadok mindent"
B verzió: "Rendben, elfogadom"
C verzió: "Értem, elfogadom"
```

Mérd a konverziós rátát (hány % ad consent-et).

### 2. Süti Időtartam Optimalizálás

- **30 nap**: Gyakrabban kérdez újra (több consent)
- **180 nap**: Balanced (alapértelmezett)
- **365 nap**: Ritkábban kérdez újra

### 3. Megnyitó Gomb Elhelyezése

Ha zavaró a bal alsó sarokban, kapcsold ki és add hozzá egyedi helyre:

```html
<!-- functions.php vagy template fájlban -->
<button onclick="window.CM.open()">
    🍪 Sütibeállítások
</button>
```

### 4. Automatikus Elfogadás Bizonyos Felhasználóknak

```php
add_filter('cmv2_auto_accept', function($auto) {
    // Pl. admin felhasználóknak automatikus elfogadás
    if (current_user_can('administrator')) {
        return true;
    }
    return false;
});
```

## 📞 Support

Ha problémád van:
1. Nézd meg ezt a dokumentációt
2. Ellenőrizd a Console-ban a hibaüzeneteket (F12)
3. Teszteld private/incognito módban
4. Próbáld meg alaphelyzetbe állítani a színeket

---

**Készítve ❤️-vel WordPress fejlesztőknek**

Verzió: 2.0.0 | Utolsó frissítés: 2025-10-08
