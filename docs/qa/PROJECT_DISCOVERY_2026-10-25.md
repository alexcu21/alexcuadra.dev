# Búsqueda y filtros de proyectos — 25 octubre 2026

## Entrega

Archivo de proyectos EN/ES con búsqueda por título (ignora mayúsculas y tildes), categoría y tecnología/área combinables. Opciones derivadas únicamente de los proyectos del idioma y de todas sus tecnologías, incluidas las que la tarjeta resume con +N. No se añadieron consultas CMS, campos, dependencias ni cambios de WordPress.

El HTML conserva todos los enlaces y proyectos sin JavaScript; los controles solo aparecen tras inicializarse. El filtrado es local y no cambia URLs/canonical. Conteo con role=status/aria-live, etiquetas nativas, estado vacío y botón de limpieza que restaura el foco al buscador. Los proyectos sin título no rompen la búsqueda. Los filtros no persisten al cambiar de idioma.

## Validación

- 281 unitarias correctas; dos pruebas de filtros cubren coincidencias exactas, combinación, tildes, ausencia de clasificación/título y términos completos. Tras la protección final de título nulo se repitieron esas dos pruebas correctamente.
- Suite SSR correcta: listado EN/ES, opciones/atributos completos, controles ocultos inicialmente y regresiones de fichas, Blog/LAB/índice. Primer intento de nueva aserción falló por atributos CSS de Astro en option; corregida la aserción, sin eliminar cobertura.
- Builds staging/producción correctos, configuración de destino y autenticación contrastadas, contacto=false, keep_vars=true.
- Navegador local: consulta DEMOSTRACION + categoría Sitio WordPress + Svelte produce 1/2; consulta inexistente 0/2; limpiar con Enter recupera 2/2 y foco project-search. A320CSS: scrollWidth320, controles apilados y conteo visible.
- Staging real ES: 6/6 inicial, categoría de bloques + Svelte 1/6 (Christianity Today), búsqueda inexistente 0/6 y limpiar por teclado 6/6. La tarjeta filtrada vuelve a opacity1 tras Motion. EN/ES200, identidad CMS verificada, CSP y noindex correctos.

## Publicación

Staging `c1c1c7b7-c2be-484a-b7a4-36d2be4d1381`, previa `0cf469e9-670d-43e6-98a9-f5e40ce0a269`.
Producción `712986be-0b19-4474-95b6-ab5816e0a91e`, previa `a20d636d-93df-4be8-af8e-8140c393dc0a`.

[Recibo staging](project-discovery-staging-2026-10-25.json), [recibo producción](project-discovery-production-2026-10-25.json), [móvil sintético local](project-discovery-local-320-2026-10-25.png), [staging real](project-discovery-staging-es-2026-10-25.png).

Para revertir este incremento basta restaurar la versión previa del Worker: no hay migración CMS ni cambios de esquema. No se ejecutó rollback, push, cambios de DNS, credenciales, permisos, contacto ni MCP editorial. Los200 son muestras puntuales: disponibilidad CMS/429 y aceptación integral/zoom real200% siguen abiertos.

Postflight producción: ambos archivos EN/ES200 con seis li de proyecto, identidad CMS verificada, CSP y sin noindex. Navegador producciónES confirma controles activos y filtro Svelte=1/6 (Christianity Today). [Captura de producción](project-discovery-production-es-2026-10-25.png). Viewport temporal restaurado.
