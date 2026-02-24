# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Authors History is a generic plugin for OJS (Open Journal Systems) and OPS (Open Preprint Systems) 3.4+. It adds an "Authors History" tab to the submission workflow view that lists all submissions from each contributor, allowing editors/moderators to quickly see other works by the same authors.

The plugin lives at `plugins/generic/authorsHistory` within an OJS/OPS installation.

## Architecture

**Plugin entry point:** `AuthorsHistoryPlugin.php` — extends `PKP\plugins\GenericPlugin`. Hooks into `Template::Workflow::Publication` to inject the Authors History tab into the workflow UI.

**Data layer:** `classes/AuthorsHistoryDAO.php` — extends `PKP\db\DAO`. Finds similar authors across submissions by matching on ORCID and/or email, using Laravel's query builder (`Illuminate\Support\Facades\DB`). Key lookup strategy:
- First searches by email; if results exceed `itemsPerPage`, narrows by given name + email
- Then merges in results by ORCID
- Builds lightweight `Submission`/`Publication` objects from raw DB queries (joins across `authors`, `author_settings`, `publications`, `submissions`, `publication_settings` tables)

**Frontend:** Smarty template (`templates/authorsHistory.tpl`) with client-side pagination via `templates/pagination.js`. Styling in `styles/authorsHistory.css`.

**Namespace:** `APP\plugins\generic\authorsHistory` (and `APP\plugins\generic\authorsHistory\classes` for DAO).

## Testing

### Unit Tests (PHPUnit)

Tests are in `tests/AuthorsHistoryDAOTest.php` and extend `PKP\tests\DatabaseTestCase` (requires a running OJS database).

Run from the OJS root directory:

```bash
php lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration lib/pkp/tests/phpunit.xml -v plugins/generic/authorsHistory/tests/AuthorsHistoryDAOTest.php
```

### Cypress E2E Tests

Tests are in `cypress/tests/`. They run against a full OJS/OPS instance:
- `Test01_pluginSetup.cy.js` — enables the plugin
- `Test02_authorHistory.cy.js` — creates a submission, publishes it, and verifies the history tab shows correct data

Custom Cypress commands are in `cypress/support/commands.js`.

## CI/CD

- **GitLab CI** (`.gitlab-ci.yml`): runs unit tests and Cypress tests for both OJS and OPS using shared pipeline templates
- **GitHub Actions** (`.github/workflows/generate-package.yml`): on version tags (`v*`), validates `version.xml` and creates a release with a `.tar.gz` package

## Localization

Translation files are in `locale/` using `.po` format. Supported locales: en, es, pt_BR, ar, ru.
