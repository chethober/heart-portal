# Heart Portal platform — agent notes

- Read README.md for setup, tenancy rules and commands. Run PHP tooling inside Docker:
  `docker compose exec -T app <command>` (host PHP lacks pdo_pgsql; Composer is not installed on the host).
- Any new model holding patient/clinical data must `use BelongsToHospital`, and tests must prove
  another hospital cannot read or modify it.
- Before finishing: `pest`, `pint --test`, `phpstan analyse --memory-limit=1G` must all pass.
