# otten-it/version-report

Lets the Otten IT hosting dashboard read which Laravel, PHP and package versions an application
runs on: for the supported-versions overview and the check against known vulnerabilities.
Installed in every project Otten IT hosts.

One endpoint, `GET /version-report`, behind a shared token:

```
GET /version-report
Authorization: Bearer <token>

{
  "report_version": 1,
  "laravel": "13.43.0",
  "php": "8.3.14",
  "packages": [{"name": "guzzlehttp/guzzle", "version": "7.10.0"}, …],
  "generated_at": "2026-11-21T06:00:00+01:00"
}
```

## Security

- **404 without the right token**, also while no token is configured: to anyone else the
  endpoint does not exist. A version list tells an attacker what to try, so it is never public.
- Token compared in constant time (`hash_equals`), only from the `Authorization` header (never
  a query string, which ends up in logs).
- Outside the web middleware group: no session, no cookies. Throttled to 10 requests a minute.
- Production packages only (no `require-dev`). Nothing else: no environment, no config.

## Installing

1. Add the repository and require the package:

   ```json
   "repositories": [
       {"type": "vcs", "url": "git@git.otten-it.nl:version-report.git"}
   ],
   ```

   ```bash
   composer require otten-it/version-report:^1.0
   ```

2. Generate a token and put it in the production `.env`:

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   ```sh
   VERSION_REPORT_TOKEN=
   ```

3. In the Otten IT admin panel → Uptime → the project's site: the report URL
   (`https://…/version-report`) and the same token.

The service provider is discovered automatically. Other path: publish the config with
`php artisan vendor:publish --tag=version-report` and change `path`.

## Developing

```bash
composer install
vendor/bin/phpunit
```

Releases are git tags (`v1.0.0`); projects require `^1.0`.
