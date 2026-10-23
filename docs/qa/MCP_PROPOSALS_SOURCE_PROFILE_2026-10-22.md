# rc.2 y perfil de plugins actualizado — 22 octubre 2026

Resultado: **288 comprobaciones correctas** con los plugins exactos del respaldo privado
restaurado y datos exclusivamente sintéticos. Instalación real permanece HOLD hasta revisar
compatibilidad de MU/tema/drop-ins/configuración del hosting y preparar aprobación concreta.

Candidato rc.2 sin cambios, SHA256
`c31e6814864be0692035de7b46322fc2fc0159d2104e0b4d25404c3b15859f07`.
Procedencia del código: respaldo SHA256
`13f20d924a5d161f28444fabf24adbcba40f1c7bbb8951a57c1e4a00f9541d62`, previamente restaurado.
Se comprobaron9590 archivos/160287119B contra su manifiesto privado antes de seed.

## Perfil y cobertura

Inventario de18 plugins originales,13 activos y5 inactivos, coincide con el preflight de Alex.
Se comprueban versiones y estado antes/después de todas las pruebas, inclusión de cada
archivo principal activo y exclusión de los inactivos. Se añade únicamente Proposals rc.2
a los activos. Core de referencia WP7.1.3/PHP8.5.11; MariaDB de imagen11.8 fijada por digest.

Incluye WPGraphQL2.25.0, IDE5.7.0, Hostinger3.0.80, onboarding3.0.5, Reach1.8.7,
LiteSpeed7.9.2, beta-tester4.0.1, Editorial Read0.1.0, Polylang3.9.1, Adapter0.7.1,
ACF6.8.10, WPGraphQL ACF3.0.0 y WPGraphQL Polylang0.7.1 en su carpeta original.
El adaptador modifica únicamente expectativas de versión/carpeta en los tests, no el plugin.

- 67 permisos/framework/idiomas/patch y fuentes preservadas.
- 44 historial/restore privado, deriva de fuente, límites y fallos SQL.
- 37 idempotencia/cuotas/rollback/fallos SQL.
- 86 HTTP/MCP: autenticación, sesiones, permisos mínimos, privacidad, reintentos, revocación,
  desactivación/reactivación y concurrencia. Dos carreras update/update y update/restore
  tienen resúmenes directos de dos workers/solapamiento. Cuatro casos de admisión comprueban
  workers independientes, duplicados create/update, clave reutilizada y último cupo. Sus
  assertions pasan; el resultado público conserva sus nombres de casos, no los cuatro
  resúmenes de admisión adicionales del arnés original. No afirmar que el JSON los contiene.
- 54 GraphQL/ACF/Polylang: CPT privado oculto por node/contentNode/colecciones para dueño,
  otro y administrador; preparación no concede edición genérica. Fuentes conservadas salvo
  deriva deliberada introducida/restaurada por fixtures; credenciales/scopes sintéticos retirados.
- 34 tests locales de backup/aislamiento/verificador correctos, incluidos siete nuevos de
  procedencia y rechazo de archivos alterados/extra/omitidos/enlaces/FIFO/deriva de perfil.

## Aislamiento y límites

No SSH, acceso al CMS, nuevas imágenes, descargas de plugins o exportación privada adicional.
Plugins originales montados solo lectura en seed; no SQL ni configuración original ejecutados.
Docker internal:true confirmado por inspección, sin puertos publicados; wordpress/db exited.
Guard HTTP permite únicamente http://wordpress interno, bloquea HTTP externo y correo;
cron/updaters/file mods desactivados antes de activar plugins. HTTPS se simula en fixture,
no hay prueba TLS real. El paquete y permisos reales permanecen intactos.

No se importaron opciones/contenido/usuarios de producción. ACF/CPT se registran con fixture
y exportación local conocida. No se cargaron MU/tema/drop-ins del respaldo. Plugins que
omitan ramas por WP-CLI, entorno local, detección del proveedor u opciones ausentes no quedan
certificados en esas ramas. No equivale a reproducir el servidor LiteSpeed/Hostinger real.

Perfil instalado combina plugins del respaldo con las opciones sintéticas creadas durante
la activación. La copia original de archivos/SQL se conserva y su restauración básica ya pasó;
esta suite no repite su roundtrip. No sumar los17 checks de archivo de la regresión anterior
a los288 de este ensayo.

Consumo:153MiB lógicos de plugins copiados a tmpfs ya configurado (WP768MiB, DB512MiB,
archivo64MiB), sin nueva copia privada en host. RAM/swap/capas de Docker añaden consumo.
Libre antes38369464320B; después de pruebas38371049472B, con captura posterior en resultado.
Estos cambios de espacio del sistema no equivalen a una medición exclusiva del ensayo.
Logs/metadatos privados quedan fuera del repo en su carpeta0700/0600; sin borrados programados.

Siguiente: revisar MU/tema/drop-ins/configuración real y preparar una instalación revisable
sin ejecutar SSH. El usuario ejecutará cualquier comando remoto que resulte necesario.

[Resultado público limitado](MCP_PROPOSALS_SOURCE_PROFILE_RESULT_2026-10-22.json).
