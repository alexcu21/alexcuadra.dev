# Blog nativo bilingüe — 19 octubre 2026

## Entrega

Archivo y ficha compartidos para `/blog/`, `/blog/[slug]`, `/es/blog/` y `/es/blog/[slug]`. Consumen exclusivamente `post` nativo de WordPress; `articles` conserva sus referencias externas y URLs legacy.

- Lectores paginados con validación pública e idioma explícito; sin fallback ES→EN, contenido privado ni inventarios parciales.
- Archivo vacío, ficha ausente o slug del otro idioma:404/no-store/noindex. Fallo CMS, incluyendo páginas tardías y navegación:503/no-store/noindex.
- Gutenberg pasa por `WordPressContent`; extractos convertidos a texto para tarjetas/metadatos. Fecha real GMT presentada en UTC, sin fecha de sustitución.
- Canonical propio y hreflang/selector solo hacia traducciones públicas verificadas. Archivos solo se vinculan entre idiomas cuando ambos contienen posts públicos.
- Menú anuncia Blog con una consulta de disponibilidad de un registro público del idioma, sin descargar cuerpos ni paginar todo el archivo. Peticiones idénticas se deduplican dentro de cada SSR.
- Sitemap incorpora archivos no vacíos y slugs reales por idioma; su lector completa la paginación antes de emitir XML. Sin fechas SEO inventadas.

## Contenido CMS autorizado y publicado

Alex pidió «en el blog crea un post de ejemplo» y autorizó publicar qa-training-lab en LAB. No hay que volver a solicitar esa autorización.

| Idioma | ID | Título / slug |
| --- | --- | --- |
| EN | 202 | Example post: structuring a test case / `example-post-structuring-a-test-case` |
| ES | 204 | Publicación de ejemplo: cómo estructurar un caso de prueba / `publicacion-de-ejemplo-como-estructurar-un-caso-de-prueba` |

Publicados y vinculados mediante Polylang. Editor confirma «is now live»; tabla de Posts confirma ambos Published y enlaces recíprocos. Texto educativo original, etiquetado como ejemplo ficticio, sin atribuir trabajo de cliente ni resultados ejecutados. Extractos específicos en cada idioma, sin imagen inventada. WordPress registra20 octubre UTC (19 octubre local); frontend conserva esa fecha real en UTC. No se añadió autor editorial al template a partir del usuario administrativo.

El CMS es compartido: estas dos publicaciones sí modifican contenido real. El código frontend de este incremento permanece local, sin deploy staging/producción ni envío a Search Console.

## Validación

- 181 pruebas unitarias correctas, incluyendo paginación, visibilidad/idioma, traducciones públicas, rutas, fechas, extractos y disponibilidad de menú/sitemap.
- `npm run test:references-ssr`: correcta sobre templates reales y transporte GraphQL sintético. Incluye archivos/fichas EN/ES,404/503, sanitización, hreflang, menú actual, archivo vacío sin enlace y fallo tardío del menú sin HTML parcial.
- `npm run build`: correcto,137 archivos Astro revisados,0 errores/advertencias/hints de diagnóstico. Avisos de configuración Shiki/CSP conocidos permanecen; no representan una auditoría CSP del Worker.
- Navegador integrado accedió al mismo origen4325 tras iniciar Astro. CMS real→GraphQL→archivo EN→ficha EN→selector ES→ficha ES→archivo ES→archivo EN verificado.
- Ficha ES oscura a320px CSS: sin overflow. Menú móvil incluye Blog, Escape cierra; archivo EN oscuro y ficha EN clara a1440px CSS: sin overflow ni colisiones de navegación. Salto de contenido enfoca `main-content`.
- Sitemap real local muestra38 URLs, incluidas4 del Blog (dos archivos y dos fichas). PlayYellow sigue fuera del inventario.
- Contacto mantiene `CONTACT_FORM_ENABLED=false` en ambos entornos de Wrangler; no se modificó configuración, se enviaron mensajes ni se resolvieron challenges.

## Evidencia

- [CMS: dos publicaciones vinculadas](blog-cms-published.png).
- [Ficha ES oscura,320px CSS](blog-es-dark-320.png).
- [Archivo EN oscuro,1440px CSS](blog-en-dark-1440.png).
- [Ficha EN clara,1440px CSS](blog-en-light-1440.png).

## Próximo incremento y límites

Crear modelo `lab_project` en ACF y lectores/templates LAB separados; preparar primera entrada qa-training-lab con la autorización ya recibida. La lectura anterior de su README confirma plataforma personal de formación QA/Playwright/TypeScript, contenidos bilingües y desarrollo en curso; evitar métricas privadas, presupuestos, datos operativos y enlaces repo/demo sin verificar su visibilidad pública. No modificar el repositorio de la aplicación.

La matriz visual integral EN/ES/temas/zoom y QA de Workers sigue abierta. Tampoco se han implementado filtros editoriales, TOC ni schema Article con autor verificado. La etapa MCP editorial con permisos acotados continúa separada, sin ampliación de roles/capacidades.
