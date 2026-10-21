# Anchors e índice editorial — local,20 octubre2026

Actualización posterior: [TOC/anchors desplegados y comprobados en staging](EDITORIAL_OUTLINE_STAGING_2026-10-20.md), versión1b12660e,237 unitarias/suite SSR/build/dry-run y cuatro fichas EN/ES correctas. Este documento conserva el QA local inicial; ya no es un componente exclusivamente local. Visual/teclado/zoom/lector siguen pendientes y producción no recibió este cambio.

## Entrega

Blog y LAB EN/ES incluyen tabla de contenidos desplegable y enlaces a secciones cuando hay al menos dos encabezados no vacíos. Blog usa títulos de su cuerpo; LAB reúne cuerpo y secciones ACF en un solo documento y no publica índices aislados por campo. Objetivos de aprendizaje siguen como texto plano. No hay contenido inventado ni cambios de esquema, idiomas, slugs de rutas o CMS.

`WordPressContent` compila el HTML en servidor después de la política allowlist existente. Genera destinos propios `wp-content-*`, conserva una H1 de página, evita IDs repetidos y remapea únicamente fragmentos locales con un destino editorial real. Encabezados de otras vistas también reciben destinos; no se activa TOC adicional en ellas. El índice usa `details`/`summary` y listas jerárquicas, etiquetas EN/ES, foco visible y scroll nativo, sin JavaScript ni solicitudes extra al CMS.

htmlparser2 se declara directamente en versión exacta12.0.0, ya presente por sanitize-html; npm informa up to date. No se actualizaron deliberadamente otras versiones. No se realizó una auditoría nueva de dependencias.

## Verificación

- 228 pruebas unitarias pasan (diez nuevas de outline, incluida regresión de todos los bloques Gutenberg admitidos): etiquetas/entidades/código, acentos/Unicode, colisiones de IDs/sufijos, anchors explícitos y fragmentos codificados, prioridad entre campos ACF, secciones vacías, niveles saltados, conservación del sanitizador, texto plano y validación de scopes/keys.
- Suite SSR con templates reales pasa. Nuevos escenarios sintéticos de fichas Blog/LAB EN/ES comprueban labels localizados, details abierto, destinos existentes, IDs únicos, una H1, texto escapado del índice, jerarquía ACF y remapeo de anchors. Una ficha sin encabezados no muestra TOC. Se mantienen los escenarios200/404/503/idiomas/renderer de la suite.
- Build correcto:157 archivos,0 errores/advertencias/hints de Astro check. Avisos conocidos Shiki/CSP permanecen; no se desactivó CSP para eliminarlos.
- `git diff --check` correcto. Lockfile y manifest fijan htmlparser2=12.0.0. Configuración generada apunta solo a staging y contacto false.
- Dev existente escuchando en127.0.0.1:4325 preservado, sin reiniciar ni cambiar de puerto. No se consultaron páginas CMS reales, no hubo smoke remoto ni nuevo despliegue en este incremento: el contenido sintético permite verificar el cambio sin otra carga sobre el hosting.

## Límites

Implementación local preparada; staging sigue en `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c` y aún no contiene este TOC. No se ha verificado render visual, activación real con teclado de details/enlaces, scroll/foco en el navegador, zoom200%, lector de pantalla ni contraste específico del componente. El navegador no se reintentó ni se eludió. Las pruebas SSR comprueban HTML/contratos y no sustituyen esas revisiones.

Un título modificado sin anchor editorial estable o un cambio de orden de duplicados puede alterar fragmentos generados; las rutas canónicas, hreflang y slugs CMS no cambian. IDs originales de otros elementos siguen retirados. Links a fragmentos desconocidos y URLs externas/completas se conservan. No se añade copiar enlace, scrollspy ni búsqueda en este incremento.

El429 histórico sigue abierto según [diagnóstico](CMS_429_DIAGNOSTIC_2026-10-20.md). Contacto desactivado, sin challenge/correo. Sin cambios CMS/roles/MCP/hosting/producción, sin commit/reset/limpieza de cambios locales.

## Siguiente incremento

Revisar visualmente el TOC y los bloques Gutenberg en Blog/LAB EN/ES (320/1440, claro/oscuro, teclado, fragmentos y zoom real), cuando exista acceso efectivo al navegador o evidencia manual del usuario. Antes de esa aceptación no dar por cerrado el diseño ni publicar este cambio en producción. Trabajo independiente disponible: preparar un ensayo de recuperación del Worker staging, conservando la restricción GraphQL y el contacto false; la etapa MCP editorial sigue separada.
