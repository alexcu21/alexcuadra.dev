# Propuestas privadas: integración WordPress — 26 octubre 2026

**72 comprobaciones nativas correctas, cero fallos**, más82regresiones del planificador y93del repositorio SQLite,247en total. [Recibo y hashes](editorial-blog-proposal-wordpress-2026-10-26.json), [runner](../../tests/integration/wordpress-blog-proposals/README.md).

## Perfil y aislamiento

WordPress7.1.3/PHP8.5.11/MariaDB11.8.9/Polylang3.9.1; Adapter0.7.1 instalado como dependencia del perfil, sin nuevas abilities registradas. Imágenes oficiales fijadas por digest y ZIP públicos con checksum reutilizados del laboratorio del lector. Sin SQL, configuración, credenciales o contenido del hosting importados.

Proyecto final alexcuadra-blog-proposals-lab-20261026-r6, red internal=true, sin puertos publicados; WordPress y BD confirmados detenidos. También se confirmaron detenidos los cinco proyectos previos de esta depuración. Sin prune ni borrado de imágenes/volúmenes. Las bases y WordPress usan tmpfs256MiB cada uno; mounts de código readonly. El fixture bloquea correo y se impide HTTP externo/cron.

## Implementación probada

- Proyección interna bajo scope de preparación nativo y fuente publicada/sin contraseña/tipo post. Cuerpo/título/extracto desde columnas actuales de la BD, sin depender del objeto WP_Post cacheado. Rechaza fuente privada/borrador/futura/papelera/contraseña/page/project/ausente. No entrega ACF o postmeta.
- Grupo real Polylang con reciprocidad, identidad/idioma de sus miembros y detección de destinos enlazados aunque sean draft/private. Para esos destinos solo se consultan identidad/tipo/relación, no cuerpos. Categorías y etiquetas nativas por idioma, catálogo acotado; no copia IDs de origen al traducir ni confunde tags con categorías.
- Repositorio usa exclusivamente el wpdb global, tablas propias ac_blog_lab_* y cuatro tablas InnoDB con claves foráneas. Lock de una fila por propietario antes de replay/cuotas/versión. Instalación y alta de fila del propietario explícitas desde CLI/admin del laboratorio; ninguna concede permisos ni sustituye tablas existentes.
- Creación privada, before/after exactos, versión1 y actualizaciones por versión esperada. Historia conservada y patch reemplazado explícitamente. Un rebase con base nueva produce otra revisión, sin modificar fuente.
- Identidad nativa actual, sin aceptar owner/actor/snapshot desde request. Anónimo/admin sin scope/subscriber sin scope/lector público/scope de proyectos rechazados. Otro usuario con scope propio no puede consultar/actualizar la propuesta ajena ni ver su inventario.
- Scope revocado bloquea lectura y replay. Recibos independientes por propietario, clave conflictiva rechazada y replay histórico conservado tras cambiar fuente/cabecera/vínculo de traducción.
- Dos procesos WordPress distintos con idéntica petición obtienen exactamente una revisión; dos escritores desde la misma versión producen un ganador y un conflicto. Usa conexiones reales separadas a MariaDB y no simula concurrencia con un array.
- Trigger sintético que falla al insertar recibo demuestra rollback de cabecera/revisión/petición tanto al crear como al actualizar. Transacción ajena rechazada sin commit implícito por esos métodos. DDL de instalación va aparte: MariaDB no lo hace transaccional.
- Cuotas nativas de16propuestas por propietario y16versiones por propuesta, replay estando lleno y cuota independiente del otro propietario. El resto de cuotas tiene cobertura SQLite previa; no se afirma que se agotaran todas en MariaDB.
- Huellas de posts/postmeta/terms/term_taxonomy/term_relationships/termmeta iguales durante fases de operaciones privadas. El fixture realizó expresamente una edición de título y un nuevo vínculo para probar base/vínculos cambiantes; esas mutaciones sintéticas se excluyen de la afirmación de conservación. No hay conservación global de toda la BD: usuarios, permisos y tablas privadas sí se preparan/modifican.
- Scopes sintéticos de preparación retirados de ambos usuarios al terminar. HTML, SQL literal, comillas y emoji se guardan como texto privado, sin renderizado, ejecución SQL o publicación. No equivale a saneamiento del futuro escritor público.

## Errores encontrados y corregidos

La primera ejecución montó el directorio del laboratorio anterior; se corrigió la ruta relativa de Compose. La segunda detuvo el fixture por no declarar wpdb global dentro de eval-file. Las siguientes detectaron un rechazo real de wpdb al insertar una traducción: las columnas ASCII de hash hacían que la detección de charset de la tabla rechazara texto Unicode del documento. Todas las columnas textuales ahora heredan utf8mb4_bin; la pasada final comprueba get_table_charset= utf8mb4 y round-trip de español/emoji. El diagnóstico SQL temporal se retiró antes de la pasada final.

Una ejecución completó operaciones pero produjo un recibo vacío porque el contador tampoco estaba declarado global en el ámbito eval-file. El verificador lo rechazó correctamente y esa salida no se contó como validación. Se corrigió el contador, se añadió impresión de recibo al fallar y la pasada final conserva72casos completos. Los proyectos fallidos se detuvieron sin sobrescribir sus bases; cada corrección se ensayó en uno nuevo.

Permanecen avisos de WordPress.org bloqueado por aislamiento, HTTP_HOST durante instalación y deprecaciones de Colors.php de WP-CLI/PHP8.5. No se ocultaron para considerar la pasada correcta ni se presentan como fallos del hosting real.

## Límites y siguiente incremento

Es código experimental con guard que exige entorno local, home/site http://wordpress, DB_HOST=db y opt-in explícito. No hay plugin, rutas, herramientas MCP, paquetes instalables ni scopes reales nuevos. La autenticación HTTP, sesiones y privacidad por transporte no están probadas aquí; los actores se seleccionan en fixture con wp_set_current_user.

Faltan pérdida de conexión/reconexión automática wpdb, SIGKILL/commit ambiguo en MariaDB y barrera de identidad de conexión antes de considerar robustez operativa. Los SIGKILL del incremento anterior pertenecen únicamente a SQLite. El esquema se crea solo en laboratorio vacío y se comprueba motor, no hay una migración/aceptación operacional completa de forma/collation/índices/cuotas/archivo/backups para hosting.

La proyección combina SQL actual de campos con APIs Polylang/términos y sus cachés. Se detecta deriva de fuente entre peticiones y se prueba el grupo completo nativo; no se certifica un snapshot atómico frente a cualquier escritor externo de Polylang o caché persistente. El lock por propietario coordina propuestas de ese propietario, no todos los escritores del CMS. Revalidación y coordinación de fuente/grupo serán obligatorias para aprobación/aplicación futuras; esta capa solo guarda intenciones históricas privadas.

No se crea un post/borrador/traducción, publica, aprueba, aplica o recupera contenido. No se cambia lector real, ZIP sellado, propuesta227/revisión1, CMS, frontend/contacto/producción/staging. Sin SSH ni correos. Siguiente: transporte MCP/HTTP y fallos de conexión/proceso del repositorio experimental, antes de revisar empaquetado o permisos reales.
