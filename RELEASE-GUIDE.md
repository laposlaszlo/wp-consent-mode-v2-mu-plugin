# 🚀 Release Guide - Automatikus Plugin Frissítések

Ez az útmutató leírja, hogyan készíts új release-t a pluginból, hogy az automatikus frissítés működjön.

## 📋 Előfeltételek

- Git telepítve
- Composer telepítve
- GitHub repository létrehozva a pluginhoz
- GitHub account hozzáférés a repóhoz

---

## 🔄 Release Folyamat

### 1️⃣ Verzió frissítése

Frissítsd a verzió számot a `consent-mode-v2.php` fájlban:

```php
/**
 * Version: 2.4.0  ← Frissítsd ezt
 */

define('CMV2_VERSION', '2.4.0');  ← És ezt
```

### 2️⃣ Composer dependencies telepítése

**FONTOS:** A GitHub release-hez kell a `vendor/` mappa!

```bash
cd /path/to/wp-consent-mode-v2-mu-plugin
composer install --no-dev --optimize-autoloader
```

Ez létrehozza/frissíti a `vendor/` mappát a szükséges library-kkel.

### 3️⃣ Változások commitolása

```bash
git add .
git commit -m "Release v2.4.0 - Added automatic update checker"
git push origin main
```

### 4️⃣ GitHub Release létrehozása

**Opció A: GitHub UI használata**

1. Menj a GitHub repódra
2. Kattints a **"Releases"** menüpontra (jobb oldali sidebar)
3. Kattints a **"Draft a new release"** gombra
4. Töltsd ki:
   - **Tag version:** `v2.4.0` (fontos a "v" előtag!)
   - **Release title:** `Version 2.4.0 - Automatic Updates`
   - **Description:** Írd le a változásokat:
     ```markdown
     ## 🎉 Új funkciók
     - ✅ Automatikus plugin frissítés GitHub-ról
     - ✅ Popup pozíció választás (közép, lent balra, lent jobbra)
     - ✅ Duplikált cm_default esemény javítása
     
     ## 🐛 Javítások
     - Duplikált consent esemény eltávolítása JavaScript-ből
     
     ## 📦 Telepítés
     1. Töltsd le a zip fájlt
     2. WordPress Admin → Bővítmények → Új hozzáadása → Feltöltés
     3. Aktiválás
     ```
5. **FONTOS:** Pipáld be a **"Set as the latest release"** checkboxot
6. Kattints a **"Publish release"** gombra

**Opció B: GitHub CLI használata**

```bash
# Tag létrehozása
git tag -a v2.4.0 -m "Release v2.4.0"
git push origin v2.4.0

# Release létrehozása (ha van gh CLI)
gh release create v2.4.0 \
  --title "Version 2.4.0 - Automatic Updates" \
  --notes "Automatikus frissítés és új funkciók"
```

### 5️⃣ Tesztelés WordPress-ben

Menj a WordPress admin → **Bővítmények** oldalra.

Ha minden jó, ekkor megjelenik:
- 🔔 **"Frissítés elérhető"** értesítés a plugin mellett
- 📦 **"Frissítés most"** gomb

Kattints a **"Frissítés most"** gombra, és a plugin automatikusan frissül!

---

## 🔍 Mit csinál az Update Checker?

A plugin automatikusan:
1. ✅ 12 óránként ellenőrzi a GitHub release-eket
2. ✅ Összehasonlítja a telepített verzió számot a GitHub-on lévővel
3. ✅ Ha újabb verzió elérhető, értesítést jelenít meg
4. ✅ Lehetővé teszi az egy kattintásos frissítést

### Működés részletei

**GitHub API hívás:**
```
GET https://api.github.com/repos/laposlaszlo/wp-consent-mode-v2-mu-plugin/releases/latest
```

**Válasz:**
```json
{
  "tag_name": "v2.4.0",
  "zipball_url": "https://github.com/.../archive/v2.4.0.zip",
  "name": "Version 2.4.0",
  "body": "Release notes..."
}
```

A Plugin Update Checker:
- Összehasonlítja a `tag_name` verzió számot a `CMV2_VERSION` konstanssal
- Ha újabb → megjelenik a frissítés WordPress-ben
- Letölti és kicsomagolja a zipball_url-t frissítéskor

---

## 📝 Changelog vezetése

### Javasolt formátum:

```markdown
## [2.4.0] - 2025-02-11

### Hozzáadva
- Automatikus plugin frissítés GitHub-ról
- Popup pozíció választás (3 opció)

### Javítva
- Duplikált cm_default esemény JavaScript-ben
- OLD backup fájlok eltávolítva

### Módosítva
- Verzió 2.3.0 → 2.4.0
- Admin beállítások bővítése
```

Ezt add hozzá a `README.md` vagy `CHANGELOG.md` fájlhoz!

---

## 🐛 Hibaelhárítás

### A frissítés nem jelenik meg

**1. Ellenőrizd a GitHub release-t:**
- Van `vX.X.X` formátumú tag?
- "Latest release" van bejelölve?
- Publikus a repó? (vagy token van beállítva privát repóhoz)

**2. Cache ürítés WordPress-ben:**
```php
// WordPress admin → Tools → Site Health → Info → Transients
// Vagy WP-CLI:
wp transient delete-all
```

**3. Debug mód engedélyezése:**
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Nézd meg: wp-content/debug.log
```

**4. Ellenőrizd a vendor/ mappát:**
```bash
ls -la vendor/yahnis-elsts/plugin-update-checker/
# Ha nincs → composer install --no-dev
```

### Private repository esetén

Ha a GitHub repo privát:

```php
// consent-mode-v2.php fájlban add hozzá:
$updateChecker->setAuthentication('your-github-personal-access-token');
```

GitHub Personal Access Token létrehozása:
1. GitHub → Settings → Developer settings → Personal access tokens
2. Generate new token (classic)
3. Scopes: `repo` (teljes jogosultság)
4. Copy token és add hozzá a kódhoz

---

## 📦 Plugin ZIP készítése kézi telepítéshez

Ha valaki kézzel szeretné telepíteni (nem frissítés):

```bash
# 1. Vendor dependencies telepítése
composer install --no-dev --optimize-autoloader

# 2. ZIP létrehozása (ne vedd bele a git mappát!)
zip -r wp-consent-mode-v2-mu-plugin.zip . \
  -x "*.git*" \
  -x "*.DS_Store" \
  -x "node_modules/*" \
  -x "*.log"

# 3. A ZIP-et fel lehet tölteni WordPress-be
```

---

## ✅ Checklist új release előtt

- [ ] Verzió szám frissítve a plugin header-ben
- [ ] Verzió szám frissítve a `CMV2_VERSION` konstansban
- [ ] `composer install --no-dev` futtatva
- [ ] `vendor/` mappa létezik és tartalmazza a plugin-update-checker-t
- [ ] Változások dokumentálva (README vagy CHANGELOG)
- [ ] Git commit és push
- [ ] GitHub release létrehozva `vX.X.X` taggel
- [ ] Release "Latest" jelzéssel
- [ ] Tesztelve WordPress-ben

---

## 🎯 Következő lépések

1. **Push a GitHub-ra:**
   ```bash
   git push origin main
   ```

2. **Release létrehozása GitHub-on** (lásd fent)

3. **Tesztelés:** Menj egy teszt WordPress oldalra és nézd meg megjelenik-e a frissítés

4. **Produkció:** Ha működik, add ki az éles verziónak

---

## 📚 További információk

- **Plugin Update Checker dokumentáció:**  
  https://github.com/YahnisElsts/plugin-update-checker

- **GitHub Releases dokumentáció:**  
  https://docs.github.com/en/repositories/releasing-projects-on-github

- **WordPress Plugin API:**  
  https://developer.wordpress.org/plugins/plugin-basics/

---

**Verzió:** 2.4.0  
**Utolsó frissítés:** 2025-02-11  
**Készítette:** Lapos László
