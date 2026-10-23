# Private restoration checks

Run through `scripts/editorial-private-restore.py` with an archive path and pinned SHA256.
Never commit an archive, extracted files, manifest, SQL or private process log.

The coordinator verifies the archive, creates a private snapshot and extracts regular files
outside the repository. Every selected file and the SQL are hashed again. Source PHP,
configuration, plugins, themes, MU plugins and drop-ins are never executed.

All containers use `network_mode: none`, no published ports, cached digest-pinned images
and a shared Unix database socket. Database networking is disabled as well. A fresh
reference core and configuration start with source plugins/themes skipped and a trusted
HTTP/mail guard. Cron and file modifications are disabled. The archive and scratch files
remain private; only this new project's containers are stopped. No deletion is scheduled.
Database/runtime volumes use tmpfs: they are not durable recovery copies.

`inspect.php` compares all 37 table schemas to the source export manifest, checks table
health and computes private row counts/checksums. A local SQL export/import roundtrip
must match all schemas, counts and checksums before core bootstrap. Source export did
not capture live per-row checksums, so this does not assert a live source row comparison.
Bootstrap changes, if any, are reported separately and do not alter the original archive.

This establishes archive/file/database restoration and isolated reference-core bootstrap.
It does not establish acceptance of the original active plugins, hosting configuration,
public TLS, outbound integrations, or the proposals installation candidate.
