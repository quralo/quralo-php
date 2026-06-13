# AGENTS.md

## Quick commands

```bash
# Install deps (also runs post-install patch)
docker compose run --rm quralo-php composer install

# Run all tests
docker compose run --rm quralo-php ./vendor/bin/phpunit

# Run example
docker compose run --rm quralo-php php examples/php/basic_usage.php

# Interactive shell in container
docker compose run --rm -it quralo-php bash

# Reapply vendor patch manually (if skipping composer install)
docker compose run --rm quralo-php composer run-script post-install-cmd
```

## Architecture

- **PHP 5.6 target** — no scalar type hints, no return type hints, no `??`. Use `isset(...) ? ... : ...` and `array()` for tests.
- Single PSR-4 namespace: `Quralo\` → `src/`. Entrypoint: `Quralo::ecl()` returns an `Ecl` instance.
- Only one module: **ECL** (External Context Linking). Generates compact, encrypted QR payloads and PNG QR codes.
- `composer.lock` is `.gitignore`d; `vendor/` is committed (line commented out in `.gitignore`).

## Non-obvious gotchas

### Vendor patch (constant redefinition)
`composer.json` post-install/post-update scripts `sed`-patch `vendor/aferrandini/phpqrcode/lib/PHPQRCode.php` to wrap `define()` calls in `if (!defined(...))`. Without this patch, the library will throw `Notice: Constant already defined` in environments where the dependency is loaded more than once. `composer install` runs this automatically. `src/Ecl.php` also uses `@require_once` to suppress notices at load time.

### Secret key normalization
Client secrets can be binary (32 bytes), hex (64 chars), or base64 (44 chars). `Ecl::normalizeKey()` auto-detects and normalizes. Use a 64-char hex key in tests (e.g., `'6927e247e82536c7623815b2a9580074bfb04aa2b3e8ae2ea2b44a1e78628d53'`).

### GD extension required
All QR tests and `generateQrCode()` require the GD extension. Tests skip themselves when GD is absent. The Docker image includes it.

### No linter, formatter, or CI
There is no configured PHP-CS-Fixer, PHPCS, or GitHub Actions. When editing, match existing style: 2-space indentation, `array()` in tests, `[]` in `src/`.

## Testing

- Framework: PHPUnit 5.7. Config: `phpunit.xml` (testdox + colors, bootstrap via `vendor/autoload.php`).
- Single test suite in `tests/` directory. All tests in `Quralo\Tests\QuraloTest`.
- Run a single test: `docker compose run --rm quralo-php ./vendor/bin/phpunit --filter testEclInstantiation`

## QR payload format

```
QRL|1|ecl|<client_id>|<timestamp>|<crypto_version>|<encrypted_data>|<mac>
```
- `encrypted_data` = `salt:iv:ciphertext` (base64url), AES-256-CBC, key via PBKDF2-SHA256 (100k iterations).
- `mac` = HMAC-SHA256 over the preceding `|`-delimited string (using raw original secret, not the PBKDF2-derived key).
