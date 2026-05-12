# Celemas Sessions

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![CI](https://github.com/celemas/session/actions/workflows/ci.yml/badge.svg)](https://github.com/celemas/session/actions)
[![Psalm level](https://shepherd.dev/github/celemas/session/level.svg?)](https://celemas.dev/session)
[![Psalm coverage](https://shepherd.dev/github/celemas/session/coverage.svg?)](https://shepherd.dev/github/celemas/session)

Helper classes for native PHP sessions, flash messages, and CSRF.

## Installation

```bash
composer require celemas/session
```

## Documentation

Start here: [docs/index.md](docs/index.md).

## Quick start

```php
use Celemas\Session\Session;

$session = new Session();
$session->start();

$session->set('user_id', 123);
$userId = $session->get('user_id');

$session->flash->add('Signed in.');

$token = $session->csrf->token('profile');
```

`Session` merges custom options with secure defaults for Secure and HttpOnly cookies, SameSite=Lax, strict session IDs, cookie-only session IDs, disabled transparent session IDs, and PHP's `nocache` session cache limiter. Set `cookie_secure` to `false` only for intentional plain HTTP environments, such as local development without TLS.

## License

This project is licensed under the [MIT license](LICENSE.md).
