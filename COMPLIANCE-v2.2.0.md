# Google Consent Mode V2 Compliance – v2.2.0

## Kritikus javítások (2025-10-09)

Ez a verzió **kritikus compliance javításokat** tartalmaz, amelyek elengedhetetlenek a Google Consent Mode V2 és a GDPR teljes körű betartásához.

---

## 🔴 Mi változott?

A v2.2.0 verzióban **4 új paramétert** adtunk hozzá a `gtag('consent', 'default')` híváshoz:

### 1. `wait_for_update: 500` ⚠️ **KRITIKUS**

**Mit csinál:**
- A Google Tag Manager **500 ms-ot vár**, mielőtt a tag-eket elindítaná
- Ez az idő elegendő ahhoz, hogy a felhasználó consent állapotát betöltsük localStorage-ból
- Ha nincs `wait_for_update`, a GTM azonnal elindul **még akkor is**, ha a consent `denied` állapotban van

**Miért kritikus:**
- ❌ **Nélküle**: A GTM azonnal elindul, még mielőtt a consent módot beállítanánk → GDPR sértés
- ✅ **Vele**: A GTM megvárja, amíg betöltjük a mentett consent állapotot → Compliance

**Példa:**
```javascript
gtag('consent', 'default', {
  ad_storage: 'denied',
  analytics_storage: 'denied',
  ad_user_data: 'denied',
  ad_personalization: 'denied',
  wait_for_update: 500 // ⬅️ 500ms várakozás
});
```

---

### 2. `region: [...]` ⚠️ **KRITIKUS**

**Mit csinál:**
- Meghatározza, hogy **mely országokban** legyen `denied` az alapértelmezett consent állapot
- EU/EEA országokban GDPR szabályok érvényesek → `denied` alapból
- Más országokban (pl. USA) nem kell ilyen szigorú → lehet `granted`

**Implementált országok** (EU/EEA + UK + EFTA):
```javascript
region: [
  'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
  'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
  'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB', 'IS', 'LI',
  'NO', 'CH'
]
```

**Miért kritikus:**
- ❌ **Nélküle**: Az összes látogatónak `denied` az alapállapot, még USA-ban is
- ✅ **Vele**: Csak EU/EEA-ban `denied`, más országokban lehet `granted`

---

### 3. `url_passthrough: true` ✅ **Ajánlott**

**Mit csinál:**
- URL paraméterekben továbbítja a marketing campaign adatokat (pl. `gclid`, `utm_*`)
- **Akkor is működnek** a kampány jelentések, ha a látogató még nem adott consent-et

**Miért fontos:**
- ❌ **Nélküle**: Elvesznek a campaign paraméterek, ha nincs consent → marketing adatvesztés
- ✅ **Vele**: Campaign tracking működik `denied` állapotban is (GDPR compliant módon)

**Implementálás:**
```javascript
gtag('set', 'url_passthrough', true);
```

---

### 4. `ads_data_redaction: true` ✅ **Ajánlott**

**Mit csinál:**
- Törli/redukálja a reklámadat-gyűjtést `denied` állapotban
- IP címeket és személyes adatokat nem küld Google szervereire

**Miért fontos:**
- ❌ **Nélküle**: Részleges adatok továbbra is elküldésre kerülnek
- ✅ **Vele**: Teljes adatredukció `denied` esetén → GDPR compliance

**Implementálás:**
```javascript
gtag('set', 'ads_data_redaction', true);
```

---

## 🎯 Teljes kód – consent-banner.js

```javascript
/**
 * Set default consent (GDPR compliance)
 * Must be called BEFORE GTM loads
 */
function setDefaultConsent() {
  // URL passthrough for cross-domain tracking
  gtag('set', 'url_passthrough', true);
  
  // Ads data redaction (GDPR compliance)
  gtag('set', 'ads_data_redaction', true);
  
  // Set default consent state (denied for EU/EEA)
  gtag('consent', 'default', {
    ad_storage: 'denied',
    analytics_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied',
    wait_for_update: 500, // KRITIKUS: 500ms várakozás GTM indítás előtt
    region: [
      'AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR',
      'DE','GR','HU','IE','IT','LV','LT','LU','MT','NL',
      'PL','PT','RO','SK','SI','ES','SE','GB','IS','LI',
      'NO','CH'
    ]
  });
  
  // Push default consent event to dataLayer
  window.dataLayer.push({
    event: 'cm_default',
    consent_default: {
      ad_storage: 'denied',
      analytics_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied'
    }
  });
}
```

---

## 📋 Compliance Checklist

### ✅ Google Consent Mode V2 Paraméterek

| Paraméter | Állapot | Megjegyzés |
|-----------|---------|-----------|
| `ad_storage` | ✅ Denied | Alapértelmezetten tiltva |
| `analytics_storage` | ✅ Denied | Alapértelmezetten tiltva |
| `ad_user_data` | ✅ Denied | Alapértelmezetten tiltva |
| `ad_personalization` | ✅ Denied | Alapértelmezetten tiltva |
| `wait_for_update` | ✅ 500ms | **KRITIKUS** – GTM várakozik |
| `region` | ✅ EU/EEA | **KRITIKUS** – régiófüggő |
| `url_passthrough` | ✅ True | Campaign tracking működik |
| `ads_data_redaction` | ✅ True | IP/adat redukció |

### ✅ GDPR Követelmények

- ✅ **Opt-in alapú**: Felhasználónak aktívan jóvá kell hagynia (nem pre-checked)
- ✅ **Explicit consent**: "Elfogadok mindent" vagy "Csak szükséges" gomb
- ✅ **Granular control**: Külön kapcsoló Analytics és Ads számára
- ✅ **Banner blocking**: Felhasználó nem zárharja be ESC-el vagy backdrop kattintással
- ✅ **Cookie button**: Később is módosíthatók a beállítások
- ✅ **180 napos lejárat**: Automatikus consent expire
- ✅ **Region-specific**: Csak EU/EEA-ban `denied` alapból

---

## 🔍 Tesztelés

### 1. GTM Preview Mode

1. Nyisd meg a GTM Preview módot
2. Látogasd meg az oldalt
3. Ellenőrizd a `dataLayer` eseményeket:
   - ✅ `cm_default` esemény megjelenik
   - ✅ GTM **500ms-ot vár** az első tag indítása előtt
   - ✅ Consent állapot: `denied`

### 2. Google Tag Assistant

1. Telepítsd a [Google Tag Assistant](https://tagassistant.google.com/) Chrome extension-t
2. Nyisd meg az oldalt
3. Ellenőrizd:
   - ✅ `wait_for_update: 500` megjelenik
   - ✅ `region` tartalmazza az EU országokat
   - ✅ Consent mode: `denied` → `granted` váltás működik

### 3. LocalStorage Tesztelés

1. Nyisd meg a böngésző Developer Tools → Application → Local Storage
2. Ellenőrizd:
   - ✅ `cmv2_state` kulcs létezik
   - ✅ `version`, `ts`, `choices` mezők kitöltöttek
   - ✅ Újratöltéskor a banner **nem jelenik meg** (ha van consent)

---

## 🚀 Migrálás v2.1.2 → v2.2.0

1. **Töltsd le** az új `consent-mode-v2.php` és `assets/js/consent-banner.js` fájlokat
2. **Cseréld ki** a régieket az MU plugins mappában
3. **Töröld** a böngésző cache-t (Ctrl+Shift+Del)
4. **Teszteld** GTM Preview módban
5. **Ellenőrizd** a consent jeleket Google Tag Assistant-tel

**Nincs szükség** adatbázis migrációra vagy beállítások újrakonfigurálására!

---

## 📚 További források

- [Google Consent Mode V2 Dokumentáció](https://developers.google.com/tag-platform/security/guides/consent?consentmode=advanced)
- [GDPR Compliance Guide](https://gdpr.eu/)
- [GTM DataLayer Events](https://developers.google.com/tag-platform/devguides/datalayer)

---

## ⚠️ FONTOS

Ez a verzió **KRITIKUS compliance javításokat** tartalmaz. A `wait_for_update: 500` paraméter nélkül **a plugin NEM GDPR compliant**, mivel a GTM azonnal elindul, még mielőtt a consent módot beállítanánk.

**Frissíts azonnal a v2.2.0-ra**, ha production környezetben használod!

---

**Verzió:** 2.2.0  
**Dátum:** 2025-10-09  
**Szerző:** You  
**License:** MIT
