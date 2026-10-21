# Release útmutató – automatikus frissítés GitHubról

A plugin normál WordPress pluginként (`wp-content/plugins/wp-consent-mode-v2-mu-plugin/`) települ.
A frissítést a [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) hozza a
GitHub Release-ekből, így megjelenik a szokásos frissítésértesítés, az "Update now" gomb és az
**automatikus frissítés** kapcsoló a Bővítmények oldalon.

## Új kiadás

1. Emeld meg a verziót **két helyen** a `consent-mode-v2.php`-ban (a header `Version:` és a `CMV2_VERSION` konstans).
2. Commit + push a `main`-re.
3. Tag és push:
   ```bash
   git tag v2.9.0
   git push origin v2.9.0
   ```
4. A `.github/workflows/release.yml` ekkor automatikusan:
   - ellenőrzi, hogy a tag, a header és a `CMV2_VERSION` megegyezik (különben leáll),
   - `composer install --no-dev`-et futtat,
   - elkészíti a `wp-consent-mode-v2-mu-plugin.zip`-et (a zipen belül a mappa neve a slug),
   - létrehozza a GitHub Release-t a zippel és generált változásnaplóval.
5. A WordPress-oldalak a következő ellenőrzéskor (12 óra, vagy a plugin sorában a "Check for updates" link) látják a frissítést.

## Telepítés / átállás a régi MU példányról

1. Töröld a `wp-content/mu-plugins/consent-mode-v2.php` fájlt (és a `includes/`, `assets/`, `vendor/` mappáit, ha oda másoltad).
2. Töltsd fel a Release zip-jét: Bővítmények → Új hozzáadása → Bővítmény feltöltése, majd aktiváld.
3. A beállítások az adatbázisban vannak (`cmv2_settings`), így megmaradnak.
4. A Bővítmények oldalon kapcsold be az "Automatikus frissítések engedélyezése" opciót.

Ha a régi MU példány még aktív, az új plugin nem tölt be, és admin figyelmeztetést mutat.

## Privát repó

```php
// wp-config.php
define('CMV2_GITHUB_TOKEN', 'ghp_...'); // csak olvasási jogú (contents:read) token
```

## Hibaelhárítás

- **Nem jelenik meg a frissítés:** Van `vX.Y.Z` tag és publikált Release a zip assettel? A Release verziója nagyobb a telepítettnél?
- **Gyorsítótár:** `wp transient delete update_plugins` vagy a "Check for updates" link a plugin sorában.
- **A frissítés után más nevű mappa lesz:** a Release assetnek `wp-consent-mode-v2-mu-plugin*.zip` nevűnek kell lennie, a workflow ezt adja.
- **Debug:** `WP_DEBUG` + `WP_DEBUG_LOG`, majd `wp-content/debug.log`.
