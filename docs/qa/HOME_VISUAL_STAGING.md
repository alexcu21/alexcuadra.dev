# Primera entrega visual en staging — 19 octubre 2026

Versión final: `36744d8c-bd3f-47f5-8162-e85ddc54f094`.

## Recuperación local

Alex reportó `.vite/deps_ssr/astro_03a_middleware.js?v=4fdcefb1` ausente. El servidorPID63010 seguía escuchando4325 mientras la dependencia ya no existía; se habían ejecutado builds con dev activo. Este patrón es compatible con invalidación de la caché compartida, no demuestra incompatibilidad del middleware.

Astroconfig usa hook astro:config:setup para separar cacheDir en `.vite/dev`, `.vite/build`, `.vite/sync` y `.vite/preview`. Se verificó soporte del hook en tipos instalados. No se cambiaron dependencias ni optimizeDeps.exclude. Servidor anterior detenido y nueva vista previa foreground en4325 con ignore-lock; sesión73205.

Smoke local de HomeEN/ES y ficha real pasa antes y después de build con dev activo. `.vite/dev/deps_ssr/astro_03a_middleware.js` permanece presente. Alex confirmó que el error desapareció y se veía el diseño.145 pruebas pasan y build correcto.

## Cambios visuales

Tokens claros/oscuros basados en Obsidian Flow, Plus Jakarta Sans, superficies/cian/lavanda y fondos sin las imágenes decorativas anteriores. Menú con breakpoint64em y foco visible. Hero Home usa contenido e imagen CMS, dos acciones y roles compactos; especialidades y proyectos en tarjetas, encabezados H2/H3 coherentes. Tarjetas muestran subtítulo solo cuando existe y mantienen el idioma real de cada destino. Contadores y selector de tema ajustados a tokens de contraste.

Contrastes calculados de texto/fondo principales>4,5:1; no sustituyen auditoría visual. No se publicaron terminales, disponibilidad, métricas ni atribuciones del mockup. Textos CMS y categorías sin editar.

## Validación y límites

Smoke staging HomeEN/ES+ficha real, identidad CMS, SEO/CSP, renderer y contrato contacto desactivado pasa. Producción y CMS sin cambios.

El navegador siguió denegando la observación de127.0.0.1:4325 por permiso guardado/administrado. No hubo observación automatizada por otro origen o navegador para eludirlo. La confirmación manual de Alex permitió continuar la entrega a staging; no equivale a aprobación visual integral. No hay capturas automáticas del resultado por ese bloqueo.

Pendiente: revisión visual manual o acceso permitido en móvil/escritorio y ambos temas, comprobar menú/foco y páginas afectadas por estilos compartidos (privacidad, About, archivos/fichas). La fase visual completa permanece abierta. El formulario sigue false y no se enviaron correos.
