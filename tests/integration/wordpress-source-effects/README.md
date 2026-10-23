# Ensayo de fuente y efectos WordPress/MariaDB

Ejecutar `PYTHONDONTWRITEBYTECODE=1 python3 tests/integration/wordpress-source-effects/run.py` con Docker local e imágenes cacheadas. No ejecuta SSH. Verifica contra el manifiesto privado los624 archivos ACF y320 LiteSpeed antes de copiarlos a una instalación sintética nueva. No importa SQL, configuración o contenido real. El origen `http://wordpress` existe solo en la red interna, sin puertos host ni red externa.

Capacidad: mínimo8GiB libres; imágenes existentes, volúmenes temporales tmpfs512MiB DB y768MiB WP. Estos pueden consumir RAM/swap. Solo pequeñas definiciones/logs privados en /private/tmp, no copias de archivo ni del CMS del respaldo. Detiene únicamente su proyecto en finally; no elimina contenedores/volúmenes previos, prune ni respaldos. No repetir el ensayo sin cambios, fallos o cobertura pendiente que lo justifique.

WP7.1.3/PHP8.5, MariaDB11.8.9, ACF6.8.10 y LiteSpeed7.9.2.58 checks: cuerpos/tagline por APIs reales, fuente/aprobación de fixture/recibo/intención en la misma conexión; rollback del cuerpo/meta/aprobación y caché fresca, fallo al insertar intención, replay, consumidor posterior a COMMIT con tags nativos, tagline sin transición, hook intacto ante éxito/excepción/anidamiento y llamadas ajenas preservadas. Esquema/versión/caché externo/transacción externa rechazados; consumidores independientes, tokens caducados, límites8 y errores de purga sin repetir fuente. El fallo de inserción se induce con una fila huérfana de fixture desde otra conexión: se prueba que no puede ejecutarse sin evento confirmado.

Los tests no crean ni activan plugin editorial, usuarios de CMS real, capabilities o Application Passwords. La aprobación es una fila de fixture, no el autorizador final. El runner valida que el contador de checks sea al menos45 para no aceptar por error un contador local de eval-file con valor0.

Resultado y logs quedan en el directorio privado anunciado. Solo result.json contiene metadatos publicables. La primera ejecución paró por un indicador de caché null (resuelto comprobando backend core); el contador inicialmente local a eval-file se corrigió antes de aceptar el informe. No se publica contenido ni logs privados en Git.

Límites: CLI con dos plugins y opciones sintéticas; no perfil completo ni propagación Polylang, HTTP ni entrega de caché del hosting. Las pruebas de proceso muerto existentes son SQLite, no MariaDB. No afirmar atomicidad del caché externo ni certificación de otras callbacks de plugins a partir de este ensayo.
