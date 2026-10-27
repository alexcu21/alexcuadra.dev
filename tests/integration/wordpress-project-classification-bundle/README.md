# Full-profile classification bundle

Build deterministic ZIPs with `python3 scripts/package-project-classification-bundle.py`; rc.2 and the local classification prototype stay sealed. Uses the existing full-profile runner and its verified code-only cache. No source SQL/config/accounts or real proposal imported.

Run with a new own BLOG_SITE_PROFILE_PROJECT=alexcuadra-blog-site-profile-...:

- `python3 tests/integration/wordpress-blog-proposals-site-profile/run.py --release bundle-upgrade`: synthetic retained proposal1/v1, eight upgrade preflights, backup rc.2, coordinated ZIP installs, read/get/replay, rc.3 recovery of rc.2 artifact, rollback and exact source/role/capability preservation.
- `--release bundle-regression`:57classification native+22HTTP+72proposal native+34fault+43HTTP+29contracts.
- `--release bundle-recovery`:39recovery checks and4CLI paths with both new packages active.
- `/opt/homebrew/bin/php tests/integration/wordpress-project-classification-bundle/guard-check.php`:14package site/local/production opt-in guard cases.

Synthetic taxonomies are stored via the native ACF taxonomy API and Polylang options, avoiding an extra MU or new unpinned registration fixture in the measured profile. The original safety MU remains byte-identical. HTTP remains internal only; mail/egress blocked, no host ports. Own projects stopped in finally, no prune.

The upgrade preflight must use eval-file --use-include: the default WP-CLI path-constant rewriter exhausts PCRE JIT stack on its embedded policy and otherwise silently yields no marker. The driver rejects missing markers, validates exit status/ready/check counts and records actual file hashes. No PCRE setting is relaxed.

Do not restart the last container mounting tmpfs wp-data/private data midway through this test: unmounting destroys those synthetic fixtures. No Apache bootstrap occurs before the version transition; the separate HTTP suite loads the final packages normally. This is not evidence about hosting OPcache behavior.

[Operator procedure](../../../docs/WORDPRESS_MCP_PROJECT_CLASSIFICATION_PACKAGE.md), [QA](../../../docs/qa/MCP_PROJECT_CLASSIFICATION_BUNDLE_2026-10-27.md).
