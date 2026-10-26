# Propuestas editoriales de posts y traducciones

## Incremento 1: documento de cambios local

Implementado [planificador PHP puro](../wordpress/experimental/editorial-blog-proposals/planner.php), separado de los plugins instalados. Produce un documento privado previsto para revisión, sin guardar ni registrar herramientas. No amplía el permiso real alexcuadra_read_public_posts. [Contrato técnico y límites](../wordpress/experimental/editorial-blog-proposals/README.md).

| Operación propuesta | Condiciones | Documento |
| --- | --- | --- |
| update-post | Post nativo publicado sin contraseña, idioma exacto, huella vigente | Before/after de title/content/excerpt/categories/tags solicitados; omitir conserva, vacío explícito retira |
| create-translation | Idioma opuesto EN/ES sin vínculo existente en grupo completo, huella vigente | Cinco campos explícitos de destino y relación prevista al grupo; sin ID asignado ni traducción automática |

Actor autenticado y scope nuevo previsto alexcuadra_prepare_post_changes. No se ha concedido ni registrado ese scope en el CMS. Actor, fuente y catálogo son proyecciones internas fiables: no estarán en el esquema de entrada MCP. El lector público actual no entrega cuerpos ni vínculos privados y **no basta como proveedor de esas proyecciones**. El futuro adaptador requerirá lectura interna acotada para preparar un diff autorizado, sin añadir un endpoint genérico de cuerpos privados.

El request contiene exactamente request_id hexadecimal32, source_id entero, locale EN/ES, base_hash SHA256, operación y campos. El resultado incluye propietario, versión inicial1, request_hash, document_hash, base_hash y aprobación no solicitada. La huella de base incluye proyección normalizada y catálogo recibido; cambios de cuerpo/título/extracto/términos/grupo invalidan la preparación. La huella no demuestra por sí sola una lectura atómica o ausencia de carreras.

Clasificación exclusivamente category/post_tag y en el idioma destino. No reutiliza taxonomías propias de proyectos ni traslada sus permisos. Sin cambios de slug/URL, estado, autor, contraseña, opciones o ACF. La creación prevista no implica publicar; el futuro escritor deberá crear borrador y aprobar publicación en una fase distinta.

HTML/bloques se conservan como texto literal de propuesta. Toda vista debe escapar los valores; no incrustar el documento como HTML. No se considera saneamiento de publicación: cualquier normalización futura que cambie el contenido revisado exige nueva revisión y aprobación. Se rechazan UTF-8 inválido, controles no permitidos y tamaños excesivos, conservando acentos/emoji/código.

## Siguientes capas

1. Repositorio privado aislado, lecturas por propietario y cuotas/retención. Idempotencia durable por owner/request_id con conflicto si cambia request_hash, y compare-and-swap de versión. No copiar tablas o revisiones de proyectos sin una migración propia revisada.
2. Adaptador WordPress/Polylang con grupo completo y reciprocidad real, IDs/idiomas/taxonomías contrastados, incluyendo destinos vinculados no públicos. Lecturas y creación concurrentes con transacción/locks y revalidación de base; un snapshot del lector público no detecta todo eso.
3. Pruebas reales de almacenamiento y transporte en laboratorio; acceso entre propietarios, revocación, concurrencia, replay y fallos. Después paquete de propuestas privadas, separado de publicación.
4. Aprobación, aplicación y recuperación con scopes y recibos independientes. Política HTML/bloques/hooks/efectos y revalidación al aplicar. Una traducción debe impedir duplicación de grupo y publicación implícita. No reaprovechar la propuesta227, revisión1 o grants de proyectos.

Estas capas no están implementadas por la versión inicial. No hay nuevas tablas, borradores, credenciales, herramientas MCP o instalación pendientes de ejecutar por Alex para este incremento. Siguiente trabajo concreto: repositorio y versionado privados en laboratorio; no repetir comandos del lector ya instalado.
