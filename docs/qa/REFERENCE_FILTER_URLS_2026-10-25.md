# Búsqueda editorial compartible — 25 octubre 2026

## Entrega

Artículos y menciones conserva título/canal en q/channel. Recargar o abrir un enlace restaura la selección; Atrás/Adelante y pageshow sincronizan controles y resultados. Escribir reemplaza la entrada actual y cambiar canal/limpiar crea una entrada. Limpiar devuelve el foco al buscador. La URL generada apunta a #reference-directory-title para compartir la zona de búsqueda.

Las entradas llevan el contexto a la ficha. «Back to results» conserva filtros y devuelve al encabezado del directorio, enfocable mediante fragmento. El canal se valida contra las categorías públicas de la propia referencia, sin consultar otro inventario. Valores desconocidos/ambiguos se ignoran; búsqueda limitada a200caracteres y datos codificados como URL. Los parámetros ajenos y fragmentos existentes se conservan al editar filtros.

El archivo mantiene su idioma y rutas editoriales legadas. No se traducen textos del CMS ni se modifican publicaciones, taxonomías, roles o permisos. Sin nuevas consultas GraphQL/dependencias/storage. Canónicas y BreadcrumbList no incluyen filtros; sinJavaScript permanecen las diez entradas/enlaces SSR, y los controles se muestran al inicializar.

## Validación

- 309unitarias correctas: cuatro nuevas sobre canales exactos, parámetros ambiguos, límite/codificación y limpieza que conserva contexto ajeno.
- SSR correcto: archivo compartido conserva canónica e inventario, ficha incluye retorno válido, canal desconocido no genera retorno falso y canónica de ficha sinquery. Builds staging/producción correctos.
- Local: Second+medium1/2 persiste tras recarga, ficha y enlace de retorno; foco en reference-directory-title. Atrás tras limpiar restaura1/2 y Adelante2/2.
- Staging real: Gutenberg+medium1/10, recarga/ficha/retorno1/10 y foco en encabezado. HTTP archivo/ficha200, identidadCMS/CSP/noindex, canónicas base y diez entradas SSR.

Staging `a9b314e7-aca3-4403-8038-4776ef215d6a`; previa `d852564e-a888-40d7-b47a-7f0b846c0722`. [HTTP](reference-filter-url-staging-2026-10-25.json), [navegador](reference-filter-url-staging-browser-2026-10-25.json).

El historial no genera lecturasCMS, pero cargar/recargar una página mantiene su dependencia del origen. Disponibilidad429 y aceptación integral/zoom real200%/movimientoSO/lector siguen abiertos. Sinpush/DNS/contacto/MCP operativo. No se reintenta el origen ni se fuerza un fallo.

## Producción y reversión

Publicado `feba64f8-5406-4c69-8522-6c3753681a37`; previa `0b29d496-2bfa-4035-9aa1-0e1e1c774696`. [HTTP producción](reference-filter-url-production-2026-10-25.json): archivo y ficha200/identidad/CSP, sin noindex, canónicas sinquery y diez entradas SSR del archivo. La ficha filtrada incluye retorno a resultados.

[Navegador producción](reference-filter-url-production-browser-2026-10-25.json): enlaceGutenberg+medium1/10 → ficha → retorno1/10/focoencabezado → limpiar10/10/focobuscador → Atrás1/10. [Captura móvil](reference-filter-url-production-320-2026-10-25.png): viewport320/scrollWidth320, controles y resultado legibles. Viewport temporal restaurado. Configuración conserva privacidadES182, autenticación y contacto desactivado.

Reversión: restaurar la versión previa del Worker. No requiere cambiosCMS ni eliminación de storage. Las URLs conservan sus rutas válidas, aunque la versión anterior ignore sus filtros. No se ejecutó rollback.
