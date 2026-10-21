# LAB bilingüe — 19 octubre 2026

## Entrega y entornos

Archivo y ficha de proyectos personales en `/lab/`, `/lab/[slug]`, `/es/laboratorio/` y `/es/laboratorio/[slug]`. LAB mantiene un modelo separado de proyectos de clientes, referencias `articles` y Blog `post`.

El modelo y las dos fichas sí se crearon/publicaron en el CMS compartido con la autorización de Alex. Frontend, menú y sitemap de este incremento permanecen **locales**, sin despliegue de Workers ni envío a Search Console. Contacto permanece desactivado, sin correo/challenges ni cambios de credenciales, roles o capacidades MCP.

## Modelo CMS

Configuración importada desde [acf-lab.json](../wordpress/acf-lab.json), mediante ACF Tools. WordPress confirmó «Imported2 items»:

| Entidad | ID | Identificador |
| --- | --- | --- |
| ACF Post Type | 206 | `lab_project`, key `post_type_alexcuadra_lab_project` |
| ACF Field Group | 207 | `group_alexcuadra_lab_project`, GraphQL `labProjectFields` |

ACF mostraba únicamente Articles y Projects antes de la importación; no se creó `lab_experiment` ni otro registro del tipo en un plugin. GraphQL expone `labProject` / `labProjects` (`LabProject`). Polylang: activado únicamente el nuevo `lab_project`, conservando Articles/Projects seleccionados. Gutenberg, extracto, imagen, revisiones y campos personalizados habilitados; sin taxonomías compartidas nuevas.

Campos ACF gratuitos: `status` (idea/in_progress/beta/released/archived), `repository_url`, `demo_url`, `purpose`, `learning_goals`, `technical_approach`, `quality_strategy`, `devops_notes`, `last_reviewed_at`, `featured`, `display_order`. Claves estables en el JSON; sin campos PRO ni secretos. Estado editorial ACF separado del estado WordPress de publicación. GraphQL devuelve el select como lista de un valor; comprobado en IDE y normalizado explícitamente.

## Primer caso real

| Idioma | ID | Slug |
| --- | --- | --- |
| EN | 219 | `qa-training-lab` |
| ES | 221 | `qa-training-lab-2` |

Título propio conservado: QA Training Lab. Publicados y vinculados mediante Polylang; ambos editores confirmaron «is now live». Frontend autenticado confirma idiomas, contenido y enlaces recíprocos. Estado ACF `in_progress`, mostrado como «In progress» / «En desarrollo».

Fuente: lectura del README de `/Users/Alex/Desktop/AlexC/projects/qa-training-lab/README.md` el19 de octubre, sin modificar ni ejecutar el repositorio de esa aplicación. Texto de presentación incluye propósito de formación personal QA/SDET, Playwright/TypeScript, explicaciones bilingües, días1–5 implementados, evaluación/refuerzo, práctica local sintética y su sincronización todavía pendiente. Se distingue lo implementado de días6–30/DevOps/Diseño de Sistemas planificados. GitHub→Railway/PostgreSQL descrito sin publicar URLs operativas, presupuestos, credenciales ni benchmarks privados. No se presenta como certificación.

Repositorio y demo permanecen vacíos; falta verificar su visibilidad pública antes de enlazarlos. Sin imagen ficticia. `lastReviewedAt` devuelve null en CMS y se omite: no se sustituye por la fecha de publicación ni por la fecha del sistema. La entrada no depende de esos campos opcionales.

## Frontend y contratos

- `lab-content.js`: consulta solo `labProjects` / `labProject`, idioma EN/ES explícito, estado publicado sin contraseña y validación de visibilidad también fuera del scope autenticado.
- Archivo paginado completo antes de ordenar por `display_order`; sin resultado parcial ante fallo tardío o cursor repetido.
- DTO allowlist; traducciones públicas sin ACF privado; enlaces opcionales solo HTTP(S) sin credenciales embebidas.
- Archivo vacío, ficha ausente o idioma equivocado:404/no-store/noindex. Fallos CMS o contenido no público:503/no-store/noindex mediante middleware compartido.
- Template compartido: estado real, propósito, cuerpo Gutenberg saneado y secciones opcionales de objetivos/enfoque/calidad/DevOps. Sin cliente, métricas o disponibilidad inventados.
- Menú consulta disponibilidad de un registro público del idioma; sin anunciar archivos vacíos. Selector/hreflang de archivo condicionado a contenido real; fichas usan relaciones públicas reales. Canonical propio.
- Sitemap incluye los dos archivos y los dos slugs reales. Comprobación DOM de XML real local: **43 elementos loc**. Esta medición sustituye las estimaciones históricas inferiores de URLs.

## Corrección de categorías compartidas

Durante QA se observó que los posts de ejemplo hacían aparecer categorías Uncategorized en Home/referencias: `category.count` de WordPress mezcla tipos que comparten taxonomía. Nuevo inventario público de referencias compartido con sitemap y `getReferenceCategoryCards` calculan cada contador por slugs únicos de `articles`; términos con solo Blog se omiten. No se cambian categorías, relaciones ni URLs del CMS. La ruta directa de una categoría válida vacía sigue distinguiéndose de una categoría inexistente.

Home ES real vuelve a mostrar únicamente Medium1, Podcast3, ValexWeb2, WordPress.TV4. Pruebas cubren categorías exclusivas de Blog, totales compartidos incorrectos, relaciones duplicadas y fallo del inventario sin tarjetas parciales.

## Validación y evidencia

- `npm test`:197 pruebas correctas.
- `npm run test:references-ssr`: correcto; fixture usa los templates reales LAB/Blog/referencias con transporte sintético. EN/ES, estados opcionales, sanitización, canonical/hreflang,404/503, inventario vacío y errores tardíos de navegación.
- `npm run build`: correcto;147 archivos revisados,0 errores/advertencias/hints de diagnóstico Astro. Persisten avisos conocidos de configuración Shiki/CSP; no se certifica CSP de Workers nuevos.
- `git diff --check`: correcto. Árbol local preexistente conservado, sin commit/reset/limpieza.
- Navegador integrado, mismo origen4325 funcional: CMS→GraphQL→archivos/fichas EN/ES y cambio de idioma real comprobados. Ficha ES oscura320px sin overflow; salto de contenido enfoca main. Menú ES de seis enlaces cabe a1024px. Archivo ES claro/ficha EN clara1440px sin overflow ni colisión. Canonical/alternates reales confirmados.
- [Archivo ES oscuro,320px](lab-es-archive-dark-320.png).
- [Ficha ES oscura,320px](lab-es-detail-dark-320.png).
- [Archivo ES claro,1440px](lab-es-archive-light-1440.png).
- [Ficha EN clara,1440px](lab-en-detail-light-1440.png).
- [Listado CMS](lab-cms-published.png); evidencia de publicación/relación adicional mediante confirmaciones de editor y navegación frontend.

## Continuidad y recuperación

Siguiente incremento: cerrar QA restante de navegación/zoom y contenido Gutenberg, preparar/verificar Blog+LAB en staging con contacto desactivado. Producción requiere revisar artefacto concreto y aprobación de salida; no asumir que estos cambios locales ya están allí. Mantener la etapa MCP editorial separada, sin ampliar permisos para desbloquear tareas rutinarias.

Rollback editorial reversible: volver219/221 a Draft preservando traducciones/fields, si se solicita. Si se retira el modelo CMS, primero retirar/restaurar el frontend que lo consulta; desactivar el CPT mientras Nav lo consulta produciría503. No borrar permanentemente entradas ni configuración. Conservar JSON y registro de IDs. No alterar datos de otros CPT ni el repositorio de qa-training-lab.
