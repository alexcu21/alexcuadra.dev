# QA — revisión y aprobación privadas, laboratorio0.4

20 octubre2026. **376 comprobaciones correctas,0 fallos, runner exit0** en `npm run test:mcp-changes-stack`:67 framework+44 historial+37 cuotas+86 HTTP anteriores+54 integración+2 guards+86 nuevas de revisión. [Resultado estructurado y hashes](MCP_EDITORIAL_REVIEWS_RESULT_2026-10-20.json). Pruebas nuevas en `tests/integration/wordpress-mcp-changes/reviews.php`; implementación `wordpress/plugins/alexcuadra-editorial-changes/reviews.php` y migración aditiva en storage.php. No se usa navegador ni otro host para sortear su bloqueo.

## Alcance completado

Editorial Changes0.4-lab registra ocho abilities: seis previas y `prepare-project-application`/`get-project-application`. Solo local/http://wordpress. No registra approve/apply/recover; **ninguna ability escribe Project**. Revisión privada de propuesta propia, con `read`+lectura pública+prepare+apply explícitos, sin Editor. Activación no concede scopes ni cambia roles. Aplicación/recuperación de fuentes queda pendiente del [contrato objetivo](../WORDPRESS_MCP_SOURCE_APPLICATION.md).

El diff guarda únicamente campos incluidos: cuerpo y/o tagline con existencia/valor exactos de sus dos filas ACF, vacío distinto de ausente; referencias originales y Unicode/comillas/barra inversa preservados. Huella canónica cubre identidad, snapshots, esquema ACF, versiones y expiración; la fuente completa/posts/meta/relaciones solo se resume mediante hash, sin copiar meta arbitrario ni destinatarios privados. Duplicados ACF ambiguos rechazados. `public_effect=would_modify_published_project`, `applied=false` explícitos.

Aprobación por función CLI local `review_approve`, operador con scope específico distinto del dueño/ejecutor. Administrador sin scope y dueño con ambos scopes denegados. Liga revisión/hash/actor/ejecutor/proyecto/idioma/operación/fuente/esquema, máximo600s del reloj del servidor; retry del mismo operador no renueva vigencia ni duplica almacenamiento. Fuente/esquema/propuesta cambiados, hash/caducidad/idioma y permisos insuficientes bloquean aprobación. El ensayo usa personas/capacidades sintéticas; **no constituye aprobación humana de contenido real**. Función no expuesta a MCP/REST/GraphQL ni herramienta PHP genérica.

Get devuelve estado actual `prepared|approved|expired`, y `current=false` si el diff dejó de coincidir. Prepare devuelve resultado histórico exacto al reintentar, incluso si después hubo aprobación/cambio de cabeza; no confundir su `current=true` histórico con elegibilidad actual. Una propuesta con otro dueño o fuente protegida bloquea acceso/replay; se revalidan scopes. Corrupción de snapshot/aprobación falla cerrada.

## Almacenamiento y pruebas

Tablas nuevas privadas sin CPT ni rutas públicas, migración0.3→0.4 aditiva. Bytes/hashes de recibos existentes se conservan y replay antiguo sigue funcionando. Recibo `source-review-v1` comparte namespace por dueño con las operaciones anteriores; clave con otra intención409, errores sin recibo de éxito. Snapshots/aprobaciones/recibos cuentan para8MiB, límite8 revisiones retenidas incluidas caducadas y2MiB por snapshot completo, sin truncar/podar. Reintentos gratuitos incluso con cuota llena.

Transacción de revisión/aprobación verifica7 tablas InnoDB y rechaza transacción ajena; READ COMMITTED solo en esa transacción. Bloqueo admisión→propuesta/meta→revisión→fuente/meta/relaciones, relectura con invalidación de cachés de post/meta/valor ACF. Snapshot+recibo o aprobación+contabilidad se confirman juntos. Fallos inducidos en snapshot, recibo, COMMIT y aprobación no dejan filas/cuota parcial; retry de aprobación posterior funciona. No se modifican fuentes mediante APIs, por lo que esto **no prueba** atomicidad de futuros hooks/revisiones/envíos/cache externa al aplicar WordPress.

Dos carreras HTTP con sesiones del mismo dueño y procesos Apache distintos, barrera anterior al bloqueo visible por conexión autocommit separada:

| Carrera | Resultado |
| --- | --- |
| Prepare idéntico | Dos respuestas iguales, una revisión y un recibo |
| Último cupo de revisión | Una respuesta correcta, un owner_quota; tope8 respetado |

Solapamiento verificado antes del bloqueo; las transacciones se serializan. No afirmar aplicación concurrente de fuente ni dos CAS simultáneos. HTTP además confirma descubrimiento de las dos abilities y ausencia de approval/apply/recover, consulta/replay correctos y rechazo de otro dueño, operador y booleano de aprobación. GraphQL introspection no expone tipos/rutas de revisiones/aprobaciones para dueño/otro/admin; colección REST inexistente404 y discovery REST de abilities oculta las dos herramientas. Los registros viven en tablas propias, no son nodos de contenido. Las54 pruebas de integración anteriores siguen cubriendo propuestas por ID/nodos/colecciones.

Stack aislado `alexcuadra-editorial-graphql-lab`, sin puertos host: WordPress7.1.3/PHP8.2.34/ACF6.8.10/WPGraphQL2.24.2/WPGraphQL for ACF3.0.0/Polylang3.9.0/conector y Adapter0.7.1. Las filas de fuentes sintéticas permanecen intactas después de las operaciones; drift del harness restaurado. Scopes y Application Passwords sintéticos retirados/verificados; cierre de sesiones solicitado. Avisos esperados de pruebas negativas WP_Ability/abilities ausentes no son fallos del runner.

## Límites y siguiente incremento

No hay aplicación/recuperación, congelación de propuestas, consumo de aprobación ni reserva de cuota para recuperar. Falta implementar ese evento transaccional y el DTO de propuesta aplicada, verificar postcondiciones de APIs/hooks y fallos, carreras entre dueños sobre la misma fuente y recuperación condicional.0.4 puede llenar la cuota de revisiones: el futuro apply deberá rechazar si no puede reservar capacidad de recuperación. No hay retención/export/borrado/backups/paquete operativo o reconciliación del sitio real. No certifica seguridad de todo el CMS ni resuelve los casos históricos ACF protegidos.

Editorial Read0.1.0 fuente/ZIP coinciden con sus hashes aprobados. CMS real, sus permisos/credenciales/contenido, staging y producción intactos; contacto/envíos/challenges desactivados. No frontend/build/smoke/deploy repetidos. No commit/reset/limpieza del árbol; se conserva dev local. Cierre del laboratorio registrado en handoff tras la suite.
