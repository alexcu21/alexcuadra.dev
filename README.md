# alexcuadra.dev

Portfolio de Alex Cuadra, desarrollador web senior con experiencia en calidad, automatización y DevOps. El proyecto está en proceso de rediseño bilingüe (inglés y español) y migración de Cloudflare Pages a Workers.

## Stack

- Astro 7 con renderizado en servidor (SSR) y TypeScript.
- Tailwind CSS 4 mediante su integración Vite.
- Cloudflare Workers con Wrangler 4.
- WordPress en `content.alexcuadra.dev`, WPGraphQL y WPGraphQL for ACF.
- ACF gratuito para el modelo editorial previsto.

Todo el contenido editorial del rediseño se administrará en WordPress. Los mockups son referencias de diseño; sus cifras no son datos reales del portfolio.

El CPT `articles` reúne referencias a publicaciones externas y menciones; el tipo nativo `post` contiene el blog propio. El rediseño conserva esta separación aunque las maquetas no la distingan. El blog todavía no está implementado.

## Desarrollo local

Usa Node.js **22.23.2**, declarado en `.nvmrc` (requisito mínimo: 22.22.0), y npm.

```sh
nvm use
npm ci
npm run dev -- --host 127.0.0.1
```

Si no utilizas nvm, instala la versión indicada de Node con tu gestor habitual. Abre [localhost:4321](http://127.0.0.1:4321/). El servidor utiliza el runtime de Workers; las páginas necesitan acceso al CMS.

Para comprobar el build local:

```sh
npm run build
npm run preview -- --host 127.0.0.1 --port 4321
```

Detén el servidor de desarrollo antes de usar el mismo puerto para preview. Para detener el preview gestionado por Astro: `npm run astro -- preview stop`.

## Comandos

| Comando | Función |
| --- | --- |
| `npm ci` | Instala las versiones fijadas en el lockfile. |
| `npm run dev` / `npm start` | Arranca el servidor de desarrollo. |
| `npm test` | Ejecuta pruebas deterministas sin CMS ni credenciales reales. |
| `npm run check` | Revisa tipos y archivos Astro. |
| `npm run build` | Imprime revisión/versiones, valida y compila para Workers. |
| `npm run build:production` | Compila para el Worker de producción propuesto, sin desplegar. |
| `npm run preview` | Sirve el build local con el runtime de Workers. |
| `npm run test:smoke` | Comprueba el favicon y siete páginas en localhost:4321. |
| `npm run test:smoke -- https://dominio` | Ejecuta las mismas comprobaciones contra un destino explícito. |
| `npm run deploy:staging` | Compila y despliega al Worker definido en `wrangler.jsonc`; requiere acceso autorizado a Cloudflare. |

Los smoke tests usan contenido publicado del CMS y necesitan un proyecto, una categoría y un artículo disponibles. No son pruebas de carga; evítalos repetidamente mientras se investiga el límite de solicitudes. Un build correcto no garantiza disponibilidad del CMS ni funcionamiento completo en producción.

## Entornos y despliegue

| Entorno | Estado |
| --- | --- |
| Producción — [alexcuadra.dev](https://alexcuadra.dev/) | Worker `alexcuadra-dev`, entorno Wrangler `production`, conectado a `codex/workers-upgrade` durante la migración. Pages se conserva como respaldo. |
| Staging — [Workers](https://alexcuadra-dev-staging.alexcu21.workers.dev/) | Worker `alexcuadra-dev-staging`, conectado a `codex/workers-upgrade`. |

El staging está desplegado, pero su validación continúa: se han observado errores HTTP 429 y 500 procedentes del CMS. Los fallos reconocidos del CMS se convierten en 503 sin caché, con diagnósticos estructurados en Observability. Las consultas repetidas se deduplican dentro de cada petición. Home, proyectos y categorías públicos usan además una caché de datos de 60 segundos por centro de datos; cookies, autorización y previews la omiten. La política y sus límites se describen en [CMS: caché y diagnóstico](docs/CMS_CACHE.md).

Workers Builds usa `npm run build` y después `npx wrangler deploy`, con la raíz del repositorio como directorio de trabajo. El nombre del Worker debe coincidir con `wrangler.jsonc`. Las respuestas SSR bajo `workers.dev` incluyen `noindex`; esto no es control de acceso.

El dominio público ya está asociado al Worker y validado por HTTP con autenticación. Antes de fusionar en `main`, desactivar los builds automáticos del antiguo Pages y coordinar el cambio de rama del Worker de producción. La configuración de esta rama está preparada para Workers; no sirve para desplegar de nuevo en Pages.

## Pruebas y CI

Las pruebas cubren contratos del cliente GraphQL, variables, errores de autorización/HTTP/red, timeouts, contenido Home ausente, diagnósticos sin datos sensibles, manejo 503, deduplicación por petición y orden de proyectos por fecha.

[El workflow de CI](.github/workflows/ci.yml) instala con `npm ci`, ejecuta `npm test` y `npm run build` en pull requests y pushes a `main` o `codex/workers-upgrade`. No despliega ni requiere secretos de producción. Las pruebas smoke contra el CMS se ejecutan por separado.

## Configuración y seguridad

El cliente consulta `https://content.alexcuadra.dev/graphql` mediante Application Password, `WPGRAPHQL_AUTH_ENABLED=true` y credenciales de runtime. **WPGraphQL exige autenticación**, validada en producción Workers, staging y local. El modo anónimo del cliente solo es compatible si el CMS vuelve a permitirlo; consultar la [guía de autenticación y reversión](docs/CMS_AUTH.md). Solo proyectos autenticados tienen caché compartida, con un contrato de campos públicos y controles de publicación/contraseña/restricción; Home verifica la identidad en origen.

No guardar tokens, Application Passwords ni credenciales en Git, contenido ACF, logs o variables `PUBLIC_*`. `.env`, sus variantes y `.dev.vars` están ignorados, salvo `.env.example`. Los secretos futuros se configurarán en el runtime del Worker, separados por entorno. No registrar respuestas crudas del CMS para diagnosticar fallos.

Los CPT actuales de proyectos y artículos proceden de un plugin custom. Su transición al registro de ACF requiere inventario, backup y validación; no desactivar ese plugin como parte de la migración frontend.

## Documentación

Los planes, guías y documentos de seguimiento se guardan en `docs/`:

- [Plan de rediseño](docs/REDESIGN_PLAN.md): contenido bilingüe, templates, LAB, migración CPT/ACF, seguridad, SEO y soporte Gutenberg.
- [Estado de ejecución](docs/EXECUTION_STATUS.md): comprobaciones realizadas, incidentes y tareas pendientes.
- [Guía de Workers staging](docs/WORKERS_STAGING.md): configuración y validación del entorno paralelo.
- [Migración de producción a Workers](docs/WORKERS_PRODUCTION.md): entorno separado, pasos de creación, traslado del dominio y reversión.
- [Inventario y transición CPT/ACF](docs/CMS_MIGRATION_GUIDE.md): claves internas confirmadas, copia de WordPress y exports necesarios antes de crear campos.
- [Configuración bilingüe](docs/BILINGUAL_SETUP.md): compatibilidad, instalación guiada y validación EN/ES sin mezclar blog y referencias externas.

Las referencias de diseño bajo `docs/design-proposal/` permanecen locales y están excluidas de Git. Las colecciones Markdown heredadas se conservan durante la transición; no son la fuente editorial prevista para el rediseño.
