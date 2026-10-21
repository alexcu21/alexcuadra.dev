# Referencias legacy: paginación y contratos de rutas — 19 octubre 2026

Actualización posterior: la comprobación HTTP SSR aislada de templates completos ya pasa; ver [REFERENCES_SSR_LOCAL.md](REFERENCES_SSR_LOCAL.md). Las menciones inferiores a ese pendiente describen el límite anterior. Verificación contra CMS/Workers reales y QA visual continúan pendientes.

## Incremento local

Lectores sin filtro idiomático de categorías, referencias por categoría e inventario de slugs solicitan conexiones de100 elementos y recorren pageInfo/after hasta completarlas. Conservar categorías/URLs legacy y referencias todavía sin reclasificar exige mantener el inventario de todos los idiomas. No se sustituyen por el lector EN/ES ni se fusionan articles y post.

collectCmsPages valida conexión/hasNextPage/cursor, detecta cursores repetidos y limita el recorrido a100 páginas. Una respuesta incompleta, error tardío o inventario fuera de ese límite falla explícitamente; no devuelve las páginas ya reunidas como resultado completo. Cada página de artículos mantiene la validación de contenido público en el contexto autenticado.

Loaders compartidos de referencia para categoría/detalle centralizan el contrato de ausencia. Las páginas Astro usan la respuesta404/no-store/noindex común y conservan el manejo503 del middleware para errores CMS. Una categoría pública vacía es distinta de una categoría inexistente. Una ficha requiere pertenecer a la categoría solicitada.

## Validación

160 pruebas pasan (15 nuevas): recorrido completo de los tres lectores, variables GraphQL y ausencia de filtros idiomáticos; conexiones inválidas, cursores repetidos y límite de páginas; contenido privado o429 en páginas posteriores sin inventario parcial; categoría posterior/vacía/ausente, pertenencia de ficha y contratos404/503. Build correcto; git diff --check correcto. Estas verificaciones usan respuestas sintéticas y el scope CMS real, sin peticiones al CMS compartido.

No constituyen prueba HTTP SSR de las páginas completas. Sigue pendiente revisar esas rutas en runtime con CMS real o fixture SSR de templates completos antes de publicación, y la aceptación visual descrita en ABOUT_REFERENCES_LOCAL.md. No se repite smoke remoto ni se sortea el bloqueo del navegador. Sin deploy, cambios CMS o permisos MCP. Contacto desactivado.

Límites restantes: la conexión anidada de categorías de una ficha conserva su implementación anterior; no se declara cerrada toda paginación anidada ni SEO editorial/idiomas. Las capacidades MCP editoriales se planifican por separado en WORDPRESS_MCP_EDITORIAL_PLAN.md.
