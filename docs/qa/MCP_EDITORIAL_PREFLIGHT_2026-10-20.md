# MCP editorial: revisión previa y propuesta de instalación —20 octubre2026

## Inspección real mediante el conector

- Descubrimiento: siguen3 definitions core (`get-site-info`, `get-user-info`, `get-environment-info`), sin las abilities propias del paquete0.1.0 ni herramientas de instalación/gestión de permisos.
- Schema de `core/get-user-info` inspeccionado: admite campos de perfil/roles, no capacidades. Se pidió exclusivamente `fields=[id,roles]`; devolvió usuario2 y subscriber. No se consultó el perfil completo ni credenciales.
- Schema de `core/get-environment-info` inspeccionado y ejecución limitada a `wp_version`/`php_version`: `Permission denied`. No se reintentó ni se amplió el usuario para leer versiones.
- Con estas herramientas **no puede certificarse la ACL/capacidades efectivas ni instalarse el paquete**. Las versiones de laboratorio no se atribuyen al sitio real. No se interpreta subscriber como prueba de ausencia de capacidades adicionales.

Alex proporcionó la conexión SSH. Se conectó con BatchMode y StrictHostKeyChecking, usando la autenticación existente, sin solicitar/leer/imprimir claves ni contraseñas. WP-CLI disponible y raíz CMS localizada en `domains/alexcuadra.dev/public_html/content`. El diagnóstico se transmitió por stdin, sin copiar archivos/instalar nada. `/dev/stdin` falló con un warning antes de ejecutar el diagnóstico, aunque WP-CLI devolvió0; no se contó como éxito. Se usó el soporte oficial `eval-file -`, verificando siempre prefijo JSON y aserciones además de exit status.

## Preparado y validado

[Runbook de instalación propuesta/reversión](../WORDPRESS_MCP_READ_INSTALL.md), con cuenta observada2 y capacidad `alexcuadra_read_public_projects` por usuario. Paquete0.1.0/ZIP conservados, sin cambios de fuente ni permisos automáticos.

[Diagnóstico operativo WP-CLI](../../wordpress/operations/editorial-read-preflight.php), separado del plugin, solo lectura. Comprueba contexto del sitio, usuario, permisos almacenados/evaluados, dependencias y fases antes/después. No instala ni concede capacidades, nunca consulta Application Passwords, perfiles completos, contenido/ACF ni opciones privadas. `manualAclReviewRequired=true`/`installationAuthorized=false` evitan presentar un resultado técnico como aprobación o auditoría completa.

Se validó en el mismo Docker aislado, sin puertos del host, montando únicamente el nuevo directorio operativo como solo lectura. [Harness sintético](../../tests/integration/wordpress-mcp-read/preflight-check.php), [resultado](MCP_EDITORIAL_PREFLIGHT_LAB_RESULT_2026-10-20.json):

| Escenario sintético | Resultado esperado/observado |
| --- | --- |
| Antes: subscriber, sin scope, plugin propio inactivo | exit0 |
| Después: subscriber+scope, plugin propio activo | exit0 |
| Capacidad extra `manage_options` | exit1; concesión almacenada/efectiva detectada |
| Fase después sin scope | exit1; permiso requerido ausente |
| Usuario inexistente | exit1; error controlado |

**5 escenarios correctos.** Hash de usuarios/usermeta/posts/postmeta/options/relaciones antes/después confirma que el diagnóstico no modifica esas tablas en los cinco casos; usuario actual en memoria restaurado también en los cinco. El harness sí crea usuarios y cambia permisos/plugin para construir casos **solo en laboratorio**, con limpieza de esas capacidades extras/restauración del plugin después de cada caso. No ejecutar el harness contra el sitio real; tiene guard local/http://wordpress.

Primer intento señaló `level_0` como inesperado; corregido el diagnóstico para aceptar esa capacidad base histórica de subscriber, sin permitir niveles superiores ni permisos extra. Fuente WordPress enlazada en runbook. Sintaxis PHP/shell y `git diff --check` correctos. Las91 pruebas se repitieron después en una combinación diferente por el hallazgo real descrito abajo; frontend/build no se repitieron. ZIP/PHP del plugin intactos.

Reproducción de cada escenario, con laboratorio preparado como describe el [QA HTTP](MCP_EDITORIAL_HTTP_2026-10-20.md):

```sh
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml run --rm --no-deps cli eval-file /lab/preflight-check.php before
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml run --rm --no-deps cli eval-file /lab/preflight-check.php after
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml run --rm --no-deps cli eval-file /lab/preflight-check.php extra-cap
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml run --rm --no-deps cli eval-file /lab/preflight-check.php wrong-phase
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml run --rm --no-deps cli eval-file /lab/preflight-check.php missing-user
```

## Estado y continuación

**Revisión acotada real completada**, [resultado saneado](MCP_EDITORIAL_REAL_PREFLIGHT_RESULT_2026-10-20.json). Usuario2/subscriber con read y sin scope editorial; sin capacidades de edición/administración comprobadas ni concesiones almacenadas inesperadas. Rol global subscriber sin scope. Abilities API/Application Passwords/CPT público-traducible disponibles; plugin propio ausente. Preflight final `before` exit0.

El primer preflight real detectó **Polylang3.9.0 y PHP8.2.34**, distintos del perfil original3.8.11/PHP8.5; falló correctamente antes de proponer instalación. Se preparó otro proyecto Compose `alexcuadra-editorial-site-stack-lab`, imágenes oficiales fijadas por digest, sin puertos host/datos reales. `npm run test:mcp-site-stack` exit0:55 framework+36HTTP correctos con WordPress7.1.3/PHP8.2.34/Polylang3.9.0/Adapter0.7.1; también5 escenarios del diagnóstico correctos con hashes DB/contexto intactos. [Evidencia](MCP_EDITORIAL_SITE_STACK_LAB_RESULT_2026-10-20.json). Apache de laboratorio y CLI tienen PHP8.2.34, sin replicar todos los plugins/configuración/SAPI/proxy del hosting. El diagnóstico acepta ahora las dos combinaciones probadas, sin cambiar versiones del sitio.

Inventario de plugins normales/MU y tema real revisados; en hooks seleccionados de capacidades/visibilidad/consultas aparecen core/Polylang, sin override de membresía identificado en esa superficie. Leídos los bloques pertinentes de Polylang: capacidades de gestión de idiomas/traducciones, edición de privacidad y borrado del término predeterminado no amplían lectura de proyectos; pre_get_posts habilita caché de términos del tipo traducido. Muestras146EN/196ES publicadas, sin contraseña, públicamente visibles, legibles por usuario2 e idioma correcto. **No es auditoría completa de todos los plugins/contextos ni de permisos dinámicos por objeto**; `completeAclAudit=false` y la nota manual del diagnóstico se conservan. La aceptación real MCP del plugin nuevo requiere instalarlo primero y sigue pendiente.

Siguiente: decisión expresa sobre [propuesta concreta](../WORDPRESS_MCP_READ_INSTALL.md): instalar ZIP0.1.0, activar exclusivamente ese plugin, asignar `alexcuadra_read_public_projects` solo a usuario2 manteniendo subscriber y verificar en lectura desde el conector. Sin escritura/ACF/cuerpos. Reconciliar de nuevo baseline/identidad/versiones antes de aplicar, por posibles cambios concurrentes/actualizaciones; no interpretar este informe como instalación realizada. El rollback concreto está preparado y no se ejecutó en el CMS.

Sin cambios de CMS, roles/capacidades reales, Application Passwords reales, Workers/producción/contacto/envíos. Navegador no utilizado ni eludido, dev4325 preservado. Lab detenido al finalizar; cambios locales conservados. TOC visual y429 del hosting siguen abiertos.
