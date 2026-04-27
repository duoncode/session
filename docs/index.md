# Duon Sessions

Duon Sessions wraps native PHP sessions and provides a small CSRF helper.

## Installation

```bash
composer require duon/session
```

## Start a session

```php
use Duon\Session\Session;

$session = new Session(options: [
    'cookie_secure' => true,
]);
$session->start();

$session->set('user_id', 123);
$userId = $session->get('user_id');
```

`Session` merges custom options with secure defaults before calling `session_start()`:

```php
[
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_only_cookies' => true,
    'use_strict_mode' => true,
    'use_trans_sid' => false,
]
```

Set `cookie_secure` to `true` for HTTPS deployments. The library does not enable it by default because it cannot know whether the current request came through a trusted HTTPS proxy.

If you pass a custom session handler, `start()` throws when PHP cannot register it.

Call `$session->regenerate()` after `start()` when a user logs in or changes privileges. Call `$session->forget()` on logout.

## Session data

```php
$session->set('name', 'Chuck');

if ($session->has('name')) {
    echo $session->get('name');
}

$session->unset('name');
```

`get()` throws when the key is missing unless you pass a default value:

```php
$name = $session->get('name', 'Guest');
```

## Flash messages

```php
$session->flash('Saved.');
$session->flash('Could not save.', 'error');

$messages = $session->popFlashes();
$errors = $session->popFlashes('error');
```

Flash messages are escaped with `htmlspecialchars()` when stored.

## Remembered URI

```php
$session->rememberUri('/account');

return $session->rememberedUri();
```

`rememberedUri()` returns the stored URI once, then removes it. It returns `/` when the URI is expired or not a safe local path. Remembered URIs must start with a single `/`; external URLs are rejected.

## CSRF tokens

Start the session before creating or verifying CSRF tokens.

```php
use Duon\Session\Csrf;

$csrf = new Csrf();
$token = $csrf->get('contact');
```

Use the token in a form:

```php
<input type="hidden" name="csrftoken" value="<?= htmlspecialchars($token) ?>">
```

Verify submitted tokens on unsafe requests:

```php
if (!$csrf->verify('contact')) {
    throw new RuntimeException('Invalid CSRF token');
}
```

`verify()` reads the token from the explicit argument, `$_POST['csrftoken']`, or the `HTTP_X_CSRF_TOKEN` server value.
