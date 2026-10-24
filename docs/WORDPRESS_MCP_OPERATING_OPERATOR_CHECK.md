# Comprobación del operador y almacenamiento actual

**24 octubre — cambio de prioridad acordado con Alex:**se pausa la ampliación y certificación editorial MCP para dedicar el esfuerzo al portafolio visible. El comprobador de operador preparado queda aplazado: no ejecutar su comando por continuidad automática. No se desactivaron plugins ni revocaron permisos; se conservan revisión1, propuesta227, backups, paquetes y evidencia. Aplicación/recuperación productivas y privacidad de otro dueño autenticado siguen sin certificar; incidencia histórica postmeta abierta, sin más búsquedas ciegas. Retomar solo ante una necesidad editorial concreta y un diff público aprobado por separado.

Preparada el 24 de octubre de 2026; **pendiente de ejecución en el hosting**. No cambia el paquete sellado rc.3, no repite instalación, permisos ni aceptación inicial y no aprueba la revisión caducada.

Comprueba los permisos de las seis abilities con las identidades 0/1/2 (6 permitidos, 12 denegados). El operador 1 mantiene aprobación independiente por CLI y carece de los scopes de aplicación y recuperación; el usuario 2 conserva su separación respecto a aprobación y administración.

Inspecciona la revisión privada 1 mediante el método existente del controlador como operador 1 y dueño 2; exige el hash registrado y documentos íntegramente iguales. Solo imprime identidades, contadores y resultados, nunca los textos privados. Es una comprobación **CLI**, no una autenticación HTTP como otro propietario.

La observación de almacenamiento exige globalmente una revisión, una solicitud y cero aprobaciones, aplicaciones, eventos y efectos. Audita las relaciones del dueño 2 con el auditor del paquete sin lecturas bloqueantes, calcula su uso lógico y exige cuota válida. Las observaciones antes/después usan transacciones READ ONLY/REPEATABLE READ y deben coincidir, incluidas capacidades y huellas editoriales/configuración. Esto compara el estado durante esta prueba; no reconstruye ni sustituye la baseline histórica fallida de postmeta.

La inspección existente usa SELECT FOR UPDATE: se ejecuta separadamente, en autocommit y con un filtro que rechaza consultas wpdb de escritura. No se presenta esa inspección como una transacción SQL READ ONLY. No se llama a prepare, approve, apply, recover ni a consumidores. La carga normal de WordPress conserva sus hooks habituales; las huellas antes/después detectan cambios en el alcance observado. Un escritor externo puede hacer fallar la comparación: no aceptar desviaciones automáticamente ni repetir grants.

## Impacto y ejecución

El equipo tenía 45 GiB libres. El script generado ocupa 21 452 bytes; el servidor conserva un auxiliar de aproximadamente ese tamaño en una carpeta privada con umask 077. No descarga WordPress, backups, SQL ni dependencias; no inicia Docker. La salida contiene solo metadatos. No se ejecutó SSH desde el agente.

Alex ejecuta:

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-check-operator.sh
```

Recoger desde `EDITORIAL_OPERATING_OPERATOR_CHECK_V1` hasta `END_EDITORIAL_OPERATING_OPERATOR_CHECK_V1`. Si falta el marcador final o aparece error, detenerse y revisar la salida. El wrapper exige resultado explícito incluso si WP-CLI devuelve exit 0 tras un fatal.

## Verificación local y límites

Pasan 10 casos sintéticos: éxito, conteo inesperado, hash incorrecto, inspecciones distintas, cambio entre observaciones y admisión/rechazo de consultas de lectura/escritura. También pasan la sintaxis del helper completo generado y del shell, y una simulación del wrapper con fatal/exit 0 sin falso marcador final. No se suman como integración real WordPress/MariaDB ni sustituyen el resultado remoto.

## Qué falta para cerrar

1. Recibir y evaluar este resultado de separación CLI, auditoría y conteos reales.
2. Verificar privacidad frente a otro dueño autenticado por HTTP en un contexto autorizado. No crear credenciales ni usuarios solo para completar una casilla sin definir ese contexto.
3. Para certificar edición pública: presentar el cambio deseado concreto, obtener aprobación independiente de una revisión vigente y comprobar aplicación y recuperación reales. «Continuar» no aprueba el texto público; la revisión 1 retenida es evidencia histórica y no debe regenerarse por demostración.

La diferencia histórica de postmeta sigue como incidencia aparte: no hay valores anteriores ni autor identificados. No puede estimarse su resolución sin evidencia nueva. Tras este comando quedan dos ámbitos funcionales (otra identidad HTTP y ciclo público autorizado), que pueden necesitar varias ejecuciones; no hay una cifra exacta de iteraciones justificable todavía. Exportación privada y retención no se convierten en tareas nuevas sin necesidad concreta.
