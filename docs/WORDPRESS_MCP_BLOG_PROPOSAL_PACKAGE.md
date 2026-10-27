# Paquete revisable de propuestas privadas

**27 octubre — primera exportación real recibida:** Alex aporta export ready=true, respaldo backup-20261027T160054Z-d1f849d991c44fdb y manifestSha256 39022b62…a5b0c76; cuenta1propietario y0propuestas/revisiones/recibos, sin escrituras de BD. [Recibo](qa/blog-proposal-backup-destination-export-2026-10-27.json). Siguiente verify sobre ese mismo directorio/SHA con scope todavía ausente; no repetir exportación, modificar artefactos, restaurar sobre el CMS vivo ni conceder permiso todavía. restorationVerified=false es correcto: no se ejecutó restauración del hosting.

**27 octubre — respaldo/recuperación operativos preparados y ensayados:** dos PHP externos al plugin;39pruebas de recuperación y5recorridos CLI correctos, incluida restauración exitosa en prefijo aislado y get/list/replay originales. Exportación nativa READ ONLY/REPEATABLE READ, archivos privados/manifiesto/hashes, destino ocupado rechazado, rollback de filas ante fallo sin reintento de DDL. [Procedimiento vigente](WORDPRESS_MCP_BLOG_PROPOSAL_RECOVERY.md), [QA/límites](qa/MCP_BLOG_PROPOSAL_RECOVERY_2026-10-27.md). ZIP rc.2 intacto; laboratorios propios detenidos/red interna vacía. Siguiente: Alex sube solo blog-proposal-backup.php y recovery.php a private-tools/blog-proposal-rc2, crea carpeta0700 y ejecuta primera exportación con scope ausente. No se ha hecho backup/restauración/grant real desde el agente; revisar recibo antes del nuevo permiso.

**27 octubre — propietario real de rc.2 verificado:** Alex aporta after-owner ready=true, 15 comprobaciones principales y 11 del perfil correctas. El check de esta fase exige únicamente propietario2; propuestas/revisiones/recibos siguen vacíos y las ocho capacidades anteriores se conservan. [Recibo](qa/blog-proposal-rc2-destination-after-owner-2026-10-27.json). Siguiente: preparar y probar exportación/restauración operativas limitadas a las cuatro tablas antes de grant. No repetir enroll/install ni conceder alexcuadra_prepare_post_changes todavía. El respaldo genérico histórico está fijado a otro perfil y no se reutiliza para este módulo. Ningún comando adicional requerido ahora a Alex.

**27 octubre — almacenamiento real de rc.2 verificado:** Alex aporta after-storage ready=true, 15 comprobaciones principales/11 del perfil correctas. Cuatro tablas completas InnoDB/utf8mb4_bin con esquema exacto, sin propietarios ni propuestas/revisiones/recibos. Ocho capacidades previas conservadas y scope nuevo ausente. [Recibo](qa/blog-proposal-rc2-destination-after-storage-2026-10-27.json). El rechazo de install no requiere reparar/recrear; no se conoce el momento ni la ejecución que creó las tablas. Siguiente: enroll(2) mediante operador1 existente y after-owner, sin grant. Respaldo operativo antes de habilitar escritura de propuestas sigue pendiente.

**27 octubre — creación de almacenamiento detenida:** Alex aporta blog_proposal_storage_not_empty en wordpress-repository.php:37. Esa línea está en la comprobación de ausencia, antes de todos los CREATE: esta invocación no llegó al DDL. Se observó al menos una tabla propia existente; se desconocen cuáles, sus filas y el origen del cambio desde after-enable (present=0). [Observación](qa/blog-proposal-rc2-storage-install-rejected-2026-10-27.json). Siguiente únicamente preflight rc.2 `2 after-storage` de solo lectura. No reintentar install, borrar/alterar tablas, inscribir propietario ni conceder scope. No atribuir éxito de almacenamiento o causa sin el diagnóstico.

**27 octubre — activación real de rc.2 verificada:** after-enable de Alex ready=true, 15 comprobaciones principales y 11 del perfil correctas. Plugin/guard activos y cinco herramientas registradas; archivos exactos, sin tablas propias o scope nuevo y con las ocho capacidades anteriores. [Recibo](qa/blog-proposal-rc2-destination-after-enable-2026-10-27.json). Siguiente: operador CLI1 existente crea únicamente las cuatro tablas vacías del módulo y after-storage; sin propietario/grant/propuestas. No reinstalar, reactivar o repetir fases anteriores. El procedimiento de respaldo del hosting sigue pendiente antes de escritura privada real.

**27 octubre — instalación inactiva de rc.2 verificada:** Alex aporta after-install ready=true, 15 comprobaciones principales/11 del perfil correctas, archivos exactos del ZIP 7e5bc2b3…d4d6def. Plugin inactivo, guard apagado, sin tablas propias o nuevo scope; ocho capacidades anteriores conservadas. [Recibo](qa/blog-proposal-rc2-destination-after-install-2026-10-27.json). Siguiente: constante de sitio exacta, activar solo este plugin y after-enable; aún sin DDL, propietario o grant. No reinstalar ni repetir before/fingerprint.

**27 octubre — before real de rc.2 recibido:** ready=true, 15 comprobaciones principales y 11 del perfil correctas, SHA del ZIP exacto. No hay paquete instalado, tablas propias, guard activo ni scope nuevo; se conservan las ocho capacidades previas. [Recibo de Alex](qa/blog-proposal-rc2-destination-before-2026-10-27.json). Siguiente: instalación inactiva del ZIP rc.2 exacto por Alex y after-install; sin activar, crear tablas o conceder permisos. No repetir before/fingerprint. Respaldo operativo del hosting pendiente antes de escritura privada.

**27 octubre — candidato rc.2 preparado:** ZIP separado de rc.1, ocho archivos/17.693bytes, SHA7e5bc2b3…d4d6def.149funcionales+29contratos+10admisión correctas; ciclo de instalación/restauración/retirada en laboratorio con nueve preflights y30checks. [Paquete/procedimiento vigente](WORDPRESS_MCP_BLOG_PROPOSAL_RC2.md), [QA/límites](qa/MCP_BLOG_PROPOSAL_RC2_2026-10-27.md). Respaldo/restauración solo sintéticos, no herramienta de hosting ni API reabierta tras restaurar. Instalación/grant reales pendientes. Siguiente preflight rc.2 de solo lectura con su pareja de PHP en private-tools/blog-proposal-rc2; no repetir fingerprint ni instalar rc.1/experimento. CMS/Worker/contacto intactos.

## Archivo histórico de rc.1: procedimiento sustituido por rc.2

**Actualización:**149pruebas funcionales/MCP/fallos correctas en laboratorio y11comprobaciones de huellas del servidor coincidentes. rc.1 permanece retenido; el experimento local no se instala. [Pruebas](qa/MCP_BLOG_PROPOSAL_SITE_FUNCTIONAL_2026-10-26.md), [recibo real aportado](qa/blog-proposal-code-fingerprint-destination-2026-10-26.json).

## Paso actual: candidato distribuible y ciclo operativo

El diagnóstico autocontenido ya fue ejecutado por Alex: codeMatchesLocalFixture=true, installationApproved=false, nuevo scope ausente y ocho capacidades conservadas. No repetir el comando ni subir otros diagnósticos ahora. Las versiones/main hashes de16plugins, dosMU, entradas de tema y wpdb coinciden con la referencia local. Esto no verifica árboles completos de plugins ni identidad exacta de PHP: hosting8.5.12, laboratorio8.5.11.

Preparar una nueva versión distribuible separada del rc.1 sellado, con guard del destino y perfil contrastado. Ensayar instalación inactiva, activación, almacenamiento/propietario/scope, respaldo consistente, restauración aislada y retirada conservando historia. Verificar el ZIP exacto y sus diagnósticos antes de presentar instrucciones de instalación real. No se ha preparado todavía ese candidato ni se ha autorizado instalación por este recibo.

**Estado posterior al diagnóstico real:** before recibido, bloqueado solo por perfil. Código y arranque contrastados, seguidos de149checks funcionales/MCP/fallos correctos en experimento local; main hashes reales ya coinciden; falta otra versión distribuible y su ciclo operativo. rc.1 sigue retenido y sin cambios. [Recibo y siguiente fase](qa/MCP_BLOG_PROPOSAL_DESTINATION_PROFILE_2026-10-26.md). No repetir el comando inicial ni instalar/conceder scopes ahora.

**0.1.0-rc.1, probado en laboratorio; instalación y primer scope reales pendientes.** Este paquete no publica ni edita fuentes. El diagnóstico inicial ya se recibió; el paso vigente es preparar el nuevo candidato y su ciclo operativo.

- [ZIP](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.1.zip):17.074bytes, SHA256 `ca33ebd4b3a874a939b8d18dbf42c2cea324325b26716f01208cd1f85154635a`.
- [Manifiesto](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.1.manifest.json), [pruebas y límites](qa/MCP_BLOG_PROPOSAL_PACKAGE_2026-10-26.md).
- Permiso nuevo propuesto: `alexcuadra_prepare_post_changes`, directo al usuario2/subscriber. No se ha concedido.
- Cinco herramientas alexcuadra-blog/*: contexto de fuente publicada, crear/actualizar/get/listado de planes privados. El lector público permanece separado.

## Diagnóstico inicial ya completado (registro histórico)

Alex ejecuta SSH; el agente no abre conexión al hosting. Subir estos **dos archivos juntos**, conservando sus nombres, a `/home/u776991923/domains/alexcuadra.dev/private-tools/`:

1. [blog-proposal-admission.php](../wordpress/operations/blog-proposal-admission.php).
2. [blog-proposal-preflight.php](../wordpress/operations/blog-proposal-preflight.php).

Desde cualquier carpeta SSH, ejecutar:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-preflight.php 2 before
```

Compartir la línea `BLOG_PROPOSAL_PREFLIGHT_RESULT=...` y cualquier error. No subir el ZIP todavía ni modificar plugins, tema, URLs, constantes o permisos para hacer pasar el perfil. El public_html superior es otra instalación: la raíz correcta del CMS es public_html/content.

El diagnóstico inspecciona versiones, entradas/hashes de perfil, esquema/índices/FK, conteos privados y permisos. No lee cuerpos de posts/propuestas ni solicita Application Passwords; no instala, activa, crea tablas, concede capacidades o guarda estado propio. El arranque normal de WordPress/otros plugins puede tener efectos ajenos al observador: no certifica conservación global de la BD ni sustituye la evidencia histórica de postmeta.

## Qué puede bloquear el perfil

El perfil admitido es deliberadamente el ensayado: WP7.1.3/PHP8.5, wpdb core fijado por hash, Polylang3.9.1/Adapter0.7.1, lector0.1.0-rc.1 y este candidato; tema Twenty Twenty-Five1.5 con hashes de entradas conocidos, caché estándar y ningún drop-in. Fuera del laboratorio no admite MU adicionales ni query filters desconocidos.

El hosting ya tiene plugins/MU/tema adicionales según evidencia anterior; **no se presupone que este candidato los admite**. Un ready=false por perfil es un bloqueo de compatibilidad, no una instrucción de desactivarlos o cambiar tema. Usar el recibo para reproducir/verificar el perfil pertinente y revisar un candidato nuevo. Las huellas de entradas no equivalen a auditoría íntegra de todos los archivos, hooks, plugins o tema.

La rama de laboratorio permite solo local/http://wordpress/DB_HOST=db, un fixture MU conocido y callbacks de inyección de fallos. Esa excepción no aplica al CMS real. El destino requiere home/site exactos https://content.alexcuadra.dev, sitio único y constante específica; no se configura aún.

## Secuencia posterior, sin ejecutar todavía

1. Revisar diagnóstico real y compatibilidad antes de aprobar instalación del ZIP exacto.
2. Instalar inactivo; comprobar after-install. Activar con guard específico solo tras revisión; after-enable exige cinco herramientas y ningún permiso nuevo. Activación no crea tablas.
3. Preparar respaldo/recuperación y admitir cuatro tablas nuevas por separado mediante CLI/operador: after-storage exige InnoDB/collation/columnas/defaults/índices/FK/triggers exactos. DDL no es transaccional: una instalación parcial se detiene, sin DROP ni reintento ciego.
4. Crear únicamente la fila de propietario2, sin grant; after-owner. Revisar primer scope real y conceder solo alexcuadra_prepare_post_changes; after-scope. Conserva exactamente las ocho capacidades actuales y deniega16permisos amplios.
5. Verificar MCP real y una propuesta privada concreta autorizada por separado. No crear credenciales nuevas ni ejecutar una publicación para demostrar lectura.

Los estados before/after-install/after-enable/after-storage/after-owner/after-scope son de aceptación inicial. No repetir los que exigen tablas vacías después de empezar a guardar propuestas ni borrar datos para satisfacerlos. La comprobación withdrawn es de retirada y no exige vaciar tablas.

## Conservación, respaldo y retirada

Conservar versiones/recibos; sin poda o vencimiento automáticos. Cuotas por propietario:16propuestas,64revisiones,128peticiones/8MiB lógicos;16versiones por propuesta y786432bytes/documento. Al llenarse se rechazan nuevas escrituras, manteniendo lectura/replay. Es presupuesto lógico, no medida de disco físico, índices, journal o backups.

Antes de habilitar escritura privada real: respaldo consistente y privado de las cuatro tablas propias, acompañado del manifiesto/hashes y una verificación de restauración aislada. Pausar los escritores de propuestas durante exportación/restauración coordinada. No incluir credenciales en la salida; conservar archivos fuera de public_html con acceso restringido. Registrar propietario/versión/replay/conteos antes/después. No se ha realizado ese respaldo o ensayo real en el hosting; forma parte de la admisión operativa pendiente.

No restaurar wp_posts, postmeta, tablas operativas de proyectos o capacidades antiguas para recuperar este módulo. Restaurar solo sus tablas requiere revisar integridad/propiedad/recibos en laboratorio antes de volver a abrir acceso. No liberar cuota borrando historia por continuidad; preparar un archivo/exportación privado y una política explícita si resulta necesario.

Retirada probada con una propuesta existente: quitar solo el nuevo scope, desactivar el plugin y retirar su constante. Las tablas, una revisión/recibo y permisos anteriores permanecen; el lector público sigue activo. Sin uninstall hook ni DROP automático. No hay edición pública que revertir por guardar un plan privado.

## Alcance excluido

No hay approve/apply/recover, escritor de posts, creación de borradores/traducciones reales o publicación. HTML se conserva como texto privado para revisión, con salida JSON cerrada; cualquier vista debe escaparlo. Las futuras escrituras requieren política HTML/bloques/hooks/efectos y revalidación coordinada de fuente/grupo. Propuesta227/revisión1, ZIP de proyectos, contacto/producción y la incidencia histórica de postmeta no se modifican.
