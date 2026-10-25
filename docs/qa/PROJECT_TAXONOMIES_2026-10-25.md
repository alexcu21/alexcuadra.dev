# Clasificación propia de proyectos — 25 octubre 2026

## Resultado

Completada la migración solicitada por Alex a categorías y tecnologías exclusivas de proyectos. Se registraron mediante ACF `project_category` (jerárquica, ID237) y `project_technology` (no jerárquica, ID238), con traducciones Polylang vinculadas. Cuatro pares de categorías y veinte pares de tecnologías; asignadas las doce versiones públicas de seis proyectos. Makers Unite y alextecuenta usan las tecnologías aportadas por Alex, sin inferir tecnologías adicionales.

El tipo `project` (configuración ACF155) ya solo está asociado a ambas taxonomías propias. Sus campos GraphQL `categories` y `tags` han desaparecido; `projectCategories` y `projectTechnologies` permanecen. Posts y Articles conservan sus taxonomías nativas. No se eliminaron términos globales ni relaciones antiguas de la base de datos; quedan como historial para reversión, sin uso ni asociación activa al tipo Project.

## Frontend y preservación

Lectores y scripts de inventario consultan las conexiones propias; la proyección valida idioma y paginación, sin fallback a taxonomías del blog. Categorías visibles en tarjetas/fichas; tecnologías y áreas de trabajo se muestran por separado. Las tarjetas muestran cuatro tecnologías más un indicador +N; las fichas muestran todas.

`project-taxonomy-assigned.json` confirma la asignación inicial y conservación de las relaciones antiguas. `project-taxonomy-postflight.json` verifica después de retirar la asociación nativa: doce proyectos y sus traducciones/asignaciones propias idénticos, campos públicos no taxonómicos conservados, y relaciones de dos posts y diez menciones intactas. Los cambios de asignaciones modifican las fechas `modified`; no se falsearon fechas. Esta evidencia pública no es una auditoría global ni un backup de la base de datos. Las líneas base históricas permanecen congeladas; la migración autorizada provoca deriva esperada y no cierra la incidencia histórica de postmeta del MCP.

## Validación y despliegue

- 279 pruebas unitarias correctas y suite SSR correcta con archivos/fichas EN/ES, idiomas mixtos rechazados, casos vacíos y lista de tecnologías extensa.
- Builds staging y producción correctos, sin diagnósticos Astro. Se requirió permiso de ejecución fuera del sandbox para los puertos locales usados por Cloudflare.
- Lectura con los lectores nuevos contra CMS real: EN200 y ES200.
- Staging: `a67ef581-766b-46d3-997d-aa1d18e55b1e`; anterior `05b5c8d8-6e4d-4566-8c27-04c1bfc2ec60`. Primer intento de carga de assets falló con Cloudflare10013; un reintento acotado pasó. ListadoES y ficha Christianity TodayES200, identidad CMS verificada, noindex y clasificación propia correctas.
- Producción: `43c88691-2f4c-4e68-b045-56398276b4c6`; anterior `79cb5e81-05e2-4747-9fd4-b450c855ee0c`. ListadoES y fichaEN200, identidad CMS y CSP correctas. Configuración generada contrastada: dominio real, autenticación CMS=true, contacto=false, keep_vars=true.
- Después de retirar las asociaciones nativas: postflight GraphQL correcto y ficha real ES observada en navegador con todas las tecnologías traducidas. Vista320CSS con scrollWidth320; categoría y ocho tecnologías se ajustan sin desbordamiento horizontal. Capturas escritorio y móvil adjuntas. No certifica el resto de la matriz de accesibilidad/zoom200%/movimiento del SO.

La disponibilidad continua del CMS y su límite429 siguen abiertos: estas muestras200 no prueban que Hostinger lo haya resuelto. No se cambiaron protecciones, DNS, credenciales, permisos, contacto ni el sistema editorial MCP aplazado.

## Reversión

Antes de volver a una versión anterior de cualquiera de los Workers, restaurar en ACF155 las asociaciones `category` y `post_tag` junto a las dos propias: el frontend anterior necesita esos campos GraphQL. Las relaciones anteriores permanecen almacenadas; no borrar los términos propios ni sus traducciones. La configuración previa del formulario está en `../wordpress/project-type-settings-before.json`. Después se puede restaurar la versión previa del Worker y comprobar las rutas. No se ejecutó reversión.

## Evidencia

- [Plan y mapa](../wordpress/project-taxonomy-migration.json), [IDs de términos](../wordpress/project-taxonomy-term-map.json), [postflight](../wordpress/project-taxonomy-postflight.json).
- [HTTP staging](project-taxonomy-staging-2026-10-25.json), [HTTP producción](project-taxonomy-production-2026-10-25.json).
- [Producción ES](project-taxonomy-production-es-2026-10-25.png), [móvil320](project-taxonomy-production-es-320-2026-10-25.png), [fixture local sintético](project-taxonomy-local-detail-2026-10-25.png).
