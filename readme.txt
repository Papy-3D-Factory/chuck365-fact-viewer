=== Papy3D Fact Viewer for Chuck365 ===
Contributors: papy3d
Tags: chuck norris, facts, humor, api, gutenberg
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 2.0.4
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

== Installation ==

*   **Download**: Obtain the plugin folder.  
*   **Upload**: Move the folder to the `/wp-content/plugins/` directory of your WordPress installation.  
*   **Activate**: Enable the plugin through the 'Plugins' menu in your WordPress dashboard.  
*   **Configure**: Visit the **"Chuck365"** settings page to customize your widget’s appearance.  
*   **Display**: You can showcase the facts using the **"Chuck365 Fact"** block within the Gutenberg editor or the `[chuck_fact]` shortcode.

== Changelog ==

= 2.0.4 =

* Plugin name changed to Papy3D Fact Viewer for Chuck365
* Security: Added Security Sanitization and Output Escaping (Late Escaping)
* Removal of Development Functions
* Standardized English description and cleaned up (removed residual command).
* Updated: Language files and Gutenberg color handling.
* Header and Documentation: Plugin headers and the readme.txt file have been updated
* Maintenance: Incremented version to 2.0.4 and cleaned up the readme.txt file.

= 2.0.1 =
* Security improvements.
* Fixed JSON error handling using JsonException.

== Frequently Asked Questions ==

= Does this plugin affect site speed? =
No. By using the WordPress Transients API, the fact is fetched once a day and stored in your database, meaning no external API call is made for regular visitors.

= Can I hide the copyright link? =
Yes, you can toggle the copyright display in the plugin settings menu.