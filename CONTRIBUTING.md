# Contributing

## Getting set up

```bash
git clone https://github.com/istiyakamin/laradantic.git
cd laradantic
composer install
```

## Before opening a PR

```bash
composer test   # Pest - every test must pass
composer stan    # PHPStan level 8 - no errors
composer pint    # Laravel Pint - run this to auto-fix style, then commit the result
```

CI runs the same three checks across the PHP 8.2–8.4 × Laravel 10–12 matrix (see `.github/workflows/tests.yml`) - a change that only works on one combination will fail CI even if it passes locally.

## Guidelines

- **Work in small, focused changes.** This package was built milestone by milestone (see the README's roadmap) precisely to avoid one PR trying to do everything at once.
- **Add tests for every new public behavior**, and for every bug fix (a regression test that fails before your fix and passes after it).
- **No real network calls in tests, ever.** Provider-facing tests use `Http::fake()` - see [docs/testing.md](docs/testing.md).
- **Match the existing style**: strict types (`declare(strict_types=1)`), constructor property promotion, `final` classes unless a class is genuinely meant to be extended (like `Schema` and `Tool`), and no doc-comments that just restate what a well-named method already says.
- **Don't add speculative features.** If something isn't demonstrated by an existing example or asked for in an issue, open an issue to discuss it first rather than sending a large speculative PR.
- **Public API stability** (pre-1.0): breaking changes to `Schema`, `AI`, `AIProvider`, `AIResponse`, `Tool`, `ToolCall`, `ToolResult`, the `Exceptions\*` hierarchy, or the `Schema\Attributes\*` classes should be called out explicitly in the PR description and in `CHANGELOG.md`, even though semver doesn't require it below `1.0.0`.

## Reporting bugs / requesting features

Open a GitHub issue. For a bug report, include: the LaraDantic version, PHP/Laravel versions, a minimal reproduction (ideally as a failing Pest test), and what you expected vs. what happened.

## Security issues

Do not open a public issue - see [SECURITY.md](SECURITY.md).
