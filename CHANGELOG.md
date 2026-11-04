# Changelog

All notable changes to this project are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-11-04

First public release.

### Added

- Public menu for phones: cover, logo, open/closed status, contact buttons (WhatsApp, directions, Instagram), notice of the day, search, category tabs, recommended dishes and a detail sheet for each dish.
- Admin panel in English and Spanish: dishes with photo, sizes and labels; categories; one-tap availability (sold out); restaurant details; account.
- Opening hours with two shifts per day, including shifts that end after midnight.
- Optional second currency calculated from an exchange rate, with a quick rate update on the dashboard.
- Brand color with automatic contrast, and an optional "Made with menu-qr" credit.
- QR code: PNG and SVG downloads and printable table cards (one large card or four per page).
- Privacy-friendly visit counter: visits per day, QR vs link, most viewed dishes. No cookies and no personal data.
- ZIP backup and restore, with strict validation of the uploaded file.
- Photos converted to WebP automatically, with EXIF rotation fixed.
- Docker deployment with no manual steps: database secrets generated on the first start, automatic migrations, setup code in the logs, password recovery with `MENUQR_RECOVERY=1`.
- Two Compose files: `docker-compose.yml` (one port) and `docker-compose.traefik.yml` (HTTPS with Hostinger's Traefik template).

[1.0.0]: https://github.com/reinalddo/menu-qr/releases/tag/v1.0.0
