# Activación y almacenamiento rc.3 — fase para revisión

Estado: Alex autoriza con «si adelante» el opt-in del sitio, activación de rc.3 y creación de las seis tablas vacías, manteniendo permisos y contenido. Ejecución remota pendiente de Alex; el agente no ejecuta SSH. No incluye grants, aceptación global con pausa externa, aprobación de un diff ni edición pública. Alex completó la fase inactiva:rc.3/hash correcto,15 archivos verificados,14 Projects y propuestas/permisos sin cambios, sin opt-in/tablas/abilities. [Resultado real](qa/MCP_OPERATING_LIVE_INACTIVE_INSTALL_2026-10-23.json). No repetir la subida o instalación.

Alcance propuesto:

1. Capturar en carpeta privada del servidor una baseline de huellas/conteos, sin cuerpos/SQL/config privada.
2. Añadir únicamente `AC_EDITORIAL_OPERATING_SITE='https://content.alexcuadra.dev'` mediante WP-CLI y activar el plugin rc.3 ya instalado.
3. Con el operador1 existente, preparar las seis tablas propias y el marker `operating-storage-v2`. No ALTER/DROP de tablas existentes. Validar esquema e InnoDB, marker ready y cero filas.
4. Comprobar registro de seis abilities y18 denegaciones de permisos:anónimo, operador1 y usuario2, cada uno frente a las seis abilities. Mantener todos los scopes actuales:la fase exige ausencia de apply/recover/approve en todos los usuarios/roles antes de empezar y no los concede.
5. Comparar fuentes/propuestas privadas/ledger/usermeta/roles/otros plugins y el código de wp-config excepto la nueva constante. Detenerse después de la verificación.

No se prepara/aplica/recupera una propuesta, no se crea aprobación pública y no se ejecuta aceptación global fingiendo una pausa de editores. La aceptación global y los grants requieren su fase posterior. [Contrato](WORDPRESS_MCP_OPERATING_CONTRACT.md).

Ensayo local cerrado:10 escenarios y18 comprobaciones de permisos correctos. [QA y límites](qa/MCP_ACTIVATION_STORAGE_2026-10-23.md). No se repiten pruebas del candidato sellado al no cambiar sus bytes.

## Scripts concretos

[Activación/almacenamiento](../wordpress/operations/editorial-operating-activate-storage.sh), [comprobador](../wordpress/operations/editorial-operating-activation-check.php), [desactivación/retiro del opt-in](../wordpress/operations/editorial-operating-disable.sh). Se generan con `python3 scripts/prepare-editorial-operating-activation.py` desde el inventario del ZIP sellado y helpers revisables. No modifican el ZIP ni conectan al hosting.

Antes de cambiar la configuración se verifican los15 archivos exactos y la admisión prospectiva, estado inactivo/opt-ins/tablas/marker/abilities ausentes, cuentas/roles sin scopes operativos. Después se usa la guarda real del plugin instalado. Solo user1/manage_options puede migrar por CLI; no se le añade approve.

La baseline compara huellas de posts/meta/usermeta/términos/relaciones/owners/requests rc.2/roles/otros plugins. El wp-config se lee en memoria y se comparan tokens de código, omitiendo espacios/comentarios y exclusivamente la definición exacta del nuevo opt-in; también se conserva el modo del archivo. No se copia su contenido privado. La baseline permite verificar la modificación acotada, no reconstruir arbitrariamente la configuración.

Antes de DDL se vuelve a comparar la baseline y la guarda. La migración usa sus locks, valida todas las tablas existentes antes de crear nada y admite reanudar solamente esquemas vacíos/compatibles; DDL no es transaccional. Una denegación de CREATE o interrupción puede conservar tablas parciales y marker preparing. No se borran para reintentar. Si cambia contenido entre lecturas, detenerse/revisar; no se certifica bloqueo de editores externos.

Los comandos exigen marcadores de baseline, migración y recibo final; no confían solo en exit0 de wp eval-file. Se conserva un identificador `OPERATING_ACTIVATION_RUN=operating-activate.XXXXXXXX` antes del cambio de configuración, para localizar su baseline y permitir desactivar aunque falle después. No mostrar baseline.base64 ni logs privados completos en el chat.

## Comando preparado para Alex después de autorizar esta fase

El agente mantiene la instrucción de no ejecutar SSH. Este comando activa el plugin y crea las seis tablas; no es de solo lectura:

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-activate-storage.sh
```

Pasar toda la salida, incluido `OPERATING_ACTIVATION_RUN`, `OPERATING_ACTIVATION_STORAGE_RESULT` y `END_EDITORIAL_OPERATING_ACTIVATE_STORAGE_V1`. Se espera active/storageSchemaVerified/newStorageEmpty=true,tableCount6,permissionDenialsVerified18,projectCount14,capabilitiesAdded/editorialDataWritten/globalStorageAcceptancePerformed=false. La estimación de datos/índices de tablas se informa en el resultado.

Si falla, detenerse y aportar la salida/identificador; no repetir el script completo ni invocar manualmente install para evitar la comparación contra otra baseline. Revisar el estado antes de autorizar una reanudación. Este documento no certifica aún privilegios DDL del servidor.

## Desactivar sin borrar ni deshacer cambios ajenos

Conservar el ZIP, los archivos instalados, todas las tablas/marker/registros y el respaldo. La desactivación no elimina DDL ni restaura el SQL del sitio.

La reversión comprueba paquete, contexto y valor del opt-in; captura una baseline **inmediatamente antes de desactivar**. Desactiva únicamente Editorial Operating y elimina únicamente la constante SITE que coincide con la añadida. Comprueba conservación de fuentes, propuestas, roles, permisos y demás código de configuración frente a esa lectura actual. Así conserva también ediciones/permisos externos aparecidos después de activar; no revoca grants ajenos. Cada ejecución guarda una baseline distinta y no pisa registros anteriores. Retiene tablas incluso si la creación quedó parcial.

Sustituir el identificador del ejemplo por el emitido en esta activación:

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s -- operating-activate.ID_DE_LA_SALIDA' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-disable.sh
```

El ejemplo es un marcador, no ejecutar literalmente. La salida acaba en `END_EDITORIAL_OPERATING_DISABLE_V1`, con plugin inactivo/opt-in ausente y número de tablas conservadas. Si hay una desviación de paquete/contexto que bloquea el verificador, detenerse y revisar; el comando básico de detención del plugin está en el [plan anterior](WORDPRESS_MCP_OPERATING_DEPLOYMENT.md). Ningún paso devuelve contenido público a una versión anterior; eso requiere una inversa aprobada.

## Almacenamiento y respaldo

En el ensayo inicial, información de MariaDB para las seis tablas vacías:131072bytes de datos+índices. Es una estimación estadística, no el espacio físico de ficheros/overhead ni una certificación de cuota. El resultado real informará su valor. Se añaden además scripts/baselines pequeños y temporales normales; no se descarga ni exporta otro respaldo al equipo. El equipo mostraba32GiB libres antes y después del ensayo (redondeados); Docker puede usar RAM/swap/capas.

El respaldo privado anterior se conserva:247734036bytes/SHA esperado comprobados de nuevo y evidencia de restauración previa coincidente. No se presenta como dump nuevo ni como fotografía actual de los datos posteriores. Esta fase es aditiva y su reversión conserva las tablas; no restaura ciegamente ese respaldo.
