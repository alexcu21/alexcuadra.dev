# Integración del contrato operativo — 23 octubre

**52 controles nuevos y132 de regresión del adaptador correctos**, con perfil completo original+Proposals rc.2, WordPress/MariaDB/ACF/Polylang/LiteSpeed reales y datos sintéticos.45 checks de frontera del writer correctos. No sumar ejecuciones repetidas como cobertura adicional.

Se preparó una revisión desde una propuesta rc.2 de subscriber con scopes específicos. Admin sin scopes no puede preparar/aprobar; mismo actor no puede aprobarse aun con manage_options+scope. Operador CLI independiente inspecciona hash/diff congelado y aprueba; actor/restauración de contexto verificados. Apply sin aprobación, idioma/dueño incorrectos, propuesta/fuente vinculada cambiadas, revisión caducada, scope revocado, input extra, clave reutilizada y transacción externa se rechazan. Sin edición/admin global para el executor ni cambio de rol subscriber.

Aplicación de25000 caracteres acentuados más tagline por APIs reales: se respeta límite de caracteres UTF-8, se conserva ES y no se altera ningún byte de recibos/payloads rc.2. Recibo nuevo inmutable con effect_state_at_commit=pending; lectura mutable muestra requested tras consumir, sin reescribir fuente ni recibo. Un no-op mantiene el source hash y aun así la aprobación no puede usarse de nuevo. Fallo de inserción tardío revierte fuente, consumo de aprobación, reserva, app y receipt; la intención huérfana inyectada por fixture no es ejecutable.

Cuotas probadas separadamente:8 revisiones,64 requests,8MiB lógicos operativos conservadores, con reserva1review/2requests/560KiB antes de fuente. El agotamiento de bytes es reserva sintética, no padding. Rc.2 conserva presupuesto propio y sigue admitiendo sus operaciones dentro de ese presupuesto independiente. No hay eliminación/retención automática. La recuperación operativa se reserva pero todavía no se ejecuta por este contrato.

No hay abilities/paquete/migración final instalados. Las tablas nuevas solo tienen admisión InnoDB en esta fase; falta esquema/decoder estrictos y recuperación operativa antes de candidato/MCP/export/preflight/reversión. La prueba HTTP132 pertenece al adaptador y no certifica transporte de las nuevas acciones del contrato. Configuración privada y entrega de caché hosting no verificadas.

35GiB libres al inicio. Sin SSH, grants o escrituras CMS reales, Workers/contacto, pulls, archivos de respaldo nuevos o SQL/config importados. Proyecto Docker interno y sin puertos, datos sintéticos, tmpfs512+768MiB, contenedores detenidos en finally. Paquete rc.2 y lab0.5 intactos. [Contrato y próximos entregables](../WORDPRESS_MCP_OPERATING_CONTRACT.md).

Pendiente obligatorio antes de candidato: impedir y probar la reconexión automática de wpdb durante la transacción. El código de WordPress reintenta la consulta tras error2006 en otra conexión; los SIGKILL de workers no cubren ese caso y podrían perderse los límites transaccionales. La atomicidad indicada corresponde a los escenarios ensayados, no a pérdida/reconexión de la sesión DB. Este endurecimiento y su prueba de desconexión bloquean el candidato instalable.

La caducidad que ocurre durante save_post también provoca rollback de fuente y conserva la aprobación sin consumir.
