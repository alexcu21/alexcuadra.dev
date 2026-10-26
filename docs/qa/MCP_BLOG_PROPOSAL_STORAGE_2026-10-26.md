# Repositorio privado de propuestas — 26 octubre 2026

**Resultado: 93 comprobaciones SQLite correctas y 82 regresiones del planificador correctas, cero fallos.** [Recibo con casos y hashes](editorial-blog-proposal-storage-2026-10-26.json). PHP8.5.11/SQLite3.53.4, base de datos real en archivo temporal privado, procesos PHP independientes para concurrencia e interrupciones. Sin CMS, red, Docker o SSH.

## Implementación

[repository.php](../../wordpress/experimental/editorial-blog-proposals/repository.php) solo admite CLI, opt-in AC_BLOG_PROPOSALS_STORAGE_LAB=true, PDO SQLite y ausencia de ABSPATH. Tres tablas propias en una base dedicada inicialmente vacía: cabeceras, revisiones y recibos de peticiones. Claves foráneas activas, transacciones BEGIN IMMEDIATE y cuotas comprobadas bajo el mismo bloqueo. No auto-migra ni reemplaza tablas existentes.

create/update/get/listing requieren actor interno autenticado con el scope de preparación propuesto. El cliente futuro no podrá proporcionar actor, propietario o snapshots. La lectura de cuerpos propuestos usa ese permiso privado; read_public_posts y permisos de proyectos no bastan. El listado devuelve solo ID/versión y usa cursor por ID. Las revisiones son inmutables desde la API; cada actualización añade una versión y cambia la cabecera mediante comparación de versión esperada.

El documento liga propietario, ID de propuesta y versión con su hash. El recibo de petición liga owner/request_id, operación create/update, ID, versión esperada y payload normalizado. Un replay comprueba primero el permiso actual y devuelve la revisión original, aunque cambien la cabecera o la fuente. Reutilizar una clave con otra operación, contenido o versión esperada falla. Un replay antiguo es evidencia histórica, no una aprobación vigente ni certificación de la fuente actual.

Actualizar reemplaza el patch propuesto completo contra una fuente explícita; no hereda campos omitidos de la versión anterior. Un cambio de base exige aportar su nueva huella y genera otra revisión, sin reescribir la historia. No puede cambiar la identidad de fuente, idioma u operación de una propuesta.

## Pruebas reales en SQLite

- Creación atómica de cabecera/revisión/recibo, lectura privada de cabecera/historia y replay desde otra conexión.
- Otro propietario no puede leer ni actualizar; ausente y ajeno devuelven el mismo error. Inventario aislado por propietario y claves de petición independientes entre propietarios.
- Anónimo, scope revocado, lector público y administrador sin scope no pueden acceder, ni siquiera recuperar un recibo previo.
- Conflictos de versión, identidad y base; historial conservado al actualizar y al cambiar explícitamente de base.
- Fallo inducido al insertar el recibo revierte también cabecera/revisión; reintentar después funciona. Una transacción exterior se rechaza y permanece bajo control del llamador.
- Cuotas de propuestas, revisiones, peticiones, versiones, documento y bytes. Fallos sin filas parciales; replay funciona estando lleno; cuota de un propietario no bloquea la de otro.
- Dos procesos con la misma clave/payload producen exactamente una propuesta/revisión/recibo. Dos escritores desde la misma versión producen un ganador y un conflicto, sin sobrescribir cambios.
- SIGKILL antes de insertar el recibo dentro de la transacción revierte todo; SIGKILL después de commit y antes de devolver respuesta conserva todo. En ambos casos reintento y replay producen una sola revisión.
- Traducción guardada como intención privada, sin tabla de posts ni ID de destino. Un vínculo aparecido después bloquea una nueva revisión; replay conserva la intención histórica sin crear ni enlazar contenido.
- Documento alterado se rechaza por integridad, archivo0600/carpeta0700 e integrity_check=ok. El hash detecta alteración accidental, no sustituye autenticación o protección contra un atacante con control de la BD.

El primer ensayo de interrupción se detuvo por una deprecación de PDO::sqliteCreateFunction en el fixture. Se cambió el worker al controlador Pdo\Sqlite y createFunction. La pasada final de93casos contiene ambas interrupciones y no produjo esos avisos. No se suprimieron advertencias para aceptar el ensayo.

## Cuotas y conservación

Por propietario:16 propuestas,64 revisiones,128 peticiones,8MiB de presupuesto lógico;16 versiones por propuesta y786432bytes por documento. El presupuesto suma documentos más reservas fijas para cabeceras/recibos; no pretende medir páginas físicas, journal, backups o disco total. La admisión de una propuesta nueva reserva espacio para el mayor ID antes de conocerlo, de modo conservador. Los límites del constructor solo pueden reducir esos máximos para pruebas.

No hay borrado automático, caducidad, poda ni restauración destructiva: conserva revisiones/recibos y rechaza nuevas escrituras al agotar cuota. La política operativa de archivo/retención y respaldo aún deberá definirse para el repositorio de WordPress. Las bases de pruebas contienen solo datos sintéticos y se eliminan al terminar; no hay datos reales importados.

## Reproducción y límites

```sh
/opt/homebrew/bin/php tests/editorial-blog-proposals/check.php
/opt/homebrew/bin/php tests/editorial-blog-proposals/storage-check.php
```

No es un plugin instalable, un adaptador de MariaDB ni una ampliación del conector real. Los actores, snapshots y términos siguen siendo sintéticos: no se certifica autenticación WP, concurrencia de escritores CMS/Polylang, HTTP, hooks, publicación, aprobación o recuperación. La serialización SQLite bloquea la base completa; no equivale al futuro esquema/locking de MariaDB. El guard impide cargarlo desde WordPress y no deben subirse estos archivos al hosting.

Los82casos del planificador se repitieron porque se extrajo validación compartida de actor/request, necesaria para replay previo a revalidar una fuente cambiante. El recibo histórico del planificador inicial se conserva; este nuevo recibo fija los hashes actuales. No cambió el lector MCP instalado, ningún ZIP sellado, scopes reales, propuesta227/revisión1, producción/staging/contacto o contenido editorial. No se repitió el piloto de correo.

Siguiente incremento: adaptar el repositorio y las proyecciones internas a WordPress/MariaDB/Polylang en laboratorio y comprobar ownership, versiones, replay y grupo completo antes de exponer nuevas herramientas.
