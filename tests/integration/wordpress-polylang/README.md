# WordPress / Polylang security lab

Run from the repository root with Docker Desktop active:

```sh
sh tests/integration/wordpress-polylang/run.sh
```

This downloads exact plugin versions into an isolated WordPress instance, bound only to `127.0.0.1:8087`. Images are pinned by digest. Database and WordPress volumes use bounded tmpfs storage; they are ephemeral and use up to about 1 GB RAM in addition to container processes. No production environment file, source CMS data, media or credentials are mounted. Credentials in setup commands are synthetic and local only.

Requires internet access for official plugin downloads and the tagged connector ZIP. Reports go to `docs/wordpress/POLYLANG_LAB_RESULTS.json` and `POLYLANG_PASSWORD_CONTROL_RESULTS.json`. Generated lab credentials go to `.wrangler/polylang-lab.json`, ignored by Git. Do not publish that file. Fixtures are code-registered CPT/ACF groups, not a clone of the production database or an end-to-end test of the ACF UI.

The runner currently returns nonzero for four password-protected ACF policy checks. The control test records the same behavior with the connector inactive: these failures must not be represented as a confirmed connector vulnerability. The matrix also checks the portfolio's existing public-content predicate against live results. See `docs/wordpress/POLYLANG_LAB_REPORT.md` for interpretation and limits.

After reviewing results, stop only this lab:

```sh
docker compose -f tests/integration/wordpress-polylang/compose.yaml down --volumes
```

This removes this project's disposable fixture volumes; it must not be replaced with `docker system prune`. Stopping all consumers of a tmpfs volume loses its fixture data. Run again to create fresh fixtures. The normal unit test/CI suite does not start Docker or use this integration lab.
