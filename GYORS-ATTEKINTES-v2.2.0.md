# 🎯 v2.2.0 – Gyors Áttekintés

## Mi változott?

A v2.2.0 **kritikus Google Consent Mode V2 javításokat** tartalmaz. A plugin most már **teljesen GDPR compliant** és **helyes consent jeleket továbbít** a Google Tag Manager felé.

---

## 🔴 4 Új Paraméter

### 1. wait_for_update: 500 ⚠️ KRITIKUS

**Mit csinál:**
- GTM **vár 500ms-ot**, mielőtt tag-eket indít
- Ez alatt betöltődik a localStorage consent állapot
- **Nélküle**: GTM azonnal elindul → adatvesztés
- **Vele**: GTM megvárja a consent módot → compliance

### 2. region: [EU/EEA országok] ⚠️ KRITIKUS

**Mit csinál:**
- Csak **EU/EEA országokban** legyen `denied` alapállapot
- Más országokban (pl. USA) lehet `granted`
- **32 ország** hozzáadva: AT, BE, BG, HR, CY, CZ, DK, EE, FI, FR, DE, GR, HU, IE, IT, LV, LT, LU, MT, NL, PL, PT, RO, SK, SI, ES, SE, GB, IS, LI, NO, CH

### 3. url_passthrough: true ✅ Ajánlott

**Mit csinál:**
- URL paraméterek (gclid, utm_*) továbbítása `denied` állapotban is
- Campaign tracking **nem vész el**

### 4. ads_data_redaction: true ✅ Ajánlott

**Mit csinál:**
- IP címek és személyes adatok törlése `denied` esetén
- Teljes GDPR compliance

---

## 📋 Érintett Fájlok

| Fájl | Változás |
|------|----------|
| `consent-mode-v2.php` | Verzió: 2.1.2 → 2.2.0 |
| `assets/js/consent-banner.js` | `setDefaultConsent()` bővítve |
| `COMPLIANCE-v2.2.0.md` | Új compliance dokumentáció |
| `RELEASE-v2.2.0.md` | Release notes |
| `CHANGELOG.md` | Verzió történet frissítve |

---

## 🚀 Telepítés (5 lépés)

1. **Töltsd le** az új fájlokat
2. **Cseréld ki** a régieket az MU plugins mappában
3. **Töröld** a böngésző cache-t (Ctrl+Shift+Del)
4. **Teszteld** GTM Preview módban
5. **Ellenőrizd** Google Tag Assistant-tel

**Nincs szükség** adatbázis migrációra!

---

## ✅ Compliance Checklist

- ✅ `ad_storage: denied` alapértelmezetten
- ✅ `analytics_storage: denied` alapértelmezetten
- ✅ `ad_user_data: denied` alapértelmezetten
- ✅ `ad_personalization: denied` alapértelmezetten
- ✅ `wait_for_update: 500` GTM várakozás
- ✅ `region: [...]` EU/EEA targeting
- ✅ `url_passthrough: true` Campaign tracking
- ✅ `ads_data_redaction: true` IP redukció

---

## 🔍 Gyors Teszt

### GTM Preview Mode

1. Nyisd meg GTM → Preview
2. Látogasd meg az oldalt
3. Nézd meg a `dataLayer` eseményeket
4. Ellenőrizd: `wait_for_update: 500` megjelenik?

### Google Tag Assistant

1. Telepítsd a Chrome extension-t
2. Nyisd meg az oldalt
3. Ellenőrizd a consent signals-t

---

## ⚠️ FONTOS

Ez a verzió **KRITIKUS compliance javításokat** tartalmaz. Nélküle:

- ❌ Plugin **NEM GDPR compliant**
- ❌ GTM **azonnal elindul** (adatvesztés)
- ❌ Campaign tracking **nem működik**

**Frissíts azonnal**, ha production környezetben használod!

---

## 📚 További Dokumentáció

- **COMPLIANCE-v2.2.0.md** - Részletes magyarázat
- **RELEASE-v2.2.0.md** - Teljes release notes
- **CHANGELOG.md** - Verzió történet

---

**Verzió:** 2.2.0  
**Dátum:** 2025-10-09  
**Kritikusság:** 🔴 MAGAS  
**Szerző:** You
