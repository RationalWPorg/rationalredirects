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
| `RationalRedirects_Admin` | `class-admin.php` | Admin UI, tabbed settings page, redirect manager |
| `RationalRedirects_Activator` | `class-activator.php` | Activation, deactivation, DB table creation |
| `RationalRedirects_Import_Manager` | `import/class-import-manager.php` | Importer registry and orchestration |
| `RationalRedirects_Import_Admin` | `import/class-import-admin.php` | Import tab UI and AJAX handlers |
| `RationalRedirects_Import_Result` | `import/class-import-result.php` | Import operation result data object |

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
| `rationalredirects_get_importers` | `ajax_get_importers()` | List available importers |
| `rationalredirects_preview_import` | `ajax_preview_import()` | Preview import data |
| `rationalredirects_run_import` | `ajax_run_import()` | Execute import |

**Nonces:**
- `rationalredirects_nonce` - Redirect CRUD operations
- `rationalredirects_import` - Import operations

## Transients

| Key | TTL | Purpose |
|-----|-----|---------|
| `rationalredirects_regex_redirects` | 1 hour | Cached regex redirects |

## Admin Menu

Registered under shared RationalWP parent menu:
- Parent: `rationalwp`
- Submenu: `rationalredirects`
- Page hook: `rationalwp_page_rationalredirects`
- Tabs: Redirects (default) | Settings | Import

## Import System

**Importer Interface:** `RationalRedirects_Importer_Interface`
- `get_slug()`, `get_name()`, `get_description()`
- `is_available()`, `get_redirect_count()`
- `preview()`, `import($options)`

**Built-in Importers:**

| Importer | Slug | Source |
|----------|------|--------|
| Yoast SEO Premium | `yoast` | Options: `wpseo-premium-redirects-base`, `wpseo_redirect` |
| Rank Math | `rankmath` | Table: `{prefix}_rank_math_redirections` |
| All in One SEO | `aioseo` | Table: `{prefix}_aioseo_redirects` |
| SEOPress | `seopress` | Post meta: `_seopress_redirections_*` |
| Redirection | `redirection` | Table: `{prefix}_redirection_items` |

**Registration Hook:** `rationalredirects_register_importers`
```php
add_action( 'rationalredirects_register_importers', function( $manager ) {
    $manager->register( new My_Custom_Importer( $manager->get_redirects() ) );
} );
```
