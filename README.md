# Astro Theme: Portfolio

```sh
npm create astro@latest -- --template portfolio
```

![portfolio](http://alexcuadra.dev/wp-content/uploads/2024/12/alexcuadra_FI.png)

## 🧞 Commands

All commands are run from the root of the project, from a terminal:

| Command                   | Action                                           |
| :------------------------ | :----------------------------------------------- |
| `npm install`             | Installs dependencies                            |
| `npm run dev`             | Starts local dev server at `localhost:4321`      |
| `npm run build`           | Build your production site to `./dist/`          |
| `npm run preview`         | Preview your build locally, before deploying     |
| `npm run astro ...`       | Run CLI commands like `astro add`, `astro check` |
| `npm run astro -- --help` | Get help using the Astro CLI                     |

## 👀 Want to learn more?

Feel free to check [our documentation](https://docs.astro.build) or jump into our [Discord server](https://astro.build/chat).

## Validation and redesign

- `npm test`: deterministic content-client and project-ordering tests using Node.js 22; no live CMS or credentials required.
- `npm run check`: Astro and TypeScript diagnostics.
- `npm run build`: diagnostics and Cloudflare SSR build. A successful build does not verify live CMS access.
- `.github/workflows/ci.yml`: runs tests and build on pull requests and pushes to main. No deployment or production secrets.
- [Redesign plan](REDESIGN_PLAN.md): proposal analysis, free ACF content model, server-only GraphQL authentication, migration and acceptance criteria.

## Workers staging migration

Work in progress on `codex/workers-upgrade`; main remains configured for the existing Pages deployment. Requires Node from `.nvmrc`.

Run `npm ci`, `npm test`, `npm run build`, then `npm run preview -- --host 127.0.0.1 --port 4321`. With preview running, `npm run test:smoke` checks seven rendered routes using the live published CMS.

See [Workers staging setup](WORKERS_STAGING.md) and [execution status](EXECUTION_STATUS.md). Do not deploy this branch to Pages or merge into main before the hosting transition is validated.
