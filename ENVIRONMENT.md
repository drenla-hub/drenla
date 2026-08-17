# Environment & Config — Drenla Backend + Frontend

_Written by CO, 2026-07-02. Covers what each app needs to run and how they relate today — not aspirational config for the deferred React integration._

## Topology today

Two separate apps in this repo, **not yet connected**:

- **`drenla/`** — the Laravel backend. Serves `/admin` (staff), `/portal` (clients), `/api/site/*` (public JSON), and `/` (a minimal landing page — not the public site).
- **`frontend/`** — a standalone Vite/React app (shadcn/ui). This is the actual public marketing site today, deployed independently, and **does not call the Laravel API yet** (confirmed: no `VITE_API_*` env vars, no fetches to `/api/site/*` anywhere in `frontend/src`). Per `plan.md` §5, don't wire this up until told.
- **`frontend/drenla/`** — stale scaffolding, not a real app. See `plan.md` §1. Ignore.

Because the two apps don't talk to each other yet, there's no CORS config to write today (`drenla/config/cors.php` doesn't exist — Laravel's default `HandleCors` middleware runs but with no published config, meaning no cross-origin allowances). When React integration resumes, that's the first thing to add: publish `config/cors.php`, allow the frontend's origin, and decide whether the future authenticated JS-heavy client experience (`plan.md` §3, still deferred) needs cookies (`supports_credentials`) or a token instead.

## Backend (`drenla/`) — required env vars

Beyond Laravel's own defaults (`.env.example`), these matter specifically for Drenla's features:

| Variable | Used by | Notes |
|---|---|---|
| `APP_URL` | Portal magic-links, proposal/finance signed print URLs, `MediaAsset` public disk URLs | Must be the real reachable URL in any non-local environment — it's baked into every generated link (`route()` calls default to `APP_URL`) and into `Storage::disk('public')->url()`. |
| `DRENLA_PROPOSAL_CHROME_PATH` (`config/proposals.php`) | `ProposalPdfExporter`, `FinanceDocumentPdfExporter`, and by extension `DocumentDeliveryService` | Path to a headless-Chrome-capable binary. Both PDF exporters shell out to it via `Symfony\Process`. If this is wrong/missing, PDF export **and** "send to client" both fail loudly (`RuntimeException`), not silently. |
| `MAIL_MAILER` + `MAIL_FROM_ADDRESS`/`MAIL_FROM_NAME` | `DocumentDeliveryService` (`App\Mail\{ProposalDeliveryMail,FinanceDocumentDeliveryMail}`) | Defaults to `log` locally (mail bodies land in `storage/logs/laravel.log`, nothing actually sent) — fine for dev, **must** be a real driver (smtp/ses/postmark/etc.) before "send to client" is usable in production. |
| `FILESYSTEM_DISK` (default `local`) + `AWS_*` if using S3 | `MediaAsset`, `ProposalFile` | See the asset URL strategy decision in `collaboration-notes.md` — swapping to S3 later is env-only, no code change. Whichever disk is active, `php artisan storage:link` must have been run for the `public` disk to actually serve files. |
| `GEMINI_API_KEY` / `OPENAI_API_KEY` (`config/ai_assistant.php`) | Admin AI assistant only (`app/AiAssistant/**`) | Not used by anything CO owns; listed here for completeness since it's a real prod-blocking var if unset (`AI_ASSISTANT_PROVIDER` defaults to `null`, i.e. disabled). |
| `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE` | Both the `web` (staff) and `client` (portal) session guards | They're independent guards but share the same cookie-jar config — no per-guard override exists in `config/session.php`. In production behind HTTPS, set `SESSION_SECURE_COOKIE=true`. No subdomain-splitting is needed since admin, portal, and API are all served from the same Laravel app/origin. |
| `QUEUE_CONNECTION` | Not currently required — everything (PDF export, mail send) runs synchronously | If email volume grows, `DocumentDeliveryService`'s `Mail::to(...)->send(...)` can be swapped to `->queue(...)` (both Mailables already `use Queueable`) — that's the only change needed, no restructuring. |

One-time setup, any environment: `php artisan migrate`, `php artisan storage:link`, `npm install && npm run build` (Vite assets — admin and portal views both `@vite(...)` the same `resources/css/app.css`/`resources/js/app.js`, no separate build needed for the portal).

## Frontend (`frontend/`) — nothing Laravel-specific yet

No env vars point at the Laravel backend today. When the deferred React-consumes-Laravel-content work starts, expect to add:
- A `VITE_API_BASE_URL` (or similar) pointing at `{APP_URL}/api/site`.
- `config/cors.php` on the backend allowing that origin for `GET`/`POST` on `/api/site/*` (those endpoints are public/unauthenticated today, so `supports_credentials` likely stays `false` unless a future authenticated JS experience needs cookies — see `plan.md` §3's "Public API" note, still explicitly deferred).

Not doing any of this now — flagging so whoever picks up the React integration later doesn't have to rediscover it.
