# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry describes changes between its release tag and the immediately preceding published release; changes made later on `master` are excluded.

## 1.0.4 - 2026-10-15

### Changed

- Added the plugin icon image at `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the plugin release identifier from `1.0.3` to `1.0.4` and its build number from `2026101500` to `2026101501`.

No functional changes to the video-tracking activity were identified between the published `1.0.3` and `1.0.4` tags.

[Compare 1.0.3...1.0.4](https://github.com/EduardoKrausME/moodle-mod_videotrackerprime/compare/1.0.3...1.0.4)

## 1.0.3 - 2026-10-15

### Fixed

- Added the missing protected `get_suffixed_name(string $fieldname): string` helper in `mod_form.php`, which builds completion-field names by appending the form's current suffix using `get_suffix()`. This addresses the undefined completion-field helper in the activity settings form.

### Changed

- Updated the GitHub Actions CI PostgreSQL service from version 16 to 17.
- Updated the MariaDB CI service from version 10 to 11.4, revising its startup environment variables and health-check configuration.
- Updated the plugin release identifier from `1.0.2` to `1.0.3` and its build number from `2026101200` to `2026101500`.

[Compare 1.0.2...1.0.3](https://github.com/EduardoKrausME/moodle-mod_videotrackerprime/compare/1.0.2...1.0.3)
