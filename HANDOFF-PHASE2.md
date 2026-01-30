# Phase 2 Handoff: Add Import System to RationalRedirects

## Context

Phase 1 is complete. RationalRedirects is a standalone WordPress plugin that handles URL redirects. It works independently of RationalSEO.

**Full plan location:** `/Users/jhixon/.claude-personal/plans/spicy-growing-eich.md`

## Phase 1 Completed

- Created standalone RationalRedirects plugin
- Core redirect functionality: add, delete, regex support, auto-redirect on slug change
- Admin UI with redirect manager table
- Database table `{prefix}_rationalredirects_redirects`
- Settings page with `redirect_auto_slug` toggle

## Goal

Add an import system to RationalRedirects that can import redirects from:
1. Yoast SEO Premium
2. Rank Math
3. AIOSEO
4. SEOPress
5. Redirection plugin

## Files to Create

```
includes/import/
├── class-import-manager.php       # Importer registry
├── class-import-admin.php         # Import tab UI
├── interface-importer.php         # Importer interface
├── class-import-result.php        # Import result object
└── importers/
    ├── class-yoast-importer.php
    ├── class-rankmath-importer.php
    ├── class-aioseo-importer.php
    ├── class-seopress-importer.php
    └── class-redirection-importer.php
```

## Reference Implementation

RationalSEO has a working import system. Use these as reference (but adapt for redirects-only):

| RationalSEO File | Purpose |
|------------------|---------|
| `includes/import/class-import-manager.php` | Registry pattern |
| `includes/import/class-import-admin.php` | Admin UI for import tab |
| `includes/import/interface-importer.php` | Importer contract |
| `includes/import/class-import-result.php` | Result tracking |
| `includes/import/importers/class-redirection-importer.php` | **Most relevant** - redirects-only importer |
| `includes/import/importers/class-yoast-importer.php` | Look at `get_redirects_count()` and `import_redirects()` methods |

## Implementation Notes

1. **Importer Interface:** Should have methods like:
   - `get_slug()` - Unique identifier
   - `get_name()` - Display name
   - `get_description()` - Short description
   - `is_available()` - Check if source plugin data exists
   - `get_redirect_count()` - Number of redirects available to import
   - `preview()` - Return array of redirects that would be imported
   - `import()` - Perform the import, return result object

2. **Admin Tab:** Add an "Import" tab to the existing settings page that:
   - Lists available import sources
   - Shows count of redirects available from each
   - Preview button to see what will be imported
   - Import button with progress/result feedback

3. **Duplicate Handling:** Skip redirects that already exist (by `url_from`)

4. **AJAX Actions:** Create AJAX handlers for:
   - `rationalredirects_preview_import` - Preview redirects from a source
   - `rationalredirects_run_import` - Execute the import

## Source Plugin Data Locations

| Plugin | Where Redirects Are Stored |
|--------|---------------------------|
| Yoast Premium | Options: `wpseo-premium-redirects-base`, `wpseo_redirect` |
| Rank Math | Table: `{prefix}_rank_math_redirections` |
| AIOSEO | Table: `{prefix}_aioseo_redirects` |
| SEOPress | Options: `seopress_pro_redirects` |
| Redirection | Tables: `{prefix}_redirection_items`, `{prefix}_redirection_groups` |

## Key Class Renames

Follow the established naming pattern:
- `RationalRedirects_Import_Manager`
- `RationalRedirects_Import_Admin`
- `RationalRedirects_Importer_Interface`
- `RationalRedirects_Import_Result`
- `RationalRedirects_Yoast_Redirects_Importer` (etc.)

## Files to Modify

- `rationalredirects.php` - Add require_once for import system files
- `includes/class-rationalredirects.php` - Initialize import manager and admin
- `includes/class-admin.php` - Add Import tab to settings page

## Verification Checklist

- [ ] Install Yoast Premium with some redirects
- [ ] Import tab appears in RationalRedirects settings
- [ ] Preview shows correct redirect data
- [ ] Import creates redirects in `rationalredirects_redirects` table
- [ ] Duplicates are correctly skipped
- [ ] Each importer only appears when source data exists
