# Exact source-plugin profile with synthetic data

Extended mode: `python3 tests/integration/wordpress-proposals-profile/run.py --hosting` also
verifies/copies the original MU plugins and themes. A separate networkless SQL-only container
imports the verified dump to select only stylesheet/template; no source SQL enters the
application database, no source wp-config executes. Metadata container has no host mounts
and stops before the application starts. Active theme is Twenty Twenty-Four1.6, two Hostinger
MU plugins, no source drop-ins. The theme and every MU main file must be included and match
before/after the suites. This mode passed303 checks (288+15), with41 local unit tests.
See docs/qa/MCP_PROPOSALS_HOSTING_COMPONENTS_2026-10-22.md. Statements below about excluding
themes/MU and source SQL describe the original default mode only.

Run `python3 tests/integration/wordpress-proposals-profile/run.py` only with the verified
private restoration retained at the path recorded in the runner. This does not run SSH.
Do not copy private plugin files, logs or the backup manifest into Git or chat.

The runner verifies18 plugin versions/activation states against the sanitized source profile,
then hashes all9590 source plugin files against the private backup manifest. Missing, extra,
changed, linked and special files are rejected. Source plugin tree is mounted read-only into
seed only; no source SQL, wp-config, MU plugins, theme or root drop-ins are mounted/copied.
Plugins are copied into tmpfs, chowned to the synthetic runtime user and activated according
to the observed profile. An additional rc.2 candidate is checked against ZIP/manifest/source.

Uses cached images, a unique fresh project, internal network without host ports, HTTP/mail
guard and disabled cron/updaters/file modifications. Only internal HTTP requests to the
wordpress service are allowed. HTTPS context is simulated; this does not test public TLS.
Raw command errors/warnings remain in private logs. Only synthetic test results are printed.
All containers in this new project are stopped in finally; no prune/reset/down-v or deletions.

Requires8GiB free. Source plugins160287119B already exist in the private restoration; no
additional host backup or plugin download. Docker tmpfs/RAM/swap/layers can add storage use.
Do not repeat completed suites without new changes/failures or another unresolved concern.

Success:288 checks (67 framework,44 history,37 quotas,86 HTTP/MCP,54 GraphQL/ACF), including
six independent-worker races. Before/after inventory asserts all18 source versions,13 active
main files included,5 inactive main files excluded, plus only the candidate active addition.
Seven new local verifier tests cover hashes, file sets, state/version drift, symlinks and FIFO.

This accepts the restored plugin code with fresh synthetic options/data and a trusted fixture.
Vendor branches gated by CLI/local/hosting detection or missing source options may not execute.
It does not establish acceptance of source MU/theme/drop-ins, provider configuration or live TLS.
