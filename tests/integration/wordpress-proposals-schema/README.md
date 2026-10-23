# Synthetic schema acceptance

Run `python3 tests/integration/wordpress-proposals-schema/run.py` with local Docker and
cached digest-pinned PHP8.5/MariaDB11.8 images. No SSH or backup archive is consumed.
Requires8GiB free. New uniquely named project; never reset/prune previous test resources.

All services have no network and communicate through a Unix socket. Fresh reference
WordPress is seeded after mounting the CLI tmpfs volume, then a synthetic site is installed.
The fixture requires AC_SCHEMA_TEST=true, schema_ prefix and the exact local database/socket
before dropping its own fixture tables. No source/private configuration is mounted.

Mutations exercise real DDL and preserve a synthetic row in each table. Each incompatible
schema must be rejected with a stale readiness marker removed and identical schema/data
afterward. Expected SQL faults are injected by a WordPress query filter, removed after
each case. An outer transaction must remain open after rejection. Separate stub processes
check loading/registration without a database object. The runner stops only its new project
in finally; tmpfs is ephemeral and not a recovery archive.

Current result:208 SQL checks,95 boundary checks in9 processes. No HTTP/GraphQL/full hosting
profile claim. See docs/qa/MCP_PROPOSALS_SCHEMA_2026-10-22.md.
