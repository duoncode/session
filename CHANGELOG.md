# Changelog

## [Unreleased](https://github.com/duoncode/session/compare/0.1.0...HEAD)

### Added

- Secure default options for native PHP sessions.
- Package documentation.

### Breaking

- Remembered URI redirects now only return safe local paths.
- Session ID regeneration now throws when the session is inactive or regeneration fails.
- Custom session handler registration now throws when setup fails.
- Sessions now use PHP's `nocache` cache limiter by default. Set `cache_limiter` to `''` to disable PHP cache headers.
- CSRF helpers now throw when used without an active session.

## [0.1.0](https://github.com/duoncode/session/releases/tag/0.1.0) (2026-01-31)

Initial release.

### Added

- Session management library for PHP applications
- Secure session ID generation and handling
- Session storage abstraction
