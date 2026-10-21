# Contrato local: propuestas de cambios de proyectos

Estado20 octubre2026: prototipo `Editorial Changes0.5.0-lab`, **solo Docker**, sin instalar ni conceder permisos en el CMS compartido. Las seis operaciones de propuestas descritas aquí conservan su contrato0.3;0.5 añade revisión/aprobación y aplicación/recuperación de fuentes sintéticas en el [contrato separado](WORDPRESS_MCP_SOURCE_APPLICATION.md). Editorial Read0.1.0 sigue siendo la única extensión propia instalada allí. [Código](../wordpress/plugins/alexcuadra-editorial-changes/alexcuadra-editorial-changes.php), [QA vigente531 correctas](qa/MCP_EDITORIAL_APPLICATIONS_2026-10-20.md).

## Alcance y cuenta

Este incremento prepara cambios en un registro privado separado del proyecto. Se puede proponer cuerpo/subtítulo de un origen público, revisar y actualizar esa propuesta propia, sin modificar publicaciones existentes. No crea ni publica Projects traducidos ni cambia relaciones Polylang. Aplicar, publicar y retirar contenido serán operaciones futuras distintas.

**No hace falta crear un usuario Editor para este alcance.** El prototipo exige `read` + `alexcuadra_read_public_projects` + `alexcuadra_prepare_project_changes`, concedidos solo a cuentas sintéticas en pruebas. No añade capacidades automáticamente ni altera roles. La cuenta real2 conserva únicamente su lectura ya autorizada. Una futura habilitación de propuestas reales requerirá presentar un paquete y una asignación concreta; no convertir automáticamente la cuenta en Editor.

La implementación autoriza de forma explícita únicamente estas operaciones sobre propuestas propias. No concede las capacidades primitivas globales de edición de páginas/proyectos. Las funciones internas WordPress de almacenamiento no constituyen autorización por sí mismas: el callback verifica permisos, campos, dueño, tipo, estado, idioma y fuente antes de usarlas.

## Operaciones

| Ability | Entrada cerrada | Resultado |
| --- | --- | --- |
| `create-project-change` | `project_id`, `locale=en|es`, `patch`, `request_id` | Propuesta propia nueva, versión y huella de origen |
| `get-project-change` | `proposal_id`, `locale` | Solo la propuesta propia, con patch normalizado |
| `update-project-change` | `proposal_id`, `locale`, `expected_version`, `patch`, `request_id` | Nueva versión;409 si cambió la versión o fuente |
| `list-project-change-revisions` | `proposal_id`, `locale`, `expected_version`, `page`, `per_page` | Metadatos paginados de revisiones propias; sin cuerpos |
| `get-project-change-revision` | `proposal_id`, `locale`, `revision_version` | Snapshot de una revisión de esa propuesta propia |
| `restore-project-change-revision` | `proposal_id`, `locale`, `expected_version`, `revision_version`, `request_id` | Recupera el snapshot como una versión nueva; nunca modifica la fuente |

Nombres completos prefijados por `alexcuadra/`. Schemas con propiedades adicionales prohibidas; callbacks repiten validación. DTO de create/get/update/restore: `proposal_id, project_id, locale, version, base_version, patch, state=draft, applied=false`. No devuelve cuerpo/subtítulo original, meta privado, opciones, usuarios ni credenciales. El patch es contenido no confiable que requiere revisión.

`patch` no vacío admite solamente:

- `body_html`: hasta40000 caracteres Unicode/160000 bytes. Normalizado por `wp_kses` a texto, párrafos, H2–H4, listas, cita, código, salto y enlaces HTTP(S). No imagen/iframe/formulario/scripts ni atributos de eventos. No soporte completo de bloques Gutenberg complejos.
- `tagline`: hasta500 caracteres Unicode/2000 bytes, texto plano normalizado por WordPress. Su eventual destino ACF es `project_tagline` / `field_676dcb2aec8d4`, obtenido del export local del grupo Projects `group_676d40f7bb646` del18 de octubre. **No se escribe ese campo en este prototipo**; clave/tipo/reglas del sitio deberán reconciliarse antes de un futuro apply.

Permite cadenas vacías para proponer limpiar un campo, sin aplicarlo. Rechaza `post_status`, autor, título/slug, ID/idioma de destino, URLs, campos/meta arbitrarios y cambios Polylang. JSON/UTF-8, IDs enteros positivos, límites y bytes de control validados.

## Privacidad y concurrencia

CPT interno `ac_project_change`: no público, sin UI/rutas REST/registro GraphQL, autor fijo usuario actual y estado draft. Los handlers no aceptan autor/estado/tipo del cliente. Guardado del payload en una clave interna fija; lectura/actualización exige dueño/tipo/estado correcto. Otros suscriptores o Editores con capacidad de preparación siguen sin acceso a esa propuesta. Ausencia/tipo/autor/idioma incorrecto comparten404.

El origen exige CPT project publicado, sin contraseña, visibilidad pública, `read_post` e idioma exacto. La huella contempla identidad/título/slug/cuerpo, estado/contraseña/idioma/traducciones y valor/referencia del subtítulo. No se expone su contenido. Retirada del origen →404; cambios relevantes →409. No rebase automático ni garantía de snapshot transaccional entre fuente y propuesta; como nunca se aplica la propuesta, un cambio externo no se sobrescribe.

Versión propia aleatoria de64 hex. Actualizar o recuperar requiere el token actual, compara el payload anterior y ejecuta **un solo UPDATE acotado** a meta_id/post_id/clave/dueño/tipo/estado con igualdad binaria del valor anterior. SQL interno fijo y preparado, sin herramienta SQL/PHP genérica expuesta. Un escritor que pierde la carrera recibe409 y no deja evento de historial. Se invalidan las cachés del registro interno; no se invocan actualizaciones del proyecto fuente.

## Historial y recuperación0.2

El payload incorpora snapshots normalizados de cada versión. Cada entrada guarda `version`, `parent_version`, `operation=create|baseline|update|restore`, `restored_from`, `recorded_at` UTC y `patch` completo. Cabeza e historial se guardan juntos en la misma fila mediante CAS: un fallo de escritura no puede guardar solo uno de ellos. Las abilities no editan ni borran entradas anteriores. No es un registro externo a prueba de alteraciones administrativas, ni un backup de WordPress.

La lista devuelve `proposal_id,current_version,revisions,total,next_page`, orden de creación, páginas desde1 y `per_page`1–10, sin `patch` en sus metadatos. Exige `expected_version` para detectar cambios entre páginas; una página posterior al final devuelve lista vacía. La consulta individual devuelve `proposal_id,project_id,locale,current_version,revision,state=draft,applied=false`; solo la revisión elegida contiene su patch.

Recuperar copia el snapshot completo de una revisión de esa misma propuesta y genera un token nuevo, con padre actual y `restored_from` histórico. Campos ausentes en la revisión recuperada dejan de estar propuestos; no se mezclan con campos añadidos después. Incluso recuperar la versión actual crea otra revisión. La fuente debe seguir pública y conservar su huella; cambio409/retirada404, sin rebase. Historial/recuperación usan los mismos scopes, dueño e idioma, sin permisos nuevos.

Tope técnico del journal:32 snapshots y2MiB de JSON codificado por propuesta, contando cabeza e historial. Al alcanzar cualquiera se rechaza la escritura con409 `history_full`; nunca se podan revisiones silenciosamente. Se mantienen además las cuotas por propietario de0.3 descritas abajo; no hay retención/borrado automático. El historial se valida al leer: cadena, tokens únicos, snapshots normalizados y coincidencia con la cabeza; incoherencia503.

Compatibilidad con0.1: su única versión superviviente se expone como `baseline` con fecha desconocida/null, sin inventar versiones perdidas. Leer no migra la DB; la primera escritura guarda baseline y nueva revisión juntos.0.1 no entiende payloads0.2: no se promete downgrade del formato. El laboratorio usa volúmenes tmpfs sintéticos; persistencia entre peticiones/reactivación está verificada, no recuperación tras perder el volumen ni restauración de backups.

## Idempotencia y cuotas0.3

Las tres escrituras exigen `request_id`:32 caracteres hexadecimales minúsculos, generado por el cliente para una operación lógica y conservado al reintentar con los mismos parámetros, incluido `expected_version`. El identificador se comparte entre todas las operaciones de ese propietario. Se guarda un hash SHA256 de operación/entrada original con claves ordenadas: cambiar orden de propiedades no importa; cambiar valores/operación reutilizando la clave produce409 `request_reused`, aunque ambos textos se normalicen igual. No se genera una clave nueva para un reintento.

Una solicitud ya confirmada devuelve su DTO original, sin otra propuesta/revisión/recibo ni consumo de cuota. Ese DTO puede ser una versión anterior si hubo cambios posteriores: consultar get para la cabeza actual. La repetición no revierte la cabeza. Se comprueban de nuevo permisos, dueño, idioma, fuente y correspondencia del snapshot/recibo; una retirada o cambio de fuente puede bloquearla. Los errores no se guardan como éxitos y se puede reintentar la misma clave tras un fallo de almacenamiento.

Dos tablas internas locales: `ac_change_owners` contiene solo la fila de bloqueo por usuario y `ac_change_requests` guarda clave/hash/ID de propuesta/DTO confirmado, con PK(owner_id,request_id). Sin endpoints públicos ni herramienta SQL genérica. No se exportan claves, hashes o recibos por estas abilities. No hay expiración ni liberación de claves en este prototipo.

Admisión serializada por propietario con `SELECT ... FOR UPDATE`, transacción InnoDB y verificación de los cuatro motores (posts/postmeta y las dos tablas internas). La misma transacción engloba cuota, cambio de propuesta, historial y recibo. Fallo/rechazo revierte todo; la caché del registro se invalida también al revertir. Una fila de bloqueo vacía puede quedar creada, sin contenido ni contador. Transacciones ya abiertas o estado desconocido se rechazan; no se anidan ni se confirma una transacción ajena. El payload se lee antes del bloqueo; el CAS sigue rechazando una cabeza cambiada durante la espera.

Límites de laboratorio por propietario:

- 8 propuestas, contando todos los estados de su CPT interno, incluidas antiguas.
- 128 solicitudes de escritura únicas confirmadas; reintentos gratuitos antes de esa comprobación.
- 8MiB lógicos sumando JSON de todos sus payloads y DTOs guardados como recibos; no mide índices/overhead físico de MySQL.

Rechazo429 `owner_quota`, sin reservar otro recibo ni dejar cambios parciales; puede seguir consultando y repetir operaciones confirmadas. El tope de propuestas no impide actualizar las existentes si quedan solicitudes/bytes. No es rate limiting temporal ni una cuota global del sitio; no se garantiza frente a escritores externos que ignoren estas tablas/bloqueos. No hay UI, reset, borrado, TTL, retención o mantenimiento aprobados para producción. Desactivar la admisión0.3 mientras se permiten escrituras0.2 eludiría estas garantías: no se promete un downgrade operativo seguro.

## Validación y continuidad

67 comprobaciones WordPress/framework/meta-tools oficiales y dos escenarios del guard de entorno correctos. Incluyen suscriptor sin permisos globales de edición, aislamiento entre usuarios/roles, entradas prohibidas, normalización, revocación, source drift y rechazo de escritura anidada sin confirmar la transacción exterior. La intercalación PHP histórica se sustituyó al introducir transacciones0.3; las carreras entre procesos se prueban por HTTP.

Aceptación base0.3: **236 correctas** (67 framework+44 historial+37 idempotencia/cuotas+86 HTTP+2 guards) en `npm run test:mcp-changes`. Seis carreras HTTP entre procesos: update/update, update/restore, create duplicado, update duplicado, clave reutilizada con distinto contenido y último cupo de propuestas. Barrera antes del bloqueo de admisión, visible por conexión autocommit separada; versiones cargadas antes, transacciones serializadas. Duplicados devuelven dos DTO iguales con un solo cambio/recibo; último cupo admite uno/rechaza otro. Cuotas de solicitudes/bytes verificadas con ocupación sintética y reversión de fila/historial/recibo. Fuentes intactas. [QA base](qa/MCP_EDITORIAL_IDEMPOTENCY_2026-10-20.md). `npm run test:mcp-changes-http` ejecuta solo HTTP.

Perfil adicional `npm run test:mcp-changes-stack`: **290 correctas**, las236 anteriores y54 de integración con ACF6.8.10/WPGraphQL2.24.2/WPGraphQL for ACF3.0.0/Polylang3.9.0/conector0.7.1. Grupo Projects original importado desde export local, valores sintéticos; claves/textarea/GraphQL/idiomas/relaciones recíprocas verificados. El CPT privado no aparece en schema ni accesos genéricos por ID/lista de propietario/otro usuario/administrador sintéticos. Prepare no concede mutaciones GraphQL de Projects. Update/restore de propuestas conserva fuentes y cambios externos ACF/referencia/relación invalidan la propuesta. [QA vigente y límites](qa/MCP_EDITORIAL_GRAPHQL_STACK_2026-10-20.md). Prototipo/paquete de lectura intactos. No certifica todo el sitio ni resuelve los casos históricos de ACF en fuentes con contraseña; hay que reconciliar el esquema real antes de aplicar.

Actualización0.5: [aplicación y recuperación implementadas solo en laboratorio](qa/MCP_EDITORIAL_APPLICATIONS_2026-10-20.md), **531 correctas** en test:mcp-applications. Once abilities con scopes apply/recover y aprobador CLI distinto, fuera de MCP. Eventos/recibos, estado de propuesta aplicado/recuperado y congelado, consumo único, reserva y recuperación condicional comprobados; recibos0.3 intactos como resultados históricos. Cuatro carreras HTTP y fallos/confirmación perdida correctos. [Contrato vigente](WORDPRESS_MCP_SOURCE_APPLICATION.md). Siguiente: revisión operativa y candidato/rollback, no instalar ni ampliar permisos reales por un continúa. Staging TOC ya actualizado; visual/429 pendientes. Mejora Astro–WordPress aplazada a la última etapa del plan.

Fuentes: [WordPress wp_insert_post](https://developer.wordpress.org/reference/functions/wp_insert_post/), [wp_update_post](https://developer.wordpress.org/reference/functions/wp_update_post/) y [ACF update_field por field key](https://www.advancedcustomfields.com/resources/update_field/). El uso futuro de esas APIs no reemplaza comprobaciones de permisos ni control de concurrencia.
