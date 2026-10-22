# Respaldo privado previo a instalación — decisión de Alex22oct

Destino elegido por Alex: **disco externo o carpeta privada de su equipo**. Conservar hasta validar instalación y restauración; luego decidir. No TTL, tarea de eliminación ni30 días heredados de contacto. El agente sigue sin ejecutar SSH; Alex ejecuta comandos revisables y comparte únicamente metadata/resultados saneados, nunca dumps, credenciales o contenido privado.

## Propuesta concreta de almacenamiento

Carpeta por defecto para el futuro coordinador local: `~/PrivateBackups/alexcuadra.dev/`, fuera del repositorio y de Documents/Desktop sincronizados por defecto. Se podrá sustituir por una ruta absoluta en disco externo elegida por Alex. Todavía no se ha creado la carpeta ni se asume su cifrado o política del dispositivo. Al crearla: directorios0700, archivo SQL/bundle y manifiesto privado0600, nombres únicos, sin sobrescribir respaldos anteriores. Rechazar destino dentro del repositorio/webroot o symlinks inesperados; comprobar espacio del volumen de destino en ese momento, no reutilizar los42GiB del volumen actual para un disco externo.

Último tamaño observado del CMS≈690,5MiB de archivos+DB física; no es tamaño prometido del SQL/archivo comprimido ni del respaldo restaurado. Reservar margen para bundle, extracción y copia restaurada además de Docker. El ensayo sintético anterior usó SQL12,25MiB, no extrapolar su tamaño al dump real. La caché pública56MiB no es parte del respaldo y puede desaparecer de tmp.

## Siguiente paso: prerrequisitos de hosting, sin respaldo aún

WP-CLI db query falló por proc_open/proc_close deshabilitadas. Un cliente mysqldump/mariadb-dump invocado por el shell puede funcionar sin esas funciones PHP; hay que comprobar disponibilidad/versión y motores antes de diseñar la exportación exacta. No habilitar proc_open ni improvisar un exportador SQL parcial.

Alex ejecuta:

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-backup-readiness.sh
```

El script consulta herramientas locales/versiones, motores y tipos agregados de tablas, objetos DB visibles y metadata de plugins; no exporta filas, secretos, nombres de DB/usuarios/host ni crea archivos. MYSQL visibilidad0 no certifica privilegios ni ausencia de objetos ocultos. Se omiten plugins normales/temas al bootstrap WP-CLI, pero MU/drop-ins siguen cargándose: no es una certificación exhaustiva de efectos de terceros. Revisar prefijo y marcador final, errores y JSON, sin leer configuración privada al chat.

## Coordinador de respaldo que se preparará con esos resultados

Transferencia SSH ejecutada **por Alex** hacia carpeta local privada; si se usa staging temporal remoto será privado, fuera de public_html, con cleanup de credenciales efímeras y fallo explícito. Nunca contraseña en argumentos/URL/salida; no solicitar DB_PASSWORD al usuario ni guardar config en Git. Si un cliente externo necesita credenciales, diseñar archivo de opciones temporal0600 dentro de directorio0700 y cleanup acotado, sin modificar configuración WordPress. No asumir todavía soporte de host/socket/opciones de cliente: depende del diagnóstico.

Exportar DB coherente con motores/privilegios admitidos, archivo de plugins/MU/temas/medios y configuración ACF/CPT necesaria; decidir explícitamente tratamiento protegido de wp-config y otros secretos para restaurar sin activar servicios reales. Detectar fallos del cliente y del compresor con pipefail, guardar descargas incompletas como tales, verificar tamaño/hash e integridad de archivo y no afirmar éxito solo por gzip válido. DB transaccional no vuelve atómica una copia de archivos concurrente; coordinar ventana sin despliegues/cambios de esquema y registrar huellas/versiones.

Preparar destino aislado de restauración con correo/webhooks/cron/actualizaciones/red externa bloqueados **antes del primer bootstrap**; nunca reemplazar el CMS compartido para ensayar. No copiar credenciales de producción a una app conectada a Internet. Comparar esquema/filas/perfil/relaciones/ACF y permisos tras restaurar. Conservar manifest privado con versiones/fecha/hashes, rutas/identificadores internos separados de cualquier evidencia pública saneada. Dump real puede incluir usuarios, hashes y opciones privadas: no se añade al repo/chat ni al resultado QA.

Este documento fija decisiones y controles del procedimiento; **no es una implementación del coordinador ni un backup/restauración realizados**. Una vez resueltos prerrequisitos se entrega comando concreto y revisable de creación para Alex. Instalar/conceder scope sigue siendo otra puerta posterior con paquete/hash/scopes precisos y aprobación concreta.
