=== RationalRedirects ===
Contributors: rationalwp
Tags: redirect, 301 redirect, 302 redirect, url redirect, regex redirect
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple, fast URL redirects with regex support and automatic slug change tracking.

== Description ==

RationalRedirects is a lightweight WordPress plugin for managing URL redirects. It supports exact path matching, regular expressions with capture groups, and automatically creates redirects when post/page slugs change.

**Features:**

* **Simple Redirects** - Redirect one URL to another with 301, 302, 307, or 410 status codes
* **Regex Support** - Use regular expressions with capture groups for flexible pattern matching
* **Auto Slug Redirects** - Automatically create redirects when you change a post or page URL slug
* **Hit Counter** - Track how many times each redirect is triggered
* **Fast Performance** - Database-indexed lookups and transient caching for regex patterns
* **Import System** - Import redirects from Yoast SEO Premium, Rank Math, All in One SEO, SEOPress, and Redirection

== Installation ==

1. Upload the `rationalredirects` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to RationalWP > Redirects to manage your redirects

== Frequently Asked Questions ==

= How do I create a simple redirect? =

Go to RationalWP > Redirects, enter the source path (e.g., `/old-page`), the destination URL, select a status code, and click "Add Redirect".

= How do I use regex redirects? =

Check the "Is Regex" box when creating a redirect. Use capture groups in your pattern and reference them in the destination with `$1`, `$2`, etc.

Example: Pattern `/blog/(.*)` with destination `/news/$1` will redirect `/blog/my-post` to `/news/my-post`.

= What status codes are available? =

* **301** - Permanent redirect (recommended for SEO)
* **302** - Temporary redirect
* **307** - Temporary redirect (preserves request method)
* **410** - Gone (content permanently removed)

= How do automatic slug redirects work? =

When enabled in settings, the plugin monitors changes to post and page URLs. When you change a slug, it automatically creates a 301 redirect from the old URL to the new one.

= Can I import redirects from other plugins? =

Yes! The Import tab supports importing from Yoast SEO Premium, Rank Math, All in One SEO, SEOPress, and Redirection.

== External Services ==

This plugin connects to the following external services:

= RationalWP Plugin Directory =

This plugin fetches a list of available RationalWP plugins from [rationalwp.com](https://rationalwp.com/) to display in the WordPress admin menu. Only the menu file version number is sent as a cache-busting query parameter. No user data is transmitted. The response is cached locally for 24 hours.

* Service URL: [https://rationalwp.com/plugins.json](https://rationalwp.com/plugins.json)
* Terms of Service: [https://rationalwp.com/terms/](https://rationalwp.com/terms/)
* Privacy Policy: [https://rationalwp.com/privacy/](https://rationalwp.com/privacy/)

== Changelog ==

= 1.0.0 =
* Initial release
* Simple and regex redirect support
* Automatic slug change tracking
* Hit counter for redirects
* Import system for popular SEO plugins

== Upgrade Notice ==

= 1.0.0 =
Initial release.
