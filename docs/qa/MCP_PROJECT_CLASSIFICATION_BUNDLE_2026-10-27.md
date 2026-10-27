# Paquete coordinado de clasificación — 27 octubre 2026

Dos ZIP reproducibles: clasificación0.1.0-rc.1 SHA48d0734c…a4dfe2d (4385bytes) y propuestas0.1.0-rc.3 SHA581dc9a0…cb00df (18023bytes). [Manifiesto](../../artifacts/wordpress/project-classification-bundle-0.1.0-rc.1.manifest.json), [procedimiento](../WORDPRESS_MCP_PROJECT_CLASSIFICATION_PACKAGE.md). rc.2 y todos los paquetes instalados/fuentes selladas se conservan.

## Ensayos finales

| Recibo | Resultado |
| --- | --- |
| [Actualización/reversión](project-classification-bundle-upgrade-2026-10-27.json) | 33checks de fixture,8preflights (7correctos y1rechazo esperado por historySha ajeno),6recorridos de backup/verificación/rechazo de destino ocupado |
| [Regresiones](project-classification-bundle-regression-2026-10-27.json) | 57clasificación WP+22HTTP+72propuestas WP+34fallos+43HTTP+29contratos=257correctas |
| [Recuperación](project-classification-bundle-recovery-2026-10-27.json) | 39checks de integridad/restauración/get/list/replay y4recorridos CLI correctos |
| Guards del paquete | 14casos local/sitio/opt-in/producción/multisite/ausencia de WordPress; recibidos en los tres informes |

46checks de arranque preceden a cada ejecución, sin sumarlos como operaciones nuevas. Las credenciales/scopes de pruebas HTTP/operaciones quedan retirados según los recibos. Los ZIP se instalaron por WP-CLI, con reemplazo de la versión conocida solo en el laboratorio; nunca se alteraron los archivos originales de los paquetes para hacerlos pasar.

La propuesta sintética1/v1 y su request original se crean con rc.2 antes del before. Tras rc.3+lector activo, get y replay coinciden exactamente, con una sola propuesta/versión. SHA de tablas editoriales, roles y capacidades coincide antes/después. Los lectores de posts/proyectos anteriores y ambas herramientas nuevas funcionan. Se verifica código completo de los tres archivos del lector; alteración del body, archivo extra y plugin activo desconocido cierran el perfil. Un historySha distinto se rechaza.

El artefacto rc.2 exportado con escritor pausado se verifica con rc.3 y restaura en un prefijo aislado: mismo documento/get/replay. No se escribe en el espacio privado original o fuentes durante esa restauración. Se exporta/verifica después un artefacto rc.3, y se rechaza restaurar sobre las tablas existentes. Desactivar lector y recuperar rc.2 devuelve el mismo plan, versión, recibo, roles, permisos y fuente sintética.

## Perfil y problemas del arnés resueltos

WP7.1.3/PHP8.5.11/Polylang3.9.1/Adapter0.7.1/MariaDB11.8.9. Código previo verificado de16plugins más propuestas/lector, dosMU y TW4. Las dos taxonomías se registran con definiciones nuevas mediante ACF y opciones Polylang sintéticas. No se añade otro MU o relaja su hash. No se importaron SQL/configuración/usuarios/licencias o la propuesta real del hosting.

Los dos primeros recorridos se detuvieron antes de actualizar por el nuevo preflight: el reescritor de eval-file devolvía null. Se reprodujo directamente WP_CLI\\Utils\\replace_path_consts: pregError6, JIT stack limit exhausted. --use-include evita esa reescritura y el driver exige recibo único/ready/estado correctos; no se modifican límites PCRE o controles. Un diagnóstico de reanudación del segundo laboratorio encontró la BD temporal ya desmontada y no aportó aceptación nueva.

El tercer recorrido pasó actualización e instalación inactiva, pero el reinicio de WordPress desmontó el último volumen tmpfs y eliminó los fixtures temporales. Se eliminó ese reinicio del arnés, sin cambios de los ZIP, y el cuarto recorrido completó actualización/reversión. El modo extra bundle-recovery se añadió a las opciones CLI tras ese recorrido: el recibo conserva el hash del arnés ejecutado y la diferencia de esa única opción. Ningún archivo de producto cambió entre los ensayos finales. No se afirma validación del OPcache del hosting.

Doce contenedores propios detenidos y red externa interna reutilizada vacía, sin puertos o prune, verificados en los recibos. Los comandos auxiliares de inspección de WP-CLI se ejecutaron en contenedores efímeros sin red. Sin SSH, lecturas MCP reales, backup/restauración/grant del hosting, correo o deploy en este incremento.

## Siguiente fase real

Subir solo el preflight nuevo, ejecutar --use-include before y revisar perfil, forma1/v1 y pilotDocumentSha256 contra el documento real ya observado. Captura únicamente metadatos de las cuatro tablas nuevas, no baseline de fuentes/postmeta ni reconstrucción del incidente histórico. historySha debe permanecer idéntico en fases sucesivas. Sigue pendiente un respaldo actualizado del plan real antes de cambiar código/permisos.

Instalación aún no aprobada. El nuevo paquete no migra esquema, concede permisos, edita clasificaciones o publica posts. El catálogo público, temporalidad de lecturas y rama PHP del hosting conservan los límites descritos en el procedimiento. No se certifica toda la BD u opciones/cachés/árboles completos del servidor ni aceptación de accesibilidad/frontend/contacto/CMS429.

## Before real recibido y contrastado

[Recibo de Alex](project-classification-upgrade-destination-before-2026-10-27.json): ready=true/19checks, rc.2 exacto y lector ausente. SHA del documento coincide con MCP; SHA de historia también coincide con el derivado local de petición original, propietario2 y metadatos del plan1/v1/recibo. No se efectuó una nueva lectura MCP o SQL remota independiente. Siguiente pausa solo del scope privado y respaldo nuevo con helper rc.2 existente; no instalar paquetes ni recapturar baseline.

## Respaldo actualizado real exportado

[Recibo de Alex](project-classification-upgrade-backup-export-2026-10-27.json): export ready=true, cuatro conteos1 y sin escrituras de BD. Helper rc.2 exige pausa del scope privado y valida integridad interna del artefacto. No hay verificación remota independiente del agente; siguiente verify separado con el SHA recibido. Mantener historia75a0aa5f…60c7835a y scope pausado, sin instalación o restauración todavía.

## Respaldo actualizado real verificado

[Verify de Alex](project-classification-upgrade-backup-verify-2026-10-27.json): integrityVerified=true y cuatro conteos1, sin escrituras o restauración de BD. Artefacto/SHA originales conservados. Siguiente presentar el ZIP exacto rc.3 y sustituir únicamente el rc.2 conocido con scope pausado; proposal-upgraded debe conservar historySHA75a0aa5f…60c7835a y ocho capacidades previas. Sin actualización remota ejecutada por el agente ni instalación real anticipada.

## Rc.3 real activo y preservación verificada

[proposal-upgraded de Alex](project-classification-upgrade-destination-proposal-upgraded-2026-10-27.json): ready=true/19checks; paquete rc.3 exacto/activo, historia/documento/conteos originales y ocho capacidades previas intactos. Scope privado pausado y lector nuevo ausente. No hubo actualización remota ejecutada por el agente ni lectura nueva de cuerpos. Siguiente instalación inactiva del lector exacto y classifier-installed, sin --force o activación.

## Rechazo de instalación del lector por carpeta existente

[Salida aportada por Alex](project-classification-install-existing-directory-2026-10-27.json): SHA del ZIP correcto y rechazo de instalación sin --force por destino existente; preflight encadenado no ejecutado. Estado/integridad/activación de la carpeta pendientes de conocer. Se observó purga de cachés del hosting; no se afirma conservación global de BD/caché. Siguiente diagnóstico classifier-installed ya preparado, de solo lectura y con historySha original; no borrar/forzar/reactivar o restaurar scopes.

## Instalación inactiva del lector conciliada

[Classifier-installed de Alex](project-classification-upgrade-destination-classifier-installed-2026-10-27.json): ready=true/19checks, archivos exactos e inactividad del lector, rc.3 activo y hashes/conteos originales preservados. El diagnóstico resuelve el estado de la carpeta sin identificar su ejecución de instalación. No se reinstala o fuerza. Siguiente opt-in de sitio + activación + classifier-enabled, todavía con scope privado pausado y sin nuevos grants/asignaciones.

## Activación real y lecturas MCP verificadas

[Classifier-enabled](project-classification-upgrade-destination-classifier-enabled-2026-10-27.json) de Alex ready=true/19checks e historia conservada, con scope privado pausado. [Conector independiente](MCP_PROJECT_CLASSIFICATION_LIVE_2026-10-27.md):20abilities,146EN/196ES y catálogos4/20por idioma correctos/recíprocos, sin mutaciones. Siguiente instalar solo los dos helpers operativos rc.3 fuera del árbol público y verify del artefacto rc.2 antes de reabrir el scope privado; no restauración del CMS vivo.

## Compatibilidad del respaldo verificada con helpers rc.3 reales

[Verify aportado por Alex](project-classification-upgrade-backup-verify-rc3-2026-10-27.json) tras ejecutar el comando indicado en la carpeta nueva: ready/integrityVerified=true, cuatro conteos1 y sin escrituras/restauración de BD. Siguiente restitución exclusiva del scope privado anterior y after-scope con hash original; cerrar por get/list/replay del plan1/v1 vía MCP, sin crear otro. No se han ejecutado cambios remotos por el agente.
