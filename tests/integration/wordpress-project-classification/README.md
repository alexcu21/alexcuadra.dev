# Project classification integration

Requires local Docker and cached, hash-pinned Polylang3.9.1/MCP Adapter0.7.1 ZIPs already used by blog read labs. No download or hosting connection. Uses fresh synthetic WordPress7.1.3/PHP8.5/MariaDB11.8 images pinned in the reused base compose; a dedicated internal network without host ports.

```sh
PROJECT_CLASSIFICATION_LAB_PROJECT=alexcuadra-project-classification-next-20261027 python3 tests/integration/wordpress-project-classification/run.py
```

Use a new own project name for a justified new run. Existing installed databases are rejected. The fixture registers project and its two translated taxonomies, blocks mail/outbound HTTP except the internal WordPress endpoint and loads only the local candidate. No source SQL/configuration/accounts imported.

57 native WordPress/Polylang/adapter cases plus22 real HTTP cases. HTTP uses synthetic application passwords/sessions, verifies authorization/EN/ES/DTOs/cursor/private exclusions/revocation and removes scopes/credentials. Source table fingerprints are compared during reads; deliberate fixture changes are not misrepresented as reader mutations.

```sh
/opt/homebrew/bin/php tests/integration/wordpress-project-classification/guard-check.php
```

Nine subprocess guard cases check registration and runtime permission, including missing/false flag, wrong environment/database/URLs, multisite and absent WordPress. No real WordPress required for those gate checks.

The runner validates a single report marker, unique complete case counts, transport and cleanup flags; it stops only its own compose project in finally, no prune. [Receipt](../../../docs/qa/project-classification-reader-2026-10-27.json) and [QA](../../../docs/qa/MCP_PROJECT_CLASSIFICATION_READER_2026-10-27.md). This lab does not certify the full17-plugin hosting profile or compatibility with the existing private proposal guard.
