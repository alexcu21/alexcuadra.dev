# Ensayo de fuente y efectos WordPress/MariaDB

Ejecutar `PYTHONDONTWRITEBYTECODE=1 python3 tests/integration/wordpress-source-effects/run.py` con Docker local e imágenes cacheadas. No ejecuta SSH. Verifica contra el manifiesto privado los624 archivos ACF y320 LiteSpeed antes de copiarlos a una instalación sintética nueva. No importa SQL, configuración o contenido real. El origen `http://wordpress` existe solo en la red interna, sin puertos host ni red externa.

Capacidad: mínimo8GiB libres; imágenes existentes, volúmenes temporales tmpfs512MiB DB y768MiB WP. Estos pueden consumir RAM/swap. Solo pequeñas definiciones/logs privados en /private/tmp, no copias de archivo ni del CMS del respaldo. Detiene únicamente su proyecto en finally; no elimina contenedores/volúmenes previos, prune ni respaldos. No repetir el ensayo sin cambios, fallos o cobertura pendiente que lo justifique.

WP7.1.3/PHP8.5, MariaDB11.8.9, ACF6.8.10 y LiteSpeed7.9.2.58 checks: cuerpos/tagline por APIs reales, fuente/aprobación de fixture/recibo/intención en la misma conexión; rollback del cuerpo/meta/aprobación y caché fresca, fallo al insertar intención, replay, consumidor posterior a COMMIT con tags nativos, tagline sin transición, hook intacto ante éxito/excepción/anidamiento y llamadas ajenas preservadas. Esquema/versión/caché externo/transacción externa rechazados; consumidores independientes, tokens caducados, límites8 y errores de purga sin repetir fuente. El fallo de inserción se induce con una fila huérfana de fixture desde otra conexión: se prueba que no puede ejecutarse sin evento confirmado.

Los tests no crean ni activan plugin editorial, usuarios de CMS real, capabilities o Application Passwords. La aprobación es una fila de fixture, no el autorizador final. El runner valida que el contador de checks sea al menos45 para no aceptar por error un contador local de eval-file con valor0.

Resultado y logs quedan en el directorio privado anunciado. Solo result.json contiene metadatos publicables. La primera ejecución paró por un indicador de caché null (resuelto comprobando backend core); el contador inicialmente local a eval-file se corrigió antes de aceptar el informe. No se publica contenido ni logs privados en Git.

Límites: CLI con dos plugins y opciones sintéticas; no perfil completo ni propagación Polylang, HTTP ni entrega de caché del hosting. Las pruebas de proceso muerto existentes son SQLite, no MariaDB. No afirmar atomicidad del caché externo ni certificación de otras callbacks de plugins a partir de este ensayo.

## Extensión HTTP y cortes de procesos

El runner también ejecuta `transport.php`:46 comprobaciones adicionales sobre Apache HTTP real dentro de la red Docker. El fixture de rutas solo se carga con `AC_SOURCE_EFFECTS_HTTP=synthetic-v1`, opt-in/local y Host wordpress; usa REST y Application Passwords de usuarios sintéticos. Anónimo/subscriber no pueden ejecutar. No es una ruta de producción ni el autorizador final: manage_options aquí solo controla escenarios de fixture y nunca se registra una ability.

`http-fixture.php` permite únicamente casos/IDs sintéticos previamente creados y fases cerradas. No acepta PHP/SQL/parches arbitrarios. Se consulta mediante `?rest_route` sin depender de reglas de reescritura. El guard HTTP solo admite http://wordpress:80 sin userinfo y bloquea lo demás; no hay puertos host ni TLS público. Los helpers comunes se movieron a support.php para ejercitar el mismo lector/escritor en CLI y HTTP.

Cuatro SIGKILL reales a workers Apache: antes/después de COMMIT y antes/después de API de purga. Un marcador independiente confirma el checkpoint antes del corte; la respuesta HTTP fallida por sí sola no basta. MariaDB revierte fuente completa/intención/counter no confirmados y conserva lo confirmado. Un contador transaccional separado comprueba que replay y recuperación del consumidor no vuelven a ejecutar el writer. Se expira únicamente el lease de fixture en SQL para retomar sin esperar30s. Dos llamadas de invalidación tras perder acknowledgment después de invocar la API prueban explícitamente entrega al menos una vez, no exactamente una vez.

Se comprueba cabecera X-LiteSpeed-Purge en la respuesta real del consumidor; fuente normal/rollback no emiten purga. Tags/headers permanecen sin cambios dentro de la escritura. Las colas/tags generadas al crear fixtures se limpian antes de medir las peticiones, solo en este laboratorio. No se certifica entrega de caché del hosting ni perfil completo; esos son los límites pendientes de esta extensión.
