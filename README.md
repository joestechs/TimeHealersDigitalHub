# Time Healers Digital Hub

Native PHP/MySQL digital marketplace application.

## Requirements

- PHP 8.2 or newer
- MySQL 8 or MariaDB 10.6+
- Apache with `mod_rewrite`
- Composer 2
- PHP extensions `pdo_mysql`, `json`, and `mbstring`; enable `curl` only if you configure an external chatbot provider

## Local setup (Windows/XAMPP)

Start Apache and MySQL from the XAMPP Control Panel. Open **Command Prompt** (not a MySQL prompt), then run:

```bat
cd /d C:\xampp\htdocs\TimeHealersDigitalHub
if not exist .env copy .env.example .env
notepad .env
```

In `.env`, use the database settings for your local XAMPP MySQL instance. The defaults in `.env.example` target a local database named `thdh` and the default XAMPP `root` account; set a password if your local MySQL account has one. Keep `.env` out of public access and never replace an existing `.env` blindly.

Import the schema and starter service/category data:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -p < database\migrations\001_initial_schema.sql
C:\xampp\mysql\bin\mysql.exe -u root -p < database\seeders\001_seed.sql
C:\xampp\mysql\bin\mysql.exe -u root -p < database\migrations\002_chatbot_schema.sql
C:\xampp\mysql\bin\mysql.exe -u root -p < database\seeders\002_chatbot_seed.sql
```

Enter the MySQL password when prompted. If the local `root` account has no password, remove `-p` from all four commands. The schema creates and selects the `thdh` database; keep `DB_DATABASE=thdh` in `.env`, or change both the SQL and configuration together.

Install PHP dependencies and create the first administrator:

```bat
composer install
C:\xampp\php\php.exe install-admin.php
```

The CLI prompts for a name, email, and password. Password input is hidden. The default role is `super_admin`; for instructions on creating another administrator, see [Administrator setup](#administrator-setup).

Open `http://localhost/TimeHealersDigitalHub/` when using the XAMPP Apache service. Alternatively, run PHP's local server from the project root:

```bat
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public public\index.php
```

Then visit `http://127.0.0.1:8000/`.

### Enable chatbot on an existing installation

From **Command Prompt** in the project root, apply the incremental schema and starter knowledge (do not rerun migration `001` on an existing installation):

```bat
cd /d C:\xampp\htdocs\TimeHealersDigitalHub
C:\xampp\mysql\bin\mysql.exe -u root -p < database\migrations\002_chatbot_schema.sql
C:\xampp\mysql\bin\mysql.exe -u root -p < database\seeders\002_chatbot_seed.sql
```

Enter the database password at each prompt; if the local MySQL `root` account has no password, remove `-p` from both commands. Confirm that `.env` uses `DB_DATABASE=thdh`. Then sign in as an administrator and open `http://localhost/TimeHealersDigitalHub/admin/chatbot`. The schema migration and seed are safe to reapply; the seed will not overwrite knowledge that has already been edited.

## Administrator setup

Create the initial super administrator only after importing the schema and configuring `.env`:

```bat
cd /d C:\xampp\htdocs\TimeHealersDigitalHub
C:\xampp\php\php.exe install-admin.php --role=super_admin
```

The script asks for account details interactively, validates the email and a 12-character minimum password, hashes the password with Argon2id, and never accepts a password as a command-line argument. On Windows, enter the password in the hidden PowerShell prompt opened by the script. Do not paste credentials into SQL, source control, or shared terminal transcripts.

After the super administrator exists, create a standard administrator (which can use the admin dashboard but is not a super administrator) by running:

```bat
C:\xampp\php\php.exe install-admin.php --role=administrator
```

Both roles can manage catalog, customer, inquiry, content, newsletter, and chatbot records. Only `super_admin` can change staff roles; `administrator` is not otherwise a granular permission tier, so grant it only to trusted staff. For a non-XAMPP PHP installation, run the same commands with `php install-admin.php` from the project root. Confirm accounts in the `users` table by checking `email`, `role`, and `status`; never select or print `password_hash`. Do not create public/default-password administrator accounts. Keep CLI/database access restricted to trusted operators.

## Access the admin dashboard

For the XAMPP project folder, open `http://localhost/TimeHealersDigitalHub/admin`. If you are not signed in, the app sends you to `http://localhost/TimeHealersDigitalHub/login`; sign in with an active account whose role is `super_admin` or `administrator` to reach the dashboard. Create the first account using the CLI steps above. A customer account cannot open the admin dashboard.

The dashboard at `/admin` manages products, categories, vendors, customer accounts, staff roles (super-admin only), service inquiries, services, Knowledge Hub articles, newsletter subscriptions, and customer-support chatbot settings and knowledge. Products can be published, archived, restored as drafts, and assigned an image URL or a JPEG/PNG/WebP upload up to 5 MB. Customer profile updates and account deactivation do not expose passwords. Orders and payment records are read-only.

Customers can use the public **Sign in** and **Create account** navigation links. Their dashboard is at `/account`; profile and password settings are at `/account/settings`, and order history is at `/account/orders`. Customers can edit their own name, email, and password. Administrators can edit customer contact details and deactivate/reactivate accounts.

The home-page support button opens the chatbot after the `002_chatbot_schema.sql` migration and seed are applied. The assistant uses published knowledge and current public products/services locally by default; customer messages are not saved as chat transcripts. In `/admin/chatbot`, administrators can manage settings and knowledge, and preview CSV/JSON imports before committing. Import fields are `question` and `answer` (required), plus optional `stable_key`, `category`, `keywords`, `link_label`, `link_path` (local public route only), `status` (defaults to draft), and `sort_order`. A missing stable key is generated from the question. Imports accept at most 100 records and 256 KB; the preview reports invalid rows and existing duplicates, which are skipped by default or updated only after an explicit selection. Archived entries can be restored as drafts. Provider credentials are optional and must remain in server environment settings, never in the dashboard or browser. If a provider is configured, the widget discloses external processing and requires customer consent before sending a message. See [CHATBOT-ROADMAP.md](CHATBOT-ROADMAP.md) for boundaries and rollout notes.

See [deployment.md](deployment.md) for production deployment and [deployment-amendment.md](deployment-amendment.md) for clean-URL deployment and smoke-test instructions.
