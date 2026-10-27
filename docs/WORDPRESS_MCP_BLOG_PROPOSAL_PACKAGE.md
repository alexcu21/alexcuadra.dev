# Paquete revisable de propuestas privadas

**Estado posterior al diagnóstico real:** before recibido, bloqueado solo por perfil. Código disponible contrastado y46checks de arranque del perfil ampliado correctos; falta ensayar CRUD/MCP/fallos antes de una nueva versión. rc.1 sigue retenido y sin cambios. [Recibo y siguiente fase](qa/MCP_BLOG_PROPOSAL_DESTINATION_PROFILE_2026-10-26.md). No repetir el comando inicial ni instalar/conceder scopes ahora.

**0.1.0-rc.1, probado en laboratorio; instalación y primer scope reales pendientes.** Este paquete no publica ni edita fuentes. El siguiente paso es únicamente el diagnóstico de compatibilidad del CMS real.

- [ZIP](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.1.zip):17.074bytes, SHA256 `ca33ebd4b3a874a939b8d18dbf42c2cea324325b26716f01208cd1f85154635a`.
- [Manifiesto](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.1.manifest.json), [pruebas y límites](qa/MCP_BLOG_PROPOSAL_PACKAGE_2026-10-26.md).
- Permiso nuevo propuesto: `alexcuadra_prepare_post_changes`, directo al usuario2/subscriber. No se ha concedido.
- Cinco herramientas alexcuadra-blog/*: contexto de fuente publicada, crear/actualizar/get/listado de planes privados. El lector público permanece separado.

## Paso actual: diagnóstico de solo lectura

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
