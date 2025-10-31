# 🍪 Sütibeállítások Gomb Ki/Be Kapcsolása

## Beállítás Helye

**WordPress Admin:**
```
Dashboard → Beállítások → Consent Mode V2 → Haladó tab
```

**Checkbox neve:**
```
☑️ Megnyitó gomb megjelenítése
```

---

## 🎯 Opciók

### ✅ Bekapcsolva (Alapértelmezett)

**Viselkedés:**
- 🍪 Cookie gomb megjelenik a bal alsó sarokban
- User bármikor újra megnyithatja a banner-t
- Választásait módosíthatja

**Mikor használd:**
- ✅ GDPR/privacy compliance (user kontroll)
- ✅ Ha szeretnéd, hogy user később módosíthasson
- ✅ Privacy-first megközelítés
- ✅ B2B/enterprise oldalak
- ✅ Regulated industry (health, finance, etc.)

**Előnyök:**
- ✨ Felhasználóbarát
- ✨ Transparent
- ✨ GDPR best practice
- ✨ User kontroll

---

### ❌ Kikapcsolva

**Viselkedés:**
- 🚫 Cookie gomb NEM jelenik meg
- Banner csak első látogatáskor látható
- User nem tudja később módosítani választását
- Egyszerűbb, tisztább UI

**Mikor használd:**
- ✅ Blog/személyes oldal
- ✅ Minimal design
- ✅ Kis traffic, nem kritikus tracking
- ✅ Nem szeretnéd "zavarni" a usert
- ✅ Landing page (one-time visit)

**Előnyök:**
- ✨ Tisztább UI (nincs cookie gomb)
- ✨ Kevesebb distraction
- ✨ Egyszerűbb UX

**Hátrányok:**
- ⚠️ User nem tud később módosítani
- ⚠️ Kevésbé transparent
- ⚠️ GDPR szempontból kérdéses

---

## 🎨 Vizuális Különbségek

### Bekapcsolva (show_open_button = true)

```
┌─────────────────────────────────────┐
│                                     │
│    [Oldal tartalma...]             │
│                                     │
│                                     │
│  🍪 Sütibeállítások  ← LÁTHATÓ    │
└─────────────────────────────────────┘
```

**Kattintás:**
```
🍪 Sütibeállítások → Banner újra megnyílik
```

---

### Kikapcsolva (show_open_button = false)

```
┌─────────────────────────────────────┐
│                                     │
│    [Oldal tartalma...]             │
│                                     │
│                                     │
│                          ← NINCS GOMB │
└─────────────────────────────────────┘
```

**Tisztább UI, nincs cookie gomb!**

---

## ⚙️ Beállítás Módosítása

### WordPress Admin Felületen

1. **Navigálj:**
   ```
   WordPress Dashboard
   └─ Beállítások
      └─ Consent Mode V2
         └─ Haladó tab
   ```

2. **Keresd meg:**
   ```
   ☑️ Megnyitó gomb megjelenítése
   ```

3. **Kapcsold ki/be:**
   - ✅ Bejelölve = gomb LÁTHATÓ
   - ☐ Nincs bejelölve = gomb NINCS

4. **Mentés:**
   ```
   [Beállítások mentése] gomb
   ```

5. **Cache ürítés:**
   - WordPress cache plugin (WP Rocket, W3TC, stb.)
   - Browser cache: `Ctrl+Shift+R` / `Cmd+Shift+R`

---

### Programmatikusan (Fejlesztőknek)

#### Option API

```php
// Lekérés
$options = get_option('cmv2_settings');
$show_button = $options['show_open_button']; // bool

// Módosítás
$options['show_open_button'] = false; // kikapcsol
update_option('cmv2_settings', $options);
```

#### Direct Override (functions.php)

```php
add_filter('cmv2_options', function($options) {
    $options['show_open_button'] = false; // Force OFF
    return $options;
});
```

#### Environment-based (staging vs production)

```php
add_filter('cmv2_options', function($options) {
    // Csak production-ben mutasd a gombot
    if (defined('WP_ENV') && WP_ENV === 'production') {
        $options['show_open_button'] = true;
    } else {
        $options['show_open_button'] = false;
    }
    return $options;
});
```

---

## 📊 Ajánlások Típus Szerint

### 🏢 Corporate / Enterprise
```
Beállítás: ✅ BEKAPCSOLVA
Indok: Compliance, transparency, user rights
```

### 💼 E-commerce
```
Beállítás: ✅ BEKAPCSOLVA
Indok: GDPR, user trust, conversion optimization
```

### 📰 Blog / News Site
```
Beállítás: ✅ BEKAPCSOLVA (vagy kikapcsolva, ha minimal design)
Indok: Depends on audience & traffic
```

### 🎨 Portfolio / Landing Page
```
Beállítás: ❌ KIKAPCSOLVA
Indok: Minimal design, one-time visits
```

### 🏥 Healthcare / Finance
```
Beállítás: ✅✅ KÖTELEZŐEN BEKAPCSOLVA
Indok: Regulatory compliance, patient/client rights
```

---

## 🔍 Debugging

### Gomb Nem Jelenik Meg?

1. **Ellenőrizd admin beállítást:**
   ```
   Beállítások → Consent Mode V2 → Haladó → Megnyitó gomb megjelenítése
   ```

2. **Ellenőrizd localStorage-t:**
   ```javascript
   // Browser Console (F12)
   localStorage.getItem('cmv2_state')
   
   // Ha van mentett választás, a banner NEM jelenik meg
   // De a gomb-nak láthatónak kell lennie!
   ```

3. **Ellenőrizd HTML-t:**
   ```javascript
   // Console
   document.getElementById('cmv2-open')
   
   // Ha null → gomb nincs a DOM-ban
   // Ha element → gomb létezik
   ```

4. **Ellenőrizd CSS-t:**
   ```javascript
   // Console
   const btn = document.getElementById('cmv2-open');
   if (btn) {
     console.log(window.getComputedStyle(btn).display);
     // "block" vagy "inline-block" = látható
     // "none" = rejtett
   }
   ```

5. **Cache probléma?**
   - WordPress cache törlése
   - Browser hard refresh: `Ctrl+Shift+R` / `Cmd+Shift+R`

---

## 🎓 Best Practices

### ✅ DO

- ✅ Kapcsold BE, ha GDPR compliance kell
- ✅ Tesztelj mindkét beállítással
- ✅ Dokumentáld a döntést a csapatnak
- ✅ Figyeld a user feedback-et

### ❌ DON'T

- ❌ Ne kapcsold KI regulated industry-nél
- ❌ Ne rejtsd el a gombot CSS-sel (használd a beállítást!)
- ❌ Ne felejts el tesztelni módosítás után

---

## 📚 Kapcsolódó Dokumentáció

- [HASZNALAT.md](HASZNALAT.md) - Teljes használati útmutató
- [FEATURE-v2.1.0.md](FEATURE-v2.1.0.md) - Kétlépcsős banner feature
- [CHANGELOG.md](CHANGELOG.md) - Verziókövetés

---

## ✅ Összefoglalás

**Beállítás:** `Megnyitó gomb megjelenítése`  
**Hely:** Admin → Beállítások → Consent Mode V2 → Haladó  
**Alapértelmezett:** ✅ BE (true)  
**Ajánlott:** ✅ BE (GDPR compliance)  
**Opcionális:** ❌ KI (minimal design)  

**Verzió:** 2.1.2+  
**Dokumentáció frissítve:** 2025-10-09

---

*Dokumentációt készítette: GitHub Copilot*
