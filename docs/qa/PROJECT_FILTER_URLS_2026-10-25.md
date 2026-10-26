# Filtros compartibles y retorno a resultados — 25 octubre 2026

## Entrega

El archivo de proyectos EN/ES guarda búsqueda/categoría/tecnología en q/category/technology. Un enlace compartido o una recarga restaura controles y resultados. Escribir reemplaza la entrada actual del historial; cambiar una selección o limpiar crea una entrada. Atrás/Adelante y pageshow restauran estado desde la URL, sin mover el foco.

Las tarjetas llevan el contexto a la ficha; allí «Volver a resultados» recupera la selección. Anterior/siguiente se limita al subconjunto filtrado y mantiene sus parámetros. Sin filtros permanece el recorrido completo del portafolio. «Todos los proyectos» sigue ofreciendo el listado sin filtros.

Se validan nombres exactos contra las opciones públicas del idioma; valores desconocidos/ambiguos se ignoran, q se limita a200caracteres, y los textos se codifican como URL. Los parámetros ajenos y el fragmento del archivo se conservan al editar filtros. No se guardan nuevos datos en storage ni se añaden operaciones GraphQL/dependencias. Los parámetros no forman parte de la consulta CMS. No se trasladan filtros al cambiar de idioma: sus términos pueden diferir. Si un término publicado cambia de nombre, un enlace antiguo puede perder ese filtro en vez de adivinar una equivalencia.

Las canónicas de archivo/ficha siguen sin query. Sin JavaScript se muestran todos los proyectos y enlaces SSR; aplicar el filtro del enlace requiere JavaScript. El HTML público completo no se sustituye por un fallback vacío.

## Validación

- 305unitarias correctas: cinco pruebas nuevas de lectura validada, ambigüedad/límites, codificación, limpieza y preservación de parámetros ajenos/ruta/fragmento.
- Suite SSR correcta: canónica sin query, HTML completo sinJS, retorno a filtros válidos, exclusión de parámetros desconocidos y navegación solo entre coincidencias. Builds staging/producción correctos.
- Navegador local: título/categoría/React combinados1/2 tras recarga y vuelta desde ficha. Navegador Atrás tras limpiar devuelve1/2; Adelante recupera2/2. Limpiar conserva foco en project-search. Retorno explícito de ficha conserva la URL y controles tras inicializar el script. Se espera a controles visibles antes de evaluar el estado cliente; el HTML SSR inicial contiene todos los proyectos.
- Staging realES: enlace technology=Svelte abre1/6; ficha CT conserva /es/proyectos/?technology=Svelte y no ofrece vecinos fuera de la selección. Canónica de ficha sin query. Volver a resultados y recargar mantienen1/6. Registro del navegador adjunto.
- HTTP staging EN/ES200/identidad verificada/CSP/noindex, canónicas base y seis entradas SSR. Configuración de producción contrastada: auth=true/contacto=false/privacidadES182/keep_vars=true.

## Evidencia y alcance

Staging final `d852564e-a888-40d7-b47a-7f0b846c0722`; previa al incremento `5ac24c2f-d949-4c44-8167-165e98910b47`.

[HTTP staging](project-filter-url-staging-2026-10-25.json), [recorrido real staging](project-filter-url-staging-browser-2026-10-25.json).

Sin CMS/DNS/roles/credenciales/contacto/MCP operativo ni push. El429 del hosting y aceptación integral/zoom real200%/movimientoSO/lector siguen pendientes. La persistencia de URL no elimina la dependencia CMS al cargar o recargar una página.

## Producción y reversión

Publicado `0b29d496-2bfa-4035-9aa1-0e1e1c774696`; previa `7d26d563-47ac-4084-9597-d37233f244d9`. [HTTP producción](project-filter-url-production-2026-10-25.json): archivosEN/ES y fichaES filtrados200/identidad/CSP, canónicas sin query y seis entradas SSR del archivo. La ficha devuelve el enlace de resultados con Svelte. Sin noindex en las páginas públicas correctas.

[Navegador producción](project-filter-url-production-browser-2026-10-25.json): compartirSvelte1/6 → ficha → Volver a resultados1/6 → limpiar6/6/foco buscador → Atrás1/6. [Retorno desde la ficha](project-filter-url-return-production-2026-10-25.png), [móvil320](project-filter-url-production-320-2026-10-25.png): scrollWidth320 y selección1/6 conservada. Viewport temporal restaurado.

Reversión: restaurar la versión previa del Worker; no hay migración CMS ni almacenamiento que deshacer. URLs con filtros siguen apuntando a rutas existentes si se revierte, aunque la versión anterior no restaure su selección. No se ejecutó rollback.
