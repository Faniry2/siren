# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A PHP codebase ("Geosirene/Siren") that syncs French company/establishment data from INSEE's Sirene API into a local PostgreSQL database (`poi.geosirene`), enriches it with geocoding (BAN/Bano, IRIS) and third-party lookups (Pages Jaunes, société scraping), and serves/reports on the result. There is no framework — it's plain procedural/OOP PHP scripts, each a standalone entry point invoked via CLI (cron) or browser.

- `classes/` — the class library (connections, API wrappers, data model classes, utilities). It has its own `classes/CLAUDE.md` with detailed architecture notes for that directory — read it before working on anything under `classes/`.
- Repo root — entry-point scripts that orchestrate the classes (imports, geocoding runs, mailers, ad hoc reports/tools) plus `index.php`/`indexInsee.php`/`indexBano.php` as simple link-menu HTML pages into those scripts.
- `nbproject/` — leftover NetBeans IDE project metadata, not relevant to development.

## Running code

There is no test framework, linter, build step, or package.json/phpunit config. Verification is done by running entry-point scripts directly with the PHP CLI, e.g.:

```
php newMajGeosirene.php
php geocodageIris.php
php traitement20jours.php
```

Files named `*Test.php` / `test*.php` (`test.php`, `test2.php`, `testGeocodageIris.php`, `testUpdateBano.php`, `test_api_pj.php`, `classes/connectPostgreSqlTest.php`) are ad hoc manual scripts, not automated unit tests — there's no assertion framework behind them, they just exercise code paths and print/log output.

Many scripts are also reachable as web pages (linked from `index.php`, `indexInsee.php`, `indexBano.php`) rather than run via CLI.

## Configuration

- `classes/config.php` (gitignored, not present by default — see `classes/.gitignore`) defines DB and API credentials as global `define()` constants (`DB_NAME`, `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_PORT`, `DB_NAME_CUBE`, `CLE_INSEE_CONSO_*`, `CLE_INSEE_SECRET_*`, `ADR_BANO`, etc.). It's environment-specific (PROD/DEV/LOCAL blocks toggled by commenting/uncommenting) — treat it as sensitive, never print its contents or commit real credentials.
- `gettoken.php` at the repo root has an INSEE OAuth `client_id`/`client_secret` hardcoded inline (not pulled from `config.php`) — treat that file as sensitive too when reading/quoting it back.
- Most DB-touching classes `require_once 'config.php'` themselves, so scripts just need `classes/config.php` present.

## Dependencies

`composer.json`/`vendor/` at the repo root supply: Symfony DOM Crawler + CSS Selector (used by scraping code, e.g. `classes/webScrap.php`), Swiftmailer and `symfony/sendgrid-mailer` + `priyolahiri/sendgrid-php` (used by mailer scripts like `mailFinAnnee.php`, `sendMailHebdo.php`).

There's also a `vendor_1/` at the repo root — a partial duplicate composer install (subset of the same packages, missing `doctrine`, `egulias`, `priyolahiri`, `psr`, `swiftmailer`). Nothing outside `vendor_1/` itself references `vendor_1/autoload.php`; treat it as stale/unused rather than a second dependency set to keep in sync.

## Architecture

See `classes/CLAUDE.md` for the class library's internals (the two parallel Postgres connection classes, the INSEE Sirene API flow, `bindParam` gotcha, data model classes, third-party enrichment classes). At the root-script level, the main flows are:

- **Daily/periodic sync**: `newMajGeosirene.php` (and its variants `newMajGeosireneTest.php`, `newMajGeosireneScrapOtherData.php`) drive `geosireneTraitement`/`apiInsee`/`apiBano` to pull INSEE diffs and geocode new/changed establishments. `traitement20jours.php` runs the same kind of pipeline over a rolling 20-day window. These scripts commonly hardcode the date(s) to process in a `$sDateFormats` array rather than always computing "yesterday" — check for a hand-edited date list before assuming a script processes the current date automatically.
- **Repair/backfill**: `repareMajGeosirene.php`, `repareStockManquantsInseeByDate.php`, `majGeoscarABlanc.php`, `cleanStockFerme.php` fix up specific gaps in previously-synced data.
- **Geocoding**: `geocodageIris.php` / `testGeocodageIris.php` (IRIS geocoding via `connectPostreSql::geocodeIrisAndUpdateTableGeosirenHaveToIris`), `geocodeSock.php`, `geocodeBanoInternetGeosirene.php` (BAN-based geocoding).
- **Third-party lookups**: `SearchPj.php`/`pj.php`/`majPJProd.php` (Pages Jaunes), `118218.php`, `test_api_pj.php`.
- **Reporting/stats/mail**: `sendMailHebdo.php`, `mailFinAnnee.php`, `getTelByNaf.php`, `recupNomEnseigneUL.php` / `recupNomEnseigneULTotal.php`; `index.php` links to several stats pages (`statscreations20jours.php`, `statsHisto1jour.php`, etc.) that aren't present in the repo — links exist but the target files don't.
- Static reference data lives in root-level JSON/CSV files: `ape.json`/`ape20j.json`/`apestat.json` (APE/NAF code tables) and `comptables.csv`/`_comptables.csv`.

## Conventions to preserve

- No namespaces, no PSR-4, no autoloading beyond Composer's vendor libs — scripts `require`/`require_once`/`include` the exact class files they need (paths are relative to the script's own location, so root scripts use `classes/foo.php` while scripts inside `classes/` use bare filenames).
- Long-running batch/import scripts set `ini_set('memory_limit', '-1')` and raise/remove the execution time limit — preserve this for any new long-running import/geocoding entry points.
- `error_reporting(E_ALL & ~E_NOTICE)` is set at the top of many entry scripts; match existing per-file style rather than normalizing it project-wide.
