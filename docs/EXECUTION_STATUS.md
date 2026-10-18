# Ejecución del rediseño

Alex confirmó también español para IDs 78/80/87/93: nueve referencias españolas identificadas en total. Se actualizó ARTICLE_LANGUAGES.md; sigue sin aplicarse la asignación en WordPress.

## Clasificación inicial de idiomas Articles — 18 octubre 2026

Alex completó el asistente de Polylang con inglés y medios traducibles. Aclara que hay fichas españolas; cinco slugs confirmados y contrastados con IDs 97/95/91/84/82, registrados en wordpress/ARTICLE_LANGUAGES.md. Projects y Articles no están marcados como traducibles. Baseline tras el asistente sin cambios. Otras cuatro fichas con slugs españoles pendientes de confirmación del idioma del texto local; no se asigna idioma por heurística. Conector Valu v0.7.1 inspeccionado en arranque/inclusión de autoload, pendiente piloto. No se cambió contenido ni rutas.

## Preparación bilingüe — 18 octubre 2026

WP 7.1.3/PHP 8.5.11 confirmados por Alex. Investigación primaria: Polylang 3.8.11 cumple mínimos WP/PHP y declara pruebas hasta WP 7.1.3; no equivale a compatibilidad completa de PHP 8.5 y demás plugins. Conector valu-digital v0.7.1, última release/push observado mayo 2025, sin certificación encontrada para la combinación actual. Piloto necesario antes de adoptar el puente.

Comando cms:languages implementado y ejecutado con credenciales locales: esquema todavía sin languages/defaultLanguage, language/translations ni filtros en Page/Project/Article/Post/Category. Estado guardado en wordpress/LANGUAGE_SCHEMA_STATUS.md; no se instaló plugin ni se alteró contenido. BILINGUAL_SETUP.md define instalación guiada, separación EN/ES, limitaciones ACF gratuito, protección de reglas Home/About, filtros obligatorios y pruebas de aislamiento. Pendiente instalación inicial de Polylang por Alex.

## Registro ACF final verificado — 18 octubre 2026

Export acf-export-2026-10-18 (2).json: Projects usa page/pages con rename_capabilities activo; Articles conserva post/posts; feeds activos en ambos. Solo cambian esos ajustes respecto al export anterior. Cuatro grupos ACF idénticos al original; custom-fields añadido intencionalmente en ambos CPT. Snapshot final docs/wordpress/acf-current-2026-10-18.json.

Baseline posterior correcto: mismos IDs/slugs/hashes consultados en 7 proyectos y 10 referencias externas publicados. Smoke público correcto para identidad autenticada, siete rutas, favicon y dos controles 404. Se valida sustitución del registro y lectura publicada sin copiar contenido. No se declara auditada la edición ni todos los estados/meta del CMS. Plugin original se conserva desactivado como respaldo. Siguiente decisión de modelado: estrategia bilingüe antes de crear los nuevos grupos y LAB.

## Export de registros ACF revisado — 18 octubre 2026

Archivo acf-export-2026-10-18 (1).json incluye cuatro grupos idénticos al export original y los dos CPT. Claves/GraphQL, relaciones y supports correctos; custom-fields añadido intencionalmente por Alex. Diferencias pendientes: Projects usa post/posts en vez de page/pages; feeds desactivados en ambos frente al default activo del registro original. Guía de ajustes en docs/wordpress/CPT_EXPORT_REVIEW.md; lectura publicada ya validada, equivalencia de configuración todavía pendiente de estos ajustes.

## Lectura de CPT tras sustitución validada — 18 octubre 2026

Alex activó Excerpt en Articles. cms:baseline -- --verify pasa sin modificar la referencia: 7 proyectos y 10 referencias externas publicados conservan IDs, slugs y hashes de campos/relaciones consultados. No fue necesario copiar entradas. Pendiente export ACF de ambos registros para verificar permisos/supports y revisión de estados no publicados/edición desde admin. Conservar el plugin original desactivado como respaldo.

Smoke público posterior correcto: identidad autenticada, siete rutas de contenido, favicon y dos controles 404 sin dependencia del CMS.

## Aclaración Articles/Posts y verificación CPT — 18 octubre 2026

Alex informa que creó ambos CPT en ACF. Aclara articles como referencias externas (publicaciones suyas en otras plataformas y menciones de terceros) y post como blog nativo del sitio. Plan actualizado con modelos, rutas, campos, templates, bilingüismo, SEO y pruebas separados. No se trasladó contenido entre tipos ni se implementó el blog.

Comparación posterior del baseline bloqueada por error de esquema: Cannot query field excerpt on type Article. El registro original sí soportaba excerpt. Pendiente activar Excerpt en Supports de Articles y repetir la comparación completa; no se modifica el baseline para ocultar esa diferencia. Backup de BD/archivos confirmado por Alex; ACF 6.8.10 y WPGraphQL for ACF 3.0.0.

## Plugin y export ACF recibidos — 18 octubre 2026

Alex acepta operar directamente en producción con posibles interrupciones; no se exige staging. PHP recibido: solo registro de project y articles, sin hooks de limpieza ni resolvers. Projects usa capability_type page; Articles usa post por defecto. Cuatro grupos ACF conservan sus ubicaciones y field keys, sin campos PRO. Snapshot de configuración en docs/wordpress y guía CPT_TRANSITION.md. Ruta propuesta: sustituir proveedor de registro manteniendo claves, sin copiar entradas.

Baseline autenticado guardado: 7 proyectos y 10 artículos publicados sin contraseña visibles al servicio, IDs/slugs y hashes de campos consultados. No sustituye backup ni inventario administrativo de borradores/privados. Pendientes versiones ACF/WPGraphQL for ACF, backup y creación guiada de los registros; no se modificó WordPress.

## Fase 1 iniciada: inventario CMS — 18 octubre 2026

Inventario GraphQL autenticado de solo lectura: ContentType.name confirma project para proyectos y articles para artículos; GraphQL project/projects y article/articles. Cinco tipos visibles para la cuenta de servicio. Los null de configuración no se interpretan como false. Informe CMS_SCHEMA_INVENTORY.md y comando cms:inventory sin imprimir credenciales ni contenidos editoriales.

Alex confirma ausencia de staging WordPress. Se preparó CMS_MIGRATION_GUIDE.md para crear copia independiente, obtener plugin custom y exportar grupos ACF. Pendiente saber si hPanel ofrece staging y recibir definiciones antes de registrar CPT o fields. No se modificó WordPress ni se desactivó el plugin.

## Incidente 429 y amplificación por rutas inexistentes — 18 octubre 2026

Export aportado por Alex: 100 eventos entre 07:05:32 y 07:06:34 UTC, 36 cms_failure, todos CMS_HTTP/429. Incluye sondas de /.env, /README.md, /.github/copilot-instructions.md y otras rutas inexistentes, además de consultas rest_route al frontend. No incluye el requestId c4dfa945-9fb6-4017-8232-4b85b6a43133 de la prueba posterior; no se atribuye ese 503 concreto al export anterior.

La página 404 heredaba BaseLayout y consultaba Home desde WordPress para URLs inexistentes. Se sustituye por una página prerenderizada sin CMS y se rechaza rest_route en middleware con 404 antes de consultar datos. Esto elimina esa amplificación conocida; no demuestra que los bots sean la única causa ni elimina el límite de Hostinger. Los nombres de archivos solicitados no prueban exposición de sus contenidos.

## Restricción WPGraphQL activa y validada — 18 octubre 2026

Alex confirmó que solo consumen el CMS el portfolio local y los dos Workers, configuró credenciales locales y activó la restricción de operaciones autenticadas. Una consulta anónima a pages se rechaza con mensaje explícito de falta de autenticación; no se interpreta el HTTP 200 de un error GraphQL como acceso permitido. Smoke con --require-cms-auth correcto en producción, staging y local: favicon y siete rutas por entorno. El runtime local carga .dev.vars y Git lo ignora. No se leyeron ni registraron credenciales.

Quedan coordinación de main/builds del antiguo Pages, revisión visual y seguimiento del límite de Hostinger. La restricción no demuestra que los 429 estén resueltos. El Pages de respaldo usa código anónimo y requiere revertir la restricción del CMS si se vuelve a utilizar.

## Traslado del dominio confirmado — 18 octubre 2026

Alex creó alexcuadra-dev y trasladó alexcuadra.dev desde Pages. Smoke remoto del dominio público correcto: favicon y siete rutas, con identidad de servicio confirmada. Home HTTP 200, canonical https://alexcuadra.dev/ y sin cabecera/meta noindex. Se registra custom_domain solo en env.production. La prueba HTTP no sustituye revisión visual ni seguimiento de los 429/503.

Pendientes: confirmar otros consumidores de GraphQL y credenciales locales antes de activar la restricción; desactivar builds del antiguo Pages antes de fusionar main y coordinar después la rama de producción. Pages se conserva como respaldo; main sigue sin modificarse.

## Preparación de producción Workers — decisión de Alex

Alex eligió preparar la migración a Workers en lugar de adaptar Pages. Entorno Wrangler production separado, nombre propuesto alexcuadra-dev, build:production y CI para ambos destinos. No se creó el Worker ni se trasladó el dominio; instrucciones en WORKERS_PRODUCTION.md. Pendiente creación y credenciales por Alex.

Las consultas autenticadas de Home, About y artículos verifican publicación, ausencia de contraseña y ausencia de restricciones antes de renderizar. Las colecciones filtran PUBLISH/hasPassword false. La cuenta de servicio no debe recibir permisos administrativos. El historial que sigue contiene estados anteriores; este bloque y el de validación de autenticación reflejan el estado actual.

## Validación de autenticación y caché pública — 17 octubre 2026

- Staging confirmó la cuenta de servicio mediante `viewer`, HTTP 200 y `X-CMS-Auth: verified`.
- Smoke remoto: favicon y siete rutas correctas (inicio, work, About, articles, proyecto, categoría y artículo). Es una comprobación puntual, no prueba de eliminación de los 429.
- Esquema real validado: proyectos y páginas admiten status PUBLISH, hasPassword false y los indicadores status/isRestricted/hasPassword.
- Caché autenticada de proyectos limitada a un DTO público, separación por credencial y validación de visibilidad. Home continúa verificando identidad directamente. Pendientes contratos de caché para las demás operaciones.
- La restricción global de WPGraphQL continúa pendiente de adaptar producción Pages y otros consumidores. No se modificó main ni el dominio de producción.

## Fase 0.1 — en curso

Baseline: main, commit `8cea9f341a4de6602d487b9c8f959169fdfb9b3e`, conservando los cambios locales previos del plan, pruebas y correcciones. Node 22.23.2, npm 10.9.8. Sin publicaciones ni cambios en WordPress/Cloudflare.

- 14/14 pruebas pasan antes de actualizar.
- Build SSR correcto; 0 errores de tipos, 7 sugerencias de símbolos sin uso y avisos existentes de sharp/Cloudflare y Browserslist.
- `npm ls --depth=0` correcto antes de actualizar.
- Auditoría inicial npm: 45 paquetes con avisos (1 crítico, 33 altos, 10 moderados, 1 bajo). Clasificación de npm, no confirmación de explotabilidad de todas las funciones en este sitio.
- Disponibles en los rangos actuales: Astro 5.18.2, adapter Cloudflare 12.6.13, integración Tailwind 5.1.5, Tailwind 3.4.19, checker 0.9.10, TypeScript 5.9.3, graphql-request 7.4.0, dotenv 16.6.1 y tipos Node 22.20.5.
- Metadatos npm comprobados: Cloudflare 12.6.13 requiere Astro ^5.7.0; integración Tailwind 5.1.5 admite Astro 3/4/5 y Tailwind ^3.0.24; adapter Cloudflare 14.4.0 requiere Astro ^7.4.0 y Wrangler ^4.125.0.

Primer lote instalado mediante npm update dentro de los rangos actuales. Resultado: 14/14 pruebas pasan, build SSR pasa y npm ls --depth=0 no muestra conflictos. Auditoría posterior: 16 paquetes con avisos (1 crítico, 9 altos, 5 moderados, 1 bajo). Es un paso intermedio; no asumir que elimina todos los avisos. Hay avisos de Astro cuyo rango afecta también a Astro 5.18.2, por lo que el salto mayor debe evaluarse antes de declarar segura/completa la fase.

Copias locales previas a la instalación en `/tmp/alexcuadra-phase-01/` (temporales; no sustituyen un backup duradero ni un commit). Informes crudos de auditoría también temporales, sin credenciales.

## Información solicitada a Alex

Cloudflare → Workers & Pages → proyecto del sitio:

1. Tipo de proyecto: Pages o Workers.
2. Comando de compilación y directorio de salida configurados.
3. Existencia de staging/preview y su modalidad.

No compartir tokens, contraseñas ni valores de variables secretas. Esta información determina el adapter y el procedimiento de despliegue del siguiente lote mayor. No cambiar aún la restricción GraphQL ni desactivar el plugin de CPT.

## Pendiente tras el primer lote

- Validación del build en runtime Cloudflare, smoke de rutas y comparación visual.
- Matriz objetivo del salto mayor y revisión de guías de migración.
- Comprobación de staging antes de dar por cerrada la fase.
- Después: inventario del plugin CPT actual, registros ACF y contenido bilingüe con guía asistida.

El adapter actualizado emite un aviso de binding KV SESSION para sesiones. El sitio no usa sesiones explícitas; revisar su configuración y necesidad antes de desplegar, sin pedir crear KV por defecto. Persisten avisos de sharp y 7 sugerencias de símbolos sin uso. No se ha desplegado.

## Hosting confirmado por Alex

Cloudflare Pages conectado a GitHub, dominio `alexcuadra-dev.pages.dev`; build `npm run build`, salida `/dist`, raíz `/`. Staging todavía sin confirmar. No se ha cambiado esta configuración.

Smoke tras primer lote: servidor Astro 5.18.2 inicia y Home responde HTTP 200 con título Alex Cuadra y contenido del hero; no basta para dar por comprobadas todas las rutas.

Decisión requerida para el siguiente lote: el adapter moderno elimina soporte de Pages y requiere Workers, según https://docs.astro.build/en/guides/integrations-guide/cloudflare/ . Recomendación: preparar actualización y Worker de staging paralelo, mantener Pages y dominio de producción hasta validar y acordar el cambio. No crear recursos ni cambiar DNS en esta etapa. Si se decide permanecer en Pages, documentar las restricciones de versión y los avisos sin resolver; no declarar cerrada la actualización de seguridad.

## Lote Workers aprobado y preparado

Alex autorizó preparar Workers paralelo conservando Pages en producción. Rama local: `codex/workers-upgrade`.

| Componente | Versión validada localmente |
| --- | --- |
| Node | 22.23.2, mínimo Astro >=22.22.0 |
| Astro | 7.4.0 |
| @astrojs/cloudflare | 14.4.0 |
| Wrangler | 4.151.1 |
| Tailwind / plugin Vite | 4.3.3 / 4.3.3 |
| TypeScript / checker | 5.9.3 / 0.9.10 |
| graphql-request | 7.4.0 |

Retirada @astrojs/tailwind por incompatibilidad con Astro 7. Tailwind 4 usa plugin Vite y carga la configuración de colores legacy; actualizadas utilities incompatibles del botón de enlaces. Colección Markdown migrada a content.config.ts con glob loader, conservando archivos. Sesiones desactivadas para no necesitar KV; imágenes en modo compile, sin aprovisionar Cloudflare Images. Configuración Wrangler dedicada a `alexcuadra-dev-staging`, sin rutas/dominio de producción. Middleware noindex en SSR de workers.dev.

Validaciones realizadas: 14/14 pruebas unitarias/contrato; instalación limpia npm ci; build Astro/Workers correcto; npm ci reporta 0 vulnerabilidades conocidas. npm ls no presenta peers inválidos; muestra @img/sharp-wasm32 como extraneous incluso tras npm ci (paquete opcional de la cadena de imágenes, sin error de instalación/build). Revisar si afecta tamaño final antes de publicar; no se lo elimina a mano.

Preview del build activo con workerd en http://127.0.0.1:4321/. `npm run test:smoke` verifica HTTP 200, título, H1 y ausencia de errores conocidos en las siete rutas: Home, Work, About, Articles, Christianity Today, categoría Medium y su artículo. Estos smoke usan el CMS publicado y complementan las pruebas deterministas; no están en CI para evitar dependencia externa. Los controles nuevos 404/503 se implementarán en la fase de datos, no se declaran verificados todavía.

Revisión visual automática pendiente: el acceso al navegador fue rechazado por su política de URL; no se intentó eludir ese bloqueo. La verificación de HTML por HTTP y los logs de workerd son correctos, pero no sustituyen comparar capturas o revisar manualmente ambos temas.

No se ha creado Worker ni publicado en Cloudflare. Instrucciones concretas en WORKERS_STAGING.md. Falta validar staging real, revisión visual y autenticación antes de cerrar fase 0.1. No fusionar en main mientras su despliegue siga en Pages.

Rama publicada en origin/codex/workers-upgrade, commit inicial 394262c. main sin cambios. Worker de staging todavía no creado.

Alex inició la configuración desde repo con comandos de build/deploy; no se mostró selector de ramas y Preview command quedó por defecto. Pendiente confirmar nombre/URL del Worker y resultado del build. Selección de rama guiada desde Settings → Build/Builds → Branch control según documentación actual. No se declara desplegado ni validado staging.

Diagnóstico del log de Cloudflare aportado por Alex: Wrangler 3.99.0, CSS anidado y consulta redundante de PortfolioPreview siguen presentes; el build no corresponde al código migrado actual. Sin rama/SHA en el log no se identifica el commit exacto. Añadido prebuild con SHA y versiones del lockfile para el siguiente intento. No se imprimen variables de entorno ni secretos.

## Verificación del staging publicado

URL proporcionada por Alex: https://alexcuadra-dev-staging.alexcu21.workers.dev . La petición GET remota devuelve HTTP 500 con cuerpo vacío; HEAD también 500. Prueba explícita `npm run test:smoke -- https://alexcuadra-dev-staging.alexcu21.workers.dev` falla en Home. Corregido el runner para aceptar e imprimir destino por argumento: la primera ejecución con variable de entorno no constituye evidencia válida del remoto. Staging desplegado pero NO funcional/aceptado aún. Se requieren logs de ejecución del Worker para identificar la excepción; el log de build no basta. No avanzar al cambio de dominio ni restricción GraphQL.

Alex informa que staging ya es visible. Activados logs e invocation_logs en configuración Wrangler de staging para registrar errores y solicitudes tras el siguiente despliegue. Se mantiene pendiente comprobar respuestas remotas y noindex.

Comprobación posterior: smoke remoto pasó siete rutas, pero solicitudes directas GET/HEAD también devolvieron 500 con cuerpo vacío y sin X-Robots-Tag. Se confirma intermitencia y no se declara resuelta. La configuración logs.enabled/invocation_logs es válida según el schema y código de Wrangler 4.151.1; no se atribuye el panel vacío a falta de enabled raíz. Pedir versión activa y logs sin filtros; correlacionar CF-Ray a4c43429bccaccbc-MIA (petición GET fallida).

## Diagnóstico de errores aportados por Observability

Los eventos correlacionan fallos de Home/proyectos/categorías con TypeError al acceder a homePageFields; la misma versión de Worker también devuelve 200 después. No prueba un problema de cold start. Alex confirma que la restricción de autenticación WPGraphQL sigue desactivada. Los errores actuales no conservan HTTP status ni respuesta GraphQL útil, por lo que la causa del fallo upstream sigue pendiente.

Corrección: transporte central con timeout de 10 s y errores tipados/clasificados, sin volcar respuestas/headers; eliminar fallback de arrays vacíos ante fallo; validar Home antes de renderizar. Scope AsyncLocalStorage por petición para deduplicar consultas de globales sin compartir caché entre visitantes. Middleware convierte fallos de CMS registrados en 503 no-store con Retry-After y noindex; no disfraza errores de programación ajenos al CMS. Respuestas de staging se clonan antes de añadir cabeceras para admitir headers inmutables. Retirados enlaces a manifest e iconos inexistentes; se conserva favicon.svg.

25/25 pruebas pasan, incluyendo rechazos HTTP/GraphQL, ausencia de Home, timeout, logs redactados, aislamiento/deduplicación y 503. Build Astro/Workers pasa. La clasificación de la causa upstream debe comprobarse en los nuevos eventos cms_failure tras despliegue; esta entrega no implementa aún autenticación ni afirma resolver la disponibilidad de WordPress.

Archivo de eventos posterior contiene las mismas marcas de las 20:51 del 17 de octubre (hora Nicaragua), con 200 y luego upstream 429. No aporta un incidente nuevo ni identifica la regla de Hostinger/AIOS. Se añade endpoint favicon.ico con redirect directo al SVG para evitar renderizar 404 con consultas al CMS; el smoke comprueba ese redirect. No se implementan reintentos ni se modifica el límite de batch de WPGraphQL. Pendiente correlación con logs de Hostinger/AIOS.

## Investigación Hostinger / DNS

Alex confirma hosting Hostinger, AIOS 5.4.10 desactivado durante la investigación, WPGraphQL 2.24.2 y addons WPGraphQL for ACF + IDE. No se verificó aún si el 429 persiste en eventos nuevos después de desactivar AIOS. No tomar el histórico como prueba de ese resultado.

Consulta DNS pública puntual: content.alexcuadra.dev devuelve un A, sin AAAA/CNAME en esta consulta. Una única consulta POST mínima __typename devuelve HTTP 200, JSON GraphQL válido y server LiteSpeed. Esto prueba resolución/TLS/servicio desde el entorno de prueba en ese momento, no disponibilidad sostenida desde Workers ni consistencia global del DNS.

La documentación oficial de Hostinger describe límites por IP en LiteSpeed y agregación de tráfico detrás de proxies como posibles fuentes de 429. No se ha probado cuál aplica a este sitio. DNS no genera el status HTTP por sí mismo, aunque diferentes destinos/proxies pueden alterar quién aplica el límite. No cambiar nameservers/proxy ni desactivar protecciones por hipótesis.

Pendiente: logs de Hostinger en POST /graphql correlacionados con hora/IP/regla, estado Hostinger CDN y proxy de Cloudflare del registro content; confirmar con eventos posteriores al cambio de AIOS. Maximum operations per batch sigue sin relación con solicitudes por minuto: el cliente no envía batches. La futura autenticación no garantiza exención de rate limiting.

Fuentes: https://www.hostinger.com/support/hostinger-cdn-429-too-many-requests-errors/ ; https://www.wpgraphql.com/docs/security ; https://teamupdraft.com/documentation/all-in-one-security/faqs/important-note-on-intermediate-and-advanced-features/ .

## Evidencia de logs Hostinger

Alex aporta cinco POST /graphql con 429 entre 21:51:10 y 21:51:11 del 17 de octubre y dirección registrada 2a06:98c0:3600::103. Esa dirección pertenece a Cloudflare y está documentada como valor sintético de CF-Connecting-IP en subrequests cross-zone de Workers. No asumir que es una IP de salida dedicada al proyecto ni autorizarla globalmente.

Hipótesis a validar con Hostinger: agregación de tráfico por esa identidad/IP en su limitador o mecanismo de restauración de IP de cliente. Solicitar la regla, umbral, ventana y si el campo de log procede de conexión TCP/REMOTE_ADDR o de CF-Connecting-IP/X-Forwarded-For. El DNS only observado no prueba por sí solo el comportamiento de cabeceras de toda la ruta. Los registros prueban rechazo HTTP, no un fallo de resolución DNS; no justifican cambiar nameservers.

Fuente primaria: https://developers.cloudflare.com/fundamentals/reference/http-headers/#cf-connecting-ip .

## Mejoras para reducir llamadas entre visitas

Caché pública opt-in de 60 segundos mediante Cache API de Workers para Home/proyectos/categorías, segregada por query, variables, endpoint y host. Exclusión de cookies, autorización, previews y futuras cabeceras custom de transporte. Fallos no cacheados; sin reintentos automáticos. X-Request-ID, User-Agent identificable y eventos HIT/MISS/BYPASS con duración y Retry-After. 40/40 pruebas y build pasan. Dos visitas locales responden 200 con contenido y requestId distinto: logs muestran tres MISS en primera visita y tres HIT en segunda, sin llamadas al CMS en esa segunda visita. Guía operativa en CMS_CACHE.md. Autenticación y validación del límite Hostinger siguen pendientes.

Se incluye en esta entrega el traslado ya solicitado de planes/guías desde raíz a docs, con enlaces README actualizados y referencias de diseño locales aún ignoradas. README sigue en la raíz; archivo .claude local no se incorpora.
