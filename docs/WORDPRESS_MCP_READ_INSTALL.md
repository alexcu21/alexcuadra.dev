# Instalación: MCP editorial de lectura0.1.0

Estado20 octubre2026: **instalación aprobada por Alex y completada**. Editorial Read0.1.0 activo y capacidad `alexcuadra_read_public_projects` asignada solo a usuario2, que conserva subscriber/read, sin edición/administración comprobadas. Preflight before/after correctos, lectura/búsqueda/paginación/rechazos mediante conector real verificados. Huellas de14 proyectos, demás permisos, rol global y otros plugins activos intactas. [QA/estado vigente](qa/MCP_EDITORIAL_INSTALLED_2026-10-20.md). El procedimiento de abajo queda como registro y runbook; no volver a instalar ni pedir la misma aprobación. Escritura sigue fuera de alcance.

## Entrega concreta

- [Paquete0.1.0](../artifacts/wordpress/alexcuadra-editorial-read-0.1.0.zip), SHA256 `72f1fa195c04ff1da690d2788e287451d52cc210d69d67eb453c869b8922f302`.
- [Manifest y huellas](../artifacts/wordpress/alexcuadra-editorial-read-0.1.0.manifest.json); [91 comprobaciones de framework/HTTP](qa/MCP_EDITORIAL_HTTP_2026-10-20.md).
- [91 comprobaciones con la combinación observada del sitio y5 escenarios del preflight](qa/MCP_EDITORIAL_SITE_STACK_LAB_RESULT_2026-10-20.json); [preflight/inspección real](qa/MCP_EDITORIAL_REAL_PREFLIGHT_RESULT_2026-10-20.json).
- [Diagnóstico WP-CLI de solo lectura](../wordpress/operations/editorial-read-preflight.php), archivo operativo separado del ZIP. No es un plugin, endpoint ni herramienta MCP y no concede permisos.

Cuenta aprobada: **ID2, subscriber**. Capacidad asignada: `alexcuadra_read_public_projects`, solo para esa cuenta. El rol global subscriber permanece sin esa capacidad. La asignación pertenece al usuario y afecta a sus credenciales; no crea un permiso distinto para cada Application Password.

Lecturas expuestas: `alexcuadra/list-public-projects` y `alexcuadra/get-public-project`; devuelven ID/título/slug/idioma/traducciones públicas recíprocas. Sin cuerpo, ACF, opciones, datos de contacto, borradores ni escritura/publicación. Activar el plugin no concede la capacidad automáticamente.

## 1. Revisión previa de solo lectura

Desde la terminal del hosting, situarse en la raíz del WordPress correcto. Copiar el diagnóstico a una ubicación privada **fuera del directorio público**, por ejemplo sustituyendo `/ruta/privada` por una carpeta real del hosting. No subirlo a plugins, mu-plugins ni editar functions.php. No enviar contraseñas ni claves privadas al chat.

La revisión real se hizo sin copiar archivos: SSH con autenticación no interactiva existente/verificación estricta de host, transmitiendo el diagnóstico por stdin a `wp --path=domains/alexcuadra.dev/public_html/content --url=https://content.alexcuadra.dev eval-file - 2 before`. Usar `-` para stdin; `/dev/stdin` no funcionó en ese hosting. También sigue siendo válida la ejecución desde archivo privado descrita abajo.

```sh
wp --url=https://content.alexcuadra.dev eval-file /ruta/privada/editorial-read-preflight.php 2 before
```

La salida empieza por `EDITORIAL_PREFLIGHT_RESULT=`. Solo muestra ID/roles, versiones pertinentes, capacidades seleccionadas, nombres de concesiones almacenadas inesperadas y dependencias. No consulta contraseñas de aplicación, perfil, cuerpos, ACF, destinatario de contacto ni exporta opciones. Sale1 ante comprobaciones técnicas fallidas; sale0 no constituye autorización para instalar.

Verifica contexto del sitio, single-site, usuario existente/subscriber/read, ausencia inicial de la capacidad específica y de edición/administración comprobadas, ausencia de concesiones almacenadas inesperadas, dependencias del CPT público/traducible, versiones y estado inicial del plugin propio. Cambia/restaura únicamente el usuario actual **en memoria** para evaluar filtros de permisos en ese contexto; no modifica sus roles/capacidades guardadas.

El diagnóstico reconoce `read`, `level_0` y subscriber como concesiones base de ese rol. Falla ante permisos extra almacenados y calcula capacidades efectivas seleccionadas con WordPress, incluyendo las de edición que declare el CPT. No enumera todos los posibles permisos dinámicos por objeto/contexto. Perfiles probados: WordPress7.1.3/Adapter0.7.1 con PHP8.5+Polylang3.8.11 o PHP8.2+Polylang3.9.0. Cambios de combinación requieren revisión; no actualizar ni desactivar terceros para forzar un resultado correcto.

**Revisión manual de ACL:** se examinaron inventario de plugins normales/MU, tema Twenty Twenty-Four1.6, procedencias de filtros seleccionados y bloques pertinentes de Polylang. No se identificaron overrides de membresía en esa superficie; los dos proyectos146EN/196ES cumplen publicación/sin contraseña/visibilidad/lectura e idioma con usuario2. Esto es una revisión acotada en contexto CLI, no auditoría completa de cada plugin/filtro/contexto REST. Confirmar cualquier regla privada de membresía conocida antes de instalar; aceptación real MCP permanece posterior a instalación. No desactivar seguridad ni GraphQL autenticado. `manualAclReviewRequired=true` permanece siempre en el diagnóstico para impedir que él solo certifique esas reglas.

## 2. Instalación y permiso — procedimiento aplicado

La revisión anterior/ACL y la aprobación expresa de Alex precedieron al cambio. La cuenta administrativa que instala es distinta de la cuenta MCP que ejecuta lecturas; esta última conserva subscriber. Para una futura reinstalación o cambio de alcance, reconciliar el estado existente y seguir el respaldo del hosting. No aplicar de nuevo estos comandos sobre la instalación ya completada.

Comprobar SHA256 del ZIP copiado al hosting. Desde el WordPress correcto, con WP-CLI administrativo ya autenticado en el hosting:

```sh
sha256sum /ruta/privada/alexcuadra-editorial-read-0.1.0.zip
wp --url=https://content.alexcuadra.dev plugin install /ruta/privada/alexcuadra-editorial-read-0.1.0.zip --activate
wp --url=https://content.alexcuadra.dev user add-cap 2 alexcuadra_read_public_projects
wp --url=https://content.alexcuadra.dev eval-file /ruta/privada/editorial-read-preflight.php 2 after
```

No usar `--force`, activar a nivel de red, cambiar rol a administrador ni conceder la capacidad al rol subscriber. Si el plugin ya existe/está activo antes de empezar, detener la instalación y reconciliar ese estado; el preflight inicial debe señalarlo. No sobreescribir una versión desconocida.

## 3. Aceptación real después de la instalación — completada

Desde el conector MCP ya configurado, descubrir definitions, inspeccionar schema y listar metadatos EN/ES con páginas pequeñas; comprobar ficha real146EN y su relación196ES. Consultar IDs existentes, no crear entradas de prueba en el CMS. Rechazar idioma incorrecto, campos arbitrarios y `per_page=9999`; no probar cargas masivas. Confirmar ausencia de cuerpos/ACF y de operaciones de escritura.

Ejecutado correctamente: seis proyectos EN/seis ES en dos páginas por idioma, fichas146↔196 y búsqueda; rechazos específicos para idioma, borrador143, campos arbitrarios y límite excesivo. Denegación sin capacidad comprobada después de activar y antes de asignarla. No hace falta repetir esta aceptación sin nuevos cambios/fallos que lo justifiquen.

Si se aprueba el ensayo de revocación en el sitio real: retirar solo la capacidad, comprobar denegación de una lectura previamente autorizada y restaurarla únicamente conforme al estado final aprobado. No revocar la Application Password real ni crear otra sin autorización específica: esa revocación ya se probó con credenciales desechables en laboratorio.

## 4. Reversión de la instalación

Revocar acceso de lectura de esta extensión y desactivar únicamente este plugin, sin borrar contenido:

```sh
wp --url=https://content.alexcuadra.dev user remove-cap 2 alexcuadra_read_public_projects
wp --url=https://content.alexcuadra.dev plugin deactivate alexcuadra-editorial-read
wp --url=https://content.alexcuadra.dev eval-file /ruta/privada/editorial-read-preflight.php 2 before
```

Comprobar que las dos abilities propias desaparecen y las core siguen disponibles. No desactivar Polylang, ACF, WPGraphQL ni MCP Adapter. No eliminar el usuario ni el rol ni modificar otras capacidades. Estos comandos corresponden al baseline observado (sin permiso/plugin activo); reconciliar cualquier cambio concurrente antes de aplicarlos.

## Fuentes y límites

[WP-CLI eval-file](https://developer.wordpress.org/cli/commands/eval-file/), [capacidad por usuario](https://developer.wordpress.org/cli/commands/user/add-cap/), [comprobación de capacidades WordPress](https://developer.wordpress.org/reference/functions/user_can/) y [capacidades base históricas de subscriber](https://developer.wordpress.org/reference/functions/populate_roles_160/). Las comprobaciones de transporte en Docker no certifican los filtros, TLS/proxy o membresía del sitio real.

Contacto desactivado, sin correo; no incluye despliegues de Workers, cambio de contenido CMS ni políticas de navegador. [Evidencia de esta preparación](qa/MCP_EDITORIAL_PREFLIGHT_2026-10-20.md).
