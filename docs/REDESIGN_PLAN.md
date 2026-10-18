# Plan de rediseño de alexcuadra.dev

Fecha: 17 de octubre de 2026. Estado: propuesta técnica y editorial; la interfaz aún no se ha rediseñado.

## 1. Objetivo y alcance

Presentar a Alex como desarrollador web senior con experiencia demostrable en calidad, automatización y DevOps. La prioridad es que una persona pueda entender su especialidad, revisar evidencia de proyectos y contactar. Mantener Astro, Tailwind, TypeScript, Cloudflare, WordPress, WPGraphQL y ACF gratuito. Mantener las URLs inglesas existentes para evitar perder enlaces y posicionamiento; añadir URLs españolas propias sin sustituir las actuales.

Las cifras de los mockups NO son reales y no deben migrarse como contenido ni usarse como valores por defecto. Las propuestas son únicamente una guía visual y de los datos que habrá que agregar. El posicionamiento acordado es desarrollo web senior, calidad, automatización y DevOps; SDET puede describir una especialidad respaldada por contenido, sin sustituir ese posicionamiento. Sitio bilingüe en inglés y español desde el rediseño. Mantener inglés en las URLs actuales y agregar español bajo `/es/`; gestionar ambas versiones y sus metadatos en WordPress, con traducciones revisadas y publicación independiente por idioma. Véase la propuesta de la sección 13.

### Fuente única de contenido: content.alexcuadra.dev

Toda la información publicable se agregará y administrará en **content.alexcuadra.dev**: identidad y posicionamiento, textos de secciones, especialidades, experiencia, proyectos, casos de estudio, tecnologías utilizadas, resultados, métricas, testimonios, artículos, menciones, imágenes, CV, disponibilidad, contacto, enlaces y metadatos SEO. Astro consume ese contenido por GraphQL autenticado y lo presenta; no mantener copias editoriales en componentes, archivos Markdown locales o constantes del frontend.

Los mockups determinan el tipo de información y su composición, no su veracidad. No importar sus cifras, nombres de testimonios, tecnologías atribuidas a clientes, estados de CI ni afirmaciones de rendimiento. Definir primero los campos o CPT necesarios con ACF gratuito, cargar los datos reales en WordPress y después conectar los componentes. Si un dato opcional falta, ocultar el bloque; si falta contenido esencial, mantenerlo en borrador y bloquear la publicación de esa página. No completar huecos con cifras ficticias ni mostrar cero como si fuera una medición.

Código, tokens visuales, estructura de componentes, validadores y mensajes técnicos de error siguen en el repositorio. Conteos, tiempo de lectura y otros valores derivados pueden calcularse desde contenido real del CMS; no convertirse en datos ficticios de marketing. Las etiquetas editoriales y de navegación se incluyen en los ajustes o páginas del CMS cuando se requiera editarlas. Los fixtures de pruebas serán sintéticos, explícitos y exclusivos del entorno de test; nunca serán fallback de producción.

El inventario de contenido debe mapear cada bloque del mockup a entidad/campo, responsable de carga, evidencia y estado (pendiente, borrador o aprobado). Para DevOps, documentar contribuciones concretas en CI/CD, despliegues, infraestructura, observabilidad o recuperación únicamente donde Alex haya participado, indicando rol, alcance y resultados verificables. Las herramientas de DevOps no se atribuyen a un proyecto solo porque aparezcan en la maqueta.

## 2. Análisis de las propuestas locales

Se revisaron las cuatro imágenes `screen.png`, sus correspondientes `code.html`, `obsidian_flow/DESIGN.md` y el inventario de texto del sitio dentro de `docs/design-proposal/`.

| Propuesta | Qué conservar | Qué ajustar antes de implementar |
| --- | --- | --- |
| Home / Senior Web Developer & SDET | Hero de posicionamiento, disciplinas, proyectos con contexto, CTA visible; buen puente entre frontend y calidad | Reducir altura y densidad en móvil. El terminal de pruebas y la disponibilidad deben ser información real cargada en WordPress; si falta, ocultar el bloque. No lanzar un botón de ejecutar CI público |
| Template individual de proyectos (carpeta `christianity_today_case_study_alex_cuadra_portfolio`) | Estructura reutilizable para TODOS los proyectos: rol, alcance, problema, decisiones, arquitectura, resultados y navegación entre proyectos | Christianity Today es solo el ejemplo visual del template, no una página especial ni su contenido definitivo. Cada proyecto aporta sus propios datos desde WordPress; mostrar únicamente los bloques que correspondan |
| Articles & Mentions / archive | Tarjetas por formato, búsqueda, filtros por plataforma y temas, artículo destacado | Los cuatro canales actuales son plataformas, no tipos de contenido. No confundir un artículo propio con una mención externa. Reducir estadísticas y evitar filtros vacíos; paginar antes de hacer búsqueda global |
| Article template | Lectura con buen ancho, resumen, bloques de código, tabla de contenidos, autor y relacionados | En móvil, TOC desplegable. Sin contador de aplausos, bookmarks con cuenta ni estado de CI en vivo en MVP. Compartir/copiar puede usar APIs del navegador; estados accesibles al completar o fallar |
| Obsidian Flow | Plus Jakarta Sans para títulos/cuerpo, JetBrains Mono para código y metadatos; cian, violeta y verde con jerarquía | Hay contradicciones entre tokens YAML, descripción y HTML: fondo `#0e131f` vs `#030712`, radios y primarios distintos. Definir una única fuente de tokens antes de construir componentes |

### Dirección visual recomendada

Base oscura `#0e131f`, superficie `#161c28`, texto `#dde2f3`, texto secundario `#bcc9cd`, acento principal `#4cd7f6`, secundario `#d0bcff` y éxito `#4edea3`. Conservar verde solo para estados comprobados. Verificar contraste por pareja real, incluyendo botones, chips, foco y texto sobre gradientes; no asumir que la paleta garantiza accesibilidad.

Contenedor de 1200 px; móvil de una columna con 16 px de margen, tablet con 24–32 px, escritorio con composición de hasta 12 columnas. Cuerpo de 16–18 px y lectura de 65–75 caracteres. Títulos fluidos con `clamp()`, sin copiar tamaños de escritorio al móvil. Desenfoques decorativos discretos, sin animaciones continuas costosas; respetar `prefers-reduced-motion` y colores forzados.

Implementar componentes Astro reutilizables: SiteHeader, MobileNav, Button, Badge, SectionHeader, ProjectCard, MediaCard, Metric, Prose, CodeBlock, TableOfContents, Breadcrumbs y ContactCTA. Mantener JavaScript limitado al menú, tema, filtros y acciones de copiar/compartir. Usar el Tailwind instalado y los iconos SVG actuales; los scripts CDN de Tailwind y las fuentes de iconos de los HTML son referencias de mockup, no dependencias de producción.

Mantener el selector de tema actual, pero diseñar y validar expresamente la variante clara: no existe en las referencias. Fuentes locales/subconjuntos y solo pesos necesarios; reservar proporciones para imágenes. Un único H1, skip link, navegación por teclado, foco visible, menú con `aria-expanded` y objetivos táctiles suficientes.

## 3. Arquitectura de información

La tabla siguiente describe las rutas inglesas existentes. Las equivalentes españolas se especifican en la sección 13 y forman parte del rediseño.

| Ruta | Alcance MVP |
| --- | --- |
| `/` | Posicionamiento, 3–4 especialidades, 3–4 proyectos destacados seleccionados, contenido reciente y contacto |
| `/work/` | Archivo de proyectos; filtro por disciplina/tecnología cuando el volumen lo justifique |
| `/work/[slug]` | Caso de estudio: contexto, rol, alcance, problema, decisiones, entregables y resultados verificables |
| `/about/` | Trayectoria, contribuciones, experiencia y formas de colaborar; ampliar la página actual |
| `/articles/` | Archivo unificado de artículos propios, apariciones, vídeos y charlas con destino interno o externo explícito |
| `/articles/category/[slug]` | Conservar archivos actuales de plataformas como páginas indexables; no cambiar slugs existentes |
| `/articles/category/[categorySlug]/[articleSlug]` | Conservar URLs de artículos; validar la relación categoría/artículo y devolver 404 si no existe |
| `/#contact` | CTA por email y perfiles reales, sin formulario ni proveedor de newsletter en primera fase |
| `/lab/` y `/lab/[slug]` | Nueva sección y detalle de proyectos personales en desarrollo, con CPT propio `lab_project`; primer candidato: qa-training-lab. Datos reales desde WordPress, sin ejecutar aplicaciones ni CI desde el portfolio |

Orden principal: Work, About, Articles y Contact; Home desde el logotipo. Incluir Lab como sección nueva del rediseño, con contenido propio y diferenciada de los trabajos para clientes. El avatar de cuenta de las propuestas no aporta un flujo al portfolio público y se omite. No lanzar RSS, newsletter, búsqueda remota o telemetría sin definir su fuente y funcionamiento.

## 4. Modelo editorial con ACF gratuito

Primero exportar/inventariar CPT, taxonomías y grupos ACF existentes desde WordPress y confirmar versiones de ACF, WPGraphQL y WPGraphQL for ACF. Los nombres actuales comprobados en código son `projects`, `articles`, `homePageFields`, `projects`, `articlesFields` y `aboutPage`. No renombrarlos directamente ni inventar queries contra campos aún no registrados. Los nombres nuevos siguientes son una propuesta, sujetos a comprobar colisiones en el esquema.

### Reutilizar primero

| Entidad | Campos propuestos / tipo | Uso |
| --- | --- | --- |
| Página Home existente | `specialties` relationship, `headline` text, `intro` textarea, `availability` select, `availability_note` text, `resume` file opcional, `featured_projects` relationship | Sustituir roles numerados por un mensaje editorial; selección explícita de destacados |
| Página de ajustes del sitio, slug `site-settings` | `contact_email` email, `github_url` URL, `linkedin_url` URL, `youtube_url` URL, `social_description` textarea, `default_social_image` image | Datos globales en una página normal con campos simples, sin ACF Options Page; decidir visibilidad y excluirla de navegación/sitemap |
| CPT `projects` existente | `summary` textarea, `client_name` text, `role` text, `period` text, `live_url` URL, `repository_url` URL, `featured` true/false, `display_order` number, `discipline` taxonomy | Tarjetas y cabecera del caso; conservar campos legacy hasta migrar |
| Caso de estudio, mismo proyecto | `problem` textarea, `approach` WYSIWYG, `outcomes` WYSIWYG, `testimonial_quote` textarea opcional, `testimonial_author` text, `evidence_url` URL | Estructura común más cuerpo Gutenberg para arquitectura, capturas, listas y bloques de código |
| CPT `articles` existente | `content_kind` select (article, talk, podcast, video, mention), `source_url` URL, `source_name` text, `published_at` date, `duration_minutes` number opcional, `featured` true/false, `summary` textarea, `social_image` image opcional | Diferenciar formato, plataforma y destino. Usar contenido nativo para artículo propio y resumen/enlace para mención externa |
| Artículo propio | título, extracto, contenido Gutenberg, autor, fecha, imagen destacada y temas nativos; `quote` actual opcional | Evitar duplicar datos nativos con nuevos campos; lectura estimada derivada del contenido |
| Página About existente | Campos actuales más `resume` file opcional; trayectoria en Gutenberg | No hace falta crear un CPT de experiencia para una sola biografía |

Conservar categorías actuales para Medium, Podcast, ValexWeb y WordPress.TV durante la migración. Añadir taxonomía de temas si se necesitan filtros transversales y tecnología/disciplina para proyectos. No convertir de golpe Podcast en taxonomía distinta: guardar una tabla de equivalencias y preservar sus archivos públicos.

No usar Repeater, Flexible Content, Gallery, Clone, ACF Blocks ni Options Pages PRO. Para colecciones variables, usar relaciones entre posts o Gutenberg. Las imágenes de un caso pueden vivir en bloques de imagen nativos. Confirmar que el editor y los tipos de campo escogidos están disponibles en la instalación gratuita real.

### Especialidades y contenido transversal

Incluir un CPT ligero `specialty` para las tarjetas editables de especialidades: título, extracto, cuerpo, `icon_key` select limitado al catálogo local, `display_order` number y relación opcional a proyectos. La Home selecciona registros mediante `specialties` relationship. Permite desarrollo web, calidad, automatización y DevOps sin campos numerados ni Repeater PRO; la taxonomía de disciplina de proyectos clasifica trabajos y no sustituye la descripción editorial de cada especialidad.

Ampliar `site-settings` y los campos de cada página para títulos y descripciones SEO, CTA y etiquetas editoriales; agregar los campos antes de conectar la UI. Imágenes y CV se administran en la biblioteca de medios de WordPress. Las métricas reales, si se publican, se guardan en el contenido del proyecto o en el CPT de métricas previsto, con fuente, fecha y método. La telemetría externa futura deberá tener su configuración y representación editorial en WordPress y una integración explícita; no crear contadores simulados en el frontend.

### CPT LAB confirmado: proyectos personales

Crear `lab_project` mediante ACF como entidad independiente de los CPT de proyectos de clientes y artículos. LAB es parte del alcance confirmado, no una opción condicionada al número de entradas. El nombre interno propuesto debe comprobarse por colisiones; no crear también `lab_experiment`.

Campos nativos: título, slug, extracto, cuerpo Gutenberg, imagen destacada, fechas y revisiones. Campos ACF gratuitos: `status` select (idea, in_progress, beta, released, archived), `repository_url` URL opcional, `demo_url` URL opcional, `purpose` textarea, `learning_goals` textarea, `technical_approach` WYSIWYG, `quality_strategy` WYSIWYG, `devops_notes` WYSIWYG, `last_reviewed_at` date, `featured` true/false y `display_order` number. Taxonomías de tecnologías/temas cuando aporten navegación; relaciones opcionales a artículos que explican el trabajo.

Archivo `/lab/` y detalle `/lab/[slug]`, con equivalentes `/es/laboratorio/` y `/es/laboratorio/[slug-es]`. Incluir selector de idioma, SEO y estados editoriales como el resto del sitio. Puede reutilizar componentes del template individual de proyectos, con variante para objetivos, avances, aprendizajes y próximos pasos; no exigir cliente, impacto comercial ni métricas de producción.

Primera entrada prevista: `qa-training-lab`, una aplicación personal de Alex en desarrollo. Antes de redactarla, revisar con Alex su propósito, funciones, stack, repositorio público/privado, demo y estado real. El nombre no permite inferir funciones ni métricas. No inspeccionar ni modificar su repositorio como parte de esta actualización del plan. Todo su contenido de presentación se carga en content.alexcuadra.dev; la aplicación vive y se ejecuta en su entorno propio.

### CPT de métricas condicionado a necesidad editorial

`project_metric` solo si se requieren métricas estructuradas y filtrables en varios proyectos: relación al proyecto, etiqueta, valor, unidad, valor anterior opcional, método, fecha de medición, fuente y permiso de publicación. Para MVP, resultados en Gutenberg evitan crear un CPT exclusivamente para reproducir tarjetas de una maqueta. Nunca usar columnas numeradas `metric_1`…`metric_4` para una lista que vaya a crecer.

Gestionar los registros de CPT/taxonomías y los grupos de campos desde ACF y exportar su configuración JSON/PHP compatible para versionado y backup. No volver a registrar los mismos CPT en otro plugin después de la transición. Este repositorio frontend no contiene el plugin custom actual: inspeccionarlo antes de desactivarlo y decidir dónde conservar los exports y cualquier lógica residual necesaria. WPGraphQL for ACF es el puente gratuito para exponer los grupos; activar Show in GraphQL y nombres únicos en CPT, taxonomías y grupos que corresponda.

## 5. GraphQL solo autenticado

La opción de WPGraphQL bloqueará las consultas anónimas que hace hoy `src/data/client.js`. Activarla sin desplegar primero el cliente autenticado rompería el sitio. El visitante seguirá entrando sin login: la autenticación ocurre entre Astro SSR y WordPress.

Flujo: navegador → Astro/Cloudflare → WPGraphQL con credencial de servicio → normalización de contenido publicado → HTML público. No añadir un proxy genérico que permita al visitante ejecutar queries arbitrarias con la credencial.

1. Crear un usuario de servicio con permisos mínimos para leer el contenido publicado requerido. No usar una cuenta administradora. Confirmar capabilities de CPT, ACF y taxonomías; la autenticación no implica autorización a todos los campos.
2. Preferir Application Password dedicada sobre HTTPS para servidor a servidor, si funciona con la instalación WPGraphQL real. Verificar soporte: la documentación advierte limitaciones; puede requerir integración/extensión compatible para reconocer `/graphql`. Alternativa: JWT con mecanismo de renovación documentado. No asumir que un password normal o una cookie del administrador servirá en Cloudflare.
3. Centralizar el transporte. Configuración propuesta: `WPGRAPHQL_URL` y secretos `WPGRAPHQL_USERNAME`, `WPGRAPHQL_APP_PASSWORD`. Variables del servidor, nunca `PUBLIC_*`. En Basic, codificar `usuario:application_password` y enviar Authorization; Base64 no cifra la credencial y requiere HTTPS.
4. Cargar secretos en runtime con el mecanismo compatible con Astro 5 + adapter Cloudflare 12: `astro:env/server`/`getSecret` o binding de runtime por contexto. No limitarse a `process.env` ni incrustar secretos con `import.meta.env` al compilar. Local: `.dev.vars` excluido de Git; Cloudflare: secretos del proyecto/entorno, separados para preview y producción. No enviar credenciales de producción a previews de PR no confiables.
5. Ejecutar POST, timeout acotado y clasificación de fallos: HTTP 401/403, `errors` GraphQL incluso con HTTP 200, respuesta inválida y caída de red. No registrar cabeceras, credenciales o errores crudos que puedan contener datos privados. No reintentar fallos de autenticación; como máximo reintento acotado de fallos transitorios idempotentes.
6. Cargar datos globales una vez por petición y pasarlos a Nav/Footer/CTA; eliminar peticiones desde componentes presentacionales. Caché por petición primero. Caché compartida de contenido publicado solo después de definir TTL, invalidación y separación estricta de borradores/previews; no almacenar indiscriminadamente respuestas autenticadas.
7. Consultar por slug/URI configurables en vez de IDs Home=19 y About=68. Tipar DTOs del frontend y normalizar nulos de ACF/imágenes/enlaces. Separar datos ausentes, 404 y caída de CMS: 404 real para contenido inexistente; respuesta 503 controlada para indisponibilidad, sin simular un archivo vacío ni exponer stack traces.
8. En staging validar primero lectura autenticada de Home, About, proyectos y artículos; activar restricción y confirmar que anónimo falla y usuario de servicio funciona. Desplegar frontend configurado y coordinar activación en producción con una ventana breve de verificación y rollback. Rotar/revocar credenciales sin reconstruir el frontend.

Este transporte todavía NO está implementado ni probado contra WordPress: requiere secretos y acceso al esquema. Las pruebas añadidas reproducen rechazo por autenticación, pero no demuestran que la instalación real acepte Application Passwords.

## 6. Hallazgos del código y SEO

Corregidos en esta revisión: fechas ISO restadas como números al ordenar proyectos; consulta redundante en cada PortfolioPreview; `<style>` dentro de otro `<style>` en el archivo de categorías.

Pendientes prioritarios: `getHomeInfo`/proyectos/categorías capturan errores y devuelven arrays vacíos, luego Nav/Home/Footer acceden a `[0]` sin validación; falta política común de errores. Detalles inexistentes lanzan excepciones en lugar de devolver 404. El artículo consulta `featuredImage.node.sourceUrl` pero intenta renderizar `article.imageUrl`. Varias relaciones ACF/imágenes se desestructuran asumiendo que nunca son null. Las listas GraphQL no implementan paginación por cursores: el límite por defecto puede ocultar registros; `getAllArticleSlugs` se limita a 100.

MainHead tiene viewport/descripciones duplicados, imágenes OG/Twitter vacías, idioma OG español frente a sitio inglés y referencias a manifest/iconos que no existen en `public`. About usa una descripción Lorem Ipsum. El enlace LinkedIn está rotulado YouTube. Corregirlos durante la base del rediseño; añadir imagen social real, metadata por contenido y canonical de producción. Añadir sitemap y robots con exclusión de páginas técnicas; JSON-LD Person, Article y breadcrumbs únicamente con datos reales. No eliminar URLs legacy durante el cambio visual.

## 7. GitHub y despliegue

Inspección local e historial: no hay `.github/workflows` previo. API de GitHub para `alexcu21/alexcuadra.dev`: `total_count: 0` y sin ejecuciones de Actions. Por tanto no existe un workflow remoto que pueda estar fallando. Esto no descarta compilación automática conectada directamente desde Cloudflare; no se ha inspeccionado su dashboard, configuración ni logs.

Añadido `.github/workflows/ci.yml`: PRs, push a main y ejecución manual; checkout, Node 22, instalación por lockfile, pruebas y `astro check && astro build`; permisos de lectura, cancelación de ejecuciones obsoletas y timeout. No usa CMS real ni secretos. Acciones oficiales verificadas en GitHub: checkout v7.0.1 y setup-node v7.1.0, con credenciales de checkout no persistentes. Se usan tags de versión; fijarlas a SHA y automatizar su actualización puede añadirse con la política del repositorio.

El build SSR actual no visita las páginas, así que compilar NO confirma que GraphQL esté accesible ni que el HTML renderice bien. No incluir secretos en builds de forks. Una futura prueba de integración autenticada debe ejecutarse solo en un entorno confiable y sin imprimir respuestas privadas.

Antes de activar despliegues: confirmar si el hosting es Cloudflare Pages o Workers, rama de producción, comando `npm run build`, directorio `dist`, compatibilidad de runtime y secretos por entorno. Conservar el adapter instalado en esta etapa; no sustituir el despliegue por un comando Wrangler de otra versión. No añadir un segundo despliegue de Actions si Cloudflare ya despliega por push. Configurar CI obligatorio en protección de main cuando el workflow esté publicado y haya ejecutado correctamente; la creación local no activa esta protección.

## 8. Pruebas y criterios de aceptación

### Añadidas ahora

`npm test` usa `node:test` sin dependencias nuevas ni CMS externo. Contratos de respuesta del cliente para Home, About, proyectos, categorías, artículo y slugs; variables GraphQL separadas; artículo ausente; errores GraphQL con HTTP 200; HTTP 403 y fallo de red. Pruebas de orden de fechas reales y colecciones vacías. Los mocks comprueban la lógica del cliente, no la existencia del esquema de WordPress. `npm run build` aporta validación Astro/TypeScript y compilación para Cloudflare. Resultado local de esta revisión: 14/14 pruebas pasan; build correcto sin errores de tipos ni la anterior advertencia de CSS anidado. Persisten avisos de compatibilidad sharp/Cloudflare, Browserslist antiguo y sugerencias por símbolos sin uso. YAML de CI parseado y validado localmente; todavía no ejecutado en GitHub.

### Durante la implementación

- Transporte: Authorization correcto solo en servidor, secretos ausentes, credencial inválida, timeout, errores HTTP/GraphQL, logs redactados y rotación. Prueba explícita que no retorna borradores ni datos privados con el usuario de servicio.
- Fuente editorial: modificar un texto, especialidad o metadato en WordPress debe reflejarse tras la invalidación/TTL definidos, sin editar ni reconstruir componentes; verificar ausencia de cifras de mockup y de fallbacks editoriales ficticios en producción.
- Modelo: nulos ACF, imágenes ausentes, fechas, relaciones, selección y orden de destacados, paginación por cursores, enlaces externos y variantes de formato.
- Rutas SSR: `/`, `/work/`, `/about/`, `/articles/`, un proyecto, una categoría y un artículo con fixtures; 404 de slug inválido, 503 de CMS caído. Verificar cuerpo y status, no solo HTTP 200: Astro puede comenzar una respuesta antes de fallar durante streaming.
- E2E con Playwright en la fase de componentes: navegación móvil/desktop, tema persistente, teclado, filtros/búsqueda/paginación, TOC, copiar enlace y ausencia de credenciales en HTML/bundles/red del navegador. Servidor de fixtures determinista local para no depender de WordPress en cada PR.
- Accesibilidad con axe + revisión manual de teclado, lector de pantalla, zoom y reduced motion. Capturas de regresión de cuatro plantillas en 375, 768 y 1440 px, con fuentes/imágenes estabilizadas.
- Rendimiento: medir producción/staging, no Astro dev. Objetivo LCP ≤2.5 s, CLS ≤0.1 e INP ≤200 ms cuando exista medición de campo. Lighthouse móvil ≥90 como referencia de laboratorio con entorno estable, sin publicarlo como métrica real de clientes.

No afirmar cobertura 100%, cero defectos o métricas de CI en UI a partir de estas pruebas. Añadir E2E/visuales cuando existan los nuevos componentes, para comprobar comportamiento real en lugar de snapshots del código actual que se reemplazará.

## 9. Fases y entregables

| Fase | Entregable | Criterio de salida |
| --- | --- | --- |
| 0. Base, esta revisión | Plan, contratos de pruebas, CI local y correcciones pequeñas verificadas | Tests y compilación pasan; sin cambios de WordPress o deploy |
| 0.1. Actualización y compatibilidad | Matriz de versiones verificadas, migraciones por lotes, lockfile reproducible y validación del runtime Cloudflare | Sin conflictos de peers/engines; pruebas, build y smoke SSR pasan; staging y rollback verificados |
| 1. Contenido, transición CPT y esquema | Inspección del plugin custom y transición de sus CPT a ACF; guía asistida de campos/CPT e idiomas, inventario/export ACF, textos EN/ES y evidencias de desarrollo senior, calidad, automatización y DevOps; carga completa en content.alexcuadra.dev; tabla legacy→nuevo y usuario de servicio | Datos publicables completos en WordPress, sin cifras de mockup; queries validadas en staging; respaldos y rollback disponibles |
| 2. Datos y autenticación | Transporte servidor, DTOs por idioma, relaciones de traducción, rutas EN/ES, paginación, globales por petición, estados 404/503 y secretos runtime | Anónimo rechazado y SSR autenticado operativo; sin secretos en cliente/logs |
| 3. Sistema visual y Home | Tokens unificados, temas, navegación y componentes Astro responsive; Home con contenido real | Revisión visual desktop/móvil y pruebas de teclado; sin enlaces de maqueta |
| 4. Proyectos y LAB | Archivo y template individual reutilizable para proyectos; CPT, archivo y detalle LAB con qa-training-lab como primera entrada prevista | Datos reales en CMS, variantes EN/ES, un proyecto y una entrada LAB completos; rutas antiguas conservadas |
| 5. Editorial y About | Archivo, filtros/paginación, artículo, TOC, perfiles y contacto | Destinos internos/externos correctos y contenido legible/accesible |
| 6. QA y salida | E2E EN/ES, axe, capturas, SEO técnico/editorial bilingüe, revisión Cloudflare, autenticación y monitoreo | CI verde, staging aprobado, canonical/hreflang/sitemap validados, 404/503 correctos y rollback ensayado |
| 7. Opcional | Telemetría real o suscripción | Fuente de datos/proveedor definido y contenido suficiente |

Orden recomendado: completar la fase 0.1 antes de implementar autenticación y componentes nuevos; cerrar contenido y esquema antes de construir pantallas que requieran datos inexistentes. Aplicar migración aditiva: crear campos, poblar y validar, desplegar queries compatibles y retirar legacy en una entrega posterior. Backup de base de datos y configuración antes de cambios; rollback del frontend a último deploy compatible y restauración del esquema si fuera necesario.

## 10. Decisiones abiertas

Recopilar evidencia de desarrollo web senior, calidad, automatización y DevOps; disponibilidad y CTA principal; mantener tema claro; qué casos tienen permiso para mostrar resultados; tipo de hosting Cloudflare y acceso al staging; versiones/plugins de autenticación; si artículos se escriben en WordPress o son mayoritariamente enlaces; contenido y estado real de qa-training-lab para su entrada LAB. Estas decisiones no bloquean acordar tokens y componentes base, pero sí el contenido final y la activación de autenticación.

Los documentos resultantes de planificación, seguimiento y guías se guardan en `docs/`. Los documentos se versionan en Git; las propuestas de referencia bajo `docs/design-proposal/` permanecen locales. Los documentos nuevos también deben guardarse en `docs/`. README.md permanece en la raíz como entrada del proyecto.

## Referencias oficiales consultadas

- [WPGraphQL: autenticación y problemas comunes](https://www.wpgraphql.com/docs/common-issues)
- [WPGraphQL: seguridad](https://www.wpgraphql.com/docs/security)
- [Astro 5: Cloudflare y secretos de runtime](https://v5.docs.astro.build/en/guides/integrations-guide/cloudflare/)
- [ACF: integración con WPGraphQL y CPT](https://www.advancedcustomfields.com/blog/wpgraphql/)
- [ACF: Repeater y funciones PRO](https://www.advancedcustomfields.com/resources/how-to-use-the-repeater-field/)

## 11. Seguridad: requisitos del rediseño

Esta sección es parte del alcance y de los criterios de salida. Son medidas pendientes de implementar, salvo las protecciones de CI ya indicadas. La revisión del código identifica superficies de riesgo; no demuestra que el sitio haya sufrido un ataque ni sustituye una auditoría del servidor de WordPress.

### Prioridad y responsables

| Prioridad | Medida | Dónde | Evidencia para cerrar |
| --- | --- | --- | --- |
| P0, antes de restringir GraphQL | Autenticación de servicio, permisos mínimos, secretos de runtime y pruebas de lectura de publicados | Astro + WordPress + Cloudflare | Anónimo denegado; servicio lee solo el contenido necesario; revocar la credencial impide el acceso |
| P0, antes de publicar rediseño | Sanitización del HTML y validación de URLs del CMS | Capa de normalización Astro | Payloads XSS bloqueados sin romper bloques editoriales legítimos |
| P0 | Evitar filtrado de secretos, borradores y respuestas privadas | Logs, HTML, bundles y cachés | Pruebas negativas y revisión de artefactos de producción |
| P1, antes de producción | Cabeceras de seguridad y CSP compatible con scripts/estilos de Astro | Respuesta SSR y assets Cloudflare | Pruebas sobre respuestas desplegadas, incluidas 404/503 |
| P1 | Límites de consultas, timeouts y protección ante abuso | WPGraphQL, transporte SSR y hosting del CMS | Consultas reales funcionan; solicitudes excesivas/profundas se rechazan de forma acotada |
| P1 | Actualizaciones revisadas y cadena de suministro | npm, Actions y plugins WP | Inventario de avisos relevantes, correcciones verificadas y CI verde |
| P1 | Recuperación y vigilancia | WordPress + Cloudflare | Restauración de backup ensayada, alertas de caída/auth y responsable definido |
| Condicional | CSRF, antispam y límites de escritura | Formularios/webhooks futuros | Implementar antes de habilitar cada endpoint de escritura |

### A. Secretos y aislamiento del CMS

- Antes de introducir secretos nuevos, ampliar exclusiones para `.dev.vars`, variantes `.dev.vars.*` y `.env.*`, dejando solo archivos de ejemplo sin valores reales. El `.gitignore` actual únicamente cubre `.env` y `.env.production`; no protege todas las variantes. Comprobar también archivos ya versionados: ignorar no retira secretos del historial. Si se descubre uno expuesto, revocar/rotar primero y tratar el historial por separado.
- No guardar contraseñas de WordPress, Application Passwords ni tokens en localStorage, cookies públicas, campos ACF, documentación, fixtures, reportes o commits. El valor `theme` actual puede seguir en localStorage porque no es una credencial.
- Usuario de servicio de lectura: comprobar que no tiene capabilities de publicar/editar, administrar usuarios/plugins o leer privados que el portfolio no necesita. Excluir borradores y privados explícitamente en consultas y normalización; no confiar solo en que la cuenta esté autenticada. Verificar con fixtures y registros privados de prueba.
- URLs del endpoint configuradas por servidor: aceptar exclusivamente HTTPS y el host del CMS previsto. Nunca construir el destino de una petición autenticada desde parámetros del visitante o un campo editorial. Impedir seguir redirecciones del endpoint hacia hosts no autorizados para evitar fuga de Authorization/SSRF. Si se añade un proxy de imágenes o fetch de medios, aplicar la misma política de destinos y límites.
- Separar credenciales de desarrollo, staging y producción, documentar propietario y revocación y probar rotación sin rebuild. No copiar secretos a previews no confiables. Las páginas de preview con contenido privado requieren acceso real y `Cache-Control: private, no-store`; `noindex` no controla el acceso.

### B. HTML de WordPress y enlaces

Actualmente About, proyectos y artículos insertan HTML del CMS con `set:html`; Astro no debe considerarse un sanitizador de ese contenido. El `set:html` de Icon usa SVG del catálogo local y tiene un origen distinto: no permitir que reciba SVG arbitrario del CMS.

Crear una sola función de sanitización del lado servidor, con una biblioteca mantenida compatible con el runtime Cloudflare y una política explícita. No crear un sanitizador con expresiones regulares. Mantener títulos y resúmenes como texto escapado normal. Permitir solo etiquetas/atributos editoriales necesarios; retirar scripts, atributos `on*`, `srcdoc`, URLs ejecutables, estilos arbitrarios, formularios y SVG/MathML no necesarios. Preservar enlaces, listas, citas, tablas, imágenes, encabezados y código usados realmente. Validar el HTML final después de cualquier transformación adicional.

Para embeds de YouTube/podcast, preferir componentes propios con proveedor e ID validados. Si se permiten iframes, limitar hosts HTTPS, permisos y sandbox a lo indispensable para cada proveedor; no aceptar HTML de embed libre. Para enlaces externos aceptar `https:` y, si el contenido legacy lo necesita, `http:`; tratar `mailto:` únicamente en componentes de contacto. Rechazar `javascript:`, `data:`, variantes codificadas, URLs protocol-relative inesperadas y destinos no válidos. Usar `rel="noopener"` explícito en enlaces con `target="_blank"`; añadir `noreferrer` cuando la política de privacidad lo requiera. Los enlaces inseguros deben omitirse o mostrarse como texto, sin sustituirlos por un enlace engañoso.

### C. Cabeceras HTTP y CSP

Configurar cabeceras en middleware/respuestas SSR de Astro. Cloudflare indica que `_headers` no aplica a respuestas generadas por Pages Functions/SSR; reservarlo para assets estáticos si corresponde. Comprobar el hosting real antes de elegir el mecanismo.

Base propuesta: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()` y protección de framing con CSP `frame-ancestors 'none'` y `X-Frame-Options: DENY` para este portfolio sin necesidad de ser embebido. HSTS solo en producción tras confirmar HTTPS estable; no activar `includeSubDomains` ni preload sin revisar todos los subdominios y su impacto.

Construir CSP a partir de los recursos reales: `default-src 'self'`, `object-src 'none'`, `base-uri 'none'`, `frame-ancestors 'none'`, `form-action 'self'`, y directivas específicas para scripts, estilos, fuentes, imágenes y embeds. Como GraphQL se consulta desde SSR, el navegador no necesita permiso `connect-src` hacia WordPress. Añadir únicamente orígenes de recursos públicos que la UI use. Para fuentes remotas actuales, permitir sus hosts concretos o autoalojarlas en el rediseño.

El script inline del tema y los estilos de Astro requieren hashes o nonces compatibles con esta versión; no imponer una CSP que rompa el sitio ni resolverlo con `script-src *`, `unsafe-eval` o un `unsafe-inline` permanente. Nonce único criptográfico por respuesta y coordinación con caché si se elige esa estrategia; hashes de build para contenido estático cuando resulte viable. Validar primero con `Content-Security-Policy-Report-Only` en staging y después aplicar en producción. No enviar reportes con URLs/datos privados a un tercero sin definir su tratamiento. Mantener política de desarrollo separada para HMR/WebSocket y barra de Astro.

### D. WordPress y disponibilidad

- Registrar versiones reales y mantener core, ACF, WPGraphQL, puente ACF y plugins de autenticación actualizados tras staging. Retirar plugins/temas sin uso; restringir administración a las personas necesarias y activar MFA para administradores donde la instalación lo permita. No confundir Application Password de servicio con login interactivo de un administrador.
- Verificar validación TLS entre Cloudflare, frontend y origen CMS. En el hosting de WordPress, desactivar display de errores en producción y proteger backups, dumps y logs contra descarga pública. No cambiar infraestructura sin revisar configuración y recuperación.
- Configurar límite de profundidad según las queries reales y la versión instalada; no asumir que el valor por defecto de la documentación esté activado en esta instalación. Acotar tamaño de página/aliases/batching y tiempo de ejecución donde el plugin/hosting lo permita. No bloquear introspección como sustituto de autorización; conservar herramientas de esquema solo para entornos/usuarios autorizados.
- Aplicar rate limiting al CMS y endpoints públicos costosos con las capacidades reales del proveedor. No asumir que el dominio del CMS está detrás de Cloudflare ni que el plan incluye todas sus reglas WAF. Definir umbrales a partir de tráfico legítimo y caché; una cabecera Origin/CORS no autentica una petición servidor a servidor.
- Mantener GraphQL de lectura para el portfolio: queries fijas conocidas por el servidor, sin aceptar documentos arbitrarios de clientes ni exponer un endpoint de ejecutar CI. Si el plugin permite limitar operaciones de escritura para el usuario de servicio, hacerlo y verificarlo además de los permisos de WordPress.
- Emitir errores genéricos al visitante y eventos operativos acotados sin datos sensibles; alertar sobre 401/403 persistentes, timeouts y 5xx. Servir degradación controlada cuando el CMS falle y evitar multiplicar consultas/reintentos por componente.

### E. Dependencias, CI y recuperación

Realizar `npm audit` sobre el lockfile y revisar si cada aviso afecta dependencias de producción o herramientas de desarrollo. No se ha ejecutado una auditoría de dependencias en esta revisión; no se declara el proyecto libre de vulnerabilidades. Priorizar avisos explotables y corregir versiones en una entrega comprobable; evitar `npm audit fix --force` automático que cambie el stack sin validación. Considerar las ramas/versiones soportadas de Astro y del adapter y su compatibilidad al planificar actualizaciones.

Fijar Actions a SHA revisados y añadir actualización automatizada con Dependabot; mantener permisos mínimos y nunca ejecutar código de PR no confiable con secretos mediante `pull_request_target`. La CI añadida usa `pull_request`, lectura de contenido y checkout sin credenciales persistentes, pero aún usa tags de versiones. Antes de producción configurar protección de main, revisión de cambios sensibles y gestión de secretos según las capacidades de GitHub disponibles. No introducir un escáner que suba código privado sin acordar servicio y acceso.

Backups separados de base de datos, medios y registros ACF/CPT; verificar restauración antes de migrar. Documentar rollback del frontend y del esquema, revocación de credenciales y responsable de incidentes. Un despliegue anterior no garantiza rollback si se han eliminado campos: mantener migraciones aditivas hasta completar la transición.

### F. Pruebas de seguridad que se incorporarán por fase

1. **Autenticación:** ausencia/expiración/revocación de credencial, permisos insuficientes, HTTP 200 con errores GraphQL, ausencia de borradores/privados y rechazo de operaciones anónimas. No usar secretos reales en fixtures.
2. **XSS y URLs:** scripts, eventos inline, URLs `javascript:`/`data:`/codificadas, SVG externo y embeds fuera de allowlist; confirmar que código, enlaces e imágenes legítimos se conservan. Prueba real en navegador de que payloads no se ejecutan.
3. **SSRF y secretos:** URL de CMS manipulada y redirección a otro host rechazadas; sin Authorization en HTML, bundles, mensajes al visitante, logs ni llamadas del navegador.
4. **Cabeceras:** CSP y cabeceras presentes en 200/404/503 sobre staging SSR; tema, estilos, imágenes y embeds autorizados funcionan; framing externo denegado. Validar separadamente assets estáticos y desarrollo.
5. **Caché y abuso:** preview/borrador nunca compartidos; paginación y profundidad acotadas; timeout/reintento limitados y 503 genérico. Pruebas de límites en staging controlado, sin prueba de carga sobre producción.
6. **Futuras escrituras:** si se añade formulario, validar cuerpo, longitud, email, destino fijo, rate limit y antispam; CSRF/Origin cuando existan cookies/sesiones. Para webhooks, firma/HMAC, expiración y protección contra replay. Un formulario sin backend no se considera entregado.

No se añaden hoy tests ficticios de controles que aún no existen: cada control se entrega con sus pruebas en la fase de implementación correspondiente. Los criterios P0 y P1 se incorporan a las fases 2 y 6 y bloquean la salida de producción hasta verificarse.

### Fuentes de seguridad

- [WPGraphQL: seguridad y límites de profundidad](https://www.wpgraphql.com/docs/security)
- [Cloudflare: cabeceras en respuestas estáticas y SSR](https://developers.cloudflare.com/pages/configuration/headers/)
- [OWASP: prevención de XSS y sanitización](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [OWASP: Content Security Policy](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html)


## 12. Fase 0.1: actualización de paquetes y verificación de compatibilidad

Objetivo: establecer una base actualizada, soportada y reproducible antes de desarrollar el rediseño y la autenticación. Mantener las tecnologías del stack; una actualización mayor puede requerir migrar configuración o integración sin cambiar de framework. Fase en ejecución: baseline verificado, lote intermedio completado y migración local a Astro 7/Tailwind 4/Workers aprobada por Alex. Pruebas y build pasan; instalación limpia reporta 0 avisos conocidos. Falta staging real y revisión visual. El estado y las evidencias de ejecución se registran en EXECUTION_STATUS.md.

### Inventario confirmado y restricciones iniciales

Versiones obtenidas del `package-lock.json` actual, no solo de los rangos declarados en `package.json`:

| Paquete / entorno | Base actual | Compatibilidad que hay que comprobar para el objetivo |
| --- | --- | --- |
| Astro | 5.1.1 | Versión soportada, engines de Node, cambios mayores y APIs de contenido/SSR |
| @astrojs/cloudflare | 12.1.0 | Declara peer Astro `^5.0.0`; no conservar este adapter al actualizar Astro a otro major sin comprobar un adapter compatible |
| @astrojs/tailwind | 5.1.4 | Declara Astro 3/4/5 y Tailwind `^3.0.24`; no asumir compatibilidad con Astro 6/7 o Tailwind 4 |
| tailwindcss | 3.4.17 | Configuración JS, utilities, reset, colores, tipografía y compatibilidad del pipeline de estilos |
| @astrojs/check | 0.9.4 | Compatibilidad con Astro elegido y TypeScript; peer actual TypeScript `^5.0.0` |
| TypeScript | 5.7.2 | Requisitos de Astro/check y diagnósticos de archivos Astro, JS y tipos de runtime |
| graphql-request | 7.1.2 | Peer GraphQL `14 - 16`; resolución instalada de `graphql`, fetch, errores y autenticación en runtime de Workers |
| dotenv | 16.4.7 | Carga de configuración local frente a secretos de runtime; retirar solo si se demuestra redundancia |
| @types/node | 22.10.3 | Alinear con Node de herramientas; estos tipos no garantizan que APIs Node funcionen en Cloudflare |
| Node / npm | Local verificado: Node 22.23.2 / npm 10.9.8; CI usa Node 22 | Elegir versión soportada por la combinación objetivo y fijarla de forma coherente en local, CI y build del hosting |
| Dependencias transitivas | Vite, Wrangler, Miniflare/workerd, esbuild, etc. del lockfile | Revisar versiones que arrastran Astro y adapter, engines, avisos de seguridad y módulos nativos |
| WordPress / ACF / WPGraphQL / puente ACF / autenticación | Versiones del servidor todavía no inventariadas | Compatibilidad conjunta con PHP, esquema GraphQL, permisos y reconocimiento de credenciales |

### Procedimiento de selección

1. Guardar baseline: commit de partida, lockfile, salida de tests/check/build, rutas y capturas de referencia, configuración de despliegue y avisos existentes. Añadir smoke tests de las rutas actuales antes de cambios mayores; las 14 pruebas actuales no cubren todo el runtime ni la interfaz.
2. Consultar `npm outdated` y `npm audit`; revisar changelogs, guías oficiales de migración y metadatos `engines`/`peerDependencies` de las versiones candidatas con `npm view`. Registrar para cada paquete: instalada, candidata, soporte, dependencias compatibles, cambios requeridos y evidencia de pruebas. No seleccionar automáticamente `latest` para todos.
3. Definir un conjunto objetivo coherente de Astro + adapter Cloudflare + integración de Tailwind + checker + TypeScript + Node. Revisar también dependencias transitivas mediante `npm ls`; no usar `--force`, `--legacy-peer-deps` ni overrides para ocultar incompatibilidades. Si un paquete bloquea el conjunto, actualizar/migrar su integración o documentar un objetivo temporal soportado y una fecha de resolución.
4. Aplicar primero parches/minors compatibles y después majors en entregas separadas. Para saltar desde Astro 5, revisar las guías de 6 y 7 y validar cada frontera necesaria; no es obligatorio publicar cada versión intermedia. Actualizar Astro y sus integraciones compatibles como conjunto, no el core aislado. Evitar mezclar actualización mayor, nueva autenticación y rediseño en una sola entrega.
5. Mantener `package.json` y `package-lock.json` sincronizados. Tras cada lote, hacer instalación limpia con `npm ci` en un checkout de validación/CI, sin borrar el entorno de desarrollo en uso. Revisar el diff del lockfile y las dependencias nuevas. Una instalación sin error no demuestra compatibilidad funcional.

### Cambios que requieren atención expresa

- **Cloudflare:** las guías modernas describen cambios desde Astro 6 en adapter, desarrollo con workerd, acceso a bindings/secretos y despliegue. Confirmar Pages frente a Workers y el destino soportado por la versión candidata antes de aceptar el upgrade. Si requiere migrar el modo de hosting, preparar esa migración y su rollback conservando Cloudflare. La decisión no puede basarse solo en que `astro build` pase.
- **API de secretos:** la sección 5 describe la base Astro 5 actual. Después de escoger versiones objetivo, actualizarla al mecanismo de runtime de esas versiones y probar rotación. No copiar accesos legacy como `Astro.locals.runtime.env` a una versión que haya cambiado esa API.
- **Tailwind:** comprobar si la integración actual admite Astro objetivo. Si se pasa a Tailwind 4, planificar explícitamente su integración Vite, imports CSS, tokens/configuración, cambios de utilities/preflight y requisitos de navegador. Validar visualmente componentes actuales y ambos temas antes de construir el nuevo sistema. Si se conserva Tailwind 3 temporalmente, demostrar compatibilidad y soporte en lugar de mantener una integración incompatible.
- **Contenido Astro:** revisar `src/content/config.ts`, colecciones Markdown y APIs retiradas/deprecadas; el proyecto conserva contenido local aunque las páginas usen WordPress. No eliminarlo automáticamente sin comprobar usos y acordar su destino.
- **Pruebas y CI:** alinear versión Node, tipos y lockfile; comprobar compatibilidad de futuros Playwright/axe/sanitizador con Node y Workers donde proceda. Una biblioteca válida en Node puede usar DOM o módulos nativos no disponibles en Workers.
- **WordPress:** actualizar plugins/core en staging separado, con backup. Comparar esquema antes/después y ejecutar consultas reales de todos los campos usados. Confirmar que se mantiene ACF gratuito y no aparece dependencia de funciones PRO. No actualizar a la vez el CMS y el cliente sin saber qué lado provocó una regresión.

### Puertas de salida obligatorias

- `npm ci` reproducible y `npm ls` sin peers inválidos relevantes; engines compatibles documentados.
- `npm test`, `npm run check` y `npm run build` pasan; revisar avisos nuevos y diferencias respecto al baseline.
- Arranque de desarrollo y ejecución del build en el runtime Cloudflare correspondiente; smoke tests de Home, About, archivos, proyecto, categoría, artículo y errores 404/503. Verificar contenido renderizado además del status HTTP.
- Comparación visual de móvil/escritorio y temas; navegación, enlaces, imágenes, fuentes y scripts del tema sin regresiones ni errores de consola.
- Consultas al CMS de staging, autenticación cuando esté integrada y contrato de esquema compatibles; sin exponer secretos ni alterar caché privada.
- Revisar avisos de seguridad restantes por severidad y exposición; resolver bloqueantes antes de producción y registrar excepciones justificadas con seguimiento.
- Staging desplegado en el hosting real, CI verde y rollback de dependencias/configuración probado. El resultado debe ser una matriz con estado **verificado**, **bloqueado** o **pendiente**, nunca una afirmación general de compatibilidad basada solo en versiones.

### Entregables

Matriz final de compatibilidad con versiones exactas y enlaces a guías; `package.json` y lockfile actualizados; versión de Node declarada y CI alineada; configuración Astro/Tailwind/Cloudflare migrada cuando proceda; evidencias de pruebas y despliegue de staging; registro de avisos pendientes y procedimiento de rollback. La implementación de las fases 2–6 utilizará esa matriz como base.

Referencias oficiales para seleccionar el objetivo, verificadas al preparar esta fase:

- [Astro: actualización del core y sus integraciones](https://docs.astro.build/en/upgrade-astro/)
- [Migración a Astro 6](https://docs.astro.build/en/guides/upgrade-to/v6/)
- [Migración a Astro 7](https://docs.astro.build/en/guides/upgrade-to/v7/)
- [Cloudflare adapter: compatibilidad y migraciones de runtime](https://docs.astro.build/en/guides/integrations-guide/cloudflare/)

## 13. Propuesta bilingüe: inglés y español

### URLs y navegación recomendadas

Conservar inglés como idioma predeterminado sin prefijo y añadir `/es/` para español. Esto evita migrar todos los enlaces actuales a `/en/`. Una URL siempre representa un único idioma y devuelve HTML completo en SSR: no traducir mediante JavaScript ni variar el idioma de una misma URL según cookies o Accept-Language.

| Inglés existente | Español propuesto |
| --- | --- |
| `/` | `/es/` |
| `/about/` | `/es/sobre-mi/` |
| `/work/` | `/es/proyectos/` |
| `/work/[slug]` | `/es/proyectos/[slug-es]` |
| `/articles/` | `/es/articulos/` |
| `/articles/category/[slug]` | `/es/articulos/categoria/[slug-es]` |
| `/articles/category/[categorySlug]/[articleSlug]` | `/es/articulos/categoria/[categoria-es]/[articulo-es]` |
| `/#contact` | `/es/#contacto` |
| `/lab/` y `/lab/[slug]` | `/es/laboratorio/` y `/es/laboratorio/[slug-es]` |

Los slugs españoles de contenido se editan en WordPress y pueden diferir de los ingleses. Las bases `/es/proyectos/` y `/es/articulos/` las controla el router Astro: no requieren una función de traducción de bases CPT de pago en WordPress. No crear `/en/` como copia indexable; si llega a existir, redirigir de forma permanente al equivalente inglés correcto. Definir una política única de slash final y una sola versión HTTPS/host.

Selector accesible **English / Español**, visible en desktop y menú móvil, sin banderas como sustituto de idioma. Enlaza a la traducción equivalente mediante su relación real de WordPress, no cambiando un prefijo a ciegas. La URL elegida controla el idioma; puede recordarse la preferencia para sugerencias, sin redirecciones automáticas que impidan acceder a un idioma. Si no hay traducción, mostrar un aviso localizado y un enlace explícito a la portada/archivo del otro idioma; no aparentar que existe un equivalente.

### Gestión en content.alexcuadra.dev

**Opción recomendada, condicionada a compatibilidad:** Polylang gratuito para organizar pares EN/ES de páginas, proyectos, artículos, proyectos LAB, especialidades y taxonomías, más una extensión compatible para exponer idiomas y relaciones en WPGraphQL. WPGraphQL lista una extensión gratuita WPGraphQL Polylang; su existencia no garantiza que la versión actual sea compatible con las versiones elegidas. La fase 0.1 debe verificar mantenimiento, licencias, soporte, esquema, autenticación y comportamiento de ACF antes de instalarla en producción. No asumir sincronización avanzada de ACF, slugs idénticos entre traducciones o funciones PRO.

**Alternativa sin plugin multilingüe:** campos ACF gratuitos `content_locale` select (`en`, `es`) y `translation_key` text compartido por las dos versiones, con un registro independiente por idioma. Un pequeño plugin de sitio valida unicidad por tipo + translation_key + idioma y expone búsquedas/relaciones de traducción a WPGraphQL. Las taxonomías también requieren una estrategia de idioma y equivalencias. Es viable, pero implica mantener validación, filtros y experiencia editorial propios; no mezclar este mecanismo y Polylang como dos fuentes de verdad.

Tomar la decisión definitiva al terminar la matriz de compatibilidad. En ambas opciones, toda traducción vive en WordPress, incluidos navegación, CTA, etiquetas de filtros, mensajes editoriales, disponibilidad, alt de imágenes, títulos SEO y descripciones. Añadir una página de ajustes por idioma, relacionada con su equivalente; campos técnicos compartidos como email/URLs pueden copiarse de forma explícita, sin sobrescribir los traducibles. Datos numéricos/fuentes pueden mantenerse coherentes entre versiones; sus etiquetas y explicaciones se traducen. Separar alt localizado de la imagen compartida cuando la biblioteca de medios no permita resolverlo por idioma.

No crear parejas de campos `headline_en` y `headline_es` en cada registro como solución general: duplican el esquema y dificultan publicación, slugs y SEO independientes. Reutilizar el mismo grupo ACF en los registros de ambos idiomas. Relaciones de Home, proyectos destacados, especialidades y términos deben apuntar a registros del idioma correspondiente. Los nombres internos de campos y valores de select permanecen estables; se traducen sus etiquetas visibles, no las claves del contrato GraphQL.

### Comportamiento técnico y editorial

Configurar i18n de Astro con locales `en`/`es`, inglés predeterminado sin prefijo, según las APIs de la versión seleccionada en fase 0.1. Usar un mapa central de rutas localizadas, plantillas Astro compartidas y adaptadores de datos por idioma. Evitar duplicar el markup completo de cada página. DTO común: idioma, URI pública, equivalentes publicados, título, contenido, SEO y estado editorial. No asumir nombres exactos de campos GraphQL de una extensión antes de inspeccionar su esquema.

Incluir locale en consultas, filtros, paginación, caché y claves de deduplicación; normalizar relaciones antes de construir enlaces. Un CMS autenticado no debe hacer visibles borradores de traducciones. No servir texto inglés bajo una URL española como fallback indexable. Una traducción no publicada no entra al sitemap ni a hreflang; el acceso a una URL española inexistente devuelve 404 localizada. Una caída del CMS sigue siendo 503 y no se convierte en “traducción ausente”.

Publicar Home, About, contacto, navegación y archivos principales completos en ambos idiomas antes del lanzamiento bilingüe. Los casos/artículos pueden traducirse de forma progresiva; cada archivo lista únicamente contenido publicado de ese idioma. Un recurso externo puede estar en otro idioma: indicar idioma del original y traducir la descripción, sin fingir que el recurso externo está traducido. Traducción automática solo como borrador revisado por Alex; no publicación automática de resultados, testimonios o detalles técnicos.

Criterios: `lang="en"`/`lang="es"`, formatos de fechas/números con Intl, equivalencias correctas, ninguna mezcla accidental de CTA/menú, navegación por teclado y selector utilizable sin JS. Pruebas de pares completos, traducción ausente, borrador, slugs distintos, filtros/paginación por idioma y aislamiento de caché; verificar títulos largos en ambos idiomas y los dos temas.

## 14. Ejecución guiada: creación de campos y carga editorial

Durante la fase 1, el asistente debe guiar a Alex paso a paso en WordPress; no limitarse a entregar nombres de campos ni asumir que ya existen. Las instrucciones se basarán en la versión e interfaz reales de ACF gratuito y de los plugins instalados. No se han creado campos ni instalado plugins como parte de esta propuesta.

Secuencia de trabajo por bloques pequeños:

1. Revisar juntos CPT, grupos ACF y esquema actuales; exportar/resguardar antes de cambiar. Confirmar el mecanismo multilingüe de la sección 13 y crear idiomas/relaciones en staging.
2. Entregar una ficha por grupo: nombre visible, entidad y regla de ubicación, nombre GraphQL único y lista exacta de campos. Para cada campo indicar etiqueta, nombre interno, tipo gratuito, requerido/opcional, opciones, validación, formato de retorno, comportamiento de traducción y ejemplo claramente ficticio solo para pruebas.
3. Guiar la creación en ACF: dónde entrar, qué grupo/campo agregar, cómo configurar ubicación, exposición GraphQL y publicación. Si una opción exige PRO o no está en esa versión, adaptar el modelo a campos simples, relaciones, Gutenberg o CPT; no pedir una licencia de pago como requisito implícito.
4. Empezar por ajustes globales EN/ES y Home; luego especialidades, proyectos/casos, proyectos personales LAB, artículos/medios, SEO y About. Mantener campos antiguos hasta validar los nuevos. Crear registros de cada idioma y relacionarlos; no duplicar grupos ACF por idioma.
5. Tras cada bloque, comprobar con Alex el resultado y ejecutar una consulta autenticada del esquema real. Verificar campo retornado, tipo, nulos, formato de medios y relaciones por idioma. Si falta acceso al dashboard, seguir guiando con instrucciones precisas; pedir solo la información necesaria sin solicitar credenciales en el chat.
6. Cargar textos reales, archivos, evidencias y traducciones revisadas; preparar checklist de pendientes por página. Configurar SEO de cada idioma y comprobar el render en staging antes de declarar el bloque terminado.
7. Exportar y versionar configuración ACF/CPT sin datos secretos; actualizar diccionario de campos y contrato de queries. Probar migración en staging y conservar rollback antes de retirar campos legacy.

Ficha mínima que se entregará para cada campo: **grupo → ubicación → etiqueta → nombre interno → tipo → opciones/validación → retorno → traducción/compartido → exposición GraphQL → consulta de verificación**. La creación de un campo no se considera completada hasta verlo funcionar con datos reales en la vista correspondiente.

## 15. SEO ampliado: técnico, editorial y bilingüe

### Metadatos y contenido desde WordPress

Grupo SEO gratuito reutilizable para páginas y CPT: `seo_title` text, `seo_description` textarea, `social_image` image y `indexable` true/false con valor inicial definido. Puede ampliarse con título/descripción social si hay necesidad editorial real. No imponer longitudes como garantías de cómo Google mostrará el snippet; dar orientación y revisar calidad/claridad. Fallback editorial: título/extracto reales del mismo registro e idioma, nunca contenido de mockup ni texto del otro idioma.

Canonical se genera con el dominio público y la ruta normalizada; no usar la URI del CMS. Las excepciones de canonical deben ser administrativas y justificadas, por ejemplo republicación íntegra con un original externo; una mera mención resumida mantiene su canonical propio. No exponer edición libre de URLs canónicas a campos sin validación. Si ya existe plugin SEO, inventariarlo antes de agregar grupos duplicados y escoger una única fuente compatible con WPGraphQL y el modelo bilingüe.

Títulos/descripciones únicos por página e idioma, encabezados semánticos, enlaces descriptivos y contenido original que explique rol, decisiones y resultados verificables en desarrollo web, calidad, automatización y DevOps. Plan editorial por intención de búsqueda en cada idioma, sin traducción literal forzada ni repetición de palabras clave. Relacionar especialidades, casos de estudio y artículos del idioma correspondiente. No crear archivos vacíos o páginas casi idénticas solo para aumentar URLs.

### Canonical, hreflang e indexación

Cada traducción publicada tiene canonical autorreferente. No canonicalizar español hacia inglés. Emitir alternates absolutos `hreflang="en"` y `hreflang="es"` solo para equivalentes publicados, con reciprocidad e inclusión de la propia página; usar `x-default` hacia el equivalente inglés cuando exista y sea el destino predeterminado. No enlazar cada artículo español a la Home inglesa como si fuera su traducción. Si solo existe un idioma, no inventar un par.

Sitemap XML generado desde el inventario real del CMS: rutas SSR no quedan cubiertas automáticamente por conocer los archivos Astro. Recorrer conexiones con paginación, excluir borradores, ajustes técnicos, páginas noindex, URLs inexistentes y parámetros de búsqueda; `lastmod` procede de cambios reales del contenido. Si incluye alternates, mantenerlos coherentes con el HTML y su reciprocidad. Validar sitemaps de ambos idiomas sin generar contenido vacío si el CMS falla; servir un error temporal en lugar de sustituir por un sitemap incompleto.

Robots permite recursos necesarios y referencia el sitemap. Previews/staging requieren control de acceso y noindex; robots no protege datos privados. No bloquear por robots páginas en las que el buscador deba leer noindex. En búsquedas/filtros, definir una política antes de lanzar: búsquedas internas noindex; solo archivos curados con contenido útil son indexables. Paginas de paginación accesibles por enlaces reales, canonical propio cuando representan conjuntos distintos y navegación por idioma sin ciclos ni cadenas de redirecciones.

Preservar URLs inglesas y crear mapa 301 para cambios inevitables; probar artículos, categorías y enlaces entrantes. 404 genuinas para contenido eliminado/no existente y 503 temporales para fallos del CMS, evitando soft-404 y respuestas 200 vacías.

### Datos estructurados, medios y rendimiento

JSON-LD Person/ProfilePage donde corresponda, Article para artículos propios y BreadcrumbList para páginas jerárquicas. Usar IDs estables y `inLanguage`; datos deben coincidir con contenido visible y evidencia real. No convertir menciones externas en artículos propios ni añadir ratings, premios, empleos o cifras no verificadas. La validez del schema no garantiza resultados enriquecidos. Revisar XSS al serializar JSON-LD desde campos editoriales.

Imágenes con alt localizado, dimensiones, formatos eficientes y proporción reservada; imagen principal priorizada cuando sea LCP y lazy loading para imágenes bajo el pliegue. Imagen social pública y válida por contenido/idioma, sin bloquear rastreadores con autenticación del endpoint GraphQL. Fuentes optimizadas, poco JS y HTML SSR con el contenido principal ya presente. Mantener las metas de Core Web Vitals de la sección 8 como objetivos medibles.

### Verificación y seguimiento

Añadir pruebas de head renderizado por idioma: canonical, lang, títulos/descripciones, robots, hreflang recíproco y ausencia de duplicados/valores vacíos; validar JSON-LD, URLs de sitemap, paginación, redirecciones y exclusión de contenido privado. Comprobar respuestas SSR completas y enlaces sin depender de JS.

En staging, crawler controlado y revisión manual de los tipos de página EN/ES; después de publicar, verificar Search Console del dominio, enviar sitemap y revisar indexación, errores, consultas por idioma y Core Web Vitals. Registrar baseline previo y comparar tras el cambio; no prometer mejoras de ranking. La configuración de Search Console se hará con acceso autorizado durante ejecución.

Fuentes oficiales para la propuesta bilingüe y SEO:

- [Astro: rutas internacionales](https://docs.astro.build/en/guides/internationalization/)
- [Polylang: páginas, CPT y traducciones](https://polylang.pro/documentation/support/getting-started/translating-pages-posts-categories-and-tags/)
- [WPGraphQL: extensión Polylang gratuita; compatibilidad por validar](https://www.wpgraphql.com/extenstion-plugins/wpgraphql-polylang)
- [Polylang: funciones de traducción de bases de URL de pago](https://polylang.pro/documentation/support/guides/translating-urls-slugs/)
- [Google: gestión de sitios multilingües](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites)
- [Google: equivalentes localizados y hreflang](https://developers.google.com/search/docs/specialty/international/localized-versions)

## 16. Transición de CPT del plugin custom a ACF

### Aclaración y primera hipótesis que investigar

Los CPT actuales de proyectos y artículos se registran mediante un plugin personalizado de WordPress. El objetivo acordado es sustituir ese registro por la interfaz de CPT de ACF gratuito, para facilitar mantenimiento. Los nombres GraphQL `projects` y `articles` usados en el frontend no prueban cuáles son las claves internas `post_type` de WordPress: hay que obtenerlas del código del plugin y del servidor.

**Puede que no sea necesaria una migración de contenido.** Si ACF registra exactamente las mismas claves internas, se trataría de cambiar quién registra los CPT; los registros existentes pueden continuar asociados al mismo tipo. Esto es una hipótesis a validar en staging, no una garantía ni una migración ya realizada. Cambiar una etiqueta visible o el proveedor de registro no obliga por sí mismo a copiar las entradas.

La importación documentada por ACF desde CPT UI no implica una importación automática desde cualquier plugin custom. Inspeccionar su código primero y, si no existe importador apropiado, recrear las definiciones equivalentes en ACF siguiendo la guía asistida de la sección 14.

### Investigación obligatoria antes de cambiar

Obtener el plugin custom, su versión y una copia de staging. Inventariar `register_post_type`, taxonomías, hooks de activación/desinstalación, permisos, meta/campos, registros de GraphQL, resolvers, filtros, shortcodes y automatizaciones. La desactivación podría retirar funciones adicionales; desinstalar puede incluso ejecutar limpieza de datos. No eliminar ni desinstalar el plugin durante la exploración.

Por cada CPT registrar: clave interna, etiquetas, `supports`, `public`/`publicly_queryable`, `show_ui`, `show_in_rest`, jerarquía, archivo/rewrite, capabilities/map_meta_cap, taxonomías y nombres/visibilidad GraphQL. Comparar grupos ACF y sus field keys, reglas de ubicación y metadatos existentes; no recrear field keys innecesariamente. Si el plugin almacena información fuera de posts/postmeta o calcula campos mediante resolvers, documentar y preservar esa lógica expresamente.

### Ruta A recomendada: conservar las claves internas

1. Exportar backup de base de datos, medios, configuración y plugin; registrar IDs, slugs, estados, autores, fechas, contenidos, metadatos, términos, relaciones y conteos por CPT/idioma. Guardar consultas GraphQL y rutas frontend de referencia.
2. Preparar en staging las definiciones ACF equivalentes. Coordinar un único proveedor activo por clave: no mantener registro simultáneo desde ACF y plugin para el mismo CPT. Preparar el cambio controlado sin dejar visible al público un estado intermedio.
3. Desactivar únicamente el registro sustituido cuando sea posible; si hay que desactivar el plugin completo, separar antes sus otras funciones necesarias. Activar el registro ACF con las mismas claves, taxonomías y permisos. Conservar o reproducir los nombres GraphQL y cualquier hook requerido para exponerlos; ACF UI no garantiza reemplazar todas las extensiones custom.
4. Verificar que las entradas existentes aparecen sin copiar contenido: mismos IDs, metadatos, campos, imágenes, relaciones, términos y estados. Refrescar reglas de permalinks una vez si cambia/lo requiere el registro, no en cada petición. Revisar esquema GraphQL y consultas autenticadas.
5. Probar archivos/detalles, SEO, permisos, editor y creación/edición de un registro nuevo. Añadir idiomas una vez estabilizada esta transición para no mezclar dos causas de cambios de datos.
6. Repetir procedimiento validado en producción con backup y rollback. Si falla, restaurar el proveedor anterior de registro y desactivar el nuevo, sin borrar posts ni instalar rutinas de limpieza. No eliminar el plugin hasta confirmar que ninguna lógica residual lo necesita.

### Ruta B excepcional: cambiar la clave del CPT

Solo si se demuestra necesario, diseñar una migración idempotente y ensayada, con dry-run, mapeo origen/destino y verificación de todos los IDs y referencias. Preferir conservar IDs al reasignar tipo cuando sea compatible; si hay que copiar, mapear IDs, relaciones, medios, términos, traducciones, revisiones y metadatos, evitando duplicados. Usar APIs/herramientas WordPress con alcance explícito; no ejecutar reemplazos SQL globales ni asumir que una exportación/importación preserva todo.

Actualizar ubicación de grupos ACF, permisos, esquema/resolvers GraphQL, queries, rutas, sitemap, canonical y redirecciones que cambien. No renombrar al mismo tiempo field keys o nombres GraphQL sin una razón y un mapeo independientes. Comparar conteos y valores antes/después y validar cada relación, no solo el número de posts.

### Evidencia de cierre y alcance

Entregable: informe que determine **cambio de registro sin mover contenido** o **migración de contenido necesaria**, con motivo, claves exactas, configuración ACF exportada, lógica custom que se conserva, procedimiento repetible y rollback. Pruebas de equivalencia de datos, lectura GraphQL con permisos mínimos, edición de campos, URLs anteriores y bilingüismo posterior.

`lab_project` es un CPT nuevo creado directamente en ACF: no requiere migrar entradas legacy. Su primera entrada se redactará con datos reales de qa-training-lab. El template de la carpeta Christianity Today sirve para todas las fichas individuales de proyectos; no crea un CPT ni un template exclusivo de ese cliente.

Fuentes oficiales para validar el procedimiento:

- [ACF: registro de CPT y clave del tipo](https://www.advancedcustomfields.com/resources/registering-a-custom-post-type/)
- [ACF: post types, taxonomías e importación desde CPT UI](https://www.advancedcustomfields.com/resources/post-types-and-taxonomies/)

## 17. Compatibilidad editorial con Gutenberg y estilos básicos

Evaluar e implementar soporte de contenido creado con los bloques nativos de WordPress en artículos, casos de estudio, About y LAB. No exigir convertir headings, imágenes, columnas o citas en campos ACF: el cuerpo editorial vive en Gutenberg; ACF conserva los datos estructurados del template.

Recibir HTML renderizado por WordPress vía GraphQL no importa automáticamente las hojas de estilo del tema ni los estilos globales de Gutenberg. Inventariar bloques y HTML reales del CMS antes de elegir el conjunto CSS; no cargar todo el tema de WordPress ni scripts del editor en el frontend Astro.

| Bloque / formato | Soporte previsto |
| --- | --- |
| Párrafos, negrita, cursiva, enlaces y listas | Semántica y espaciado coherentes con Prose; enlaces seguros y foco visible |
| Encabezados | H2–H6 dentro del cuerpo, con escala responsive y anchors/TOC; H1 reservado al título de página. Detectar y corregir H1 editoriales o niveles incorrectos antes de publicar |
| Imágenes y figuras | Imagen, alt localizado, caption, dimensiones y alineación; tamaños responsive y límites del ancho de lectura |
| Citas y pullquotes | Blockquote, cite y variantes permitidas, con contraste y tipografía de ambos temas |
| Columnas y grupos | Reconocer columnas, bloques anidados y alineaciones; apilar en móvil según el comportamiento editorial permitido, sin desbordamiento |
| Tablas | Encabezados semánticos, captions y scroll horizontal contenido en móvil |
| Código y preformateado | Preservar espacios, escapes y scroll; resaltado compatible, sin ejecutar contenido |
| Separadores, botones y media-text | Evaluar según inventario real; variantes acotadas y enlaces validados |

Comparar tres alternativas en staging: (A) CSS propio y acotado para HTML semántico y clases core, (B) importar solo estilos core de los bloques utilizados y adaptar tokens, (C) renderizado por bloques para casos que necesiten componentes específicos. Recomendación inicial: A para tipografía y estructura básica, B solo donde reduzca mantenimiento sin conflictos con Tailwind; C para embeds/interacciones que no puedan resolverse de forma segura con HTML. Documentar la elección y el coste de actualización de WordPress.

Crear una capa `.wp-content`/Prose aislada y una allowlist de clases estructurales necesarias, como `wp-block-columns`, `wp-block-column`, `wp-block-image`, `wp-block-quote`, clases de alineación y layouts soportados. Verificar qué selectores/tokens de layout genera la versión real; CSS core por sí solo puede no incluir estilos dinámicos del tema. Mapear tamaños y colores permitidos a tokens del diseño y valorar alinear presets del editor mediante configuración versionada de WordPress, compatible con ACF gratuito.

Valorar ajustes editoriales básicos de alineación, tamaños, proporciones de columnas y espaciados. No prometer copia pixel-perfect del tema del CMS ni permitir cualquier CSS inline. Si Gutenberg necesita estilos inline, admitir únicamente propiedades y valores validados (por ejemplo proporciones de columnas acotadas), o convertirlos a clases controladas, coordinándolo con sanitización y CSP. Mantener prohibición de scripts, eventos, HTML peligroso y URLs ejecutables. No eliminar indiscriminadamente todas las clases/estilos y romper columnas legítimas, ni permitir `style` completo solo para conservarlas.

Durante la guía de WordPress, crear con Alex una página de prueba de bloques que contenga todos los formatos acordados; explicar qué opciones son compatibles. Comprobar HTML que entrega GraphQL, sanitización, render SSR y paridad visual EN/ES, móvil/escritorio y temas claro/oscuro. Pruebas de regresión: columnas anidadas, imágenes con caption, textos largos, tablas, citas, código y payloads XSS. Confirmar que la sanitización conserva el formato aprobado y elimina el peligroso. Las actualizaciones de WordPress/core block CSS requieren repetir estas comprobaciones.

Esta valoración se hace en fase 1 al inventariar contenido; la implementación se comparte entre fase 3 (Prose y estilos) y fases 4–5 (templates), con aceptación en fase 6. No está implementada todavía.

Referencia: [WordPress: arquitectura de estilos del editor y frontend](https://developer.wordpress.org/block-editor/explanations/architecture/styles/).
