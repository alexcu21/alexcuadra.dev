# Propuestas privadas: transporte y fallos — 26 octubre 2026

**324 comprobaciones funcionales correctas:**82planificador,93SQLite,72WordPress/MariaDB/Polylang,34fallos de conexión/proceso y43HTTP/MCP. Además,18escenarios del verificador de recibos correctos. [Resultados y hashes](editorial-blog-proposal-transport-2026-10-26.json).

## Aislamiento y perfil

WP7.1.3/PHP8.5.11/MariaDB11.8.9/Polylang3.9.1/MCP Adapter0.7.1, mismas imágenes oficiales por digest y plugins públicos verificados del laboratorio anterior. Proyecto final alexcuadra-blog-proposals-lab-transport-20261026-r2; red internal=true, PortBindings={} y ambos contenedores detenidos. La primera pasada de transporte también quedó detenida, sin puertos. No se importaron SQL, configuración, credenciales o datos del hosting; no se podaron recursos.

Los registros son sintéticos. El fixture bloquea correo, cron y HTTP externo. Los intercambios Apache y las credenciales Basic/Application Passwords reales de prueba solo funcionan en la red interna del laboratorio. No hay sesión en Hostinger/Cloudflare, SSH, despliegue frontend o permisos reales nuevos.

## Protección de conexión

Nuevo connection-fence.php, independiente del protocolo de efectos de proyectos. Exige el wpdb global core de WP7.1.3 y mysqli; fija objeto de conexión, CONNECTION_ID y estado transaccional. Conserva/restaura el modo de mysqli y fuerza excepciones antes de cada consulta, para impedir el replay automático de wpdb sobre otra conexión. El guard de query debe conservar la última posición de PHP_INT_MAX. Rechaza control transaccional/DDL/SET desde callbacks dentro de su transacción. Commit/rollback usan únicamente el handle original; nunca intentan recuperar una transacción perdida en una conexión nueva.

Fallos reales ensayados antes del recibo: KILL CONNECTION desde otra sesión, modo de errores restablecido a OFF seguido de KILL, cierre explícito del objeto mysqli, sustitución del handle, intento de COMMIT mediante filtro de wpdb y modificación del orden del guard. Todos fallan sin registros privados parciales; un intento nuevo puede crear una revisión y su repetición devuelve esa misma revisión.

Dos SIGKILL de procesos WordPress prueban los extremos: antes de completar la transacción revierte cabecera/revisión/recibo; después de commit y antes de devolver la respuesta conserva la revisión y el reintento la recupera. Las tablas editoriales permanecen iguales durante estas operaciones. No se inyectó pérdida del acuse TCP del propio COMMIT: el código distingue commit_uncertain al recibir una excepción de conexión durante commit, pero esa rama no queda certificada como un fallo de red reproducido.

La primera pasada cerró72+29+43casos. Una revisión posterior identificó que cerrar explícitamente mysqli lanza Error, distinto de mysqli_sql_exception; se añadió su rechazo saneado y cinco comprobaciones. La pasada final repitió72+34+43casos, sin reducir el ensayo para ocultar el caso nuevo. Los82+93regresiones previas también pasaron con el planificador/SQLite actuales.

## Transporte HTTP/MCP

Fixture de MU plugin exclusivamente bajo tests, no plugin distribuible. Cinco abilities alexcuadra-lab/*, registradas solo en el laboratorio:

- inspect-post-context: cuerpo/campos de una fuente publicada, huella de base, catálogo público de términos e idiomas ya enlazados. Usa el scope privado de preparación, sin dar cuerpos al lector público. No devuelve IDs ni cuerpos de destinos ocultos o postmeta. Una fuente privada se rechaza.
- create-post-proposal y update-post-proposal: guardan únicamente planes privados; request/propietario/versión validados. No crean posts/borradores, enlaces de traducción o publicaciones.
- get-post-proposal y list-post-proposals: lectura privada por propietario e historial, con inventario acotado.

El contexto hace verificable el recorrido contexto→base→propuesta→lectura→actualización→historia. Este cambio de alcance está solo modelado en el fixture: el conector real sigue con las13abilities verificadas anteriormente y su scope público de metadatos; no se ha concedido acceso a cuerpos mediante MCP real.

43comprobaciones cubren autenticación válida/inválida/anónima, inicialización y sesiones, cabecera de protocolo, sesión ajena/cerrada, schema de entrada cerrado, intento de suplantar propietario, permisos insuficientes, otros propietarios/historia/inventario, replay y claves conflictivas, versión desactualizada, fuentes privadas, traducción prevista y destino draft ya vinculado. Revocar scope bloquea lectura/replay en sesión abierta; revocar Application Password bloquea la sesión. No hay approve/apply/recover. HTML/emoji son texto de propuesta, no renderizado ni saneamiento para publicación.

Credenciales sintéticas, scopes y sesiones se limpian al terminar; el recibo verifica eliminación de credenciales/scopes. Las sesiones se cierran por DELETE con una credencial temporal del mismo propietario y se registra como fallo de limpieza cualquier excepción. No se afirma una auditoría global de todo el almacenamiento de sesiones.

## Recibos y conservación

El runner exige marcador único, objeto JSON, conteos enteros positivos/coherentes, identificadores de caso únicos y flags explícitos de cada fase. El verificador se probó con18entradas válidas/adversas, incluidos bool en lugar de contador, duplicados, caso inseguro y limpieza incompleta. Se revalidaron los tres recibos reales con ese verificador final; no se repitió WordPress solo por cambiar el verificador.

Huellas conservadas de posts/postmeta/términos/taxonomías/relaciones/termmeta durante las fases de propuestas. Fixtures crean expresamente usuarios, fuentes y vínculos, y en la fase72 realizan ediciones sintéticas para probar deriva; esos cambios no se presentan como lecturas inmutables. No hay certificación global de toda la base ni modificación de la incidencia histórica de postmeta/propuesta227/revisión1.

Quedan avisos conocidos de WordPress.org bloqueado por aislamiento, HTTP_HOST durante instalación y deprecación Colors.php de WP-CLI en PHP8.5. No se presentan los logs como libres de avisos. Los recibos exportados contienen casos/metadatos/hashes, sin contraseñas, tokens, sesiones ni cuerpos de propuestas.

## Límites y siguiente incremento

Sigue siendo experimental. El esquema de salida del fixture es genérico; el candidato distribuible necesita DTOs cerrados, admisión del esquema/perfil, revisión de query filters/caché/plugins y política operativa de archivo/retención/backups. El guard actual solo admite http://wordpress/local/db: no subir estos módulos al CMS.

La barrera impide control de transacción mediante wpdb, no código arbitrario que use mysqli directamente fuera del protocolo. El lock por propietario no coordina todos los escritores externos del CMS; proyecciones Polylang/términos con caché no equivalen a snapshot atómico de fuente/grupo. No hay aprobación/aplicación/recuperación o publicación ensayadas. Lo verificado no autoriza editar contenido público ni reemplazar los plugins instalados de proyectos.

Siguiente: preparar candidato revisable de propuestas privadas con preflight de esquema/perfil y plan de conservación, manteniendo scope/instalación reales separados. No repetir instalación/grants del lector de metadatos ni el piloto de contacto.
