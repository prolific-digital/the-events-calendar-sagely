=== The Events Calendar - Sagely Integration ===
Contributors: prolificdigital
Tags: events, the events calendar, sagely, import
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.4
Requires Plugins: the-events-calendar
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Imports Sagely community events into The Events Calendar on a schedule.

== Description ==

Pulls the next 60 days of events for one Sagely community from the Sagely API into The Events Calendar, on a configurable interval (in hours) or on demand with **Sync Now**.

* Settings live under **Events > Sagely API**: the community's Sagely API key and the sync frequency.
* Venues are matched by name or created. Categories and tags are created as needed.
* Sagely provides no end time, so events default to a 1-hour duration.
* Events already imported are updated in place (matched on the `_sagely_event_id` post meta).

See the [support documentation](https://prolificdigital.notion.site/The-Events-Calendar-Sagely-Addon-ce2eb70042734bcc9edb0dc8c4c6ec54).

== Installation ==

1. Install and activate The Events Calendar.
2. Upload the release zip from **Plugins > Add New > Upload Plugin** and activate it.
3. Enter the Sagely API key and sync frequency under **Events > Sagely API**.
4. To receive automatic updates, add your license key to `wp-config.php`:
   `define( 'PROLIFIC_LICENSE_KEY_TEC_SAGELY', 'your-key' );`

== Changelog ==

= 1.1.0 =
* Updates come from the Prolific Digital license server. Define `PROLIFIC_LICENSE_KEY_TEC_SAGELY` to receive them.
* Main file renamed to `tec-sagely.php`; re-activate the plugin after updating.
* Text domain changed to `tec-sagely`. License is now GPL-2.0-or-later.
* Requires WordPress 6.5+, PHP 8.4+ and The Events Calendar (`Requires Plugins`).
* Uninstalling removes the plugin settings and scheduled sync; imported events are kept.

= 1.0.0 =
* Initial release.
