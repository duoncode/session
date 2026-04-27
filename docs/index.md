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
    'cache_limiter' => 'nocache',
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_only_cookies' => true,
    'use_strict_mode' => true,
    'use_trans_sid' => false,
]
```

Set `cookie_secure` to `true` for HTTPS deployments. The library does not enable it by default because it cannot know whether the current request came through a trusted HTTPS proxy.

Set `cache_limiter` to `''` only if your application sends its own cache headers for session-backed responses.

If you pass a custom session handler, `start()` throws when PHP cannot register it.

Call `$session->regenerate()` after `start()` when a user logs in or changes privileges. Call `$session->destroy()` on logout.

Call `$session->close()` after your last session read or write to write the current session data and close the active session for the current request. This releases PHP's session lock early, which helps long-running requests, downloads, and streamed responses avoid blocking other requests from the same session. Start the session again before accessing session data after `close()`.

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
$session->rememberUri('/account');

return $session->rememberedUri();
```

`rememberedUri()` returns the stored URI once, then removes it. It returns `/` when the URI is expired or not a safe local path. Remembered URIs must start with a single `/`; external URLs are rejected.

## CSRF tokens

Start the session before creating or verifying CSRF tokens. `Csrf` throws when no session is active.

```php
$token = $session->csrf->token('contact');
```

Use the token in a form:

```php
<input type="hidden" name="csrftoken" value="<?= htmlspecialchars($token) ?>">
```

Verify submitted tokens on unsafe requests:

```php
if (!$session->csrf->verify('contact')) {
    throw new RuntimeException('Invalid CSRF token');
}
```

`Csrf` can also be used directly with `new Csrf($session)`. `verify()` reads the token from the explicit argument, `$_POST['csrftoken']`, or the `HTTP_X_CSRF_TOKEN` server value.
