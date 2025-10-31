# 🏷️ Google Tag Manager - Teljes Konfiguráció

## 📋 Lépésről Lépésre Beállítás

---

## 1️⃣ VÁLTOZÓK (Variables)

### Data Layer Variables létrehozása

Menj a **Variables** → **New** → **Data Layer Variable**

#### 1.1 cmv2_version
```
Variable Type: Data Layer Variable
Variable Name: cmv2_version
Data Layer Variable Name: cmv2_version
Description: Consent Mode v2 verzió száma
```

#### 1.2 cmv2_analytics
```
Variable Type: Data Layer Variable
Variable Name: cmv2_analytics
Data Layer Variable Name: cmv2_analytics
Description: Analytics consent státusz (granted/denied)
```

#### 1.3 cmv2_ads
```
Variable Type: Data Layer Variable
Variable Name: cmv2_ads
Data Layer Variable Name: cmv2_ads
Description: Ads consent státusz (granted/denied)
```

---

## 2️⃣ TRIGGEREK (Triggers)

### 2.1 CM - Default Consent
```
Trigger Type: Custom Event
Event name: cm_default

Description: Tüzel amikor az oldal betöltődik és a default consent beállítódik
```

### 2.2 CM - Update Consent
```
Trigger Type: Custom Event
Event name: cm_update

Description: Tüzel amikor a felhasználó választ (elfogad/elutasít)
```

### 2.3 CM - Analytics Granted
```
Trigger Type: Custom Event
Event name: cm_update

Feltétel (This trigger fires on):
Some Custom Events

Feltétel beállítás:
{{cmv2_analytics}} equals granted

Description: Csak akkor tüzel, ha az Analytics engedélyezve van
```

### 2.4 CM - Ads Granted
```
Trigger Type: Custom Event
Event name: cm_update

Feltétel (This trigger fires on):
Some Custom Events

Feltétel beállítás:
{{cmv2_ads}} equals granted

Description: Csak akkor tüzel, ha az Ads engedélyezve van
```

### 2.5 CM - Analytics Denied
```
Trigger Type: Custom Event
Event name: cm_update

Feltétel (This trigger fires on):
Some Custom Events

Feltétel beállítás:
{{cmv2_analytics}} equals denied

Description: Tüzel ha az Analytics elutasítva (opcionális, analytics célokra)
```

---

## 3️⃣ CÍMKÉK (Tags)

### 3.1 GA4 - Configuration Tag

```
Tag Type: Google Analytics: GA4 Configuration

Measurement ID: G-XXXXXXXXXX  (cseréld a saját ID-dre)

Configuration Settings:
├─ Fields to Set:
│  └─ (üres, ha nincs egyedi beállítás)
│
└─ Advanced Settings:
   └─ Tag Sequencing: (üres)

Triggering:
├─ Firing Triggers:
│  └─ CM - Analytics Granted
│
└─ Exception Triggers:
   └─ (üres)

Description: GA4 főkonfiguráció - csak akkor aktiválódik, ha az Analytics consent granted
```

#### GA4 Debug Mód Bekapcsolása (Opcionális)

```
Configuration Settings → Fields to Set:
Field Name: debug_mode
Value: true
```

### 3.2 GA4 - Consent Default Event (Opcionális)

```
Tag Type: Google Analytics: GA4 Event

Configuration Tag: {{GA4 Configuration Tag}}

Event Name: consent_default

Event Parameters:
├─ Parameter Name: cmv2_version
│  Value: {{cmv2_version}}
│
└─ (opcionális további paraméterek)

Triggering:
└─ Firing Triggers:
   └─ CM - Default Consent

Description: Analytics esemény a default consent-ről (debugging)
```

### 3.3 GA4 - Consent Update Event (Opcionális)

```
Tag Type: Google Analytics: GA4 Event

Configuration Tag: {{GA4 Configuration Tag}}

Event Name: consent_update

Event Parameters:
├─ Parameter Name: cmv2_version
│  Value: {{cmv2_version}}
├─ Parameter Name: cmv2_analytics
│  Value: {{cmv2_analytics}}
└─ Parameter Name: cmv2_ads
   Value: {{cmv2_ads}}

Triggering:
└─ Firing Triggers:
   └─ CM - Update Consent

Description: Analytics esemény a consent frissítésről
```

### 3.4 Google Ads Conversion Tracking

```
Tag Type: Google Ads Conversion Tracking

Conversion ID: AW-XXXXXXXXXX  (cseréld a saját ID-dre)
Conversion Label: XXXXXXXXXX

Conversion Value: (opcionális)
Order ID: (opcionális)

Triggering:
└─ Firing Triggers:
   └─ CM - Ads Granted

Description: Google Ads conversion tracking - csak ads consent után
```

### 3.5 Google Ads Remarketing

```
Tag Type: Google Ads Remarketing

Conversion ID: AW-XXXXXXXXXX

Triggering:
└─ Firing Triggers:
   └─ CM - Ads Granted

Description: Google Ads remarketing - csak ads consent után
```

### 3.6 Meta Pixel (Facebook Pixel)

```
Tag Type: Custom HTML

HTML:
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', 'YOUR_PIXEL_ID');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=YOUR_PIXEL_ID&ev=PageView&noscript=1"
/></noscript>

Triggering:
└─ Firing Triggers:
   └─ CM - Ads Granted

Advanced Settings:
└─ Tag firing options: Once per page

Description: Meta Pixel - csak ads consent után
```

---

## 4️⃣ BUILT-IN CONSENT MODE BEÁLLÍTÁS (Opcionális)

Ha használod a GTM beépített Consent Mode funkcióját:

### 4.1 Container Settings

```
Admin → Container Settings → Additional Settings
└─ Enable consent overview: ✅ Checked
```

### 4.2 Consent Initialization Tag

```
Tag Type: Consent Initialization - Google tags

Consent Command: default

Regions: All regions (vagy specifikus országok)

Consent Types:
├─ ad_storage: denied
├─ analytics_storage: denied
├─ ad_user_data: denied
├─ ad_personalization: denied
└─ functionality_storage: granted (opcionális)

Triggering:
└─ Consent Initialization - All Pages

Description: GTM built-in consent mode inicializálás
```

**FIGYELEM:** Ha ezt használod, akkor a plugin már beállítja a default consent-et a `<head>`-ben, így ez DUPLIKÁLÁS lehet. Vagy-vagy!

---

## 5️⃣ PREVIEW MODE TESZTELÉS

### 5.1 Preview Mode Elindítása

1. GTM → **Preview** gomb
2. Írd be az oldal URL-jét
3. **Connect**

### 5.2 Mit Kell Látnod

#### Oldal Betöltéskor:

```
🔵 Events:
├─ Container Loaded
├─ cm_default ✅
└─ (GTM böngészőbeli események)

📊 Data Layer (cm_default):
{
  event: "cm_default",
  cmv2_version: "2025-10-08"
}

🏷️ Tags Fired:
└─ (csak azok, amik cm_default triggerre vannak)
```

#### "Elfogadok Mindent" Gombra Kattintva:

```
🔵 Events:
└─ cm_update ✅

📊 Data Layer (cm_update):
{
  event: "cm_update",
  cmv2_version: "2025-10-08",
  cmv2_analytics: "granted",
  cmv2_ads: "granted"
}

🏷️ Tags Fired:
├─ GA4 Configuration ✅
├─ Google Ads Conversion ✅
├─ Google Ads Remarketing ✅
└─ Meta Pixel ✅
```

#### "Csak Szükséges" Gombra Kattintva:

```
🔵 Events:
└─ cm_update ✅

📊 Data Layer (cm_update):
{
  event: "cm_update",
  cmv2_version: "2025-10-08",
  cmv2_analytics: "denied",
  cmv2_ads: "denied"
}

🏷️ Tags Fired:
└─ (semmi nem tüzel, mert consent denied)
```

---

## 6️⃣ GA4 ELLENŐRZÉS

### 6.1 DebugView Használata

1. GA4 Admin → **DebugView**
2. Nyisd meg az oldaladat
3. Add meg a consent-et

#### Mit Kell Látnod:

```
Events:
├─ page_view (első betöltés - NEM látszódik, mert denied)
└─ page_view (consent után - LÁTSZÓDIK) ✅

User Properties:
└─ (egyedi user property-k, ha van)

Consent Status:
├─ ad_storage: granted/denied
├─ analytics_storage: granted/denied
├─ ad_user_data: granted/denied
└─ ad_personalization: granted/denied
```

### 6.2 Realtime Report

```
GA4 → Reports → Realtime

Ha látod magadat az Active Users között → működik! ✅
```

---

## 7️⃣ HIBAELHÁRÍTÁS

### 7.1 dataLayer Üres vagy Hibás

**Problem:**
```javascript
console.log(window.dataLayer);
// []  <- üres
```

**Megoldás:**
1. Ellenőrizd, hogy a GTM container kód be van-e ágyazva
2. Nézd meg, hogy a plugin aktív-e
3. Töröld a browser cache-t

### 7.2 GA4 Nem Kapja Az Eseményeket

**Problem:**
DebugView-ban nem látszanak az események

**Megoldás:**
1. Ellenőrizd a Measurement ID-t (G-XXXXXXXXXX)
2. GA4 Admin → Data Streams → Configure tag settings
3. Kapcsold be az "Enhanced measurement"-et (opcionális)
4. Várj 5-10 percet (propagáció idő)

### 7.3 Trigger Nem Tüzel

**Problem:**
Preview Mode-ban a trigger nem aktiválódik

**Megoldás:**
1. Ellenőrizd a feltételt ({{cmv2_analytics}} equals granted)
2. Nézd meg a Data Layer-t: tényleg "granted" van-e benne?
3. Esetleg a változó neve elírva? (case-sensitive!)

### 7.4 Tag Tüzel Consent Nélkül Is

**Problem:**
GA4 tüzel, pedig nem adtam consent-et

**Megoldás:**
1. Ellenőrizd a Tag trigger-jét: CM - Analytics Granted?
2. Töröld a localStorage-t (régi consent)
3. Tesztelj private/incognito módban

---

## 8️⃣ HALADÓ KONFIGURÁCIÓ

### 8.1 Custom Dimensions (GA4)

```
GA4 Admin → Custom Definitions → Custom Dimensions

Dimension Name: Consent Version
User Property: cmv2_version
Scope: Event
Description: Consent Mode verzió tracking
```

Most már látod a reportokban, hogy melyik verzió van aktív!

### 8.2 Enhanced Conversions (Google Ads)

```
Tag Type: Google Ads Conversion Tracking

Enable Enhanced Conversions: ✅

User-provided data:
├─ Email: {{user_email}} (változó)
├─ Phone: {{user_phone}} (változó)
└─ Address: {{user_address}} (változó)

Triggering:
└─ CM - Ads Granted + Conversion Event
```

### 8.3 Server-Side GTM (Advanced)

Ha van server-side GTM container:

```
Client Type: Google Tag Manager Web Container

Consent Settings:
├─ Default consent: denied
└─ Update consent: based on user choice

Tags:
└─ GA4 Server-Side Tag
   └─ Consent Checks: enabled
```

---

## 9️⃣ GDPR COMPLIANCE CHECKLIST

### GTM Beállítások GDPR-hoz

- [x] Default consent: denied minden tracking-hez
- [x] Update consent: user choice után
- [x] Granular control: külön Analytics és Ads
- [x] No tracking before consent: minden tag trigger után
- [x] Consent version tracking: cmv2_version változó
- [x] Re-consent capability: TTL + megnyitó gomb
- [x] Consent logging: dataLayer események

---

## 🔟 EXPORT/IMPORT GTM CONTAINER

### 10.1 Container Exportálás

1. GTM Admin → **Export Container**
2. Verzió kiválasztása (legújabb)
3. Export Format: JSON
4. Letöltés

### 10.2 Container Importálás Másik Oldalra

1. Új GTM container létrehozása
2. Admin → **Import Container**
3. Fájl kiválasztása (.json)
4. Import Option: **Merge** (ha van létező) vagy **Overwrite** (ha üres)
5. **Confirm**

**FIGYELEM:** Cseréld ki:
- GA4 Measurement ID-t
- Google Ads Conversion ID-t
- Meta Pixel ID-t
- Egyéb specifikus ID-kat

---

## 📊 PÉLDA TELJES GTM STRUKTÚRA

```
📦 GTM Container: "Consent Mode V2 Setup"
│
├── 📂 Variables (3)
│   ├── 🔢 cmv2_version
│   ├── 🔢 cmv2_analytics
│   └── 🔢 cmv2_ads
│
├── 📂 Triggers (4)
│   ├── 🎯 CM - Default Consent (cm_default)
│   ├── 🎯 CM - Update Consent (cm_update)
│   ├── 🎯 CM - Analytics Granted (cm_update + analytics=granted)
│   └── 🎯 CM - Ads Granted (cm_update + ads=granted)
│
├── 📂 Tags (5)
│   ├── 🏷️ GA4 Configuration (trigger: Analytics Granted)
│   ├── 🏷️ GA4 Consent Update Event (trigger: Update Consent)
│   ├── 🏷️ Google Ads Conversion (trigger: Ads Granted)
│   ├── 🏷️ Google Ads Remarketing (trigger: Ads Granted)
│   └── 🏷️ Meta Pixel (trigger: Ads Granted)
│
└── 📂 Built-in Variables (Enabled)
    ├── Page URL
    ├── Page Hostname
    ├── Page Path
    └── Referrer
```

---

## 🎓 PRO TIPPEK

### Tip #1: Consent Rate Tracking

Hozz létre egy egyedi GA4 eseményt:

```
Tag Type: GA4 Event
Event Name: consent_choice

Event Parameters:
├─ choice_type: {{choice_type}}
│   (Változó: "accept_all" / "reject_all" / "custom")
└─ cmv2_version: {{cmv2_version}}

Triggering: CM - Update Consent
```

Most látod, hány % ad consent-et!

### Tip #2: A/B Testing

```
Variable Name: consent_variant
Variable Type: Random Number
Min: 0
Max: 1

Használd ezt a változót a banner színének változtatására
és mérd a conversion rate-et!
```

### Tip #3: Consent Expiry Tracking

```
Variable Name: consent_expiry_days
Variable Type: Custom JavaScript

Code:
function() {
  var state = JSON.parse(localStorage.getItem('cmv2_state'));
  if (!state) return 0;
  var now = Math.floor(Date.now() / 1000);
  var elapsed = (now - state.ts) / (24*60*60);
  return Math.floor(180 - elapsed); // Days until re-consent
}
```

---

## 🏁 VÉGSŐ CHECKLIST

Mielőtt publish-olod a GTM container-t:

- [ ] Minden változó létrehozva (3 db)
- [ ] Minden trigger beállítva (4 db)
- [ ] Minden tag konfigurálva (5+ db)
- [ ] Preview Mode-ban tesztelve
- [ ] GA4 DebugView-ban ellenőrizve
- [ ] Console-ban dataLayer ellenőrizve
- [ ] Private/incognito módban tesztelve
- [ ] Mobil nézetben tesztelve
- [ ] Consent elfogadva → tagek tüzelnek ✅
- [ ] Consent elutasítva → tagek NEM tüzelnek ✅

---

## 🚀 PUBLISH!

Ha minden OK:

1. GTM → **Submit**
2. Version Name: `Consent Mode V2 - Initial Setup`
3. Version Description: `Google Consent Mode V2 implementation with CMV2 plugin`
4. **Publish**

🎉 **Kész! A GTM élőben van!** 🎉

---

## 📞 További Segítség

- [Google Tag Manager Súgó](https://support.google.com/tagmanager)
- [GA4 Dokumentáció](https://support.google.com/analytics/answer/9744165)
- [Consent Mode Referencia](https://developers.google.com/tag-platform/security/guides/consent)

---

Verzió: 2.0.0  
Frissítve: 2025-10-08  
Készítette: Custom WordPress Development

🏷️ **Boldog tracking-et!** 📊
