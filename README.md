# Duon Sessions

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/83e066774ea24c5da4b7ac855797c439)](https://app.codacy.com/gh/duoncode/session/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Codacy Badge](https://app.codacy.com/project/badge/Coverage/83e066774ea24c5da4b7ac855797c439)](https://app.codacy.com/gh/duoncode/session/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_coverage)
[![Psalm level](https://shepherd.dev/github/duoncode/session/level.svg?)](https://duon.sh/session)
[![Psalm coverage](https://shepherd.dev/github/duoncode/session/coverage.svg?)](https://shepherd.dev/github/duoncode/session)

Helper classes for native PHP sessions, flash messages, and CSRF.

## Installation

```bash
composer require duon/session
```

## Documentation

Start here: [docs/index.md](docs/index.md).

## Quick start

```php
use Duon\Session\Session;

$session = new Session([
    'cookie_secure' => true,
]);
$session->start();

$session->set('user_id', 123);
$userId = $session->get('user_id');

$session->flash->add('Signed in.');

$token = $session->csrf->token('profile');
```

`Session` merges custom options with secure defaults for HttpOnly cookies, SameSite=Lax, strict session IDs, cookie-only session IDs, disabled transparent session IDs, and PHP's `nocache` session cache limiter. Set `cookie_secure` to `true` for HTTPS deployments.

## License

This project is licensed under the [MIT license](LICENSE.md).
