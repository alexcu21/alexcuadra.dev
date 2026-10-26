# Planificador de propuestas del blog — 26 octubre 2026

**82 comprobaciones correctas, cero fallos**, PHP8.5.11. [Resultado reproducible](editorial-blog-proposal-planner-2026-10-26.json), [contrato](../WORDPRESS_MCP_BLOG_PROPOSALS.md), [pruebas](../../tests/editorial-blog-proposals/check.php).

Prueba ejecutada: `/opt/homebrew/bin/php tests/editorial-blog-proposals/check.php`. Lint de módulo y prueba correcto; diff sin errores de espacios. No se ejecutó build del frontend porque no cambia la aplicación Astro.

## Cobertura

- Documento con before/after, propietario, versión inicial y aprobación no solicitada; entradas conservadas. Hash del documento detecta alteración. Salida determinista ante repetición y reordenación de claves/catálogo/IDs; request_hash distingue propietario y payload.
- Anónimo, propietario inválido, scope no booleano, read/read_public_posts/prepare_project_changes/manage_options sin scope nuevo rechazados. Son actores sintéticos, no autenticación de WordPress real.
- Campos adicionales, instrucciones de autor/estado/slug/ACF/SQL y actor en request rechazados. Campos omitidos frente a vacíos explícitos, no-op y fuente errónea comprobados.
- Fuentes project, borrador, privado, futuro, papelera y contraseña rechazadas. Grupo con idioma ajeno, identidad propia incorrecta o IDs duplicados rechazado.
- Cambio concurrente sintético de título/cuerpo/extracto/categorías/etiquetas/grupo o catálogo invalida base_hash. Esto prueba detección sobre proyecciones, no locks/transacciones ni carrera real.
- Categorías/etiquetas con idioma, taxonomía, visibilidad o ID incorrectos rechazadas; IDs duplicados/no enteros/no positivos/excesivos y taxonomías de proyectos fuera del catálogo.
- UTF-8/control/tamaño inválidos y título vacío rechazados. HTML/Gutenberg/emoji se conservan como texto literal, sin ejecutar ni sanear para publicación.
- Traducción EN→ES y ES→EN con contenido explícito, cinco campos obligatorios, sin ID reservado ni escritura. Impide usar categorías fuente, mismo idioma o destino ya vinculado; un vínculo concurrente invalida la base.

## Límites

PHP puro y proyecciones sintéticas: sin WordPress/Polylang, BD, red, almacenamiento, HTTP, hooks o efectos. No se prueba autenticación real, privacidad persistente entre propietarios, idempotencia durable, actualizaciones de versiones, creación de borradores, publicación, recuperación o ausencia de destinos privados ocultos en el CMS. Es responsabilidad del próximo adaptador proporcionar proyecciones completas y verificadas.

No se modificaron el lector instalado, sus ZIP/manifiestos, scopes reales, propuesta227/revisión1, producción, staging, contacto ni contenido editorial. No se enviaron correos ni se ejecutó SSH/Docker. El incremento cierra el documento de cambios local; siguiente es repositorio privado/versionado antes de un candidato MCP instalable.
