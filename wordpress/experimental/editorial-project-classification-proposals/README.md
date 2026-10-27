# Propuestas de clasificación de proyectos: laboratorio local

`planner.php` es PHP puro separado de los paquetes sellados. No registra hooks, herramientas, rutas, tablas ni permisos. Produce un documento inicial revisable; `repository.php` añade persistencia privada únicamente en SQLite de laboratorio. No instalar ninguno en WordPress. No aplica asignaciones o crea términos.

Entrada de cliente cerrada: `request_id` (32 hex), `project_id` entero positivo, `locale` en/es, `base_hash` (64 hex) y `fields`. Solo admite `categories` y `technologies`, listas de hasta100 IDs únicos. Cada campo presente reemplaza el conjunto completo; omitido conserva asignaciones y `[]` propone retirarlas todas. Se ordenan los IDs; no se adivinan términos por nombre, slug o traducción. Rechaza propuestas sin cambios.

El actor procede del futuro adaptador autenticado, nunca del cliente. Exige `read`, `alexcuadra_read_public_projects` y `alexcuadra_prepare_project_changes`, sin grants o capacidades de blog. La función pura comprueba la proyección del actor; no demuestra una sesión WordPress ni implementa autorización de lectura entre propietarios.

El adaptador deberá obtener una clasificación con el DTO cerrado de `get-public-project-classification` y reunir **todas las páginas** de ambos catálogos en el idioma del proyecto. `context()` acepta `{categories:[term...],technologies:[term...]}`, hasta200 términos por taxonomía, y compara los metadatos asignados con ese catálogo. Solo `project_category`/`project_technology`; no categorías/etiquetas del blog ni campos de fuente/cuerpo/meta. Las etiquetas recibidas se conservan como texto para revisión: cualquier vista deberá escaparlas, nunca renderizarlas como HTML.

El lector confiable debe verificar visibilidad pública actual, proyecto sin contraseña, taxonomías exclusivas de proyectos y reciprocidad/visibilidad de traducciones. El planificador comprueba estructura e idioma, pero **no consulta WordPress/Polylang ni demuestra esos hechos**. Un cliente no puede proporcionar el catálogo o la clasificación a una futura herramienta MCP. No se acepta una página aislada como catálogo completo. El adaptador debe fallar ante lecturas incompletas/cambios detectados, no inventar una instantánea atómica a partir de llamadas paginadas.

`base_hash()` liga clasificación y catálogo completos, incluidos nombres, slugs y traducciones. Su cambio obliga a preparar/revisar otra base; no liga cuerpo, título ni toda la fuente. Cada cambio devuelve taxonomía/idioma y listas before/after con IDs y etiquetas. Documento inicial owner/version1/approval=not-requested/source_write_performed=false. `request_hash` liga propietario y solicitud normalizada; `document_hash` liga el documento. Ninguna huella constituye aprobación, cifrado o firma de autenticidad.

Prueba del planificador: `/opt/homebrew/bin/php tests/editorial-project-classification-proposals/check.php`. Proyecciones sintéticas, sin CMS o transporte; las pruebas de almacenamiento se describen a continuación.

## Repositorio SQLite separado

Derivado explícitamente del modelo transaccional del laboratorio de blog, con namespace, guard y tres tablas propios `classification_proposals`, `classification_revisions`, `classification_requests`. Solo CLI, sin ABSPATH, PDO SQLite y opt-in `AC_PROJECT_CLASSIFICATION_PROPOSALS_STORAGE_LAB=true`. Instalación explícita en una base dedicada vacía; no migraciones, tablas del CMS o archivos instalables. El llamador del laboratorio es responsable de directorio0700/archivo0600. Las pruebas lo verifican; el repositorio no convierte un archivo existente en privado ni certifica el esquema de una base arbitraria.

Operaciones `create`, `update`, `get` (actual o revisión exacta) y `listing` (metadatos propios, cursor por ID, hasta20). Lecturas y escrituras exigen los tres scopes proyectados; el owner proviene del actor. IDs ajenos y ausentes comparten not_found. No búsqueda de otros propietarios o API de borrado. Conserva revisiones y recibos, sin TTL/poda automática. Privado por autorización de aplicación, no cifrado frente a administradores o acceso al archivo.

`update` exige versión esperada, mantiene proyecto/idioma y reemplaza **todo el patch propuesto** contra la base indicada: no hereda campos de revisiones anteriores. Un cambio de base explícito produce una revisión nueva; las antiguas permanecen intactas. No es un patch sobre el estado final de la propuesta previa.

El recibo liga owner/request_id a operación, ID de propuesta, versión esperada y solicitud normalizada. Un reintento exacto devuelve la revisión original aunque haya cambiado la fuente, el catálogo o la versión actual; no certifica vigencia de la fuente. Un payload u operación diferente bajo la misma clave se rechaza. Revalida actor/scopes antes incluso del replay. El hash del recibo añade operación/ID/versión al hash de solicitud del documento; no son intercambiables.

`BEGIN IMMEDIATE`, claves únicas y foreign keys serializan admisión/versión/recibo; head, revisión y recibo se confirman juntos. Espera de lock hasta2000ms y error storage_busy reintentable. No trasplantar este locking a MariaDB. No se prueban pérdida de conexión o acuse TCP del commit; un error incierto requiere el mismo ID, nunca inventar otro.

Cuotas por propietario:16 propuestas,64 revisiones,128 solicitudes,8MiB lógicos incluyendo estimación de metadatos; hasta16 versiones/propuesta y786432 bytes/documento. No son límites de disco físico. Al llenarse rechaza nuevas escrituras sin podar historia; los reintentos existentes siguen disponibles. Solo se permiten límites inferiores para ensayos.

Prueba: `/opt/homebrew/bin/php tests/editorial-project-classification-proposals/storage-check.php`.99 casos SQLite real: persistencia/reapertura, propietarios, permisos revocados, conflictos/replay/canonicalización, EN/ES, cuotas, rollback por fallo SQL, lock ocupado, dos procesos concurrentes y SIGKILL antes/después del commit. Copia sintética mediante VACUUM INTO y reapertura con historia/replay/aislamiento verificados; no operador de backups del hosting. Bases temporales sin red y eliminadas al terminar. [Evidencia y límites](../../../docs/qa/MCP_PROJECT_CLASSIFICATION_PROPOSAL_STORAGE_2026-10-27.md).

## Adaptador WordPress y persistencia InnoDB de laboratorio

`wordpress-source.php`, `wordpress-repository.php` y `connection-fence.php` son una capa adicional separada del repositorio SQLite. Requieren flag `AC_PROJECT_CLASSIFICATION_PROPOSALS_WORDPRESS_LAB=true`, entorno local, DB_HOST=db, home/site=http://wordpress y single site; el lector experimental debe estar cargado con su propio guard local. No hay plugin instalable, registro nuevo de abilities o grant implícito. No subir al hosting.

El actor se obtiene de la sesión/permisos nativos. La proyección reutiliza inventario/acceso público y reciprocidad del lector, obtiene ambos catálogos completos del idioma y valida estado/contraseña/tipo desde DB. Dos recogidas deben coincidir; no certifica atomicidad global o coherencia de todas las cachés frente a escritores externos. No devuelve cuerpos o metadata privada. La base vincula clasificación/catálogo, no toda la fuente.

Cuatro tablas prefijadas `ac_classification_lab_`, owners/proposals/revisions/requests, InnoDB/utf8mb4_bin. Instalación/enrollment explícitos por operador CLI; no edición de roles. Lock de fila por propietario, versión esperada, revisiones y recibos en una transacción, mismas cuotas que SQLite. Barrera derivada del laboratorio nativo de blog para fijar wpdb/mysqli/transacción. No admite transacción externa. La carga no crea tablas y un esquema existente no se repara automáticamente. La comprobación actual de readiness solo exige motores; no equivale a admisión de esquema/perfil para producción.

70 casos nativos correctos con Polylang real: proyección/EN/ES, permisos, propietario, replay/versiones, carreras entre dos procesos, fallo SQL y rollback, status cacheado y taxonomía compartida. Huellas editoriales preservadas durante operaciones privadas. [Ejecutor](../../../tests/integration/wordpress-project-classification-proposals/README.md), [QA y límites](../../../docs/qa/MCP_PROJECT_CLASSIFICATION_PROPOSAL_NATIVE_2026-10-27.md). Se retiraron scopes sintéticos y se detuvieron solo los laboratorios propios.

Siguiente: fallos de conexión/proceso, cuotas completas y recuperación nativa, transporte HTTP/MCP/privacidad. Se requiere admisión de esquema/perfil y plan de archivo/respaldo propios. No ampliar silenciosamente el esquema antiguo de propuestas de proyectos ni las herramientas públicas. La integración real requerirá un paquete y revisión del perfil de coexistencia: agregar un plugin fuera del perfil admitido cierra el módulo privado de blog rc.3.

La propuesta real de blog1/v1, propuesta histórica227/revisión1, paquetes sellados, capacidades y asignaciones actuales quedan fuera de este experimento.
