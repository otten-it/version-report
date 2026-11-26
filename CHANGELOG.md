# Changelog

## 1.3.0 (2026-11-26)

- A configured token under 32 characters disables the endpoint (404), as an empty one does: a
  short token typed by hand is too easy to guess. Check `VERSION_REPORT_TOKEN`'s length before
  upgrading.

## 1.2.0 (2026-11-23)

- `npm`: the npm packages from `package-lock.json` (name, version, dev flag), `{"lockfile": false}`
  when there is a `package.json` without a readable lock file, or `null` when there is no
  `package.json`. An addition: `report_version` stays 1.

## 1.1.0 (2026-11-22)

- `os`: the operating system's id and version, or `null`. An addition: `report_version` stays 1.

## 1.0.0 (2026-11-20)

- `GET /version-report`: Laravel, PHP and production package versions, behind a bearer token;
  404 without it.
