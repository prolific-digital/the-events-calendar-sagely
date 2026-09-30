# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-09-29

### Security
- The **Events > Sagely API** settings form and the **Sync Now** button now check a nonce and the `manage_options` capability. Before, a page on another site could change the API key or sync frequency, or start a sync, through a logged-in administrator's browser (CSRF). Input is unslashed and sanitized (the frequency must be a whole number of hours, 1 or more), and the help text is escaped.

### Fixed
- The scheduled import never ran. The activation and deactivation hooks were registered from the class file on `plugins_loaded`, so they never fired and the `sagely_event_sync` cron event was never created. They are now registered from the main plugin file. Sites that are already active create the missing event on the next page load, once an API key is saved, and deactivating the plugin clears it.

### Added
- Automatic updates from the Prolific Digital license server (`api.prolificdigital.io`). Set `PROLIFIC_LICENSE_KEY_TEC_SAGELY` in `wp-config.php` (or use the `prolific_license_key` filter). plugin-update-checker ^5.7 is installed with Composer.
- `Requires Plugins: the-events-calendar`, so WordPress enforces the dependency.
- `TEC_SAGELY_VERSION` constant; translations load from `languages/`.
- `uninstall.php` removes the API key and sync-frequency options and the scheduled `sagely_event_sync` cron event. Imported events, venues, terms and `_sagely_event_id` meta are kept.
- `readme.txt`, this changelog, coding-standards config and CI/release workflows.

### Changed
- Main plugin file renamed from `the-events-calendar-sagely.php` to `tec-sagely.php`. Re-activate the plugin after updating.
- Text domain changed from `the-events-calendar-sagely` to `tec-sagely`.
- License changed from GPL-3.0 to GPL-2.0-or-later (`LICENSE` now holds the GPL-2.0 text).
- Requires WordPress 6.5+ and PHP 8.4+.
- The namespaced `ProlificDigital\SagelyIntegration\init()` bootstrap moved to `includes/bootstrap.php` so the main file can open with the ABSPATH guard. Behavior is unchanged.

## [1.0.0] - 2024-08-10

### Added
- Initial release: scheduled and manual import of Sagely events into The Events Calendar, with venue, category and tag mapping, under **Events > Sagely API**.

[Unreleased]: https://github.com/prolific-digital/the-events-calendar-sagely/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/prolific-digital/the-events-calendar-sagely/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/prolific-digital/the-events-calendar-sagely/releases/tag/v1.0.0
