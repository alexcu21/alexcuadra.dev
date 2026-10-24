# Revisión privada por MCP — fase autorizada y completada

Acceso anónimo verificado24octubre:las2rutas MCP rechazan sin credenciales con401/rest_forbidden y no devuelven el documento privado. Usuario2/subscriber sigue leyendo revisión1/hash/caducidad originales. [QA](qa/MCP_OPERATING_ANONYMOUS_ACCESS_2026-10-24.md). No equivale a probar otro dueño autenticado o todas las rutas REST/GraphQL.

Comprobación adicional24octubre completada:replay exacto devuelve el recibo/revisión1 sin renovar expiración; documento EN y propuesta idénticos antes/después, ES rechazado sin documento.9comprobaciones correctas por MCP. [Resultado real](qa/MCP_OPERATING_NATIVE_IDEMPOTENCY_2026-10-24.md). No se preparó otra solicitud lógica ni se aprobó/aplicó contenido; no repetir/regenerar por caducidad.

Estado:Alex autorizó «si autorizo continua» el24octubre y la fase quedó completada:revisión1 preparada una sola vez e inspeccionada por MCP,12comprobaciones correctas y propuesta227 sin cambios/en borrador/sin aplicar. [Recibo y límites reales](qa/MCP_OPERATING_PRIVATE_REVIEW_LIVE_2026-10-24.md). No se aprobó/aplicó/recuperó ni se creó otra propuesta. El documento inspeccionado ocupa849bytes JSON reserializado, sin atribuirle el espacio físico total de BD. La revisión se conserva al caducar; no regenerarla para repetir la prueba. Incidencia histórica postmeta abierta. No repetir la preparación ni la aceptación inicial de tablas vacías.

Los pasos siguientes registran el alcance ejecutado y el protocolo de revisión, no una solicitud de volver a ejecutarlo.

## Operación autorizada y ejecutada

Usar el conector actual como usuario2/subscriber para crear **una** revisión privada de la propuesta227 existente, Project146/EN, que actualmente contiene únicamente el campo `tagline`. Se fijan su versión `737a0558179ee7df25706593918782eefb24297135fa4860090af450e4560b44` y su base `bbca3d9ada7d199ebd4911b39625bcdd0b6d1285c0793562a6bbb06df6074d85`, confirmadas de nuevo por lectura real. [Parámetros concretos y request_id reservado](qa/MCP_OPERATING_PRIVATE_REVIEW_PLAN_2026-10-23.json). Ese identificador de idempotencia no es una credencial y debe conservarse ante cualquier reintento.

1. Releer la propuesta y comprobar identidad, versión/base, draft/applied=false y campo tagline. Si cambian, detenerse sin actualizarla ni preparar otra automáticamente.
2. Invocar `alexcuadra/prepare-project-application` con los parámetros fijados. Crea un documento privado congelado y un recibo; no invoca el writer público.
3. Leerlo mediante `alexcuadra/inspect-project-review` usando el review_id devuelto y locale=en. Comparar hash/owner2/proposal227/project146/versions/base/operación y campos before/after; solo tagline y su referencia ACF deben figurar en ese documento. No publicar valores privados en repositorio/chat.
4. Releer la propuesta para comprobar que sigue en la misma versión/base/borrador. Registrar recibos/IDs/hashes/conteos verificables, omitiendo valores del patch y del documento.

La fase termina después de crear/leer esa revisión y comprobar la propuesta. No crea otra propuesta, no actualiza/restaura la227, no genera aprobación, no llama apply/recover ni al consumidor de efectos, no modifica usuarios/roles/configuración/plugin/Projects. El campo operation=apply en el documento identifica el tipo de revisión; preparar ese documento no ejecuta la aplicación.

## Escrituras, almacenamiento y retención

Es la **primera escritura solicitada en las tablas operativas**, después de la auditoría de solo lectura:una fila en reviews y una en requests. El lock del propietario usa la fila existente del ledger rc.2 del usuario2; no se solicita otra propuesta ni un grant. La transacción/barrera/guarda de conexión y cuotas del paquete siguen vigentes. El candidato rc.3 no cambia.

Documento máximo512KiB y recibo máximo4KiB, más índices/overhead y contabilidad del contrato; el uso real se informa cuando exista. La cuota del propietario contempla8revisiones/64solicitudes/8MiB, con reservas de recuperación independientes cuando se aplique. No se descarga un backup ni se inicia Docker; solo se guardan evidencias pequeñas de metadata en el equipo. Equipo32GiB libres al preparar esta fase.

La revisión tiene caducidad de600segundos para autorizar/aplicar un cambio, y **se conserva después de caducar**:no elimina historia ni libera cuota automáticamente. No es una operación reversible mediante borrado de filas. Si pierde vigencia no crear otra para repetir una demostración sin revisar la necesidad y su cuota. Ningún comando de aprobación/publicación está incluido en esta fase.

Si la respuesta se pierde, puede haber COMMIT aun sin recibo visible. No variar request_id/versiones ni crear duplicados:conciliar o reutilizar exactamente los mismos parámetros de idempotencia. Ante error genérico del conector, no inferir código HTTP/estado de transacción que no haya sido devuelto.

## Límites de autorización y de evidencia

La autorización previa cubrió los tres scopes y la aceptación de almacenamiento de solo lectura. Alex autorizó y completó aparte esta revisión privada persistente y su lectura; no presupone aprobación de un diff público. Una eventual edición pública necesita mostrar/aprobar el cambio exacto y la aprobación independiente por CLI con su hash vigente.

No se ejecutó SSH:el agente la preparó e inspeccionó por MCP con la autorización registrada. No se añaden permisos porque core/get-site-info esté denegado. La prueba positiva de preparación/inspección real quedó completada con revisión1; los4rechazos de lectura actuales no certifican su error404 exacto porque el conector solo devuelve el mensaje.
