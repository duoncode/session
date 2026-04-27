# Changelog

## [Unreleased](https://github.com/duoncode/session/compare/0.1.0...HEAD)

### Added

- `Contract\Helpers` and `Helpers` for customizing session helper instances.
- `Session::$uri` property for remembered URI access.
- `Csrf::refresh()` and `Csrf::remove()` helpers.
- `Session::$csrf` property for CSRF token access.
- `Flash::peek()` and `Flash::clear()` helpers.
- `Session::$flash` property for flash message access.
- `Session::close()` to write the current session data and close the active session.
- Helper methods for reading all session data, clearing the session, removing keys, and pulling values.
- Secure default options for native PHP sessions.
- Package documentation.

### Breaking

- Session cookies now default to `Secure`; set `cookie_secure` to `false` only for intentional plain HTTP environments.
- `Session` constructor arguments are now ordered as `$options`, `$name`, `$handler`, `$helpers`.
- CSRF now uses `duon_csrf_tokens`, `_token`, and `X-CSRF-Token` as default storage key, form field, and header names.
- Remembered URIs now use `$session->uri->remember()` and `$session->uri->pull()` instead of direct `Session` methods.
- CSRF tokens now use `$session->csrf->token()` or `new Csrf($session)` instead of `new Csrf()` and `Csrf::get()`.
- Flash messages now use `$session->flash->add()`, `$session->flash->pop()`, and `$session->flash->has()` instead of direct `Session` methods.
- `Session::forget()` has been replaced with `Session::destroy()`.
- Remembered URI redirects now only return safe local paths.
- Session ID regeneration now throws when the session is inactive or regeneration fails.
- Custom session handler registration now throws when setup fails.
- Sessions now use PHP's `nocache` cache limiter by default. Set `cache_limiter` to `''` to disable PHP cache headers.
- CSRF helpers now throw when used without an active session.
- Flash messages are now stored and returned as raw strings. Escape them at render time.

### Fixed

- CSRF verification no longer creates missing tokens as a side effect.
- Session cookie deletion now preserves SameSite and partitioned metadata.

## [0.1.0](https://github.com/duoncode/session/releases/tag/0.1.0) (2026-01-31)

Initial release.

### Added

- Session management library for PHP applications
- Secure session ID generation and handling
- Session storage abstraction
