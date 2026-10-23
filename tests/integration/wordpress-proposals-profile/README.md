# Exact source-plugin profile with synthetic data

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
