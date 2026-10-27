# Recuperación operativa de propuestas — 27 octubre 2026

Se prepararon dos PHP operativos separados del plugin rc.2: entrada WP-CLI y biblioteca de exportación/verificación/restauración de sus cuatro tablas. El ZIP, fuentes y manifiesto de rc.2 no cambian. [Procedimiento](../WORDPRESS_MCP_BLOG_PROPOSAL_RECOVERY.md), [recibo de integración](blog-proposal-recovery-2026-10-27.json).

## Cobertura

39 pruebas funcionales de recuperación: estado inicial owner-only; exportación con dos revisiones; snapshot y filas exactas; UTF-8/emoji; permisos privados; exportaciones sucesivas sin sustituir; pausa declarada, scope directo/filtrado y operador; rechazo de destino público, symlinks, archivos inesperados, corrupción de payload/manifiesto, paquete ajeno, filas/propietarios/columnas/continuidad/recibos/documentos inválidos; destino ocupado y transacción exterior.

Restauración de un artefacto íntegro al prefijo propio recovery_ok_: esquema y filas exactos, get de última revisión, listado y replay de create/update con las peticiones originales, sin duplicación. Una excepción inyectada al insertar recibos revierte todas las filas; las tablas DDL vacías permanecen y otro restore se rechaza, sin limpieza ni reintento automáticos. Al finalizar, filas privadas originales y tablas editoriales permanecen iguales y se retira el scope sintético.

Cinco recorridos del CLI: exportar, verificar, restaurar con éxito en otro prefijo propio, rechazar destino ocupado y rechazar falta de pausa. La variante de prefijo solo la establece el harness después de cargar WordPress sintético; no hay una opción de prefijo o bypass añadida al comando desplegable. El runner valida exactamente un marcador, estado del proceso y ready esperado, sin volcar artefactos privados al informe.

46 comprobaciones de arranque preceden a los ensayos. El paquete se instala desde el ZIP rc.2 por WP-CLI, con código de16plugins/dosMU/tema ya contrastado y datos nuevos. No se repiten149casos de operaciones que ya pasaron; las pruebas nuevas verifican específicamente la recuperación y los replays restaurados.

## Privacidad y límites

Exportación nativa mysqli READ ONLY/REPEATABLE READ, sin wpdb reconnect dentro del snapshot y con conjunto/cuotas cerrados. Archivos0700/0600, creación exclusiva y manifiesto al final. SHA del manifiesto conservado aparte permite verificar el payload; no firma/cifrado. Mensajes del CLI cerrados, sin excepción SQL/cuerpos/credenciales; errores del sistema de archivos se traducen a códigos. Los errores SQL de inserción se suprimen en la salida, conservando el rechazo/rollback y restaurando después el modo anterior de wpdb.

La pausa de escritores externos/DDL se declara por el operador; el helper no detiene procesos SQL manuales. El mecanismo no altera grants/roles y exige nuevo scope ausente. Restore-empty rechaza todo destino con alguna tabla propia existente y no aplica SQL del artefacto. Recuperación de un destino ocupado o parcialmente creado requiere revisión distinta; no hay DROP/ALTER/poda automática.

Los tres ensayos utilizaron solo proyectos propios, red interna y sin puertos de host; limpieza registrada en el recibo. Las primeras ejecuciones pasaron38+4 y39+4; el último ensayo añade el recorrido exitoso del CLI. Sin fallos de producto observados en esos ensayos; el segundo endurece permisos filtrados/privacidad de errores antes de la verificación final.

No se ejecutó SSH, backup real, restauración real, grant nuevo, propuesta real, publicación, correo o deploy. No se importaron SQL/configuración/cuentas del respaldo del hosting ni se reabrió la incidencia histórica de postmeta. PHP8.5.11 del laboratorio difiere del8.5.12 reportado por el hosting. Siguiente: Alex sube solo los dos nuevos PHP, crea carpeta privada y ejecuta export; revisar el recibo de integridad antes de grant.

## Exportación real recibida

[Recibo aportado por Alex](blog-proposal-backup-destination-export-2026-10-27.json): export ready=true, owners1 y propuestas/revisiones/recibos0, sin escrituras de BD. Respaldo identificado por directorio y SHA del manifiesto en el recibo. No hubo ejecución remota independiente del agente ni restauración del hosting. Siguiente verify en una nueva invocación, con ese mismo SHA; conservar permiso ausente y artefacto intacto.
