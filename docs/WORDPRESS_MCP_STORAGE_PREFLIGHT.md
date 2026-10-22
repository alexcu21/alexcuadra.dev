# Espacio antes del candidato operativo — 21 octubre 2026

El siguiente entregable sigue siendo el candidato de seis abilities de propuestas privadas, sin apply/recover. Este paso prepara la medición para su respaldo/restauración; no constituye el candidato ni autoriza instalación.

## Medición local

`df -h .`: volumen Data de 460 GiB, 430 GiB usados, 5,7 GiB disponibles, 99 % según el sistema. `du -sh`: proyecto 574 MiB, node_modules 491 MiB y dist 5,1 MiB. Las cifras de du son anidadas, no se suman. Son una captura puntual; el espacio disponible puede variar.

Código, manifiestos y un ZIP pequeño requieren poco espacio frente a una copia del CMS, pero todavía no hay tamaño medido del candidato. No instalar dependencias, generar builds ni levantar Docker para esta medición. No descargar dumps, medios ni una copia del hosting hasta conocer sus tamaños y elegir destino de restauración con capacidad suficiente. No eliminar datos ni volúmenes existentes.

## Comando que ejecuta Alex

Desde su terminal local; el agente no ejecuta SSH:

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-storage-preflight.sh
```

El script lee tamaños del servidor con df/du y hace un SELECT agregado de information_schema mediante WP-CLI. No crea un respaldo, copia archivos, modifica contenido ni concede scopes. Transmite un script de aproximadamente 1 KiB por stdin y muestra texto; no guarda resultados localmente salvo historial/scrollback de la terminal. du puede tardar según el número de archivos y lee todo el árbol del CMS. No inspecciona archivos de configuración ni muestra credenciales o contenido de la base de datos. El entorno de WP-CLI puede emitir diagnósticos ajenos: revisar la salida antes de compartirla.

Comprobar prefijo y marcador final, errores y las cifras completas. La suma de data_length/index_length es aproximada: no predice exactamente un dump SQL ni un archivo comprimido. df no certifica cuota de la cuenta del proveedor. No demuestra existencia, antigüedad o restaurabilidad de backups; eso sigue pendiente, junto con configuración ACF/CPT y plugins/MU.

Con la salida se decidirá dónde guardar y ensayar restauración, considerando respaldo, extracción, base restaurada, imágenes de contenedor y margen de trabajo. Hasta entonces no ejecutar una copia local de tamaño desconocido. No se fija retención ni se elimina historial editorial.

## Resultado aportado por Alex — 22 octubre

Archivos: 701956 KiB (685,5 MiB); plugins224660 KiB, MU56 KiB, temas74156 KiB y uploads50100 KiB. Componentes incluidos en el total. df muestra el volumen compartido al45%, no la cuota disponible de la cuenta. La consulta DB falló porque wp db query usa Process::run y el hosting deshabilita proc_open/proc_close; no se obtuvo marcador final ni tamaño DB. No es necesario habilitar esas funciones.

Corregido el operador para usar SELECT mediante $wpdb en wp eval-file por stdin, omitiendo plugins normales y temas. Los MU/drop-ins y bootstrap de WordPress pueden seguir ejecutándose; no se afirma ausencia absoluta de efectos de terceros. Solo imprime agregados, sin errores SQL detallados. Opción --database-only evita repetir du:

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s -- --database-only' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-storage-preflight.sh
```

## Medición completada — 22 octubre

Alex ejecutó la variante --database-only y aportó ambos marcadores y JSON: 37 tablas, 3866624 bytes de datos, 1376256 bytes de índices, total5242880 bytes (5 MiB). El total de archivos más DB aproximada es724045824 bytes (690,5 MiB). La DB comprende todas las tablas de esa base, no solo las de un prefijo. No implica un respaldo coherente ni una restauración verificada.

Nueva captura local `df -h .`: 29 GiB disponibles, 94% de uso; la captura anterior de5,7GiB ya no representa el espacio actual. El agente no borró archivos. Una copia de archivos equivalente al tamaño medido más DB ocuparía aproximadamente0,67GiB antes de considerar diferencias entre bloques asignados y bytes archivados. Guardar además una copia extraída requiere otro volumen parecido; una restauración SQL y Docker tienen consumos adicionales todavía no medidos. No garantizar espacio exacto ni proporción de compresión. Los29GiB actuales eliminan el impedimento inmediato de la primera captura para preparar código/ZIP pequeños; no se han descargado datos ni iniciado contenedores.

Decisión: preflight de tamaño cerrado. Continuar con el candidato local de seis abilities, conservando apply/recover fuera del paquete. Respaldo privado, exportación/archivo y ensayo de restauración siguen siendo condiciones antes de instalar. No pedir más SSH solo para repetir esta medición; los próximos comandos deben corresponder al procedimiento concreto de respaldo que acompañe al candidato revisable.

Estado: sintaxis sh/PHP comprobada localmente y consulta corregida ejecutada por Alex con salida completa. Sin SSH por el agente, Docker, instalación o cambios al CMS. El comando no descarga respaldo ni guarda resultados en archivos locales.
