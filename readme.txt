=== Papy3D Fact Viewer for Chuck365 ===
Contributors: papy3d
Tags: chuck norris, facts, humor, api, gutenberg
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 2.0.5
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Displays a unique and different Chuck Norris fact every day via the official Chuck365.fr API.

== Description ==

**Papy3D Fact Viewer for Chuck365** is a modern and lightweight plugin designed to add a touch of humor to your WordPress site. It automatically retrieves a unique Chuck Norris "fact" every day via the official Chuck365.fr API.  

The plugin is built with performance and security as top priorities:

*   **Optimal Performance**: It utilizes the native WordPress Transients API to cache the daily fact locally, ensuring your site remains fast and minimizes external server requests.
*   **Maximum Security**: Developed according to 2026 coding standards, the plugin includes strict data sanitization, XSS protection, and CSRF validation.  
*   **Full Customization**: A dedicated administration panel allows you to adjust colors, titles, and display options to perfectly match your theme.

**Note on External Services**: This plugin connects to `https://chuck365.fr/api.php` (API) to fetch daily facts. This is a mandatory external service for the plugin to function as intended.


== External services ==

This plugin connects to the Chuck365.fr API to retrieve a unique Chuck Norris fact each day. This external connection is required for the plugin to function.

**Service:** Chuck365.fr API (`https://chuck365.fr/api.php`)
**Purpose:** Fetching the daily Chuck Norris fact displayed in the widget.
**Data sent:** A standard HTTP GET request with a User-Agent header identifying the plugin version. No personal user data is collected or transmitted.
**When:** Once per day, when the daily cache (WordPress Transient) has expired. Regular site visitors never trigger a direct API call.
**Service provider:** Chuck365.fr, operated by Papy 3D Factory.
- Terms of service: https://chuck365.fr/terms.html
- Privacy policy: https://chuck365.fr/privacy.html
- API documentation : https://chuck365.fr/api-doc.html (fr/en)

= Donation button (optional) =

The "Support the project" tab contains an optional PayPal donation button.
Clicking it redirects to paypal.com. No data is sent automatically —
it is only triggered by a voluntary user click.
Terms: https://www.paypal.com/us/legalhub/useragreement-full
Privacy: https://www.paypal.com/us/legalhub/privacy-full

== Installation ==

*   **Download**: Obtain the plugin folder.  
*   **Upload**: Move the folder to the `/wp-content/plugins/` directory of your WordPress installation.  
*   **Activate**: Enable the plugin through the 'Plugins' menu in your WordPress dashboard.  
*   **Configure**: Visit the **"Chuck365"** settings page to customize your widget’s appearance.  
*   **Display**: You can showcase the facts using the **"Chuck365 Fact"** block within the Gutenberg editor or the `[papyfavi_fact]` shortcode.

== Changelog ==

= 2.0.5 =
* Fix: Gutenberg editor no longer ignores admin color settings when inserting a new block.
* Fix: Corrected a JavaScript error in `edit.js` where `papyfaviDefaults` was read incorrectly, causing colors to fall back to hardcoded values instead of admin settings.
* Fix: Removed hardcoded `default` values from `block.json` attributes (`borderColor`, `bgColor`, `textColor`, `title`) to allow proper fallback to admin settings.
* Improvement: Admin color settings are now injected as Gutenberg block defaults via `enqueue_block_editor_assets` hook, ensuring new blocks always reflect the configured style.
* Maintenance: Incremented version to 2.0.5 and cleaned up the readme.txt file.

= 2.0.4 =
* Security: Added `rest_sanitize_boolean` to the `papyfavi_show_copyright` setting for better data validation.
* Maintenance: Incremented version to 2.0.4 and cleaned up the readme.txt file.
* Updated: Language files and Gutenberg color handling.

= 2.0.1 =
* Security improvements.
* Fixed JSON error handling using JsonException.

== Frequently Asked Questions ==

= Does this plugin affect site speed? =
No. By using the WordPress Transients API, the fact is fetched once a day and stored in your database, meaning no external API call is made for regular visitors.

= Can I hide the copyright link? =
Yes, you can toggle the copyright display in the plugin settings menu.