# GenOne Control Center

Single-user backend dashboard for Paulo's systems (my-genone, 201 System, PayMaya, Ticketing Portal, face-service, Gen One website). Laravel 13, Blade, plain CSS/JS in `public/` (no Vite build). Deployed on Railway via `nixpacks.toml`.

## Rules

- **Never write to a watched database.** `genone` (shared by my-genone and 201 System) and `paymaya` connections reuse each app's own prod login. `AppServiceProvider` sets every session on them to `READ ONLY` with a 5 s `statement_timeout`. Keep it that way; don't add writes or remove the guard.
- All reads from watched systems go through `App\Services\Probe::run()` so one unreachable database never breaks a page, and results are cached briefly.
- my-genone and 201 store timestamps in Asia/Manila; PayMaya stores UTC (convert with `Carbon::parse($v, 'UTC')->setTimezone(...)`).
- Blade: use `@php ... @endphp` blocks only, never inline `@php(...)` (an inline one followed by a later `@endphp` breaks compilation).
- `env()` only inside `config/*.php` (the build runs `config:cache`).
- RFID "taps" are inferred (office entry, no GPS, not admin-set) because my-genone doesn't log API requests yet. See the "Not recorded yet" page for the gaps.
