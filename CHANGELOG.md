# Changelog

## 1.2.0 (2026-11-23)

- `npm`: the npm packages from `package-lock.json` (name, version, dev flag), `{"lockfile": false}`
  when there is a `package.json` without a readable lock file, or `null` when there is no
  `package.json`. An addition: `report_version` stays 1.

## 1.1.0 (2026-11-22)

- `os`: the operating system's id and version, or `null`. An addition: `report_version` stays 1.

## 1.0.0 (2026-11-20)

- `GET /version-report`: Laravel, PHP and production package versions, behind a bearer token;
  404 without it.
