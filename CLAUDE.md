# RationalRedirects

Lightweight WordPress plugin for URL redirects with regex support and automatic slug change tracking.

## Local Development Environment

- **URL:** https://development.local/wp-admin/
- **Username:** claude
- **Password:** &FKHRV4znkXt*SWn5k%IYTmN
- All `npm`/`npx` commands: prefix with `NODE_ENV=development`

## Key Classes

| Class | File | Responsibility |
|-------|------|----------------|
| `RationalRedirects` | `class-rationalredirects.php` | Singleton, hooks, initialization |
| `RationalRedirects_Redirects` | `class-redirects.php` | Redirect matching, execution, CRUD, auto-redirects |
| `RationalRedirects_Settings` | `class-settings.php` | Settings storage/retrieval |
| `RationalRedirects_Admin` | `class-admin.php` | Admin UI, settings page, redirect manager |
| `RationalRedirects_Activator` | `class-activator.php` | Activation, deactivation, DB table creation |

## Database Schema

**Options:** `rationalredirects_settings` (serialized array)

**Redirects Table:** `{prefix}_rationalredirects_redirects`
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `url_from` | VARCHAR(255) | Source path (indexed) |
| `url_to` | TEXT | Destination URL |
| `status_code` | INT | 301, 302, 307, or 410 |
| `is_regex` | TINYINT | 1 = regex pattern |
| `count` | INT | Hit counter |

## Settings Defaults

```php
array(
    'redirect_auto_slug' => true,
)
```

## Redirect Execution Flow

```
Request → template_redirect (priority 1) → maybe_redirect()
  ↓
1. Exact match query (indexed, fast)
  ↓
2. Regex match (transient-cached for 1 hour)
  ↓
Increment hit counter → wp_safe_redirect() → exit
```

## AJAX Actions

| Action | Handler | Purpose |
|--------|---------|---------|
| `rationalredirects_add_redirect` | `ajax_add_redirect()` | Add new redirect |
| `rationalredirects_delete_redirect` | `ajax_delete_redirect()` | Delete redirect by ID |

**Nonce:** `rationalredirects_nonce`

## Transients

| Key | TTL | Purpose |
|-----|-----|---------|
| `rationalredirects_regex_redirects` | 1 hour | Cached regex redirects |

## Admin Menu

Registered under shared RationalWP parent menu:
- Parent: `rationalwp`
- Submenu: `rationalredirects`
- Page hook: `rationalwp_page_rationalredirects`
