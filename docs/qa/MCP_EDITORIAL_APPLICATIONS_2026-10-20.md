# QA — aplicación y recuperación de fuentes en laboratorio0.5

20 octubre2026. **531 comprobaciones correctas,0 fallos, runner exit0** en `npm run test:mcp-applications`:376 regresiones previas+155 nuevas. [Resultado estructurado, carreras y hashes](MCP_EDITORIAL_APPLICATIONS_RESULT_2026-10-20.json). Proyecto independiente `alexcuadra-editorial-application-lab`, sin puertos host ni datos/credenciales del CMS real. [Contrato vigente](../WORDPRESS_MCP_SOURCE_APPLICATION.md).

## Entrega comprobada

Editorial Changes0.5-lab registra once abilities: seis de propuestas y cinco de aplicación/revisión/recuperación. Approve sigue exclusivamente como función CLI de operador distinto, fuera de MCP. Guard local/http://wordpress y ausencia de concesiones automáticas de permisos conservados. Read0.1.0 y su ZIP mantienen los hashes aprobados. No paquete instalable ni ampliación de permisos real.

Apply verifica identidad/propiedad/idioma, revisión/hash, propuesta/fuente/esquema, scopes de ejecutor y aprobador, caducidad y consumo. Revalida después de bloqueos y APIs/hooks. Actualiza solo body/tagline presentes mediante wp_update_post/update_field, compara resultado exacto y preservación de otras columnas/meta/relaciones, admitiendo solo timestamps de modificación. No crea fuentes, cambia título/slug/autor/estado/URLs/imagen ni traduce registros. Se probaron siete perfiles: cuerpo, tagline, ambos, vacíos explícitos, meta ausente, valor vacío sin referencia y contenido ES. Incluyen Unicode/comillas/barra inversa; recuperación devuelve los valores/existencia iniciales exactos, sin restaurar timestamps.

Tabla privada ac_change_applications enlaza propuesta, revisión y eventos con tokens nuevos; no altera el journal0.3. Propuesta queda congelada incluso después de recuperar. Get/revisión muestran applied/recovered y applied=true; update/restore rechazan proposal_frozen. Get de revisión informa consumed. Recibos source-event-v1 comparten namespace de request_id con las operaciones anteriores, validan contra eventos y no repiten efectos. Recibos0.3/prepare siguen siendo históricos, con su DTO original: un draft/applied=false antiguo no es estado vigente. Replays apply/recover también funcionan después de recuperar y no revierten la fuente.

## Transacciones, fallos y reservas

Migración aditiva0.5 conserva payloads/hashes/recibos anteriores. Transacción de aplicación READ COMMITTED, verificación de8 tablas InnoDB, admisión por dueño y orden propuesta/meta→revisión/aprobación→fuente/meta/relaciones. Dos dueños del mismo Project compiten por su fila fuente, no solo por su fila de cuota. Evento, estado congelado, consumo de aprobación, recibo y reserva se confirman juntos. Fallos inducidos de API de post, ACF, hook que toca meta no permitido, evento, consumo, recibo y COMMIT revierten filas y cachés; retry con la misma clave funciona. Transacción ajena rechazada sin terminarla. Identidad/fuente/esquema/propuesta cambiados tras aprobación y permisos revocados denegados.

Confirmación perdida simulada: el harness confirma la transacción real y provoca un error de respuesta del driver; el ejecutor devuelve error, pero un retry con la **misma** clave consulta el evento confirmado y no aplica otra vez. No se ejecuta recuperación automática a ciegas.

Antes de invocar APIs, apply exige capacidad para una revisión, dos solicitudes (prepare/recover),2097152+16384 bytes reservados y8192 bytes de margen adicional para evento/recibo. Rechaza snapshots demasiado próximos al tope2MiB para que también quepa el diff inverso. Las reservas cuentan dentro de8 revisiones/128 solicitudes/8MiB por dueño. Falta de capacidad rechaza sin escribir fuente. Prepare y aprobación de recuperación consumen bytes/cupos reservados; recover libera el remanente al confirmar. Prueba con cuotas libres llenas admite prepare, aprobación y recuperación con128 solicitudes totales, sin depender de espacio libre posterior.

La reserva garantiza **un primer intento completo dentro de su vigencia**. Si la revisión de recuperación caduca, nueva revisión/aprobación y capacidad adicional son necesarias para conservar la anterior; la prueba verifica IDs/hashes nuevos, diff anterior retenido y recuperación con nueva aprobación. No renovar autorizaciones, podar snapshots ni prometer renovaciones ilimitadas. Cambios de fuente siguen devolviendo conflicto aun con reserva; una aprobación apply no sirve para recover.

## Transporte y privacidad

Cuatro carreras HTTP con Application Passwords sintéticos, sesiones separadas y dos PIDs Apache por escenario. Barrera anterior a admisión visible mediante conexión autocommit separada, sin afirmar dos CAS simultáneos:

| Carrera | Resultado |
| --- | --- |
| Apply/apply, dos dueños sobre misma fuente | Un evento ganador y un source_changed |
| Recover/apply, dos dueños sobre misma fuente | Un efecto ganador y un source_changed |
| Apply duplicado, mismo dueño/dos sesiones | Dos DTO iguales, un evento/recibo, reserva una vez |
| Recover duplicado, mismo dueño/dos sesiones | Dos DTO iguales, un evento nuevo, consumo/liberación una vez |

Rechazo de revisión de otro dueño y replay HTTP histórico después de recuperar comprobados. Tabla de aplicaciones sin CPT/REST/GraphQL: introspection no expone rutas/tipos y colección REST devuelve404 para dueño/otro/admin sintéticos. Regresiones de revisiones/propuestas preservan sus comprobaciones por IDs/nodos/colecciones. Scopes/Application Passwords sintéticos retirados y verificados; cierre de sesiones solicitado.

Stack: WordPress7.1.3/PHP8.2.34/ACF6.8.10/WPGraphQL2.24.2/WPGraphQL for ACF3.0.0/Polylang3.9.0/conector y Adapter0.7.1. Grupo Projects/field key original del export local. Las fuentes sintéticas sí se modifican como parte del ensayo; siete perfiles recuperan sus valores, algunas carreras conservan su ganador como evidencia. **No afirmar que todas las fuentes del laboratorio quedan intactas. El CMS real no fue consultado ni modificado.**

## Incidente del harness y límites operativos

Las pasadas repetidas de pruebas de cuota llenaron el tmpfs256MiB del laboratorio GraphQL anterior: df verificó253MiB usados/99 %, seguido de fallos de almacenamiento en escenarios diferentes. Se detuvo ese proyecto y se conservaron sus volúmenes. Nuevo overlay `compose.applications.yaml` crea otro proyecto con tmpfs512MiB; suite final completa exit0; volumen nuevo42 % usado/298MiB disponibles después de las pruebas. No se borraron/resetearon datos locales ni se desactivaron plugins/protecciones para aceptar una carrera. La capacidad del harness no es una corrección del429 de staging ni una cuota física de producción.

Atomicidad limitada a tablas verificadas y escritores que cooperan con estos bloqueos. No garantiza rollback de correos/webhooks/caché externa ni exclusión de nuevas filas meta de escritores externos que ignoren el bloqueo del post. El fixture no representa todos los hooks, revisiones, temas y procesos del hosting. El guard impide instalar esta versión como paquete productivo. No resuelve la política histórica ACF sobre fuentes protegidas ni certifica todo WordPress.

Siguiente incremento: revisión operativa de hooks/editores/esquema y límites de transacción del sitio, retención/export/backups y candidato verificable con rollback, sin instalación ni scopes reales por un continúa. Antes de una escritura real se requiere aprobación expresa del paquete/cuenta/capacidades y del diff/efecto público concreto. No hace falta crear Editor para las capabilities propias.

La mejora del cliente Astro–WordPress está añadida **al final** del [plan general](../REDESIGN_PLAN.md#20-última-etapa-mejorar-el-conector-astrowordpress), separada del adaptador MCP y aún sin implementar. Frontend/Workers/CMS reales y credenciales intactos, contacto/envíos/challenges desactivados, navegador bloqueado sin reintentos. No build/smoke/deploy de frontend repetidos. Árbol local preservado; sin commit/reset. Tras la suite solo se corrigió el texto de categoría MCP para describir también aplicación/recuperación; revisión estática y hashes actualizados, sin cambio de lógica. Ambos laboratorios detenidos conservando volúmenes, devPID6326/4325 preservado; cierre en handoff.
