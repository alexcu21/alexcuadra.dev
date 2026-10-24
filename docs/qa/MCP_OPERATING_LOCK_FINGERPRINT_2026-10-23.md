# Prueba acotada de una sola renovación de lock

8 casos sintéticos pasan en PHP8.5.11 local. [Recibo](MCP_OPERATING_LOCK_FINGERPRINT_2026-10-23.json). Comando `php tests/integration/wordpress-source-effects/lock-fingerprint.php`. Sin WordPress/DB/Docker/SSH/descargas/backups nuevos. Se probó la función pura utilizada por el script; no se presenta como prueba nueva del hosting ni del circuito WordPress completo. Helpers/wrapper usan la misma lectura protegida y baseline privada de los diagnósticos anteriores. Sintaxis PHP/shell y diff --check correctos; ZIP rc.3 sin cambios.

1. Tabla ya coincidente: no inventa una diferencia.
2. Una sola hora de `_edit_lock` cambia, con el mismo usuario: reconstruye el hash exacto original y devuelve el ID correcto, sin valor/usuario/tiempo.
3. Cambio adicional de contenido: no certifica una única diferencia de lock.
4. Dos locks cambiados: resultado inconcluso.
5. Usuario del lock cambiado: inconcluso para esta hipótesis de mismo usuario.
6. Hora anterior fuera de la ventana: inconcluso, sin ampliar el intervalo.
7. Presupuesto agotado: inconcluso, sin declarar coincidencia.
8. Entrada inmutable, ningún valor privado exportado ni escritura/aceptación/autor atribuido.

Se contrasta el mismo hash que la baseline: serialize de filas completas, strings ordenados con SORT_STRING y sha256. Los valores de prueba son sintéticos. El algoritmo reemplaza solo un valor en una copia en memoria y exige coincidencia de toda la tabla; no borra ni ignora `_edit_lock`.

Límites de entrega:4 locks como máximo,601 marcas de tiempo candidatas por lock, separadas por1segundo en una ventana de600segundos,512MiB acumulados de entrada a hash/5segundos de búsqueda,5000filas/16MiB de valores. El mtime de la baseline es solo un ancla para seleccionar candidatos. La selección admite locks con timestamp entre600segundos antes y3600después del ancla. Un resultado negativo no excluye otras causas/horas/cambios múltiples. Una coincidencia no identifica al escritor ni demuestra que fuese WordPress core.

Referencia local revisada: core del respaldo verificado, `wp-admin/includes/post.php` función wp_set_post_lock y `wp-admin/includes/misc.php` wp_refresh_post_lock. Eso prueba el mecanismo posible, no la causa del incidente. Alex respondió que no tenía pantalla de edición abierta. No atribuirlo a su navegador ni cerrar la incidencia sin resultado real.

La aceptación anterior de almacenamiento sigue completada. Este diagnóstico no pide repetir grants/aceptación/pausa de esa auditoría, no muta datos y no sustituye baseline. Solo añade un helper pequeño privado en el servidor. Equipo32GiB libres redondeados; no se iniciaron laboratorios ni se borraron recursos/respaldo.
