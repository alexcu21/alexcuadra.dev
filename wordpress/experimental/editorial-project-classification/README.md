# Public project classification: local candidate

Two read-only abilities, separate from the installed reader and the sealed blog proposal package:

- `alexcuadra/get-public-project-classification`: exact project ID/locale; assigned project categories and technologies.
- `alexcuadra/list-public-project-terms`: exact project taxonomy/locale, bounded ID cursor; only terms witnessed on public projects.

Reuses `alexcuadra_read_public_projects` plus authenticated/read permission. Never grants a scope or registers taxonomies. No generic term reader, descriptions, counts, parents, termmeta, post bodies, ACF, term creation or assignments. Uses only project_category/project_technology; rejects category/post_tag and taxonomies shared with posts.

A term translation must be reciprocal, same taxonomy, exact language, and itself assigned to a public project in that language. Draft/private/password/future/trash projects, unused terms, blog-only assignments and private-only translations are not exposed. A conflicting term language fails the whole read rather than silently dropping that assignment.

Catalog inventory is bounded to200 public projects per language and200 distinct terms per taxonomy/language; project classification max100 terms per taxonomy. Exceeding a bound returns an error, never a partial result. The cursor orders public term IDs, max20 items/page. No generic search or count endpoint.

The module is gated to explicit local flag, local environment, DB_HOST=db, exact http://wordpress home/site and single-site; permission checks revalidate this gate. It has no production admission, installable plugin header or distributable ZIP. Do not upload it to the hosting.

[Integration and guards](../../../tests/integration/wordpress-project-classification/README.md), [QA/limits](../../../docs/qa/MCP_PROJECT_CLASSIFICATION_READER_2026-10-27.md). Next: versioned packaging and coexistence testing against the exact full site profile, preserving proposal1/v1 and existing permissions/history. Activating another plugin without a coordinated proposal profile update would make rc.2 reject its profile; do not modify sealed packages or force admission.
