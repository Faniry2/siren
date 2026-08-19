# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`classes/` is the PHP class library for the "Geosirene/Siren" project (repo root: `/Users/admin/Sites/siren`, one level up). It has no autoloader, no namespaces, and no build system — every class lives in its own top-level file named after the class, and callers `require_once` the files they need directly (e.g. `require_once 'classes/apiInsee.php'`). There is no composer.json inside `classes/`; the project-wide `composer.json`/`vendor/` at the repo root only supplies a handful of libraries (Symfony DOM Crawler/CSS Selector, Swiftmailer, SendGrid) used by scripts outside this directory.

The overall system syncs French company/establishment data from INSEE's Sirene API into a local PostgreSQL database ("geosirene"), enriches it with geocoding (BAN/Bano, IRIS) and third-party lookups (Pages Jaunes, société scraping), and writes results back to Postgres/MySQL. Entry-point scripts that orchestrate this live in the repo root (e.g. `newMajGeosirene.php`, `geocodageIris.php`, `traitement20jours.php`), not in `classes/`.

## Running code

There is no test framework, linter, or build step configured (no PHPUnit in `vendor/`, no phpcs/php-cs-fixer config). Verification is done by running entry-point scripts directly with the PHP CLI, e.g.:

```
php ../geocodageIris.php
php ../newMajGeosirene.php
```

Files named `*Test.php` or `test*.php` (e.g. `connectPostgreSqlTest.php`, `testGeocodageIris.php` in the repo root) are ad hoc manual scripts, not automated unit tests — there's no assertion framework behind them.

## Configuration

- `config.php` defines DB and API credentials as global `define()` constants (`DB_NAME`, `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_PORT`, `DB_NAME_CUBE`, `CLE_INSEE_CONSO_*`, `CLE_INSEE_SECRET_*`, `ADR_BANO`, etc.). It is environment-specific (PROD/DEV/LOCAL blocks are toggled by commenting/uncommenting) and holds live secrets — treat it as sensitive, never commit real credentials in it, and don't print its contents.
- Most classes that touch the DB (`connectPostgreSql.php`, `connectPostgreSqlTest.php`, `BddBilan.php`) `require_once 'config.php'` themselves, so callers just need `config.php` present in the include path (repo root or `classes/`, depending on which script runs).

## Architecture

**Two parallel Postgres connection classes**, both PDO singletons using the same `DB_*` constants:
- `connectPostreSql` (in `connectPostgreSql.php`) — the main, actively used connection/query layer. Holds most of the domain logic: geocoding against IRIS/BAN, updating `poi.geosirene`, tracking last-processed dates, sending incident emails, etc. Class name is `connectPostreSql` (note the transposed "re"), not `connectPostgreSql`.
- `connectPostreSqlTest` (in `connectPostgreSqlTest.php`) — a parallel/experimental copy of the same connection class, used by manual test scripts.
- `ConnectGeocube` connects to a separate database (`DB_NAME_CUBE`) on the same host, with its own PDO singleton and `queryPDO`/`queryPDOResulset` helpers.
- `BddBilan` is a third, mostly-duplicate PDO singleton (same `DB_*` constants) for a different query surface (bilans).

When touching connection code, check whether the fix needs to be mirrored across `connectPostreSql`, `connectPostreSqlTest`, `ConnectGeocube`, and `BddBilan` — they don't share a base class.

**PDO `bindParam` requires a variable, not a literal.** `PDOStatement::bindParam()` binds by reference, so passing a literal (`true`, `'municipality'`) throws/fails silently on some PHP versions. The existing code assigns literals to local variables first (`$varTrue = true; $sql->bindParam(':geocentrecom', $varTrue, PDO::PARAM_BOOL);`) — follow this pattern for any new `bindParam` calls in this codebase (see `connectPostgreSql.php`).

**INSEE Sirene API flow**: `apiInsee` wraps the Sirene V3/3.11 REST endpoints (siret lookup, last-update cursor, daily diff). `apiBano` wraps the BAN/Bano geocoding service (`ADR_BANO`) plus a duplicate helper for hitting the INSEE "informations" endpoint. `geosireneTraitement` (and its parallel debug copy `geosireneTraitementDebug`) orchestrates a multi-step (`etape1`, `etape2`, ...) pipeline: pull INSEE data page by page via a cursor, stage into a tmp table, then merge into `poi.geosirene`. `requestInsee.php` currently only holds a reference SQL comment, no logic.

**Data model classes** (`etablissementInsee`, `periodesEtablissement`, `adresseEtablissement`, `adresse2Etablissement extends adresseEtablissement`) are plain property bags mirroring the INSEE Sirene JSON schema (French field names matching the API/DB columns, e.g. `libellevoieetablissement`, `datederniertraitementetablissement`) — no behavior, just typed containers used when parsing API responses.

**Third-party enrichment**: `ApiPJ` (Pages Jaunes lookups via an internal REST proxy at a LAN IP), `ApiSocCom` (société scraper), `webScrap`/`WebScrap` (generic cURL scraper with a randomized user agent), `UserAgent` (random UA generator backed by `agents/agent_list.json`).

**Misc utilities**: `Util` — static helpers (array debug dumps, CSV/TXT parsing; the `csvToArray` method depends on `PHPExcel`, which is not present in this repo's vendor tree — treat that method as currently broken/unused). `Vpn` — shells out to the `nordvpn` CLI via `proc_open` to connect/disconnect a VPN (used to rotate IPs for scraping). `CompileRegion` — text/region compiler used by report generation. `RemplirDenoGeoscar` (`remplirDenoGeoscar.php`) — batch job filling in `denomination`/`enseigne` data via `connectPostreSql`, paginated with an offset loop.

## Conventions to preserve

- No namespaces, no PSR-4 — new classes should follow the existing one-class-per-file, global-class-name style to stay loadable via plain `require_once`.
- Class/method naming is inconsistent (`connectPostreSql` vs `ConnectGeocube` vs `apiInsee`) — match the existing name of the file/class you're editing rather than "fixing" casing project-wide.
- Long-running batch scripts set `ini_set('memory_limit', '-1')` and `set_time_limit(0)` — preserve this for any new long-running import/geocoding entry points.
