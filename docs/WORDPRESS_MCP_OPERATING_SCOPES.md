# Permisos operativos y aceptación inicial — preparados para autorización

La activación real de rc.3 quedó completada: seis tablas listas/vacías,14 Projects conservados y ningún permiso nuevo. [Evidencia de Alex](qa/MCP_OPERATING_LIVE_ACTIVATION_2026-10-23.json). No repetir instalación, activación ni la propuesta227. Esta fase todavía no está autorizada ni ejecutada en el hosting. Alex confirmó que puede coordinar la pausa; no confirmó que ya esté iniciada.

## Cambio exacto

| Cuenta | Capacidad directa que se añade |
| --- | --- |
| Usuario2, subscriber | `alexcuadra_apply_project_changes` |
| Usuario2, subscriber | `alexcuadra_recover_project_changes` |
| Operador1, administrador existente | `alexcuadra_approve_public_project_changes` |

No se modifican roles ni los otros permisos. El usuario2 continúa sin permisos nativos de edición/administración y no puede aprobar. El operador1 obtiene aprobación independiente por CLI, sin apply/recover. La fase no crea revisiones/aprobaciones ni aplica/recupera contenido. Cada aplicación pública sigue necesitando su revisión congelada y aprobación independiente del diff exacto; otorgar el scope no aprueba ese diff.

La concesión usa una transacción con barrera y conexión nativa vigilada, bloquea las dos filas de capacidades en orden y comprueba el delta exacto antes de COMMIT. Exige paquete íntegro, perfil real admitido, usuario1, baseline compatible, ausencia previa de los scopes y seis tablas operativas vacías. Compara fuentes/propuestas/ledger/usermeta/roles/configuración completa/otros plugins/tablas operativas. Un proceso WP-CLI nuevo comprueba el resultado y18 permisos: seis permitidos para usuario2 y doce denegados para anónimo/operador1. Solo se consulta el permiso, no se ejecutan las abilities.

La aceptación inicial es un segundo comando de solo lectura: snapshot global READ ONLY/REPEATABLE READ y barrera cooperativa; máximo100 propietarios. Exige también tablas inicialmente vacías. No guarda una habilitación persistente. Los escritores externos requieren pausa coordinada: el software no los detiene ni certifica su ausencia. Ambos comandos exigen `--external-writers-stopped` como declaración actual del operador, no como simulación de una pausa automática.

## Comandos preparados, solo después de autorización y pausa efectiva

El agente no ejecuta SSH. Primero coordinar/detener las ediciones y otros escritores editoriales, y mantener la pausa hasta revisar ambos recibos. Ejecutar el primer comando y aportar su salida completa; revisar antes de continuar con el segundo.

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s -- --external-writers-stopped' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-grant-scopes.sh
```

Conservar `OPERATING_GRANT_RUN=operating-scopes.XXXXXXXX`, `OPERATING_SCOPES_RESULT` y `END_EDITORIAL_OPERATING_GRANT_SCOPES_V1`. La baseline permanece privada en el servidor; no pegar su base64 ni logs privados completos.

Después de revisar un recibo correcto y confirmar que sigue la pausa:

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s -- --external-writers-stopped' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-accept-storage.sh
```

Aportar `OPERATING_STORAGE_ACCEPTANCE_RESULT` y `END_EDITORIAL_OPERATING_ACCEPT_STORAGE_V1`. Se espera accepted=true, totales operativos cero y datos/permisos sin cambios. No dar por certificada aceptación real antes de ese recibo.

Son operaciones separadas: si falla la aceptación, los permisos ya concedidos permanecen. No repetir ciegamente la concesión. Una interrupción durante/después del COMMIT puede dejar un resultado incierto; detenerse y revisar el estado frente a la baseline privada antes de reintentar. Los scripts exigen marcadores explícitos de baseline/mutación/recibo porque WP-CLI en este perfil puede imprimir una excepción con exit0.

## Revocación acotada

La revocación conserva el plugin activo, configuración, tablas, historial, contenido y permisos ajenos. Con el identificador real de la concesión, elimina solo las tres claves directas anteriores que todavía sean true; preserva una denegación false añadida externamente. No distingue una reconcesión externa posterior del mismo nombre: coordinar cualquier cambio de esas tres capacidades antes de revocar. Captura una baseline actual, usa transacción y postflight independiente; es repetible. No es una restauración de datos ni revoca permisos heredados de roles.

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s -- operating-scopes.ID_REAL_DE_LA_SALIDA' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-revoke-scopes.sh
```

El identificador es un marcador: sustituirlo, no ejecutar literalmente. Exigir `OPERATING_SCOPES_REVOKED` y `END_EDITORIAL_OPERATING_REVOKE_SCOPES_V1`. Si cambian paquete/perfil o hay datos inesperados, detenerse y revisar. La [desactivación del plugin](WORDPRESS_MCP_OPERATING_ACTIVATION.md) es otra operación: conserva los permisos y no sustituye esta revocación.

## Ensayo y almacenamiento

[12 escenarios locales correctos](qa/MCP_OPERATING_SCOPES_2026-10-23.md) con los scripts exactos y rc.3 sellado sin cambios. No se importó SQL real, no hubo SSH ni escritura en el CMS real. Las pruebas no certifican MCP HTTP/apply/recover en el hosting.

Equipo:32GiB libres redondeados antes y después. Sin descargas ni respaldo nuevo; el ensayo conserva su laboratorio detenido y usa imágenes existentes/red interna. Docker puede consumir disco/RAM/swap. Los tres scripts generados ocupan aproximadamente43KiB en conjunto; cada ejecución remota añade un helper y baseline/recibos pequeños en carpeta privada, sin dump ni descarga al equipo. No crea más tablas ni registros editoriales. El respaldo anterior se conserva hasta que Alex decida tras validación; no se presenta como copia actualizada.

[Generador](../scripts/prepare-editorial-operating-scopes.py), [comprobador revisable](../wordpress/operations/editorial-operating-scopes-check.php). Preparar/generar no concede permisos ni conecta al hosting.
