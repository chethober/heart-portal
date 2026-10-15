# Security policy

Heart Portal stores health data, so we take security reports seriously.

## Reporting a vulnerability

**Please do not open a public issue.** Report privately through GitHub:
[**Report a vulnerability**](https://github.com/chethober/heart-portal/security/advisories/new)
(Security → Advisories → *Report a vulnerability*).

Include:

- a description of the issue and its impact (for example: cross-hospital data access,
  authentication bypass, injection);
- steps to reproduce, ideally against a local Docker setup with the demo data;
- the commit or version you tested.

Never include real patient data in a report.

We aim to acknowledge reports within 3 working days and to agree on a disclosure timeline with
you. We are happy to credit reporters in the release notes.

## Supported versions

Only the latest release on the `main` branch receives security fixes.

## Scope

In scope: this repository's code, including tenant isolation, authorization, 2FA, audit log
integrity and report rendering. Out of scope: vulnerabilities in a specific deployment's
infrastructure that are not caused by this code, and social-engineering attacks.
