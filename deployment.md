# Deployment

1. Point the web document root to `public/` when supported. Keep application source, `.env`, SQL files, and private storage outside the public document root.
2. Create the production database and a restricted database user, then import `database/migrations/001_initial_schema.sql`, `database/seeders/001_seed.sql`, `database/migrations/002_chatbot_schema.sql`, and `database/seeders/002_chatbot_seed.sql` in that order. For an existing installation, apply only the two `002_chatbot_*` files. The chatbot migration creates only its two new tables; its seed uses stable keys and does not overwrite existing records.
3. Copy `.env.example` to `.env` outside public access and set production database credentials, `APP_ENV=production`, `APP_DEBUG=false`, and the correct `APP_URL`. Never upload a development `.env`.
4. Configure HTTPS and Apache rewrite rules.
5. Protect `.env`, `storage/`, `database/`, `bootstrap/`, `app/`, and `resources/`. The public `uploads/` directory contains product display images only; preserve its `.htaccess` rules and allow the PHP worker to write to `public/uploads/products/` (or the deployed public equivalent). Do not enable script execution in upload directories.
6. Configure PayPal sandbox/live credentials only in the environment.
7. Configure SMTP only after account setup.
8. Create the first `super_admin` from the project root using the trusted server's PHP CLI: `php install-admin.php --role=super_admin`. Enter its password only in the hidden interactive prompt; do not put credentials in the command, SQL, or deployment notes.
9. Create a standard administrator, if needed, by running `php install-admin.php --role=administrator` after the super administrator exists. These commands require CLI/database access and must not be run through a browser.
10. Schedule backups and secure private downloads outside `public/`.

Product image uploads are limited to JPEG, PNG, and WebP files up to 5 MB. Uploaded files receive generated names and are stored under the public uploads directory; include that directory in public asset deployment and backups.

The chatbot works with curated local answers and live public product/service details without an AI-provider key. Do not store chat transcripts; the public message endpoint keeps only a short-lived per-session rate-limit counter. To enable an external OpenAI-compatible chat-completions service, set `CHATBOT_API_URL` to its full chat-completions endpoint, plus `CHATBOT_API_KEY` and `CHATBOT_MODEL`, in the server environment (and optionally `CHATBOT_API_TIMEOUT`, 3–30 seconds). Use HTTPS except for a loopback development endpoint. The widget discloses provider processing and requires customer consent before sending message text; never place provider credentials in database fields or browser code. Administrators can review and disable the bot at `/admin/chatbot`.

For complete XAMPP commands and account-role details, see [README.md](README.md#local-setup-windowsxampp).
