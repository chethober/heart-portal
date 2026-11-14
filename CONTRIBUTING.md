# Contributing to Heart Portal

Thanks for helping improve Heart Portal! This guide explains how to get set up and what we look
for in a pull request.

## Ground rules

- **No real patient data, ever.** Not in issues, pull requests, screenshots, logs, test fixtures
  or database dumps. Use the factories and obviously fake values.
- Security problems go through [SECURITY.md](SECURITY.md), not public issues.
- Be kind — this project follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## Getting started

Follow the *Quick start* in [README.md](README.md), then create the test database:

```sh
docker compose exec pgsql createdb -U echo echo_test
```

## Making a change

1. Open an issue first for anything larger than a small fix, so we can agree on the approach.
2. Create a branch from `main`.
3. Keep changes focused; match the style of the surrounding code.
4. Add or update tests. In particular:
   - any new model holding patient or clinical data must use the `BelongsToHospital` trait, and
     a test must prove another hospital cannot read or change it;
   - changes to `EchoCalculations` need unit tests with the clinical reference values they rely
     on, and a citation (guideline/paper) in the PR description;
   - new clinical form fields must be added to `config/echo.php` (and a migration).
5. Make sure all checks pass:

   ```sh
   docker compose exec app ./vendor/bin/pest
   docker compose exec app ./vendor/bin/pint --test
   docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=1G
   ```

6. Open a pull request and fill in the template.

## Commit messages

Use the imperative mood and explain *why* in the body when it isn't obvious, e.g.
`Grade RV size from RVOT when basal diameter is missing`.

## License of contributions

By contributing you agree that your contributions are licensed under the project's
[MIT](LICENSE) license.
