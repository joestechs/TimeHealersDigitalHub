# Customer support chatbot roadmap

**Status:** Approved implementation. Phases 1–7 are implemented; the chatbot schema and starter knowledge have been applied to the local `thdh` database. Local migration, retrieval, admin, endpoint, and widget checks pass. External-provider behavior remains untested until a provider is configured.

## Goal

Turn the home-page contact bubble into an accessible customer-support chat widget. It should answer questions using approved public Time Healers Digital Hub information, direct customers to relevant pages, and offer the existing contact form when it cannot answer confidently.

The chatbot must not read private customer, inquiry, order, payment, administrator, or unpublished-content records; take payments; change accounts; or promise services, pricing, delivery, or policies that are not in approved knowledge.

## Current project baseline

- The home-page support button now opens an accessible chat widget and retains `/contact` as its no-JavaScript fallback.
- The project uses native PHP, PDO, MySQL/MariaDB, and a front controller in `bootstrap/routes.php`. An optional OpenAI-compatible provider adapter is configured only through server environment variables; local knowledge retrieval works without a provider.
- Admin sections are routed through `AdminController`; chatbot writes use the existing administrator authorization, CSRF, validation, and audit-log patterns.
- The schema and starter data are managed as SQL files under `database/migrations/` and `database/seeders/`.
- Existing public sources include About, Services, Products, FAQ, Contact, account pages, and the Knowledge Hub. Direct checkout is not yet enabled until PayPal is configured; external-vendor products use their vendor checkout.

## Phase 1 — Confirm provider, privacy, and permissions

1. Select the model/API provider, or approve a provider-neutral adapter that can be configured later.
2. Keep API keys and provider credentials in server environment configuration (`.env` locally, secret storage in production); never store or display them in the database or admin UI.
3. Store **no conversation transcripts** in the initial version, including opt-in transcript logging.
4. Confirm which existing admin roles may edit chatbot settings and knowledge. Recommended default: active administrators and super administrators may manage chatbot content; only super administrators manage staff roles, as today.
5. Confirm the chatbot's limits: general product/service guidance and page navigation only, with account/payment issues handed to Contact.

**Acceptance:** confirmed provider-neutral integration, no transcript storage, both administrator roles managing chatbot content, and the existing Contact form as fallback.

## Phase 2 — Define the chatbot schema and migrations

Add incremental SQL files; do not modify an already-applied `001_initial_schema.sql`.

1. Add `database/migrations/002_chatbot_schema.sql`.
2. Add a singleton `chatbot_settings` table for enabled state, bot name, greeting, fallback response, and the support/contact link. Keep provider secrets out of this table.
3. Add a `chatbot_knowledge` table for a stable key, question/title, answer/body, category, searchable keywords, publication status, source type, priority, creator, and timestamps.
4. Use `draft`, `published`, and `archived` states. Only published entries may be used for answers.
5. Add appropriate indexes and foreign keys, with portable MySQL 8/MariaDB 10.6 types.
6. Do not create a conversation-history table.

**Acceptance:** the new migration applies to a fresh database and an existing database without changing or deleting existing records; duplicate application is safe.

## Phase 3 — Prepare safe, project-grounded seed knowledge

1. Add idempotent `database/seeders/002_chatbot_seed.sql` for stable, approved starter Q&A.
2. Seed concise answers for:
   - THDH purpose and the six core services: web development, app development, digital marketing, graphic design, video editing, and corporate training.
   - How to browse products, categories, and published Knowledge Hub articles.
   - External-vendor checkout versus the current PayPal-configuration-required state.
   - How to request a custom project, contact support, register, sign in, and find account/order pages.
   - The published FAQ guidance on purchases and project customization.
3. Read changing product and service details from currently published/active public records at request time instead of copying those details into permanent seed rows. This prevents stale prices, names, and descriptions.
4. Never seed private user, inquiry, order, payment, admin, secret, draft, or archived data. Do not claim password-reset or support features the application does not currently provide.

**Acceptance:** seed data is friendly, factual, idempotent, and limited to approved public information.

## Phase 4 — Add admin chatbot configuration and knowledge management

1. Add a Chatbot section to the admin navigation and controller allowlist.
2. Add settings forms for enabled state, greeting, fallback answer, bot label, and support link.
3. Add knowledge CRUD for question/title, answer/body, category, keywords, priority, and draft/published/archive status.
4. Enforce server-side authorization, CSRF, length limits, safe URL validation, output escaping, and audit logging on every write.
5. Do not provide a form field for API keys or secrets.
6. Add a no-write admin test panel that uses the same retrieval path and does not save test messages.

**Acceptance:** administrators can create, edit, publish, unpublish, archive, and restore chatbot knowledge without hard-deleting records or exposing secrets.

## Phase 5 — Implement safe CSV/JSON import and export

1. Provide downloadable CSV and JSON templates matching the knowledge fields.
2. Build an admin-only import flow with file-size/type limits, UTF-8 handling, strict field allowlists, and per-field validation.
3. Parse imports into a preview first. Show valid, duplicate, skipped, and invalid rows with actionable row-level errors before allowing commit.
4. Commit the accepted batch in one database transaction; rollback the entire commit on database failure.
5. Use a stable key or deterministic content fingerprint to detect duplicates. Make the operator choose whether duplicates are skipped or updated; never silently overwrite published content.
6. Escape imported text on output and treat all uploaded content as untrusted data, not executable markup or instructions.
7. Record the operator, timestamp, source filename, and import counts in the audit log; do not store uploaded files or chatbot credentials.

**Acceptance:** valid imports preview and commit once; malformed files, oversized files, invalid rows, and duplicate conflicts are reported without partial writes.

## Phase 6 — Build the response endpoint and retrieval

1. Add a public, rate-limited message endpoint (for example `POST /chatbot/message`) with bounded request and response sizes.
2. Retrieve only published knowledge and active public products/services. Do not query authenticated or transactional records.
3. Use a server-side provider adapter with timeouts, a conservative context limit, and configurable spending/request limits.
4. Instruct the model to answer only from supplied project knowledge, distinguish unknown information, resist instructions embedded in customer messages or imported knowledge, and link to relevant public pages.
5. Return the configured fallback and Contact link when the provider is unavailable, the answer is unsupported, or confidence is low. Never fabricate a successful answer when the service fails.
6. Disclose before sending messages to an external AI provider that chat text is processed by that provider; minimize submitted content and do not send account or payment data.

**Acceptance:** the endpoint handles valid questions, unknown questions, malformed requests, CSRF failures, and session rate limits without exposing secrets or private data. Provider timeout behavior is implemented but awaits a configured provider test.

## Phase 7 — Replace the home-page contact shortcut with the chat widget

1. Replace the `.support-button` contact link with an accessible button that opens/closes a compact chat panel.
2. Add labeled message input, send/disabled/loading states, readable assistant/customer messages, keyboard operation, focus handling, and mobile layouts.
3. Show the privacy/provider notice before first message when an external provider is used.
4. Render assistant responses as text; allow only application-generated, validated links to public pages.
5. Keep a persistent Contact option and clear fallback if JavaScript or the chatbot service is unavailable.
6. Do not place provider credentials in browser JavaScript or expose internal prompts.

**Acceptance:** the widget is usable on desktop and mobile, accessible by keyboard/screen reader, and degrades cleanly to the existing Contact page.

## Phase 8 — Verify, deploy, and maintain

1. Test schema migration and seeds using a disposable test database, not the configured customer database.
2. Test CRUD authorization, CSRF, validation, archive/restore, CSV/JSON preview/rollback, and audit events.
3. Test grounded answers against project facts, unknown questions, prompt-injection attempts, unsafe HTML, sensitive-data requests, provider outages, and rate limits.
4. Test desktop/mobile widget behavior, screen-reader labels, long messages, network failures, and links to current public routes.
5. Document migration/seed commands, provider environment variables, privacy disclosure, import formats, backup/restore, and how to disable the bot.
6. Apply migration and seed data as a controlled deployment step; run the no-write admin test before launch, and configure any provider secret only through the server environment.
7. Review published knowledge and provider usage regularly; provide a clear disable switch and a process for correcting outdated answers.

**Local validation completed:** disposable-database migration and repeat-seed checks; FAQ and live catalog retrieval; admin settings/knowledge CRUD, archive/restore, audit logging, import commit and transaction rollback; public endpoint success, CSRF, input-size and rate-limit checks; homepage widget interaction; and desktop/mobile panel bounds. The schema and starter seed were then applied to the configured local `thdh` database. External-provider timeouts and provider-retention behavior remain pending because no provider is configured.

**Acceptance:** documented rollout and rollback work; local curated knowledge is available by default, while external AI remains inactive until its environment settings and customer-consent notice are configured.

## Proposed implementation boundaries

- Store settings and approved Q&A in MySQL; keep provider credentials in environment secrets.
- Use live public catalog/service content alongside curated Q&A so changing records do not require reseeding.
- Keep chat stateless by default; add transcript retention only after explicit privacy and retention decisions.
- Reuse existing admin authorization, CSRF, audit, routing, and SQL migration conventions.
- Apply incremental chatbot migrations only after testing them against a disposable database; the local migration and starter seed have now been applied.

## Decisions needed before implementation

- [x] Approve the provider-neutral adapter; local knowledge retrieval is the default.
- [x] Confirm that transcripts are not stored.
- [x] Confirm both administrator roles can maintain chatbot settings and knowledge.
- [x] Confirm the fallback path remains the existing Contact form.

