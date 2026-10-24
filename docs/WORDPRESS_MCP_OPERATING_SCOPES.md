# Permisos operativos — conciliación pendiente tras fallo del postflight

**Última lectura real:**729 filas de postmeta estables en dos observaciones; scopes exactos. La búsqueda de adiciones no reconstruyó la huella original. Esto no identifica la causa ni demuestra que sean locks. [Evidencia real](qa/MCP_OPERATING_LIVE_POSTMETA_DIAGNOSIS_2026-10-23.json). No se ha hecho aceptación ni se reemplazó baseline.

**Diagnóstico real recibido:**las capacidades directas de1 y2 coinciden exactamente con lo esperado; los tres scopes están true. Solo difiere `postmeta`. Posts, propuestas/ledger, usermeta normalizado, roles, configuración, otros plugins y almacenamiento operativo coinciden en esta lectura con la baseline original. No se hizo aceptación. [Resultado real](qa/MCP_OPERATING_LIVE_SCOPE_DIAGNOSIS_2026-10-23.json). La causa/fila/valor anterior no quedan identificados por una huella global; no tratar automáticamente la deriva como inocua ni sustituir la baseline.

**Incidencia real:**Alex ejecutó la concesión autorizada, run `operating-scopes.yQCBqE9c`. La mutación alcanzó el postflight independiente, que falló con `SCOPES_POSTFLIGHT_CHANGED`; no llegó el recibo final. Los permisos pueden estar concedidos: no repetir la concesión ni ejecutar aceptación. [Evidencia](qa/MCP_OPERATING_LIVE_SCOPES_INCIDENT_2026-10-23.json). Se prepara diagnóstico de solo lectura frente a la baseline original, sin reemplazarla ni aceptar desviaciones. La causa real permanece sin identificar.

La activación real de rc.3 quedó completada: seis tablas listas/vacías,14 Projects conservados y ningún permiso nuevo. [Evidencia de Alex](qa/MCP_OPERATING_LIVE_ACTIVATION_2026-10-23.json). No repetir instalación, activación ni la propuesta227. Alex autorizó esta fase con «si adelante»: apply/recover directos para usuario2, approve directo para operador1 y aceptación posterior de solo lectura. Ejecución iniciada por Alex y detenida tras el fallo descrito arriba; el agente no ejecuta SSH. La autorización no incluye aplicar un diff público. Alex confirmó que puede coordinar la pausa; no confirmó que ya esté iniciada. Ejecutar primero la concesión con pausa efectiva y revisar su recibo antes de la aceptación.

## Siguiente lectura: corroboración editorial con evidencia histórica

El diagnóstico anterior no identificó filas añadidas; no repetir búsquedas iguales ni sustituir la baseline. Se conserva evidencia anterior independiente: hash `c38acd1690f173488f075443d03a80c96b6b483a5bd407040cbbd40a7ff462af` de las filas, **todos** los metadatos y relaciones de los14 Projects, más identidad/base/versiones de la propuesta227. El ledger rc.2 guarda respuestas con los patches de cada revisión y su huella se compara con la baseline de concesión.

La siguiente lectura compara esos datos sin exportar cuerpos/metadatos/patches. Comprueba también la consistencia del payload con su fuente y los patches de cabecera/historial contra las respuestas del ledger, exigiendo que ese ledger siga intacto. Conservar el mismo token de versión no basta para pasar esa comprobación. No reconstruye las fechas del historial, no atribuye autor/causa y no certifica que todo postmeta esté intacto. No concede/revoca scopes, no acepta almacenamiento y no cambia la baseline. Un recibo final solo confirma que el diagnóstico terminó: revisar sus booleanos.

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s -- operating-scopes.yQCBqE9c' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-check-editorial-evidence.sh
```

Mantener la pausa durante la comprobación y pasar `OPERATING_EDITORIAL_EVIDENCE` y `END_EDITORIAL_OPERATING_EDITORIAL_EVIDENCE_V1`. Si la huella histórica de Projects no coincide, no inferir que todo sea contenido cambiado o que sean solo locks: la huella incluye ambos. Si coincide, acota la deriva fuera de las filas/meta/relaciones de esos14 Projects; aún no identifica la diferencia global ni garantiza todas las columnas del historial privado.

Ensayo de la nueva lectura:6 casos nuevos correctos y18 regresiones previas correctas; [QA y límites](qa/MCP_OPERATING_EDITORIAL_EVIDENCE_2026-10-23.md). El script crea solo un helper pequeño privado en el servidor; sin descarga de respaldo al equipo. No hay aún salida real de esta corroboración.

## Diagnóstico de postmeta ya ejecutado por Alex

Conservar la pausa mientras se ejecuta y revisa este diagnóstico. Los permisos ya coinciden: no volver a concederlos. No ejecutar todavía aceptación. El comando lee el CMS en dos procesos/snapshots consecutivos READ ONLY; conserva hashes pequeños de la primera observación en una carpeta privada nueva del servidor. No reemplaza ni reinterpreta la baseline original. No exporta valores de metadata, cuerpos ni credenciales, ni descarga un respaldo.

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s -- operating-scopes.yQCBqE9c' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-diagnose-postmeta.sh
```

Pegar las dos líneas `OPERATING_POSTMETA_DIAGNOSIS` y el cierre `END_EDITORIAL_OPERATING_POSTMETA_DIAGNOSIS_V1`. Buscar coincidencia exacta de mapas de capacidades, secciones distintas y estabilidad entre observaciones. Dos lecturas estables no certifican que todos los escritores externos estén detenidos.

La prueba de reconstrucción examina como máximo las32 filas con mayor meta_id: prueba excluir en memoria cada una por separado y sufijos de esas filas. Solo si el hash reconstruido coincide exactamente con el original puede afirmar que el resto de las filas actuales reproduce la baseline. No ejecuta DELETE/UPDATE, no atribuye autor/causa ni acepta esa diferencia. Devuelve únicamente IDs/categorías de las filas candidatas, y formato válido/incorrecto para un posible `_edit_lock`; no sus valores ni nombres arbitrarios de claves.

Si no encuentra coincidencia, la búsqueda es inconclusa: puede haber actualizaciones, eliminaciones o adiciones fuera de su límite. Un hash anterior no recupera los valores antiguos. No eliminar locks ni sustituir baseline para hacer pasar el control. El diagnóstico se limita a5000 filas/16MiB de valores y se detiene si excede ese tamaño.

Ensayo exacto:4 casos nuevos correctos y12+2 regresiones previas correctas; [QA](qa/MCP_OPERATING_POSTMETA_DIAGNOSIS_2026-10-23.md). No se ha identificado todavía la fila/cambio real. Sin cambios al ZIP rc.3, SSH del agente o respaldo nuevo.

## Diagnóstico general ya ejecutado por Alex

Comprobación de solo lectura del CMS frente a la baseline del run fallido; no concede/revoca permisos ni acepta almacenamiento. Conserva en el servidor un helper pequeño privado, sin cambiar la baseline original. Sin descargas al equipo. No mostrar hashes/baseline/logs privados: devuelve nombres de secciones que difieren, booleanos y solo los tres scopes previstos. Que el diagnóstico termine correctamente no significa que el postflight coincida: revisar `matchesOriginalPostflightCondition` y los detalles.

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s -- operating-scopes.yQCBqE9c' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-diagnose-scopes.sh
```

Aportar `OPERATING_SCOPES_DIAGNOSIS` y `END_EDITORIAL_OPERATING_SCOPES_DIAGNOSIS_V1`. Mantener la pausa durante esta revisión. No sustituir la baseline ni normalizar/ignorar una diferencia sin identificar su causa.

Ensayo del diagnóstico exacto correcto: dos casos adicionales sobre el circuito de12 escenarios, estado coincidente y deriva de contenido/usermeta/capacidad. Lectura READ ONLY/REPEATABLE READ sobre conexión nativa con barrera; no se añaden vías de mutación. [Resultado sintético](qa/MCP_OPERATING_SCOPE_DIAGNOSIS_2026-10-23.json), laboratorio `/private/tmp/ac-source-effects-umzgbsjw` detenido. No reproduce todavía la causa del fallo real ni certifica su resolución.32GiB libres redondeados antes/después, sin descargas/backup nuevos ni cambios al ZIP rc.3.

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
