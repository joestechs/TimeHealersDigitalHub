# Time Healers Digital Hub upgrade roadmap

This roadmap is the working scope for the current upgrade. The phases are ordered so setup guidance and the existing architecture are understood before the public experience is changed.

## Phase 1 — Establish the baseline

- Preserve the existing native PHP/MySQL architecture and established brand message.
- Review current routes, deployment notes, administrator provisioning, front-page content, CSS, and JavaScript.
- Use the former website at <https://www.thdhub.synergize.co/> as a content reference, not as a source to copy wholesale.
- The reference page describes the six core services and business promise and includes a live analog clock in the homepage hero.

**Acceptance:** the proposed changes fit the current codebase and do not require new dependencies.

## Phase 2 — Make setup and administrator creation repeatable

- Update onboarding and deployment documentation with copy/paste Windows/XAMPP commands and the equivalent portable PHP CLI commands where appropriate.
- Explain database creation/import, environment configuration, local launch, administrator roles, and credential handling.
- Document `php install-admin.php` as the supported first-account workflow, including the requirement to run it from the project root after database setup.
- Explain how to provision any additional administrator and how to verify the account role without publishing credentials.

**Acceptance:** a new operator can follow the documentation from a clean local setup through secure `super_admin` creation without guessing commands.

## Phase 3 — Add a distinctive, accessible timepiece

- Add a restrained analog clock with smoothly moving hands and a live digital time/date display to the homepage.
- Use the visitor's local browser time; do not imply that it is an official business-hours, deadline, or countdown clock.
- Keep the feature dependency-free, responsive, accessible, and respectful of reduced-motion preferences.
- Carry the source site's language about respecting people's time into the supporting copy.

**Acceptance:** the time and date update from the browser clock, the hands track time, and the component remains usable at narrow widths and with reduced motion.

## Phase 4 — Refine the public experience and content

- Give the homepage a more editorial, classical finish using the existing navy/blue identity with warm metallic accents, consistent spacing, and clear calls to action.
- Enrich the home, about, and services content with specific outcomes and service descriptions grounded in the existing business offering.
- Preserve existing navigation, product listings, inquiry flows, and responsive layouts.

**Acceptance:** existing routes and forms remain intact; new content is clear, brand-aligned, and usable on desktop and mobile.

## Phase 5 — Validate and hand off

- Run PHP syntax checks on changed PHP files and targeted checks for the JavaScript/CSS changes where available.
- Review the final diff for unrelated edits and record any checks that cannot run in the current environment.
- Keep this roadmap as the completion checklist and update its status below.

**Acceptance:** implemented items are marked complete and validation results are reported accurately.

## Progress

- [x] Phase 1 — Baseline reviewed, including a visual check of the reference clock.
- [x] Phase 2 — Setup and administrator documentation, including commands for both supported roles.
- [x] Phase 3 — Accessible live local-time clock and date with reduced-motion support.
- [x] Phase 4 — Editorial homepage, service descriptions, and expanded about/service content.
- [x] Phase 5 — PHP/JavaScript lint, browser clock and mobile checks, and handoff.

## Validation record

- PHP lint passed for the installer, front controller, homepage layout, and updated about/service views.
- Node syntax check passed for `public/assets/js/app.js`.
- Browser smoke test confirmed the time/date and clock hands update, the mobile layout has no horizontal overflow, reduced-motion settings are honored, and the navigation toggle updates its expanded state and label.
- PHP's local server served the JS and CSS assets with their correct MIME types after adding a CLI-server-only static-file pass-through.
- Local smoke tests returned HTTP 200 for `/services`, `/about`, and `/contact`.
- XAMPP subdirectory smoke test confirmed `/TimeHealersDigitalHub/admin` redirects to `/TimeHealersDigitalHub/login`, and the login page renders there.
- No dependencies were added. Production database/admin creation was not run.
