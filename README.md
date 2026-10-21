# alexcuadra.dev

Portfolio de Alex Cuadra, desarrollador web senior con experiencia en calidad, automatización y DevOps. El proyecto está en proceso de rediseño bilingüe (inglés y español) y migración de Cloudflare Pages a Workers.

## Stack

- Astro 7 con renderizado en servidor (SSR) y TypeScript.
- Tailwind CSS 4 mediante su integración Vite.
- Cloudflare Workers con Wrangler 4.
- WordPress en `content.alexcuadra.dev`, WPGraphQL y WPGraphQL for ACF.
- ACF gratuito para el modelo editorial previsto.

Todo el contenido editorial del rediseño se administrará en WordPress. Los mockups son referencias de diseño; sus cifras no son datos reales del portfolio.

El CPT `articles` reúne referencias a publicaciones externas y menciones; el tipo nativo `post` contiene el blog propio y `lab_project` los experimentos personales. Blog y LAB tienen archivo/ficha EN/ES y contenido inicial publicado en el CMS compartido; su frontend está en staging, con QA y aceptación aún pendientes antes de producción.

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
| `npm run test:mcp-read` | Prueba framework y HTTP MCP real en Docker WordPress/Polylang aislado, con fixtures sintéticos y sin puertos del host. |
| `npm run test:mcp-http` | Ejecuta solo la suite HTTP MCP del mismo laboratorio: autenticación, sesiones, lecturas y revocación. Ver [QA/cierre Docker](docs/qa/MCP_EDITORIAL_HTTP_2026-10-20.md). |
| `npm run test:mcp-site-stack` | Ejecuta framework+HTTP en otro laboratorio con WordPress7.1.3/PHP8.2/Polylang3.9.0, combinación observada por SSH; no copia configuración/datos del sitio. |
| `npm run test:mcp-changes` | Prueba propuestas privadas, historial/recuperación, idempotencia/cuotas, guards y HTTP concurrente en laboratorio; no aplica cambios a proyectos ni requiere rol Editor. [Contrato y límites](docs/WORDPRESS_MCP_PROJECT_CHANGES.md). |
| `npm run test:mcp-changes-http` | Ejecuta solo la aceptación HTTP de propuestas/historial, duplicados, cuotas, sesiones y carreras entre procesos independientes. [QA](docs/qa/MCP_EDITORIAL_IDEMPOTENCY_2026-10-20.md). |
| `npm run test:mcp-changes-stack` | Ejecuta esas pruebas en otro laboratorio con ACF/Polylang/WPGraphQL y comprueba campos, idiomas y exclusión GraphQL de propuestas privadas. [QA/cierre](docs/qa/MCP_EDITORIAL_GRAPHQL_STACK_2026-10-20.md). |
| `npm run check` | Revisa tipos y archivos Astro. |
| `npm run build` | Imprime revisión/versiones, valida y compila para Workers. |
| `npm run build:production` | Compila para el Worker de producción propuesto, sin desplegar. |
| `npm run preview` | Sirve el build local con el runtime de Workers. |
| `npm run test:smoke` | Comprueba el favicon y siete páginas en localhost:4321. |
| `node scripts/outline-smoke.mjs baseline` / `outline` | Baseline y aceptación HTTP del TOC en staging, con30s entre visitas CMS y sin navegador. Requiere el build correspondiente a la entrega. [QA](docs/qa/EDITORIAL_OUTLINE_STAGING_2026-10-20.md). |
| `npm run test:smoke -- https://dominio` | Ejecuta las mismas comprobaciones contra un destino explícito. |
| `npm run staging:recovery -- --current ID --target ID` | Inspecciona compatibilidad entre versiones de staging; solo lectura por defecto. Con `--execute`, ensaya rollback y restaura la versión inicial. |
| `npm run deploy:staging` | Compila y despliega al Worker definido en `wrangler.jsonc`; requiere acceso autorizado a Cloudflare. |

Los smoke tests usan contenido publicado del CMS y necesitan un proyecto, una categoría y un artículo disponibles. No son pruebas de carga; evítalos repetidamente mientras se investiga el límite de solicitudes. Un build correcto no garantiza disponibilidad del CMS ni funcionamiento completo en producción.

Para Blog/LAB publicados: `node scripts/smoke.mjs https://alexcuadra-dev-staging.alexcu21.workers.dev --require-cms-auth --check-seo --check-content --check-csp --check-sitemap --check-contact-disabled --check-blog-lab --editorial-only --request-gap-ms 10000`. Descubre las fichas desde los archivos, verifica idiomas/alternates,404 y sitemap, sin reintentos automáticos. La separación entre peticiones reduce la carga del QA; no modifica límites del hosting. Omitir `--check-csp` al usar Astro dev: comprobar CSP contra el Worker compilado.

Para continuar una pasada interrumpida sin repetir sus comprobaciones previas, añadir `--editorial-start-at blog-404`, `lab` o `references` con `--check-blog-lab --editorial-only`. `blog-404` requiere `--blog-entry-path /blog/slug-ingles-ya-verificado` y comprueba ausencia/idioma antes de LAB; usarlo solo si ese slug no es también el destino español publicado. Ejemplo para LAB y referencias: `node scripts/smoke.mjs https://alexcuadra-dev-staging.alexcu21.workers.dev --check-blog-lab --editorial-only --editorial-start-at lab --require-cms-auth --check-seo --check-content --check-csp --check-contact-disabled --request-gap-ms 30000`. Este modo omite rutas técnicas, Home, sitemap y endpoint de contacto; solo certifica los casos ejecutados, comprueba identidad CMS en cada página200 y ausencia del formulario/widget en las cuatro páginas LAB. Rechaza opciones de otras suites para no omitirlas silenciosamente y se detiene al primer fallo, sin reintentos.

La espera entre peticiones queda fuera del timeout de red (30 segundos por defecto;15 para contacto). Esto permite usar una separación de30s sin agotar el timeout antes de enviar la siguiente petición. Una interrupción del smoke no demuestra por sí sola un fallo del CMS: correlacionar con logs del Worker antes de atribuirla a429.

`npm run test:smoke -- http://127.0.0.1:4321 --require-cms-auth --check-seo` comprueba además canonical, ausencia de metadatos duplicados y metadatos sociales básicos del frontend actual en inglés. Usar esa opción contra un despliegue solo después de publicar los cambios SEO correspondientes.

Añadir `--check-content` verifica el renderer editorial compartido y un único H1 en About, detalle de proyecto y referencia externa. El HTML del CMS se sanitiza en servidor y recibe estilos básicos de Gutenberg; bloques admitidos, límites y guía editorial en [WORDPRESS_CONTENT.md](docs/WORDPRESS_CONTENT.md).

El cliente de contenido ofrece consultas EN/ES opcionales para proyectos, referencias externas, categorías, Home y About. Las rutas actuales conservan las consultas compatibles mientras se prepara el CMS. `npm test` incluye aislamiento de caché por idioma, proyección pública de traducciones, paginación y rechazo de respuestas con idioma incorrecto; estas pruebas usan respuestas simuladas. El laboratorio Docker también prueba esas funciones contra WordPress real con datos sintéticos; el [resultado del cliente](docs/wordpress/POLYLANG_CLIENT_RESULTS.json) tiene 13/13 comprobaciones correctas. El estado de activación y los pasos pendientes están en la [guía bilingüe](docs/BILINGUAL_SETUP.md).

## Entornos y despliegue

| Entorno | Estado |
| --- | --- |
| Producción — [alexcuadra.dev](https://alexcuadra.dev/) | Worker `alexcuadra-dev`, entorno Wrangler `production`, conectado a `codex/workers-upgrade` durante la migración. Pages se conserva como respaldo. |
| Staging — [Workers](https://alexcuadra-dev-staging.alexcu21.workers.dev/) | Worker `alexcuadra-dev-staging`, conectado a `codex/workers-upgrade`. |

El staging está desplegado, pero su validación continúa: se han observado errores HTTP 429 y 500 procedentes del CMS. Los fallos reconocidos del CMS se convierten en 503 sin caché, con diagnósticos estructurados en Observability. Las consultas repetidas se deduplican dentro de cada petición. Home, proyectos y categorías públicos usan además una caché de datos de 60 segundos por centro de datos; cookies, autorización y previews la omiten. La política y sus límites se describen en [CMS: caché y diagnóstico](docs/CMS_CACHE.md).

Workers Builds usa `npm run build` y después `npx wrangler deploy`, con la raíz del repositorio como directorio de trabajo. El nombre del Worker debe coincidir con `wrangler.jsonc`. Las respuestas SSR bajo `workers.dev` incluyen `noindex`; esto no es control de acceso.

El [procedimiento de recuperación staging](docs/STAGING_RECOVERY.md) compara versiones explícitas, contacto false, autenticación, bindings y runtime antes de cambiar tráfico. Usa únicamente versiones ya publicadas; no sube el árbol local ni modifica el CMS. Un rollback de código no restaura los datos de WordPress o de servicios de almacenamiento. El ensayo comprueba cada fase y vuelve a la versión inicial; su informe distingue restauración de versión y validación HTTP. No usarlo como procedimiento automático de producción.

El dominio público ya está asociado al Worker y validado por HTTP con autenticación. Antes de fusionar en `main`, desactivar los builds automáticos del antiguo Pages y coordinar el cambio de rama del Worker de producción. La configuración de esta rama está preparada para Workers; no sirve para desplegar de nuevo en Pages.

## Pruebas y CI

Las pruebas cubren contratos del cliente GraphQL, variables, errores de autorización/HTTP/red, timeouts, contenido Home ausente, diagnósticos sin datos sensibles, manejo 503, deduplicación por petición y orden de proyectos por fecha.

[El workflow de CI](.github/workflows/ci.yml) instala con `npm ci`, ejecuta `npm test` y `npm run build` en pull requests y pushes a `main` o `codex/workers-upgrade`. No despliega ni requiere secretos de producción. Las pruebas smoke contra el CMS se ejecutan por separado.

## Configuración y seguridad

El cliente consulta `https://content.alexcuadra.dev/graphql` mediante Application Password, `WPGRAPHQL_AUTH_ENABLED=true` y credenciales de runtime. **WPGraphQL exige autenticación**, validada en producción Workers, staging y local. El modo anónimo del cliente solo es compatible si el CMS vuelve a permitirlo; consultar la [guía de autenticación y reversión](docs/CMS_AUTH.md). Los proyectos autenticados y la disponibilidad pública de Blog/LAB tienen caché compartida con contratos explícitos de campos públicos y controles de publicación/contraseña/restricción; Home verifica la identidad en origen. Ver alcance y TTL en [CMS_CACHE.md](docs/CMS_CACHE.md).

No guardar tokens, Application Passwords ni credenciales en Git, contenido ACF, logs o variables `PUBLIC_*`. `.env`, sus variantes y `.dev.vars` están ignorados, salvo `.env.example`. Los secretos futuros se configurarán en el runtime del Worker, separados por entorno. No registrar respuestas crudas del CMS para diagnosticar fallos.

Los CPT de proyectos y artículos ya se registran en ACF conservando sus datos y nombres GraphQL; el plugin anterior se conserva desactivado como respaldo. LAB también se registra mediante ACF. No duplicar registros ni cambiar IDs/field keys; consultar el traspaso reciente y los exports en `docs/wordpress/`.

## Documentación

Los planes, guías y documentos de seguimiento se guardan en `docs/`:

- [Plan de rediseño](docs/REDESIGN_PLAN.md): contenido bilingüe, templates, LAB, migración CPT/ACF, seguridad, SEO y soporte Gutenberg.
- [Estado de ejecución](docs/EXECUTION_STATUS.md): comprobaciones realizadas, incidentes y tareas pendientes.
- [Guía de Workers staging](docs/WORKERS_STAGING.md): configuración y validación del entorno paralelo.
- [Entrega a staging](docs/STAGING_NEXT_RELEASE.md): TOC/anchors publicados para revisión, verificación y procedimiento, independiente del MCP.
- [Contrato previsto de aplicación MCP](docs/WORDPRESS_MCP_SOURCE_APPLICATION.md): revisión/aprobación del efecto público y recuperación de fuentes; todavía sin implementación.
- [Migración de producción a Workers](docs/WORKERS_PRODUCTION.md): entorno separado, pasos de creación, traslado del dominio y reversión.
- [Inventario y transición CPT/ACF](docs/CMS_MIGRATION_GUIDE.md): claves internas confirmadas, copia de WordPress y exports necesarios antes de crear campos.
- [Configuración bilingüe](docs/BILINGUAL_SETUP.md): compatibilidad, instalación guiada y validación EN/ES sin mezclar blog y referencias externas.
- [Prueba aislada del conector Polylang](docs/wordpress/POLYLANG_LAB_REPORT.md): matriz de autorización y resultados, incluidos los cuatro fallos de política de campos ACF protegidos con contraseña.

Las referencias de diseño bajo `docs/design-proposal/` permanecen locales y están excluidas de Git. Las colecciones Markdown heredadas se conservan durante la transición; no son la fuente editorial prevista para el rediseño.

El MVP incluye un formulario de contacto con Cloudflare Turnstile y envío nativo de Cloudflare al destino personal verificado. Endpoint, interfaz y coordinador global están desplegados en staging y desactivados. Bindings, vars y nombres de secrets (incluido HMAC) confirmados; falta piloto real de challenge/entrega. `/privacy/` en staging y producción consume la página EN publicada, ID 180; Home enlaza el destino de producción. Contacto sigue desactivado en ambos entornos. Estado en [CONTACT_FORM_PLAN.md](docs/CONTACT_FORM_PLAN.md).

`npm run contact:readiness` audita staging sin desplegar ni enviar correo; compara todas las versiones con tráfico y proyecta metadatos sin valores privados. Un exit code 2 indica requisitos pendientes. Ver [informe de preparación](docs/qa/CONTACT_STAGING_READINESS.md). `/privacy/` está preparada para una página EN publicada del CMS mediante `CONTACT_PRIVACY_PAGE_ID`; sin ID válido devuelve 404. Alex eligió aviso propio del portfolio; [borrador editorial EN/ES](docs/CONTACT_PRIVACY_DRAFT.md) pendiente de completar y publicar en WordPress.

`npm run test:contact-runtime` comprueba el handler completo en workerd con correo nativo simulado, rate limit y coordinador SQLite. `npm run test:contact-coordinator` verifica directamente su concurrencia y cuota. Ambas suites están en CI, sin credenciales ni red externa. Ver [evidencia de runtime](docs/qa/CONTACT_RUNTIME_QA.md).
