# Contrato operativo de aplicación — integración local

Estado23 octubre2026: integración ejecutable local, sin plugin/abilities nuevas ni instalación. `OperatingContract` enlaza con Proposals0.1.0-rc.2 y el adaptador WP/MariaDB ya verificado. No cambia el plugin instalado, sus recibos, payloads o cuotas.102 controles de contrato pasan junto con132 regresiones del perfil completo. [QA de recuperación y desconexión](qa/MCP_OPERATING_RECOVERY_2026-10-23.md). Las45 pruebas previas del writer cubren también UTF-8.

## Autoridad y revisión

El executor necesita read + scope de lectura pública + prepare de rc.2 y `alexcuadra_apply_project_changes`. Subscriber conserva su rol y no necesita edit_posts/edit_post/manage_options. Ser administrador no basta sin estos scopes. No se concede ninguno desde instalación ni activación.

`prepare` acepta únicamente proposal_id/locale/versiones esperadas de propuesta y base/request_id. Usa la propuesta privada auténtica de rc.2, comprobando autor, estado, versión y fuente. Devuelve metadatos de revisión; inspect muestra solo el diff sellado de body_html/tagline a su dueño autorizado o al operador CLI autorizado. Documento inmutable con hash, dueño, propuesta/versión, Project/idioma, antes/después, esquema y huella de fuente/vinculados; vigencia600s con reloj DB.

`approve` solo existe como método CLI, nunca como ability/REST/GraphQL. Requiere manage_options y `alexcuadra_approve_public_project_changes`, actor distinto del executor y hash exacto del diff inspeccionado. Revalida propuesta/fuente/esquema/vigencia con la identidad del executor y restaura siempre la del operador. Una aprobación no concede scopes ni permite otro diff.

`apply` exige dueño/idioma/hash y request_id exactos. Revalida todo bajo el mismo owner lock que usa rc.2. Consume la aprobación una sola vez en la transacción que escribe WP/ACF, evento, recibo e intención de purga. Se comprueba también no-op: aunque la fuente siga igual, la aprobación consumida no puede reutilizarse. Cualquier fallo antes de COMMIT revierte contenido, consumo, reserva y recibo. Se rechaza una transacción externa, sin confirmarla ni revertirla desde el módulo.

## Recibos y estado

- operating-review-receipt-v1: ID/hash/metadatos de revisión; sin valores originales en prepare.
- operating-review-inspection-v1: diff congelado para revisión autorizada.
- operating-approval-receipt-v1: aprobación CLI ligada a revisión/hash/actor.
- operating-application-receipt-v1: evento y versión de fuente, content_state=applied y effect_state_at_commit=pending, inmutables.
- operating-effect-status-v1: lectura separada y mutable del efecto/intent, sin modificar el recibo.

Una repetición exacta devuelve el recibo sin otra escritura, incluso después de aplicar/purgar o de vencer la revisión; revocar el scope impide también repetir. Una clave reutilizada con otro input se rechaza. El namespace de requests operativas es separado del de rc.2. Los bytes de recibos/payloads rc.2 no se alteran; sus comprobaciones originales de base siguen vigentes y pueden devolver source_changed después de una edición. La nueva aplicación no cambia silenciosamente el DTO antiguo draft/applied=false.

## Almacenamiento, recuperación y retención

Presupuesto operativo separado por executor:8 revisiones retenidas,64 requests incluidas reservas,8MiB lógicos conservadores. Se contabilizan documentos, slots de aprobación, respuestas, eventos/intenciones y reservas. Se rechazan nuevas operaciones al llegar al límite, sin podar históricos ni liberar aprobaciones por caducidad. No se escriben bytes de padding para las reservas: son contadores.

Antes de escribir se reservan una revisión, dos requests y560KiB para recuperación condicional. Se rechaza aplicar cuando no cabe esa reserva, aunque el diff ya esté aprobado. `prepareRecovery` consume la reserva para una revisión inversa, ligada al evento original y al source_after exacto; `recover` requiere alexcuadra_recover_project_changes y otra aprobación independiente. Recupera solo los campos revisados mediante APIs, conservando ES y recibos previos. Emite operating-recovery-receipt-v1 y otra intención; effectStatus sigue el último evento. Una revisión vencida puede renovarse con capacidad libre adicional: se conserva y cobra la anterior. No hay recuperación forzada ni renovación ilimitada.

No se impone un límite combinado8MiB a rc.2: conserva su propio8MiB/128requests/8propuestas. Ambos dominios admitidos quedan acotados a16MiB lógicos por executor, más overhead físico de DB; no prometer que el segundo presupuesto reduce retroactivamente el primero. La revisión máxima es512KiB y recibos operativos4KiB. Se alineó el writer con los40000 caracteres UTF-8 de rc.2 (hasta160000 bytes), sin confundir caracteres con bytes; el documento usa JSON UTF-8 sin escapar para conservar el límite previsto.

Conservar todos los registros, respaldo y archivos hasta validar instalación/restauración y decisión posterior de Alex. Sin cron de borrado, pruning, eliminación automática o modificación de retención. El export/archivo privado revisable y su procedimiento forman parte del candidato pendiente; no exportar cuerpos, SQL o credenciales al chat/repositorio.

## Qué falta y siguiente entrega

1. Formalizar esquema/decodificación/versiones y admisión para candidato. Las cuatro tablas tienen admisión estricta y decoder cerrado de revisiones; Aprobaciones, recibos operativos y aplicaciones ya validan estructura y vínculos al leerse. Journal y efectos se validan al leer/consumir; effectStatus contrasta aplicación/evento/revisión/aprobación/recibo. Auditoría de relaciones disponible por propietario/lotes, sin reparar; el recorrido global exige escritores detenidos y completar cursor. Falta migración final/reanudable y aceptación en reposo. [QA de auditoría](qa/MCP_OPERATING_AUDIT_2026-10-23.md). [QA del journal](qa/MCP_OPERATING_JOURNAL_2026-10-23.md). [QA de almacenamiento](qa/MCP_OPERATING_STORAGE_2026-10-23.md).
2. Registro/schema MCP acotado, export privado, preflight de configuración admitida, reversión y ZIP/manifiesto probados. Las pruebas HTTP del adaptador siguen usando fixtures; no son aún aceptación del nuevo contrato por MCP.
3. Instalación y diff público específicamente autorizados, SSH ejecutado por Alex y aceptación real. Ninguna edición real se autoriza por esta integración.

Estimación restante2–3 incrementos:1–2 para completar lo anterior y el candidato;1 para instalación/aceptación autorizadas. Esperas y fallos nuevos pueden añadir vueltas; registrar la causa. Sin ampliar las funcionalidades del laboratorio fuera de esos entregables.

Protección de reconexión implementada y verificada en el perfil local admitido: mysqli original, transacción activa, filtro final con modo estricto y lista cerrada de verbos SQL; COMMIT/ROLLBACK nativos sin reconectar y restauración en finally. Cuatro cortes reales cubren apply/recover una consulta de core en curso y el cierre del callback antes de COMMIT, además de los cuatro SIGKILL anteriores. Reintentar siempre la misma clave ante resultado incierto del COMMIT; nunca asumir fallo y crear otra operación. Código con mysqli directo o clase/drop-in DB distinto no está admitido. El candidato todavía exige la formalización y pruebas indicadas arriba.
