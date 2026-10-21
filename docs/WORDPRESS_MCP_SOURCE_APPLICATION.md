# Contrato de laboratorio: aplicar y recuperar cambios de Projects

Estado20 octubre2026: **implementado solo en laboratorio0.5**, once abilities en total: seis de propuestas y las cinco de este contrato. Aprobación independiente por función CLI, fuera de MCP. **531 comprobaciones correctas** en `npm run test:mcp-applications`; [QA y límites](qa/MCP_EDITORIAL_APPLICATIONS_2026-10-20.md). CMS/permisos reales y Workers intactos. No es un paquete operativo instalable. [Schemas y estado legibles por herramientas](wordpress/editorial-source-application-contract.json).

0.5 prepara, aprueba, aplica y recupera cuerpo/tagline de Projects sintéticos. Consume la aprobación al confirmar el evento; la propuesta queda congelada y get devuelve applied/recovered. La recuperación requiere huella posterior exacta y revisión/aprobación nuevas, usa capacidad reservada y crea otro evento. Se comprobaron APIs WordPress/ACF, postcondiciones, fallos/rollback/cachés y concurrencia entre dueños. La revisión operativa de todos los hooks/editores del sitio, retención/backups, paquete y autorización de instalación/permisos/diff público siguen pendientes.

## Decisión de alcance

Primera implementación: modificar exclusivamente cuerpo y/o subtítulo de un Project existente, público, sin contraseña y de idioma EN/ES exacto, desde una propuesta propia revisada. Mantener ID, título, slug, autor, estado, URLs, imagen, idioma y relaciones de traducción. No crear Projects, borradores traducidos, publicar borradores, retirar ni eliminar contenido. Los demás CPT quedan fuera.

**Actualizar contenido de un Project ya publicado hace público ese cambio inmediatamente.** No describirlo como guardar un borrador ni asumir aislamiento porque el frontend esté en staging. El CMS es compartido con producción. La separación de publicación exigida en el plan se concreta aquí mediante una aprobación específica del efecto público, además del permiso de aplicar; no mediante una falsa edición invisible sobre el mismo registro publicado. Un flujo futuro de borradores/publicación de entradas nuevas requiere otro contrato.

Recuperar un cambio sobre una entrada publicada también produce un efecto público y necesita una nueva aprobación. No es la operación0.2 de recuperar versiones de una propuesta, que solo cambia su registro privado.

## Actores y permisos previstos

Ninguna activación concede capacidades ni modifica roles. La cuenta MCP real mantiene su lectura actual; no convertirla en Editor/Administrador. Preparar propuestas conserva sus scopes existentes. Apply/recover/approve se comprueban únicamente con usuarios sintéticos en0.5, además de read+read_public_projects+prepare y comprobaciones de dueño/idioma:

| Capacidad | Alcance |
| --- | --- |
| `alexcuadra_apply_project_changes` | Preparar/consultar revisión de aplicación y ejecutar exactamente la aplicación aprobada |
| `alexcuadra_recover_project_changes` | Preparar/consultar revisión de recuperación y ejecutar exactamente la recuperación aprobada |
| `alexcuadra_approve_public_project_changes` | Operador humano distinto del dueño de la solicitud: aprobar una revisión concreta; nunca asignada a la cuenta MCP que prepara/aplica |

No equivalen a edit_pages/publish_pages/manage_options, no autorizan REST/GraphQL de edición genérica ni acceso a propuestas de otro dueño. Un rol administrador tampoco sustituye estos scopes específicos en el módulo propio. La habilitación productiva de cada capacidad se presentará con cuenta/objeto/alcance/rollback concretos después de las pruebas.

La aprobación se registra mediante una operación administrativa dedicada, fuera del inventario MCP de la cuenta de servicio. No aceptar `approved=true`, instrucciones dentro del HTML ni una clave fabricada por el cliente como prueba de aprobación. El código no puede verificar una conversación humana: el operador debe recibir la autorización explícita de Alex sobre el diff y ejecutar esa operación separada. En laboratorio se simula con otro usuario y datos sintéticos; ese ensayo no autoriza el CMS.

## Operaciones previstas

Todas las entradas cerradas están en el JSON adjunto. IDs positivos, idiomas en|es, hashes64 hex, request_id32 hex. El ID fuente y los campos se derivan del registro del servidor, no se aceptan cuerpo, meta, SQL, ID destino o estado nuevos en apply/recover.

| Operación prevista | Función |
| --- | --- |
| `prepare-project-application` | Congelar revisión de la propuesta actual, fuente y esquema; guardar diff/snapshots privados, sin tocar el Project |
| `get-project-application` | Consultar solo revisión/resultado propio y su estado vigente, con el scope correspondiente al tipo de revisión |
| `apply-project-change` | Aplicar exclusivamente la revisión aprobada si siguen coincidiendo propuesta, fuente, esquema, actor y permisos |
| `prepare-project-recovery` | Construir revisión de reversión desde una aplicación propia confirmada; no aceptar valores de restauración del cliente |
| `recover-project-change` | Recuperar solo los campos cambiados por aquella aplicación, si la fuente continúa exactamente en el estado posterior esperado y existe nueva aprobación |

Prepare escribe un registro privado: no es readonly. Todas las mutaciones MCP requieren request_id; get no modifica almacenamiento.0.5 registra las cinco operaciones. Aprobación CLI separada: un registro por revisión, reintento del mismo operador/hash sin duplicar ni renovar caducidad; rechaza otro actor, contenido cambiado o expiración. No acepta approved=true ni autorización dentro del HTML. Apply/recover están marcadas destructive y consumen la autorización en la misma transacción que el evento.

## Revisión y aprobación

La revisión contiene dueño, propuesta/versión, Project/locale, operación apply|recover, huella de fuente, huella del esquema, campos cambiados, snapshots antes/después exactos y caducidad. Los snapshots incluyen únicamente post_content y, si corresponde, existencia/valor de project_tagline y su referencia `_project_tagline`; no copiar todo el meta, usuarios o configuración. Distinguir valor vacío de fila ausente y rechazar filas duplicadas de las claves implicadas. No truncar snapshots para hacerlos caber.

Mostrar el diff de esos campos y el efecto público antes de aprobar. El `review_hash` cubre la representación canónica completa del servidor, incluida identidad, versión, snapshots y esquema. Aprobar liga review_id/hash, dueño que ejecutará, Project, locale y operación exactos; actor aprobador distinto, fecha/caducidad UTC máxima10 minutos y consumo único. Una revisión distinta, cambio de patch, nueva versión, esquema cambiado o expiración exige revisión/aprobación nuevas. La vigencia la determina el servidor, nunca una fecha aportada por MCP.

La cuenta de servicio no puede aprobar su propia revisión aunque accidentalmente tenga ambos scopes. Las revisiones/snapshots/aprobaciones no son públicos, no se registran en REST/GraphQL/UI pública ni se incluyen en logs. Get exige propiedad y que el origen siga siendo público y del idioma previsto; no ofrece lectura de entradas retiradas/privadas. El acceso administrativo del aprobador es una ruta separada y acotada.

Mapeo candidato del export18oct: post_content ← patch.body_html y ACF project_tagline / `field_676dcb2aec8d4` textarea ← patch.tagline, grupo `group_676d40f7bb646`. Solo los campos presentes en el snapshot propuesto se modifican. No limpiar un campo fuente porque no figuraba en una propuesta recuperada. Los valores propuestos ya están normalizados; si los filtros/API modifican el resultado respecto del diff revisado, abortar y preparar una revisión nueva. Antes de aplicar en el CMS habrá que reconciliar sus claves/tipos/reglas vigentes, no confiar solo en el export o en los290 checks.

## Estados, idempotencia y recuperación

Revisión: prepared → approved → consumed, o expired. Aplicación: prepared → applied → recovered. Una revisión fallida por conflicto no representa una escritura confirmada. El evento aplicado/recuperado se conserva; recuperar no borra el historial ni reutiliza un token anterior.

La propuesta queda congelada al aplicar, incluso después de recuperar. El estado vive en la tabla privada de aplicaciones, sin reescribir el journal histórico0.3. Get/revisión devuelven state=applied|recovered y applied=true; update/restore rechazan proposal_frozen. El token de la propuesta identifica su patch congelado; cada evento apply/recover tiene token nuevo propio. Carga de propiedad/visibilidad separada del rechazo de base_version tras aplicación propia. Recibos0.3 conservan su DTO histórico draft/applied=false; no representan estado vigente ni revierten una cabeza. Los recibos source-event-v1 validan contra eventos de la aplicación y comparten request_id por dueño con el ledger anterior.

Usar una identidad de solicitud por dueño y entradas originales, con discriminador de tipo de recibo. La migración del ledger existente deberá conservar recibos/hashes0.3 y rechazar reutilización de clave entre operaciones; no convertir un recibo de fuente en un DTO de propuesta ni tratar un fallo como éxito. Reintentar con la misma clave resuelve una confirmación perdida; nunca repetir con otra clave por un timeout. Revocación/propiedad/origen no público siguen bloqueando acceso aun con un recibo conocido.

Recuperación exige application_id confirmado, huella posterior exacta, nueva revisión y aprobación de efecto público. Restaura únicamente los valores/existencia anteriores de los campos que la aplicación cambió. No cambia idioma/relaciones/estado/URLs ni restaura una copia completa del post. Si hubo otra edición, incluso en identidad/idioma/relaciones, devolver409; no intentar una fusión ni sobrescribirla. Una recuperación de contenido no restaura timestamps de modificación ni sustituye un backup del CMS.

## Límite transaccional previsto

Aplicar solo en laboratorio hasta completar la matriz. Preflight de InnoDB, esquema conocido, snapshots íntegros, límites y ausencia de transacción ajena. Orden fijo de bloqueo: admisión del dueño → propuesta/revisión/aprobación → Project fuente → filas de meta implicadas; repetir ese orden en recuperación. Dos dueños con propuestas del mismo Project también deben competir por el bloqueo/CAS de la fuente, no solo por su fila de propietario.

Releer desde DB y comparar versión de propuesta, huella de fuente, esquema, dueño, idioma, visibilidad, aprobación y campos después de adquirir los bloqueos. No usar snapshots/cache previos como autorización de escritura. Actualización de campos mediante las APIs WordPress/ACF acotadas, comprobación del resultado exacto y conservación del resto del registro; evento/snapshot, estado de propuesta, recibo, consumo de aprobación y contabilidad se confirman en la misma transacción. Cualquier fallo antes de COMMIT revierte todo e invalida caches afectadas. Una desconexión con confirmación incierta se consulta/reintenta por request_id; no ejecutar una recuperación a ciegas.

La implementación debe probar los hooks reales de este stack y el comportamiento de cachés/revisiones. Una transacción DB no revierte correos, webhooks, cachés externos o escrituras de otros plugins en otra conexión. No afirmar atomicidad de todo WordPress. No habilitar en el sitio si no se puede acotar ese límite y reconciliar editores externos/Polylang/ACF. No modificar protecciones del hosting ni desactivar plugins para hacer pasar la prueba.

Cuotas:8 propuestas/128 solicitudes confirmadas incluyendo reservas/8MiB lógicos por dueño,32 revisiones/2MiB por propuesta y8 revisiones de aplicación/recuperación retenidas incluyendo reservas y caducadas. Antes de invocar las APIs, apply exige capacidad para un cupo de revisión, dos solicitudes (prepare+recover),2097152+16384 bytes de recuperación y8192 bytes de margen del evento/recibo; snapshots demasiado próximos al tope2MiB se rechazan para que la revisión inversa quepa. La reserva se confirma con el evento aplicado. Prepare/revisión/aprobación consumen esa capacidad; recover libera el remanente al confirmar y no depende de cuota libre posterior. Esto garantiza **un primer intento completo dentro de la vigencia**. Si su revisión caduca, hace falta nueva revisión/aprobación y capacidad nueva para conservar también la anterior; no hay renovaciones silenciosas ni intentos ilimitados. Nunca poda/trunca registros. Permisos/fuente/esquema siguen bloqueando recuperación aunque haya reserva. Retención/export/borrado/backups y cuotas productivas pendientes.

## Matriz de laboratorio y salida pendiente

| Grupo | Evidencia necesaria |
| --- | --- |
| Autorización | Anónimo, solo prepare, apply sin aprobación, mismo actor aprobador/ejecutor, dueño ajeno, revocación y locale incorrecto rechazados |
| Aprobación | Hash alterado, revisión/actor/fuente distintos, caducidad y consumo/reutilización rechazados; fallo DB no consume aprobación |
| Aplicación | Body-only/tagline-only/ambos/limpieza explícita, Unicode/comillas y valores ACF exactos; ausencia de meta distinta de vacío; ninguna mutación fuera del whitelist |
| Concurrencia | Propuesta cambiada, ACF/esquema/referencia/idioma/relación/fuente cambiados; dos dueños sobre mismo Project, apply/recover y duplicados HTTP sin doble efecto |
| Fallos | APIs, hooks, snapshots, recibo, aprobación y COMMIT fallan sin estado parcial; cachés coherentes; reintento de confirmación perdida |
| Recuperación | Vuelve a los valores anteriores exactos solo si coincide la huella posterior; otra edición bloquea; versión/evento nuevos y reserva consumida una vez |
| Privacidad/compatibilidad | Snapshots/revisiones/aprobaciones fuera de GraphQL/REST por dueño/otro/admin; contratos0.3 y fuentes no incluidas intactos, migración sin pérdida de recibos |
| Salida | QA saneado, paquete verificable, rollback operativo y reconciliación del esquema/efectos de hooks del sitio; decisión expresa sobre instalación/capacidades y diff público |

## Cuándo cambia cada entorno

**CMS:** lectura0.1 ya está instalada/aprobada. El siguiente cambio editorial por este nuevo flujo llegará después de implementar/ensayar aplicación y recuperación, preparar paquete/permisos y aprobar el diff concreto. No basta con aprobar este contrato ni con un «continúa». El CMS compartido puede afectar producción sin desplegar ningún Worker.

**Staging frontend:** es una vía independiente. Actualización posterior al contrato: versión `1b12660e-22b5-4e0d-8120-cf89c4ab60e5` ya publicada para revisión con índice/anchors,237 unitarias/SSR/build/dry-run y cuatro fichas EN/ES correctas, rollback compatiblebbb disponible. [QA de la entrega](qa/EDITORIAL_OUTLINE_STAGING_2026-10-20.md). No necesitó instalar el módulo de aplicación ni terminar MCP. El QA visual pendiente se conserva abierto y sigue siendo requisito para producción; un deploy a staging no lo certifica ni autoriza sortear el navegador. El incidente429 requiere diagnóstico separado; detener verificaciones ante nuevos fallos sin martillar CMS.

No se actualiza staging por este módulo WordPress sin cambio de frontend: la entrega TOC fue independiente y ya está completada. Siguiente incremento MCP: revisión operativa de efectos/hooks/editores y esquema, retención/export/backups y candidato verificable con rollback, sin instalar ni conceder scopes reales. La aceptación visual/429 de staging sigue abierta. La mejora del cliente Astro–WordPress queda al final del [plan general](REDESIGN_PLAN.md#20-última-etapa-mejorar-el-conector-astrowordpress), por solicitud expresa de Alex.
