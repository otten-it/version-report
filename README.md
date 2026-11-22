# otten-it/version-report

Lets a monitoring dashboard read which operating system, Laravel, PHP and package versions a
Laravel application runs on, behind a secret token. Used by [Otten IT](https://www.otten-it.nl) to check that every
application it hosts runs on supported versions and has no packages with known
vulnerabilities.

```
GET /version-report
Authorization: Bearer <token>

{
  "report_version": 1,
  "laravel": "13.43.0",
  "php": "8.3.14",
  "os": {"id": "ubuntu", "version": "26.04"},
  "packages": [{"name": "guzzlehttp/guzzle", "version": "7.10.0"}, …],
  "generated_at": "2026-11-21T06:00:00+01:00"
}
```

## Installation

```bash
composer require otten-it/version-report
```

Generate a token and put it in the application's `.env`:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

```sh
VERSION_REPORT_TOKEN=
```

Give the same token and the URL (`https://your-app.example/version-report`) to whoever reads the
report. That is all: the service provider is discovered automatically.

Requires PHP 8.3+ and Laravel 12 or 13.

## Security

A list of versions tells an attacker what to try, so the report is never public:

- **404 without the right token**, and also while no token is configured: to anyone else the
  endpoint does not exist.
- The token is compared in constant time and only accepted from the `Authorization` header,
  never from the query string (which ends up in logs).
- The route runs outside the `web` middleware group: no session, no cookies. It is throttled to
  10 requests a minute.
- Reported: versions of the operating system, Laravel, PHP and production packages (no
  `require-dev`). Nothing else: no environment, configuration or paths.

Always read the report over HTTPS, and do not follow redirects with the token attached.

Found a vulnerability? See [SECURITY.md](SECURITY.md).

## Configuration

Optional. To change the URL path, publish the config:

```bash
php artisan vendor:publish --tag=version-report
```

| Key | Default | |
|---|---|---|
| `token` | `env('VERSION_REPORT_TOKEN')` | shared secret; empty disables the endpoint |
| `path` | `version-report` | URL path |

## Report format

`os` (since 1.1.0) is `ID` and `VERSION_ID` from `/etc/os-release`, or `null` when that is
unreadable or has no version (rolling release, `open_basedir`). In a container it is the image's
system, not the host's.

`report_version` is `1`. A future incompatible change raises it, so a reader can refuse a format
it does not understand. `packages` lists every installed production package with its pretty
version (`v13.43.0`, `7.10.0`), sorted by name.

## Development

```bash
composer install
vendor/bin/phpunit
```

## License

MIT, see [LICENSE](LICENSE).
