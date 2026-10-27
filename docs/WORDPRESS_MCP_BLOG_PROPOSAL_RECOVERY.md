# Respaldo y recuperación de propuestas privadas rc.2

**27 octubre — primer piloto privado completado:** propuesta1/v1 del usuario2 sobre post228ES; solo propone «Como»→«Cómo» en el título existente. Creación/get/listado y replay idéntico verificados por MCP, una sola propuesta. Proyección completa/base_hash y metadatos públicos del post coinciden antes/después; ninguna publicación/aplicación. [Diff, evidencia y límites](qa/MCP_BLOG_PROPOSAL_PILOT_2026-10-27.md). No repetir piloto, borrar historia o ejecutar preflights iniciales que exigen tablas vacías. El respaldo inicial owner-only no cubre el plan posterior; futuras exportaciones requieren pausa/retiro solo del scope nuevo. Piloto cerrado; siguiente prioridad MCP: taxonomías propias de proyectos. Escritura pública de posts sigue pendiente.

**27 octubre — propuestas privadas rc.2 operativas por MCP:** Alex aporta after-scope ready=true/15checks; usuario2 conserva subscriber y ocho capacidades previas más solo alexcuadra_prepare_post_changes, sin grant a roles/otros usuarios y16permisos amplios denegados. [Recibo CLI](qa/blog-proposal-rc2-destination-after-scope-2026-10-27.json). Verificación independiente del conector: catálogo18abilities, cinco nuevas con DTOs cerrados y anotaciones correctas; listado privado items=[]/next_after=null. Siete llamadas de lectura/metadatos, sin crear planes o leer cuerpos. [Cierre real/límites](qa/MCP_BLOG_PROPOSAL_LIVE_2026-10-27.md). Instalación/grant/backup inicial ya cerrados; no repetir comandos. Siguiente piloto privado con fuente/diff explícitos antes de cualquier creación, o incremento de taxonomías propias de proyectos; edición/publicación de posts sigue sin implementarse.

**27 octubre — respaldo inicial real verificado:** Alex aporta verify ready=true/integrityVerified=true;1propietario y0propuestas/revisiones/recibos, sin escrituras de BD, mismo artefacto/SHA del export. [Recibo](qa/blog-proposal-backup-destination-verify-2026-10-27.json). Herramientas de recuperación ya ensayadas en entorno aislado; no se restauró sobre el CMS vivo. Siguiente concesión directa únicamente alexcuadra_prepare_post_changes al usuario2 por Alex y after-scope, conservando ocho capacidades previas/rol subscriber y16denegaciones amplias. No grant a roles/otros usuarios ni creación de propuestas todavía. Verificación MCP real de solo lectura tras recibir after-scope.

**27 octubre — primera exportación real recibida:** Alex aporta export ready=true, respaldo backup-20261027T160054Z-d1f849d991c44fdb y manifestSha256 39022b62…a5b0c76; cuenta1propietario y0propuestas/revisiones/recibos, sin escrituras de BD. [Recibo](qa/blog-proposal-backup-destination-export-2026-10-27.json). Siguiente verify sobre ese mismo directorio/SHA con scope todavía ausente; no repetir exportación, modificar artefactos, restaurar sobre el CMS vivo ni conceder permiso todavía. restorationVerified=false es correcto: no se ejecutó restauración del hosting.

Herramienta operativa separada del plugin. Exporta y recupera únicamente las cuatro tablas ac_blog_proposal_ del prefijo de esta instalación. No instala abilities, concede permisos, publica contenido ni restaura posts, postmeta, opciones, roles o credenciales. El ZIP rc.2 permanece sellado e idéntico.

Archivos que deben permanecer juntos fuera del directorio público:

- [blog-proposal-backup.php](../wordpress/operations/blog-proposal-rc2/blog-proposal-backup.php): entrada WP-CLI con resultados limitados y errores sin cuerpos/SQL.
- [recovery.php](../wordpress/operations/blog-proposal-rc2/recovery.php): validación de paquete/perfil/permisos, exportación, integridad y restauración a destino ausente.
- [Manifiesto de herramientas](../artifacts/wordpress/blog-proposal-recovery-0.1.0.manifest.json).

## Qué se exige

Operador CLI1 existente con manage_options; sitio único, guard de rc.2 activo, perfil admitido y ocho archivos del plugin exactamente iguales al ZIP esperado. El usuario2 debe conservar sus ocho capacidades anteriores y no tener alexcuadra_prepare_post_changes, ni siquiera mediante filtros de permisos. No puede haber otro usuario o rol con ese scope. El operador declara `writers-stopped`: ninguna operación externa/SQL/manual debe modificar estas cuatro tablas durante la operación. No es necesario pausar las ediciones de posts públicos por este respaldo limitado.

El comando no quita ni añade scopes automáticamente. Para futuros respaldos, retirar solo el nuevo scope, coordinar los demás escritores de estas tablas y comprobar la pausa antes de exportar. No modificar roles o capacidades anteriores para cumplir el requisito.

La exportación usa una transacción nativa mysqli READ ONLY/REPEATABLE READ con snapshot consistente; no permite reconexión de wpdb dentro del snapshot. Comprueba esquema antes/después, conexión y ausencia de escritores admitidos. No bloquea cambios DDL o SQL de operadores externos: la declaración de pausa debe ser real.

## Estado actual: respaldo inicial verificado y grant completado

After-scope y el acceso MCP real ya se verificaron; no repetir grant o respaldo inicial. [Cierre](qa/MCP_BLOG_PROPOSAL_LIVE_2026-10-27.md). El artefacto inicial solo contiene propietario2; no cubre planes futuros. Para un respaldo posterior, coordinar la pausa y retirar únicamente alexcuadra_prepare_post_changes antes de exportar; las otras capacidades permanecen. Verificar/conservar cada artefacto nuevo y su SHA, sin reemplazar o podar el anterior. No ejecutar restore-empty sobre el CMS vivo para probar instalación.

## Histórico: integridad verificada antes del grant

El [verify real de Alex](qa/blog-proposal-backup-destination-verify-2026-10-27.json) confirma integrityVerified=true con los mismos conteos. No repetir export/verify ni restaurar sobre el CMS vivo. La fase siguiente es el [grant directo único y after-scope](WORDPRESS_MCP_BLOG_PROPOSAL_RC2.md), todavía pendientes. Conservar el artefacto y su SHA; para respaldos futuros retirar únicamente el nuevo scope y coordinar pausa de escritores antes de exportar.

## Histórico: verificación separada del respaldo ya completada

El [resultado de exportación de Alex](qa/blog-proposal-backup-destination-export-2026-10-27.json) confirma el respaldo inicial esperado: owners=1, otras tres tablas=0 y databaseWritesPerformed=false. La herramienta verifica internamente antes de devolver éxito; ahora se hará una invocación nueva contra el mismo artefacto y SHA conservado en este recibo. restorationVerified=false indica que no se restauró el hosting, no un fallo de exportación.

Mantener el scope nuevo ausente. Alex ejecuta únicamente:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content --user=1 eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-backup.php verify /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-backups/backup-20261027T160054Z-d1f849d991c44fdb 39022b626ba023405c3ba2475bb7d0281f910bcfa7cfa3610385b29bba5b0c76
```

Compartir únicamente BLOG_PROPOSAL_BACKUP_RESULT; no adjuntar tables.json o manifest.json. Se espera ready=true, integrityVerified=true y los mismos conteos, sin escrituras de BD. Si falla, compartir el código y conservar el artefacto: no regenerar, editar, sustituir el SHA o restaurar para hacer pasar el control. No repetir exportación o mkdir. El grant sigue pendiente de revisión del recibo de verify.

## Histórico: primera exportación del hosting ya realizada por Alex

El after-owner de Alex ya es correcto. No repetir instalación, creación de tablas, enroll o diagnósticos anteriores. Subir los dos PHP nuevos a:

`/home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/`

Crear una carpeta privada nueva para los respaldos:

```sh
mkdir -m 700 /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-backups
```

Si ya existe, no cambiar sus permisos o contenido por continuidad: revisar antes de usarla. Mantener el nuevo scope ausente y coordinar la pausa de escritores de estas tablas. Ejecutar:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content --user=1 eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-backup.php export /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-backups writers-stopped
```

Compartir únicamente BLOG_PROPOSAL_BACKUP_RESULT. El primer respaldo debe contar owners=1 y proposals/revisions/requests=0. El resultado incluye nombre de subcarpeta y manifestSha256; no contiene cuerpos, credenciales o SQL. Conservar este SHA fuera del artefacto como referencia de integridad; no recalcular uno nuevo para hacer pasar un archivo cambiado.

La herramienta crea una subcarpeta única0700, tables.json0600 y manifest.json0600. Usa creación exclusiva y escribe el manifiesto al final. Un directorio incompleto tras un fallo no es un respaldo aceptado. No sobreescribe, elimina o poda respaldos. Son archivos privados sin cifrado añadido: no subirlos al repositorio, public_html, chats o servicios externos.

## Verificación posterior

Tras revisar el resultado de exportación, usar la subcarpeta y el SHA exactos recibidos:

```text
wp --path=RAIZ_CMS --user=1 eval-file RUTA_PRIVADA/blog-proposal-backup.php verify RUTA_DEL_RESPALDO SHA_DEL_MANIFIESTO
```

La verificación comprueba conjunto exacto de archivos, permisos, límites, SHA del manifiesto y payload, paquete/sitio, columnas y propietario, cuotas, continuidad de versiones, DTOs cerrados y hashes de documentos, cabezas y recibos asociados. No ejecuta SQL procedente del artefacto ni consulta cuerpos públicos. El hash detecta cambios respecto a la referencia conservada; no es firma criptográfica ni cifra el contenido.

El grant no se da automáticamente tras exportar/verificar. Revisar el recibo real antes de conceder únicamente el scope nuevo al usuario2. No hace falta restaurar sobre el CMS vivo para validar este primer respaldo vacío.

## Recuperación: solo destino sin las cuatro tablas

La herramienta implementa restore-empty. Antes de usarlo sobre un servidor real, revisar el artefacto exacto, manifestSha256, destino/paquete y pausa; ensayar primero en un entorno aislado sin correo/egreso, con código/perfil compatibles y cuentas sintéticas autorizadas. La recuperación real es una operación separada que requiere revisión explícita de su destino. No ejecutar este modo como parte de la primera exportación.

```text
wp --path=DESTINO_REVISADO --user=1 eval-file RUTA_PRIVADA/blog-proposal-backup.php restore-empty RUTA_DEL_RESPALDO SHA_DEL_MANIFIESTO writers-stopped
```

Rechaza si existe cualquiera de las cuatro tablas, aunque esté vacía. No tiene DROP, ALTER, borrado, upsert, cambio de roles o sustitución del destino ocupado. Crea el esquema conocido desde el paquete rc.2, no desde SQL del archivo, e inserta las filas en una transacción con barrera de conexión. Compara las filas exactas antes del commit y valida el esquema después.

DDL no es transaccional: si falla la creación o inserción, pueden quedar tablas nuevas vacías o parciales. No reintentar, borrar o completar por iniciativa propia; compartir el código del error y diagnosticar. El rechazo de destino ocupado conserva el estado existente. Un commit incierto requiere conciliación de recibos, no repetir con otra clave.

La restauración preserva IDs, propietarios, versiones, documentos y recibos originales. Para probar replay se necesitan las solicitudes originales exactas del cliente, con sus request_id/payload/versión; no reconstruirlas a partir de un diff que puede omitir campos sin cambios. Si no están disponibles, validar lectura/historia y conciliar el recibo antes de una operación nueva. No se restauran fuentes públicas ni se garantiza que su estado actual coincida con el del plan: operaciones nuevas requieren una base vigente.

## Ensayos y límites

[Pruebas](qa/MCP_BLOG_PROPOSAL_RECOVERY_2026-10-27.md) y [recibo](qa/blog-proposal-recovery-2026-10-27.json). Configuración y datos sintéticos; sin importar SQL/configuración del hosting. Restauración y API get/list/replay verificadas en prefijos propios aislados conservando las tablas originales. El helper genérico histórico de37tablas no se usa.

Laboratorio WP7.1.3/PHP8.5.11/MariaDB11.8.9; hosting declara PHP8.5.12. Se admiten las huellas de entradas conocidas, no árboles completos de plugins u opciones/cachés del hosting. No se ha exportado ni restaurado el hosting desde el agente. No hay garantía de recuperación ante pérdida física del servidor sin conservar el artefacto y las dependencias en un lugar privado adecuado; la retención/copia externa se decide por separado.
