# Propuestas editoriales de posts y traducciones

**27 octubre — before real de rc.2 recibido:** ready=true, 15 comprobaciones principales y 11 del perfil correctas, SHA del ZIP exacto. No hay paquete instalado, tablas propias, guard activo ni scope nuevo; se conservan las ocho capacidades previas. [Recibo de Alex](qa/blog-proposal-rc2-destination-before-2026-10-27.json). Siguiente: instalación inactiva del ZIP rc.2 exacto por Alex y after-install; sin activar, crear tablas o conceder permisos. No repetir before/fingerprint. Respaldo operativo del hosting pendiente antes de escritura privada.

**27 octubre — candidato rc.2 preparado:** ZIP separado de rc.1, ocho archivos/17.693bytes, SHA7e5bc2b3…d4d6def.149funcionales+29contratos+10admisión correctas; ciclo de instalación/restauración/retirada en laboratorio con nueve preflights y30checks. [Paquete/procedimiento vigente](WORDPRESS_MCP_BLOG_PROPOSAL_RC2.md), [QA/límites](qa/MCP_BLOG_PROPOSAL_RC2_2026-10-27.md). Respaldo/restauración solo sintéticos, no herramienta de hosting ni API reabierta tras restaurar. Instalación/grant reales pendientes. Siguiente preflight rc.2 de solo lectura con su pareja de PHP en private-tools/blog-proposal-rc2; no repetir fingerprint ni instalar rc.1/experimento. CMS/Worker/contacto intactos.

**26 octubre — huellas del servidor recibidas y contrastadas:** las11comprobaciones son correctas; coinciden versiones y archivos principales de16plugins, dosMU, entradas del tema y wpdb. PHP del hosting8.5.12 frente a8.5.11 del laboratorio; no se ignoró ningún fixture en el servidor. Las ocho capacidades anteriores se conservan y el nuevo scope está ausente. [Recibo aportado por Alex](qa/blog-proposal-code-fingerprint-destination-2026-10-26.json). Se cierra la espera del diagnóstico: no volver a pedirlo ni repetir instalaciones/grants del lector. Siguiente: candidato distribuible separado de rc.1 y ensayo de admisión/respaldo/restauración por fases. Las huellas de entradas no verifican árboles completos; installationApproved=false. Sin cambios remotos.

**Actualización funcional del perfil ampliado:**149checks de CRUD/MCP/fallos correctos con experimento local; siguiente huella real de código y candidato distribuible. [Resultado](qa/MCP_BLOG_PROPOSAL_SITE_FUNCTIONAL_2026-10-26.md), [diagnóstico actual](WORDPRESS_MCP_BLOG_PROPOSAL_PACKAGE.md). rc.1 y CMS reales sin cambios.

**Estado posterior al diagnóstico real:** before recibido, bloqueado solo por perfil. Código/arranque contrastados y149checks funcionales ampliados correctos; falta la huella real de main files y candidato distribuible. rc.1 sigue retenido y sin cambios. [Recibo y siguiente fase](qa/MCP_BLOG_PROPOSAL_DESTINATION_PROFILE_2026-10-26.md). No repetir el comando inicial ni instalar/conceder scopes ahora.

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

## Incremento 4: transporte MCP/HTTP y barrera de conexión

Completados43casos HTTP con credenciales/sesiones reales de prueba y34de pérdida/cierre/sustitución de conexión, control de transacción/guard y SIGKILL antes/después de commit. Más72regresiones nativas y82+93locales:324comprobaciones funcionales correctas.18casos adicionales del verificador de recibos. [Evidencia y límites](qa/MCP_BLOG_PROPOSAL_TRANSPORT_2026-10-26.md).

La barrera fija el handle/CONNECTION_ID/transacción y fuerza errores antes de consultas wpdb; no permite continuar sobre una reconexión automática. Commit y rollback usan el handle original. Recuperar un recibo confirmado no vuelve a ejecutar la preparación. No se probó pérdida del acuse TCP del propio COMMIT; no confundir el SIGKILL posterior con esa inyección de red.

Cinco abilities solo en MU fixture de tests: contexto de fuente publicada, crear/actualizar/get/listado de propuestas. Contexto privado permite preparar base/diff sin ampliar el lector público ni revelar cuerpos privados/IDs de destinos ocultos. La fase HTTP confirma propiedad de propuestas/historia/inventario, schema de entrada, revocación de scope/credencial y rechazo de sesión ajena/cerrada. No hay herramientas de aprobar/aplicar/recuperar. Los scopes/credenciales sintéticos se retiraron.

## Incremento 5: candidato privado y preflight

Paquete0.1.0-rc.1 con cinco entradas/salidas cerradas, guard de destino/perfil, esquema acotado y plan de conservación.273comprobaciones y nueve recibos de ciclo; retirada probada con una propuesta privada conservada. [Paquete y comando de diagnóstico](WORDPRESS_MCP_BLOG_PROPOSAL_PACKAGE.md), [evidencia](qa/MCP_BLOG_PROPOSAL_PACKAGE_2026-10-26.md).

No está instalado en el CMS real ni concede permisos por activación. El perfil ensayado es mínimo y deliberadamente conservador, incluido tema/drop-ins/MU/query filters y wpdb. El hosting puede diferir: el siguiente paso es un before de solo lectura, con ambos archivos de diagnóstico juntos en private-tools. No cambiar/desactivar componentes del sitio para forzar compatibilidad.

## Siguientes capas

1. Recibir/contrastar perfil real y reproducir las diferencias pertinentes antes de aceptar el destino o ajustar un candidato. Comprobar tema/plugins/MU/caché/SQL filtros/driver sin datos o cuerpos privados. La observación before no instala ni concede capacidades.
2. Admisión operativa/respaldo y prueba de restauración del almacenamiento privado, después revisión del ZIP exacto y primer scope. Activación, tablas, propietario y permiso son pasos distintos con recibos. Conservar historia bajo cuotas, sin borrado automático. El contexto con cuerpo publicado pertenece al nuevo scope privado, no al lector público.
3. Aprobación/aplicación/recuperación futuras con scopes/recibos independientes. Revalidar fuente/grupo frente a escritores/cachés; la intención privada no autoriza publicar. Validar HTML/bloques/hooks/efectos y repetir revisión si saneamiento cambia el diff. Sin duplicación de grupos o publicación implícita. No reutilizar propuesta227/revisión1 o grants de proyectos.

El lector real sigue operativo y no hay nuevas herramientas privadas instaladas. Alex ejecuta solo el diagnóstico indicado en el procedimiento; no repetir instalación/grants del lector ni el piloto de contacto.
