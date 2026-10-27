# Clasificación propia: actualización real completada — 27 octubre 2026

**Cerrado:** lector0.1.0-rc.1 y propuestas0.1.0-rc.3 activos, catálogo20 y lecturas públicas EN/ES correctas, nueve capacidades originales restituidas e historia del plan1/v1 conservada. No quedan comandos de instalación o permisos por ejecutar para esta aceptación.

## Evidencia del hosting

Alex ejecutó y aportó los recibos de [before](project-classification-upgrade-destination-before-2026-10-27.json), [propuestas actualizadas](project-classification-upgrade-destination-proposal-upgraded-2026-10-27.json), [lector instalado inactivo](project-classification-upgrade-destination-classifier-installed-2026-10-27.json), [lector activo](project-classification-upgrade-destination-classifier-enabled-2026-10-27.json) y [after-scope](project-classification-upgrade-destination-after-scope-2026-10-27.json). Todos pasan19checks. La carpeta preexistente se concilió por archivos/estado, sin forzar reinstalación del lector; no se conoce la ejecución que la creó.

El último conserva1propietario/1propuesta/1revisión/1recibo, subscriber, ocho capacidades anteriores más el único scope privado que volvió tras la pausa, sin grant a roles/otras cuentas y16permisos amplios denegados. historySHA `75a0aa5fdaab48206bf72e8e70eb274dd75c5b12af81d8b04963aff160c7835a`, documentoSHA `54945c849ee3fea44d56c640a63f335961f68577d2ca1fb41b5d7248337f6b3d`.

[Respaldo actualizado exportado](project-classification-upgrade-backup-export-2026-10-27.json), [verificado con rc.2](project-classification-upgrade-backup-verify-2026-10-27.json) y [con helpers rc.3](project-classification-upgrade-backup-verify-rc3-2026-10-27.json): cuatro conteos1, integridad correcta, sin escrituras/restauración de BD. Se conservan ambos respaldos privados; el owner-only inicial no sustituye al que incluye el piloto. El artefacto no se adjuntó o descargó en este trabajo.

## Comprobación MCP independiente

[Lecturas de clasificación](project-classification-live-read-2026-10-27.json): nueve llamadas previas sin mutaciones; catálogo20, esquemas cerrados/read-only, Christianity Today146EN/196ES con1categoría/8tecnologías y traducciones recíprocas; catálogos4categorías/20tecnologías por idioma con IDs del mapa aprobado y final de cursor.

[Cierre del acceso privado](project-classification-upgrade-live-closure-2026-10-27.json): cinco llamadas tras restituir el scope, get1/v1, inventario, replay exacto de la petición original, inventario posterior y metadatos públicos228ES. Documento completo, propietario, versión y SHA coinciden con el [piloto inicial](blog-proposal-pilot-live-2026-10-27.json). El replay devuelve ese mismo documento; listado antes/después contiene únicamente1/v1, sin otra propuesta. La comprobación local del digest vuelve a coincidir.

La solicitud repetida usa request_id d445d143c728bc9301ca4af463c06116 y el mismo payload; no se emitió otra clave o cambio. El único diff sigue siendo Como→Cómo propuesto en el título. El lector público devuelve título original sin tilde, mismo slug, categorías/etiquetas de blog y traducción239EN. No se aprobó, aplicó o publicó el plan ni se modificaron asignaciones de proyectos.

## Alcance y límites

El agente realizó lecturas MCP y una solicitud de replay ya existente; los cambios de código/permisos y backups del servidor fueron ejecutados por Alex vía SSH. Sin SSH del agente, credenciales nuevas, correos, deploy del Worker, DDL real o nueva propuesta. No hay huella global de BD ni prueba de conservación de campos fuente no leídos; los hashes de historia cubren solo los metadatos del nuevo módulo. No se reabrieron el incidente histórico de postmeta o la propuesta227/revisión1.

Recuperación y rollback se ensayaron con datos sintéticos/perfil completo; no se ejecutó restauración o rollback sobre el CMS vivo. PHP8.5.12 reportado por el hosting frente a8.5.11 de laboratorio; no se certifican opciones/cachés/árboles completos del servidor o ausencia de otros efectos de bootstrap. El instalador fallido mostró una purga de cachés y no se oculta ese dato.

El lector público no sirve para enumerar términos exclusivos de contenido privado. Las propuestas/aplicación de clasificaciones y el escritor público de posts continúan pendientes. El formulario/frontend reciente, CMS429 y QA final siguen abiertos en el [inventario](../NEXT_INCREMENTS_2026-10-26.md).

No repetir los controles iniciales que exigían almacenamiento vacío o esta forma exacta1/v1 tras futuras revisiones, ni borrar historia para hacerlos pasar. Para futuros respaldos, coordinar pausa y retirar solo el scope privado antes de exportar con los helpers rc.3, conservando artefactos/SHA y retomando acceso por revisión del estado. El respaldo actual no cubre planes creados después.
