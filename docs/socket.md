# How to integrate sockets (Reverb + Echo)

Sockets do **not** replace your API. They replace **polling**.

Keep HTTP for writes (create a circuit, move a gate, leave the board). After the write succeeds, **broadcast** so other browsers update without asking `GET /circuits` every 2s or `GET /sync` every 400ms.

```
Browser A  --POST /nodes-->  Laravel  --broadcast-->  Reverb  --push-->  Browser B (Echo)
```

Reverb is the WebSocket server. Echo + **pusher-js** is the client. Reverb speaks the Pusher protocol, so you still install `pusher-js` even though you are not using Pusher Cloud.

## 1. Install

```bash
composer require laravel/reverb:^1.0 --with-all-dependencies
php artisan install:broadcasting --reverb --no-interaction
npm install laravel-echo pusher-js
```

On Laravel 13, Reverb needs Guzzle PSR-7 2.x. Guzzle 8 needs PSR-7 3.x. If Composer fails, pin Guzzle 7:

```bash
composer require laravel/reverb:^1.0 guzzlehttp/guzzle:^7.8.2 --with-all-dependencies
```

The installer writes broadcasting/Reverb config and `.env` keys. It tries to wire Echo in `resources/js/app.ts`. If the app uses Vue (`app.js`), skip that and create Echo yourself (step 4).

`.env` must have all three or Artisan will crash when loading channels:

```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

`VITE_*` is what the browser uses. After changing it, restart Vite.

Tests should set `BROADCAST_CONNECTION=null` so Pest does not need Reverb running.

## 2. Run three processes

```bash
php artisan serve
npm run dev
php artisan reverb:start
```

Or `composer run dev` / `php artisan dev` (Reverb is registered automatically).

Without `reverb:start`, the page loads but other people never see your changes.

## 3. Auth (the part that usually breaks)

Public channels need no login. **Private** and **presence** channels do.

Echo calls `POST /broadcasting/auth` with the session cookie. Laravel then runs your channel callback. That callback’s first argument is `$request->user()`.

This app has no `User` model — only a `Participant` in the session. So you must:

1. Put that participant on the request as the user (otherwise auth returns 403).
2. Protect `/broadcasting/auth` with the same “must have a name” rule as the rest of the app.
3. For **presence**, return member info (`id` + `name`). The `id` should be a stable UUID, and the model needs `getAuthIdentifier()` / `getAuthIdentifierForBroadcasting()` so Echo and Reverb agree who is who.

Channel examples:

| Channel | Kind | Why |
| --- | --- | --- |
| `dashboard` | private | Anyone with a name can watch the circuit list |
| `circuit.{uuid}` | presence | Same, plus “who is in this board” and **client whispers** (cursors) |

Whispers (`channel.whisper('cursor', ...)`) only work on private/presence channels, and Reverb must allow client events (`accept_client_events_from` = `members`).

## 4. Client: one Echo instance

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
});
```

CSRF is read from `<meta name="csrf-token">`. Import this **before** Vue mounts.

Subscribe, then leave on unmount:

```js
echo.private('dashboard')
    .listen('.circuit.directory', applyDirectory)
    .listen('.circuit.presence', applyPresence);

echo.join(`circuit.${uuid}`)
    .listen('.circuit.board', applySnapshot)
    .listenForWhisper('cursor', applyCursor);

// later
echo.leave('dashboard');
echo.leave(`circuit.${uuid}`);
```

The leading **dot** in `.circuit.board` is required. Without it Echo prefixes `App\Events`. Match `broadcastAs()` on the PHP event.

On reconnect, fetch once (`GET /circuits` or `GET /sync?revision=`) so you do not miss events that happened while the socket was down.

## 5. Server: broadcast after a successful write

Use `ShouldBroadcastNow` so you do **not** need a queue worker.

Important pieces on the event:

- `broadcastOn()` — `PrivateChannel('dashboard')` or `PresenceChannel('circuit.'.$uuid)`
- `broadcastAs()` — short name the client listens for (`circuit.board`)
- `broadcastWith()` — JSON the client will apply
- `broadcast(...)->toOthers()` — skip the browser that just saved (it already updated itself). Needs Echo’s `X-Socket-ID` header, which Echo sends automatically.

Pattern:

```php
$node = $circuit->nodes()->create(...);
$circuit->increment('revision'); // or your bump helper

broadcast(new CircuitBoardChanged($circuit))->toOthers();
```

Do **not** broadcast on every mouse move. That is worse than the old poll.

| Data | How |
| --- | --- |
| Gates, wires, settings | HTTP write, then broadcast a snapshot (or a small patch) |
| “Online” list | Broadcast on join / leave. Optional slow heartbeat (10s) so `last_seen_at` expires |
| Cursors | Whisper on the presence channel (no HTTP) |

A 10s heartbeat is fine. A 400ms full-board poll is not.

## 6. What not to put on the socket

- Creating / updating / deleting data — still POST/PATCH/DELETE so the database stays the source of truth
- First page load — still render the current snapshot from Laravel
- Catch-up after disconnect — one HTTP sync is simpler than replaying a missed event log

## 7. Checklist if it does not connect

1. Is `php artisan reverb:start` running?
2. Does `.env` have `BROADCAST_CONNECTION=reverb` and matching `REVERB_*` / `VITE_REVERB_*`? Did you restart Vite?
3. Browser → Network: WebSocket to `localhost:8080`. If it dies, check host/port/scheme (`http` + `forceTLS: false` locally).
4. `POST /broadcasting/auth` must be **200**, not 302/403. If 403, `$request->user()` is null (session participant not bound).
5. Event name: PHP `broadcastAs()` = `circuit.board` → JS `.listen('.circuit.board', ...)`.
6. Private/presence name: PHP `PrivateChannel('dashboard')` → JS `echo.private('dashboard')`. Echo adds the `private-` / `presence-` prefix.

## How this app uses it

- **Dashboard** listens on `private-dashboard` for list + online counts. No list polling.
- **Editor** joins `presence-circuit.{uuid}` for the board. Cursors are whispers. Presence heartbeat is 10s.
- Writes still go through the existing circuit/node/wire routes; those routes broadcast after they save.
