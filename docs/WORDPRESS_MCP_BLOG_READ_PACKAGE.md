# Lector MCP del blog: paquete y comprobación de destino

Paquete **0.1.0-rc.1**, preparado y probado; pendiente de comprobación en el CMS real. Esta fase no instala el plugin ni concede el primer permiso real.

- [ZIP revisable](../artifacts/wordpress/alexcuadra-editorial-blog-read-0.1.0-rc.1.zip), 4.649 bytes.
- SHA256: `d8f8fcb1dcc83962036a70c3fb0e3a57e44dbeb96b5556e8abe1dcb7fbd205e6`.
- [Manifiesto de archivos](../artifacts/wordpress/alexcuadra-editorial-blog-read-0.1.0-rc.1.manifest.json) y [pruebas](qa/MCP_BLOG_READ_PACKAGE_2026-10-26.md).
- [Diagnóstico independiente](../wordpress/operations/editorial-blog-read-preflight.php), fuera del ZIP y del directorio público del servidor.

## Paso actual: comprobar antes de instalar

Alex ejecuta los comandos del hosting; el agente no abre SSH. Copiar únicamente el diagnóstico PHP a un directorio privado de la cuenta, fuera de public_html. Desde la raíz correcta de WordPress, usando el mismo PHP y WP-CLI del sitio, ejecutar lo siguiente sustituyendo la ruta por la ubicación real del archivo:

```sh
wp eval-file /ruta/privada/editorial-blog-read-preflight.php 2 before
```

Compartir la línea `EDITORIAL_BLOG_PREFLIGHT_RESULT=...` completa y cualquier error. No compartir credenciales, wp-config.php ni un volcado de la base de datos. No instalar todavía ni añadir la constante de activación para hacer pasar un fallo.

El resultado debe ser `ready:true` y todos los checks verdaderos. Comprueba URL exacta `https://content.alexcuadra.dev`, sitio único, usuario2 subscriber/read sin edición ni administración, ausencia del nuevo scope y de concesiones a roles/otros usuarios, dependencias, idiomas y ausencia del paquete. El perfil probado exige WordPress7.1.3, PHP8.5, Polylang3.9.1 y MCP Adapter0.7.1 activos. Una diferencia requiere revisión; el script no la corrige ni actualiza nada automáticamente.

El diagnóstico solicita lecturas de configuración, registro de herramientas, metadatos del plugin y permisos. No inspecciona cuerpos ni solicita Application Passwords. No instala, activa, concede permisos ni guarda estado propio. El arranque normal de WordPress y sus otros plugins puede tener efectos ajenos al script; esto no certifica conservación global de toda la base de datos ni resuelve la incidencia histórica de postmeta.

## Secuencia posterior, pendiente del resultado y autorización concreta

1. Revisar el recibo `before` y aprobar la instalación de este ZIP exacto, sin sustituir el lector de proyectos existente.
2. Instalar sin activar y ejecutar el mismo diagnóstico con `2 after-install`. Exige archivos exactos, plugin inactivo, guard y scope desactivados.
3. Configurar `AC_EDITORIAL_BLOG_READ_SITE` con el valor exacto `https://content.alexcuadra.dev` y activar únicamente este plugin. Ejecutar `2 after-enable`: dos herramientas registradas y ningún permiso nuevo.
4. Tras autorizar el primer scope real, conceder **solo** `alexcuadra_read_public_posts` directamente al usuario2. Conservar sus permisos anteriores; no asignar Editor, edit_posts ni conceder a un rol. Ejecutar `2 after-scope`.
5. Verificar descubrimiento y lecturas públicas EN/ES mediante el conector real. No crear credenciales nuevas ni ejecutar escritura editorial como prueba de lectura.

Las cuatro fases se probaron con el ZIP real en laboratorio. No activar simultáneamente el candidato experimental y este paquete: comparten namespace. El guard cerrado permite instalar sin exponer herramientas; la activación del plugin no asigna capacidades.

## Retirada acotada

Quitar únicamente el nuevo scope directo al usuario autorizado, desactivar este plugin y retirar su constante específica. Conservar lectores/propuestas y permisos anteriores. El paquete no crea tablas, contenido ni migraciones; no hay que borrar entradas o revisiones. Si se deja instalado pero inactivo y se retira el guard, `after-install` permite comprobar ese estado sin scope nuevo. No ejecutar desinstalaciones masivas ni reconstruir baselines históricos.

## Alcance pendiente

Estas herramientas leen metadatos públicos y relaciones EN/ES de entradas nativas, categorías y etiquetas. No editan cuerpos, crean traducciones ni cubren categorías/tecnologías propias de proyectos. Esos incrementos continúan en el [inventario vigente](NEXT_INCREMENTS_2026-10-26.md). El formulario, sus bindings de producción y la disponibilidad CMS siguen pendientes por separado.
