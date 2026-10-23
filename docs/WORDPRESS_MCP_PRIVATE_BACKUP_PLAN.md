# Respaldo privado previo a instalación — decisión de Alex22oct

Estado actualizado: Alex creó el respaldo real y la restauración básica aislada ya pasó. Ver [QA de restauración](qa/MCP_PRIVATE_RESTORATION_2026-10-22.md). Conservar el archivo original; la aceptación funcional del perfil completo y el esquema exhaustivo siguen pendientes antes de instalar. Los pasos históricos de preparación siguientes no significan que haya que repetir la exportación.

Destino elegido por Alex: **disco externo o carpeta privada de su equipo**. Conservar hasta validar instalación y restauración; luego decidir. No TTL, tarea de eliminación ni30 días heredados de contacto. El agente sigue sin ejecutar SSH; Alex ejecuta comandos revisables y comparte únicamente metadata/resultados saneados, nunca dumps, credenciales o contenido privado.

## Estado actual: coordinador preparado, respaldo real pendiente

Alex completó el preflight a23:12:18UTC del22oct: cliente MariaDB11.8.9, servidor11.8.9-MariaDB-log y37 tablas InnoDB. Triggers/rutinas/eventos visibles0, sin certificar privilegios/objetos ocultos. [Captura saneada](qa/MCP_BACKUP_PROFILE_2026-10-22.json). También hay deriva frente a21oct: PHP8.2.34→8.5.11, Hostinger3.0.79→3.0.80, Polylang3.9.0→3.9.1, WPGraphQL2.24.2→2.25.0 y GraphQL IDE5.6.0→5.7.0. Los ensayos previos conservan su alcance histórico; no se atribuyen al perfil nuevo. Hay que revalidarlo antes de instalar/aplicar.

Implementado `scripts/editorial-private-backup.py` y helpers en `wordpress/operations/editorial-private-backup.{sh,php}`.21 pruebas locales correctas con transporte simulado y fixtures privados, lint PHP/bash y payload renderizado. El agente solo ejecutó tests y dry-run; no SSH ni backup real. [QA y límites](qa/MCP_PRIVATE_BACKUP_COORDINATOR_2026-10-22.md).

### Comando que Alex ejecuta en su terminal

Este comando **sí crea y descarga un respaldo privado**. No instala el candidato ni modifica roles/permisos:

```sh
python3 /Users/Alex/Desktop/AlexC/alexcuadra.dev/scripts/editorial-private-backup.py
```

Destino por defecto `~/PrivateBackups/alexcuadra.dev/FECHA_UTC-ID/`. Para disco externo, sustituir el destino con una ruta absoluta real:

```sh
python3 /Users/Alex/Desktop/AlexC/alexcuadra.dev/scripts/editorial-private-backup.py --destination "/Volumes/NOMBRE_REAL/PrivateBackups/alexcuadra.dev"
```

No ejecutar literalmente NOMBRE_REAL. El programa exige volumen montado, destino fuera del repo y soporte de permisos privados. `--dry-run` muestra el plan sin SSH/creación. No fija ni cambia permisos de carpetas públicas existentes: rechaza un destino preexistente que no sea privado/propio.

Compartir **solo** la línea `EDITORIAL_PRIVATE_BACKUP_RESULT=...`; nunca el archivo, SQL, wp-config ni `transfer.stderr.log`. Si falla conserva `.incomplete` y log privado, sin declararlo respaldo válido; se puede compartir la etapa genérica `EDITORIAL_BACKUP_FAILED_STAGE` del log, sin su contenido restante. No borrarlo ni limpiar directorios amplios para reintentar. Cada ejecución crea una carpeta nueva y no sobreescribe respaldos previos.

### Contrato y límites del coordinador

- Directorios nuevos0700 y archivos0600, comprobados antes de SSH. Umbral8GiB en volumen de destino; límite2GiB comprimidos/3GiB expandidos, fuente seleccionada≤2GiB y SQL≤512MiB. La captura local42GiB no certifica capacidad de un disco externo. Rechaza symlinks, dispositivos/enlaces en el tar, miembros repetidos/rutas inseguras y bits especiales. No extrae ni ejecuta PHP del respaldo al verificar.
- SSH solo cuando Alex ejecuta modo de creación; hostkey estricta y autenticación existente, no contraseñas en argv. Payload fija el perfil observado: si cambian PHP/plugins/motores/tablas/esquema se detiene en lugar de exportar silenciosamente otro perfil. WP-CLI omite plugins normales y temas, aunque MU/drop-ins continúan en bootstrap; no certifica todos los efectos de terceros.
- Cliente mysqldump desde shell evita proc_open de PHP. Opciones de conexión solo en archivo temporal0600 dentro de directorio0700 fuera de public_html. Dump con single-transaction/quick/hex-blob y solicitud explícita de rutinas/eventos/triggers; falta de privilegios/fallo del cliente aborta. Visibilidad0 por sí sola sigue sin certificar objetos ocultos. Temporal remoto limitado a SQL/metadatos, no duplica plugins/medios. Trap borra solo su directorio nuevo al terminar/error/señales capturables; SIGKILL o caída del host pueden dejarlo, identificado en el log privado para revisión puntual.
- Copia archivos raíz de WordPress (incluido **wp-config con secretos**), core wp-admin/wp-includes, plugins/MU/temas/medios/idiomas y archivos regulares de wp-content salvo debug.log. Excluye otros directorios raíz y directorios no seleccionados de wp-content, como caches/backups/upgrades. No es snapshot de todo el hosting ni preserva ACL/propiedad/hardlinks como identidad: hardlinks se guardan como archivos regulares. El archivo contiene datos privados; permisos no equivalen a cifrado añadido por el programa.
- Huellas de archivos antes/después de exportar/transmitir y esquema normalizado antes/después del dump. No bloquea ediciones ni despliegues del CMS; coordinar ejecución sin cambios de archivos/DDL. La transacción SQL no hace atómica la copia global de archivos+DB. Cambios detectados o error de tar mantienen el archivo incompleto.
- Verifica gzip completo/CRC, contenido/tamaño/SHA256 de todos los miembros, SQL y manifiesto, marcador remoto final y exit0. Renombra a wordpress.tar.gz solo después. Es evidencia de transferencia/integridad, **no restauración verificada**. Guarda verification.json privado; sin expiración/borrado automático.

El preflight ya se completó; no hace falta repetirlo sin cambios. Próximo: Alex ejecuta la creación y devuelve solo el resumen; luego se diseña/restaura en entorno aislado sin arrancar configuración/servicios reales, además de aceptar PHP8.5/WPGraphQL2.25/perfil de hooks actualizado. Instalación continúa HOLD.
