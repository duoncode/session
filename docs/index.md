# Celemas Sessions

Celemas Sessions wraps native PHP sessions and provides a small CSRF helper.

## Installation

```bash
composer require celemas/session
```

## Start a session

```php
use Celemas\Session\Session;

$session = new Session();
$session->start();

$session->set('user_id', 123);
$userId = $session->get('user_id');
```

`Session` merges custom options with secure defaults before calling `session_start()`:

```php
[
    'cache_limiter' => 'nocache',
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => true,
    'use_only_cookies' => true,
    'use_strict_mode' => true,
    'use_trans_sid' => false,
]
```

`cookie_secure` defaults to `true`, so browsers only send the session cookie over HTTPS. Disable it only for intentional plain HTTP environments, such as local development without TLS:

```php
$session = new Session([
    'cookie_secure' => false,
]);
```

Set `cache_limiter` to `''` only if your application sends its own cache headers for session-backed responses.

If you pass a custom session handler, `start()` throws when PHP cannot register it.

Call `$session->regenerate()` after `start()` when a user logs in or changes privileges. Call `$session->destroy()` on logout.

Call `$session->close()` after your last session read or write to write the current session data and close the active session for the current request. This releases PHP's session lock early, which helps long-running requests, downloads, and streamed responses avoid blocking other requests from the same session. Start the session again before accessing session data after `close()`.

## Custom helpers

`$session->flash`, `$session->csrf`, and `$session->uri` are created lazily through `Celemas\Session\Contract\Helpers`. Pass a custom implementation when you need custom helper classes or storage keys:

```php
use Celemas\Session\Contract\Helpers as HelpersContract;
use Celemas\Session\Csrf;
use Celemas\Session\Flash;
use Celemas\Session\Session;
use Celemas\Session\Uri;

final class AppHelpers implements HelpersContract
{
    public function flash(Session $session): Flash
    {
        return new Flash($session, key: 'app_flashes');
    }

    public function csrf(Session $session): Csrf
    {
        return new Csrf($session, key: 'app_csrf_tokens');
    }

    public function uri(Session $session): Uri
    {
        return new Uri($session, key: 'return_to');
    }
}

$session = new Session(helpers: new AppHelpers());
```

## Session data

```php
$session->set('name', 'Chuck');

if ($session->has('name')) {
    echo $session->get('name');
}

$all = $session->all();
$name = $session->pull('name');

$session->remove('name');
$session->clear();
```

Start the session before reading or changing session data. `all()` returns the complete session data array. `clear()` removes all session data while keeping the session active.

`get()` and `pull()` throw when the key is missing unless you pass a default value:

```php
$name = $session->get('name', 'Guest');
$name = $session->pull('name', 'Guest');
```

`pull()` returns the value and removes it from the session.

## Flash messages

```php
$session->flash->add('Saved.');
$session->flash->add('Could not save.', 'error');

if ($session->flash->has('error')) {
    $errors = $session->flash->pop('error');
}

$messages = $session->flash->peek();
$session->flash->clear('error');
$messages = $session->flash->pop();
```

`peek()` returns messages without removing them. `pop()` returns messages and removes them. `clear()` removes all messages or only the given queue.

Flash messages are stored as raw strings. Escape messages when rendering them into HTML.

## Remembered URI

```php
$session->uri->remember('/account');

return $session->uri->pull();
```

`pull()` returns the stored URI once, then removes it. It returns `/` when the URI is expired or not a safe local path. Pass a custom default when you need a different fallback:

```php
return $session->uri->pull('/dashboard');
```

Remembered URIs must start with a single `/`; external URLs are rejected. `Uri` can also be used directly with `new Uri($session, key: 'return_to')` when you need a custom storage key.

## CSRF tokens

Start the session before creating or verifying CSRF tokens. `Csrf` throws when no session is active.

```php
$token = $session->csrf->token('contact');
```

Use the token in a form:

```php
<input type="hidden" name="_token" value="<?= htmlspecialchars($token) ?>">
```

Verify submitted tokens on unsafe requests:

```php
if (!$session->csrf->verify('contact')) {
    throw new RuntimeException('Invalid CSRF token');
}

$token = $session->csrf->refresh('contact');
$session->csrf->remove('contact');
```

`refresh()` replaces a token and returns the new value. `remove()` deletes the token for a page.

`Csrf` can also be used directly with custom names:

```php
$csrf = new Csrf(
    $session,
    key: 'celemas_csrf_tokens',
    field: '_token',
    header: 'X-CSRF-Token',
);
```

`verify()` reads the token from the explicit argument, `$_POST['_token']`, or the `X-CSRF-Token` request header.
