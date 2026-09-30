# Security Policy

## Reporting a vulnerability

Please **do not** open a public GitHub issue for a security vulnerability.

Instead, report it privately via [GitHub's private vulnerability reporting](https://github.com/istiyakamin/laradantic/security/advisories/new) for this repository, or by emailing the maintainer directly if that's unavailable.

Include:

- A description of the vulnerability and its potential impact
- Steps to reproduce (a minimal code sample is ideal)
- The affected version(s)

We'll acknowledge receipt within a few days and aim to have a fix or mitigation plan communicated back to you promptly. Please allow a reasonable disclosure window before making any details public.

## Supported versions

This package is pre-1.0 (`0.x`). Only the latest released `0.x` version is supported with security fixes; there is no long-term support for older `0.x` releases before `1.0.0`.

## Scope

See [docs/security.md](docs/security.md) for the design principles this package follows (explicit tool execution, confirmation boundaries, never logging API keys, etc.) - a report that the package *behaves as documented* there isn't a vulnerability in itself, but a report that it silently violates one of those documented guarantees absolutely is.

Out of scope: vulnerabilities in Laravel itself, in Guzzle, or in an AI provider's own API - please report those upstream instead.
