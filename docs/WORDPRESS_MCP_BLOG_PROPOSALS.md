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

## Incremento 2: almacenamiento privado en SQLite

Implementado repository.php, separado del planificador, con guard de laboratorio CLI y sin WordPress. Creación/get/listado privados, historia inmutable, compare-and-swap y replay durable por propietario/petición. Reintentar devuelve la revisión original; reutilizar clave con otra operación/versión/payload falla. Permisos actuales se comprueban antes de leer recibos. Actualizar reemplaza el patch completo, con base explícita, preservando versiones anteriores.

93pruebas de BD real, incluyendo procesos concurrentes y SIGKILL antes/después de commit;82regresiones del planificador correctas. Conserva sin poda automática bajo cuotas acotadas. [Resultados, política y límites](qa/MCP_BLOG_PROPOSAL_STORAGE_2026-10-26.md). SQLite es un ensayo de contrato: el adaptador del CMS real usará su conexión/transacciones MariaDB y no este archivo de almacenamiento.

## Incremento 3: WordPress/MariaDB/Polylang de laboratorio

Implementados wordpress-source.php y wordpress-repository.php con guard de sitio local, identidad actual WordPress, scope de preparación privado y conexión wpdb global. Proyección de campos actuales y grupo Polylang completo con reciprocidad; destinos vinculados no públicos bloquean otra traducción sin exponer sus cuerpos. Repositorio separado de proyectos, cuatro tablas InnoDB con claves foráneas y lock por propietario antes de replay/cuotas/versión.

72comprobaciones nativas correctas: privacidad entre usuarios, revocación, proyecciones/idiomas/términos reales, versiones/replay, dos carreras entre procesos WP-CLI, cuotas y rollback por fallo del recibo.82+93regresiones previas correctas. [Evidencia, corrección de Unicode y límites](qa/MCP_BLOG_PROPOSAL_WORDPRESS_2026-10-26.md). Es ensayo CLI: todavía sin autenticación por transporte, paquete o scopes nuevos en el CMS.

## Siguientes capas

1. Transporte MCP/HTTP y fallos de proceso/conexión sobre wpdb/MariaDB en laboratorio. Comprobar autenticación, sesiones/otros propietarios y revocación por ese transporte; barrera de identidad de conexión y tratamiento de reconexión/commit ambiguo. Los SIGKILL SQLite anteriores no certifican MariaDB.
2. Admisión del esquema/operador y política de archivo/retención/backups antes de paquete privado instalable. Completar perfil real de plugins/MU/contextos/caché. No trasladar directamente la instalación DDL de laboratorio al hosting ni reutilizar las tablas de proyectos.
3. Aprobación, aplicación y recuperación con scopes y recibos independientes. Fuente/grupo requieren snapshot/revalidación coordinados con escritores externos; las APIs/cache Polylang de la proyección no certifican esa atomicidad. Validar HTML/bloques/hooks/efectos al aplicar y revisar nuevamente cualquier saneamiento que altere el diff. Impedir duplicación de grupo/publicación implícita; crear borrador no autoriza publicarlo. No reaprovechar propuesta227, revisión1 o grants de proyectos.

No hay nuevas herramientas MCP instaladas ni comandos pendientes para Alex. Siguiente trabajo concreto: transporte y barrera/fallos de conexión en laboratorio; no repetir instalación/grants del lector existente.
