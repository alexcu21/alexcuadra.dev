# Aceptación de propuestas: preparación y ejecución incompleta

**No aceptación aprobada.** Candidato0.1.0-rc.1/ZIP SHA256 `cd990139d7915567d76012e53060def452e8f2b43796918415d5b1f82da38e14` intacto; no instalar. [Arnés/procedimiento y límites](../../tests/integration/wordpress-mcp-proposals/README.md).

## Hechos de esta sesión

- Espacio local:31GiB libres/93% al inicio y al cierre; arnés≈48KiB. No datos del CMS descargados, imágenes nuevas ni SSH por el agente.
- Docker inicialmente apagado. Alex lo abrió a petición; el acceso al socket necesitó permisos normales del sandbox. Se ejecutó el runner con imágenes ya presentes, proyecto independiente/red interna/sin puertos.
- El seed `alexcuadra-editorial-application-lab_wp-data` conserva nombre/etiqueta, pero la inspección solo lectura mostró0KiB y los seis directorios de plugins ausentes. Es tmpfs desmontado: no es un backup persistente. No se eliminaron ni reconstruyeron laboratorios anteriores.
- El runner falló en seed, antes de core install/activación/migración/scopes. finally detuvo wordpress/db del proyecto de aceptación. No hubo marcador final de aceptación ni dump/restauración. Contenedores/volúmenes conservados; no down -v/prune/reset.
- Intento acotado de obtener fuentes públicas: primero bloqueo de red del sandbox, luego Python sin CA bundle, corregido usando `/etc/ssl/cert.pem` con verificación TLS activa. El GET a `https://downloads.wordpress.org/plugin/polylang.3.9.0.zip` devolvió404. La caché temporal quedó vacía0B. No se sustituyeron versiones ni se atribuyó aceptación a otro perfil.

`docker system df` reportó imágenes20,61GB, volúmenes31GB y0 contenedores activos después del intento. Son cifras agregadas de todo Docker, no crecimiento de este ensayo ni prueba de disponibilidad de datos en tmpfs. No sumar directamente a du/df ni podar por marcar reclaimable.

## Trabajo entregado y verificado localmente

Compose fija imágenes existentes WP7.1.3/PHP8.2/MariaDB11.8, redes internas, seed sin red y mounts tmpfs512+768+64MiB. Runner rechaza Docker remoto, imágenes ausentes, seed ajeno, puertos y proyecto previo; verifica ZIP/manifiesto/fuentes antes de copiar. Tras el fallo se añadió comprobación de contenido del seed **antes de crear el proyecto**. No se reejecutó el runner para aparentar progreso sin dependencias.

Adaptador transforma únicamente los tests existentes check/history/quotas en archivos temporales, manteniendo originales y candidato sin cambios. Fixture bloquea HTTP WP y correo; configura CPT/ACF sintéticos. Nuevo ensayo before/after prevé digest de filas/DDL/versión, dump consistente privado sintético en tmpfs y restauración en DB separada con replays/cuotas sin cambios. Es código preparado, **no un resultado de migración/restauración**.

Verificación realizada: modelo Compose JSON interno/sin puertos/seed aislado/mount candidato solo lectura; AST de dos Python, lint de tres PHP y sh; adaptación real de tres suites en directorio temporal y lint de los tres PHP resultantes; diff whitespace y hash ZIP intacto. No se ejecutaron las aserciones de esas suites contra WordPress, ni el fixture de archivo. Las95 comprobaciones de stubs anteriores no se repitieron ni se cuentan como nueva aceptación.

## Siguiente dato necesario

Confirmar versión instalada ante discrepancia con la disponibilidad pública, usando solo metadata. Alex ejecuta, agente no hace SSH:

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'wp --path=domains/alexcuadra.dev/public_html/content --skip-plugins --skip-themes plugin get polylang --fields=name,version,status --format=json'
```

Sin dump, copias o modificación de permisos; solo salida textual. skip-plugins/themes no elimina bootstrap MU/drop-ins. Con ese dato se resolverá la procedencia exacta del artefacto; no modificar a ciegas versiones fijadas. Antes de reanudar inspeccionar recursos del intento parcial y decidir conservación/reutilización explícita sin borrar datos ajenos. Cualquier caché pública nueva deberá conectarse y verificarse antes de usarla como seed; el downloader auxiliar no es un bootstrap automático.

Siguen pendientes: aceptación CLI/SQL/restauración sintética completa, HTTP/MCP/GraphQL/carreras, perfil real y respaldo/archivo reales restaurados, destino/responsable/retención y aprobación concreta. Contacto/Workers/CMS compartido sin cambios.
