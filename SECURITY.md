# Security Policy

## Purpose

This repository is intended for software that may eventually process highly sensitive healthcare information. Security requirements therefore apply from the first development milestone, even before production patient data is introduced.

This file defines repository-level security expectations. It is not a substitute for a production security program, threat model, regulatory assessment, incident-response plan, or penetration test.

## Supported Versions

Until the first production release, only the current `main` branch and active Version 1 release candidates are supported.

After stable releases begin, supported versions must be listed here explicitly.

## Reporting a Vulnerability

Do not disclose suspected vulnerabilities through a public issue.

Before a public repository is launched, configure a private vulnerability-reporting channel such as GitHub Private Vulnerability Reporting or a dedicated security contact and update this section with the approved process.

Never include real patient information, production credentials, access tokens, or unredacted production logs in a vulnerability report.

## Repository Security Requirements

The repository must not contain:

- production credentials;
- API keys;
- private encryption/signing keys;
- real patient records;
- real medical-record numbers tied to real people;
- production database dumps;
- unredacted support exports;
- secrets copied into documentation or test fixtures.

Use secret scanning in CI/repository hosting.

## Sensitive Data

Development and automated tests must use synthetic healthcare data.

Sensitive healthcare data must not be placed in:

- source-control fixtures;
- URLs/query strings unless a reviewed protocol requires it;
- ordinary debug logs;
- browser analytics events by default;
- exception-reporting payloads without approved redaction;
- CI artifacts without explicit protection and retention policy.

## Authentication

For the first-party Angular SPA, the preferred baseline is Laravel Sanctum's session/cookie authentication.

Requirements for deployed environments include:

- HTTPS;
- secure cookies;
- HTTP-only authentication cookies;
- CSRF protection;
- explicit session expiration;
- session revocation;
- MFA for privileged access when identity capabilities mature.

Do not use browser `localStorage` as the primary home for long-lived first-party authentication credentials.

## Authorization

All protected authorization is enforced by the Laravel backend.

Frontend route guards and hidden controls improve UX but are not security boundaries.

New protected resources require negative authorization tests, including cross-organization/tenant scenarios.

## Audit

Sensitive healthcare operations must be considered for structured audit.

Audit records are separate from normal application logs and should capture relevant actor, patient/resource context, action, outcome, timestamp, and correlation information.

Security-relevant authorization denials should be auditable where appropriate.

## Dependencies

Before production release:

- enable automated dependency updates;
- review known vulnerabilities;
- define patch timelines by severity;
- pin production container/image versions or digests according to deployment policy;
- maintain an inventory/SBOM appropriate to the deployment environment.

## Encryption

Production deployments should use encryption in transit and at rest.

Keys and secrets should use a managed secret/key-management service rather than repository files.

## Incident Response

Before processing real healthcare information, establish:

- security contacts;
- incident classification;
- escalation paths;
- log/evidence retention;
- customer notification workflow;
- jurisdiction-specific breach-assessment process;
- credential/key rotation procedures;
- post-incident review.

## Security Release Blockers

A release must be blocked for:

- known authorization bypass;
- cross-tenant healthcare data access;
- committed production secret;
- real patient data committed to source;
- unresolved critical/high-severity issue without formally accepted risk;
- inability to restore required backups;
- silent loss or corruption of protected clinical data.
