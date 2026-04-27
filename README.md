# Duon Sessions

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/83e066774ea24c5da4b7ac855797c439)](https://app.codacy.com/gh/duoncode/session/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![Codacy Badge](https://app.codacy.com/project/badge/Coverage/83e066774ea24c5da4b7ac855797c439)](https://app.codacy.com/gh/duoncode/session/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_coverage)
[![Psalm level](https://shepherd.dev/github/duoncode/session/level.svg?)](https://duon.sh/session)
[![Psalm coverage](https://shepherd.dev/github/duoncode/session/coverage.svg?)](https://shepherd.dev/github/duoncode/session)

Helper classes for native PHP sessions and CSRF.

## Usage

```php
use Duon\Session\Session;

$session = new Session(options: [
    'cookie_secure' => true,
]);
$session->start();
```

`Session` passes its options to `session_start()` and merges custom options with these defaults:

```php
[
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_only_cookies' => true,
    'use_strict_mode' => true,
    'use_trans_sid' => false,
]
```

Set `cookie_secure` to `true` for HTTPS deployments. It is not enabled by default because the library cannot know whether the current request came through a trusted HTTPS proxy.

## License

This project is licensed under the [MIT license](LICENSE.md).
