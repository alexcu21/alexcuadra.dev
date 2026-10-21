# Solicitudes repetidas y cuotas — 20 octubre 2026

Actualización posterior: [integración ACF/Polylang/WPGraphQL completada](MCP_EDITORIAL_GRAPHQL_STACK_2026-10-20.md) en un laboratorio separado,290 checks (236 de este perfil base+54 nuevos), con fuentes PHP0.3 intactas. Este informe/JSON conserva su aceptación236; la integración instalada de plugins ya no está pendiente en laboratorio.

## Resultado

Editorial Changes **0.3.0-lab**, solo Docker. `npm run test:mcp-changes` exit0: **236 comprobaciones correctas**,67 framework+44 historial+37 idempotencia/cuotas+86 HTTP+2 guards. [Resultado saneado](MCP_EDITORIAL_IDEMPOTENCY_RESULT_2026-10-20.json), [contrato](../WORDPRESS_MCP_PROJECT_CHANGES.md), [admisión transaccional](../../wordpress/plugins/alexcuadra-editorial-changes/storage.php).

Ninguna consulta/instalación/edición/capacidad/credencial del CMS compartido. Editorial Read0.1.0 fuente/ZIP intactos, SHA256 verificados. No hace falta Editor. No hay apply/publicación de proyectos, traducciones nuevas, Workers/contacto/envíos ni navegador. Se conservan cambios locales y dev4325.

## Comportamiento verificado

Create/update/restore exigen `request_id` de32 hex minúsculos; el cliente conserva la misma clave y parámetros en un reintento. Clave por propietario, compartida entre operaciones; hash de operación y entrada original con propiedades ordenadas. No se equiparan dos textos distintos solo por normalizarse igual. Una clave reutilizada con otros datos produce409 `request_reused`.

Se guarda un recibo con el DTO confirmado y su propuesta. Repetir devuelve exactamente ese DTO sin otro cambio. Si hubo versiones posteriores, la repetición sigue devolviendo el snapshot original y no revierte la cabeza: consultar get para verla. Autorización, propietario, fuente e integridad del recibo siguen comprobándose; no sirve para eludir revocación o source drift. Los errores no se registran como éxitos.

Admisión por propietario: transacción InnoDB y bloqueo de una fila interna, cuotas comprobadas dentro, payload/historial/recibo confirmados juntos. No anida transacciones ajenas; estado transaccional desconocido o motores no InnoDB rechazan. Excepciones/rechazos revierten; caché de la propuesta invalidada incluso si falla la creación de su payload. El CAS sigue protegiendo la versión cargada antes de esperar al bloqueo.

Cuotas fijas del prototipo:8 propuestas por propietario (cualquier estado),128 escrituras únicas confirmadas y8MiB lógicos de payloads+DTOs de recibos. Además32 revisiones/2MiB por propuesta siguen vigentes. Reintentos confirmados gratuitos incluso con cuota llena. Rechazo429 `owner_quota` sin cambios parciales; el límite de propuestas permite actualizar las existentes si queda presupuesto de solicitudes/bytes. No hay TTL, reset ni poda automática.

## Pruebas

WordPress7.1.3/PHP8.2.34/Polylang3.9.0/MCP Adapter0.7.1, imágenes fijadas por digest y fixtures sintéticos sin puertos host. Fuente original/ACF de proyectos intactos por hashes de posts/postmeta/relaciones; el harness altera/restaura una fuente solo para comprobar drift.

**37 comprobaciones específicas:** reintentos create/update/restore exactos, cabeza posterior conservada, orden de propiedades, clave ausente/inválida/cambiada y cambio de operación, aislamiento entre usuarios, revocación y fuente cambiada. Cupo de propuestas completo, último recibo permitido y rechazo posterior, reintentos gratuitos; límite agregado de bytes revierte cabeza/historial/recibo. Fallos de INSERT del recibo, payload inicial y COMMIT revierten propuestas/cachés/contabilización. Se puede reintentar una clave fallida. Transacción externa se conserva y almacenamiento no transaccional/estado desconocido se rechazan.

Para ejercitar los límites128 y8MiB sin una prueba de carga, el harness introduce ocupación de recibos sintética directamente. El caso de bytes coloca el presupuesto a un byte del límite antes de intentar una actualización real. Eso verifica admisión/reversión contable, no rendimiento de128 operaciones o8MiB de tráfico real.

**86 comprobaciones HTTP**, incluidas seis carreras con dos sesiones y dos PIDs Apache independientes:

| Solicitudes paralelas | Resultado verificado |
| --- | --- |
| Update/update, claves distintas, mismo token | Una versión/revisión ganadora y un conflicto |
| Update/restore, claves distintas, mismo token | Una versión/revisión ganadora y un conflicto |
| Create idéntico, misma clave | Dos DTO iguales, una propuesta y un recibo |
| Update idéntico, misma clave | Dos DTO iguales, una revisión y un recibo |
| Create con clave igual y texto distinto | Un éxito y un `request_reused` |
| Dos creates por el octavo cupo | Un éxito y un `owner_quota`; exactamente8 propuestas |

La barrera se movió del CAS al límite anterior al bloqueo de admisión. Usa una conexión autocommit separada, exclusiva del MU de pruebas, para que ambas llegadas sean visibles mientras la transacción principal sigue abierta. Sin ella los registros de sincronización quedaban sin confirmar y la primera prueba paralela no podía observar al otro proceso. La captura final verifica dos trabajadores solapados antes del bloqueo; las transacciones se serializan por diseño. No afirmar que ambos CAS se ejecutan simultáneamente.

La prueba antigua de intercalación PHP reentrante se sustituyó por rechazo de escritura anidada, con la operación exterior conservada: una llamada interna no debe confirmar una transacción ajena. La concurrencia entre procesos sigue cubierta por HTTP. Historial/legacy/límites originales y2 guards siguen pasando. Reactivación del plugin conserva historial y recibos; sesiones/usuarios, revocación de scope/credencial y cierre se conservan. Capacidades y contraseñas sintéticas retiradas/verificadas; solicitudes de cierre enviadas, plugin restaurado y filas de barrera retiradas.

PHP lint/shell y `git diff --check` correctos. Sin cambios de frontend/lectura: no se repiten build/SSR ni91 pruebas del paquete de lectura. No se ocultaron avisos conocidos de bootstrap/pruebas negativas.

```sh
npm run test:mcp-changes
npm run test:mcp-changes-http # solo HTTP y bootstrap
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml \
  -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml \
  -f tests/integration/wordpress-mcp-changes/compose.yaml stop
```

Solo este laboratorio se detuvo al terminar. Datos de laboratorio tmpfs; persistencia entre peticiones/reactivación, no recuperación tras perder volúmenes ni backup.

## Límites y continuación

Las cuotas limitan operaciones de este prototipo: no controlan escritores externos que ignoren su bloqueo, ni overhead físico de tablas/índices, ni tasa temporal/global. Recibos sin expiración, claves/contadores sin API pública; política de retención/export/borrado y cuotas operativas reales no aprobadas. No se promete disponibilidad/performance bajo carga ni tolerancia a plugins/hooks arbitrarios con efectos externos. Un COMMIT con respuesta de red perdida se resuelve reintentando la misma clave; no se ensayó cortar la conexión DB después de confirmarlo.

No hay TLS/hosting real ni ACF/WPGraphQL instalados en este laboratorio; únicamente contratos/campos sintéticos y Polylang. **Siguiente: integración ACF/Polylang/WPGraphQL aislada**, especialmente exclusión GraphQL de propuestas privadas y lectura de las field keys conocidas. Después aplicar/publicar/recuperar fuentes será un diseño y autorización separados. No generar paquete real ni ampliar permisos por este resultado; TOC visual/429 siguen abiertos y contacto desactivado.
