# Admin dashboard and customer account upgrade roadmap

This draft is based on the current PHP routes, account views, admin dashboard, and MySQL schema. The application already has customer registration/login and tables for products, customers, vendors, orders, services, inquiries, articles, and newsletter subscriptions, but most admin screens are placeholders.

## Phase 1 — Confirm scope and data-safety rules

- Implement working management for products, product categories, vendors, customers/staff, service inquiries, services, blog posts, and newsletter subscriptions.
- Keep order/payment transaction history read-only; do not edit or delete payment payloads from the dashboard.
- Archive products and deactivate other records instead of permanently deleting them. Never display or edit password hashes.
- Restrict staff-role changes to super administrators; standard administrators may manage customer profiles but cannot promote accounts.

**Acceptance:** confirmed by the user before building write operations.

## Phase 2 — Customer-facing account experience

- Add visible Sign in / Create account links to desktop and mobile public navigation.
- Improve the customer dashboard with editable profile details and account-security actions.
- Ensure customer accounts can edit only their own profile and cannot access admin routes.
- Keep password changes verified, hashed, and protected by the existing CSRF mechanism.

**Acceptance:** a customer can register, sign in, update their own permitted details, and sign out; their session cannot access administrator data.

## Phase 3 — Professional admin foundation

- Replace the single placeholder dashboard with a consistent admin shell, navigation, summaries, status labels, and responsive tables/forms.
- Retain server-side authorization and CSRF checks on every write action.
- Add clear success/error feedback and validate every database-bound value.

**Acceptance:** admin routes are usable on desktop/mobile and unauthorized users cannot perform admin reads or writes.

## Phase 4 — Catalog and media management

- Provide product create/read/update/archive workflows with category, slug, descriptions, price/currency, fulfillment, checkout URL, featured state, and draft/published status.
- Support either a validated image URL or a validated local image upload, with safe filenames and upload limits.
- Add category/vendor management if included in the confirmed scope.

**Acceptance:** an administrator can save a draft, publish/unpublish it, update its details/image, and archive it without breaking existing product URLs or order references.

## Phase 5 — Customer and operations management

- List/search customer profiles and allow the agreed profile/status actions without exposing credentials.
- Add the agreed customer role-management boundary.
- Add inquiry/order views and status actions only where appropriate; preserve transactional data.

**Acceptance:** customer profile changes are auditable, role changes are restricted, and order/payment history remains intact.

## Phase 6 — Other content management

- Add CRUD for other confirmed database-backed public content (services, articles, newsletter records, and vendors/categories as applicable).
- Keep content escaping, publication status, and public listing behavior consistent with existing routes.

**Acceptance:** changes made in the admin UI appear on the corresponding public pages and hidden/draft content remains unpublished.

## Phase 7 — Verification and handoff

- Add focused checks for authorization, CSRF, input validation, upload rejection, status transitions, and customer self-service.
- Run PHP syntax checks and browser smoke tests for the admin and customer workflows.
- Document access paths, account setup, permitted role actions, and deployment storage requirements.
- Update this roadmap with completion status and any out-of-scope areas.

**Acceptance:** each confirmed workflow is verified end-to-end and the documentation matches the implementation.

## Progress

- [x] Phase 1 — Baseline reviewed; user confirmed management scope, role boundary, and archive policy.
- [x] Phase 2 — Customer account pages, self-service profile/password actions, and public sign-in/create-account links implemented.
- [x] Phase 3 — Responsive admin shell, role checks, CSRF-protected write routes, validation, feedback, and audit logging implemented.
- [x] Phase 4 — Product/category/vendor management, publication status, archive/restore, and URL/upload image handling implemented.
- [x] Phase 5 — Customer/staff management and read-only order/payment views implemented; only super administrators can change staff roles.
- [x] Phase 6 — Inquiry, service, article, and newsletter management implemented.
- [ ] Phase 7 — Implementation checks below pass, but authenticated write workflows and file uploads still need isolated end-to-end verification.

### Verification record

- PHP syntax checks pass for all 43 PHP files under `app/`, `bootstrap/`, `resources/views/`, and `public/`, plus the project-root PHP installer.
- With a configured local database, read-only rendering passed for the dashboard and all 10 admin management sections; each now renders through the admin document layout with a stylesheet link.
- Anonymous requests to all admin sections are login-gated, and `/admin/products` redirects to the project-local `/TimeHealersDigitalHub/login`.
- Both the clean ID-based product detail URL and the existing malformed-slug URL render successfully; the service detail route also returns successfully. The public stylesheet responds with HTTP 200.
- Browser checks confirm mobile navigation exposes Sign in and Create account without horizontal overflow, the registration form enforces a 12-character password minimum, and both registration and newsletter forms include their CSRF fields.
- A newsletter POST without a valid CSRF token returns HTTP 403 with a request-verification message before persistence.
- The configured database was not changed during verification. Do not treat POST/CRUD, password update, role-change, or real file-upload operations as end-to-end tested yet; verify them against an isolated test database before production.
