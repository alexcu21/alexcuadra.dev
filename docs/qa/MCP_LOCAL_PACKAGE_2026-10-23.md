# ZIP local rc.1 y arranque desactivado — 23 octubre

413 comprobaciones:405 de integración anteriores ejecutadas contra módulos extraídos del ZIP y8 del arranque desactivado. Perfil original/rc.2, datos sintéticos, MCP HTTP real interno. Evidencia aceptada segunda ejecución; la primera detectó carga duplicada por dos rutas del fixture. Las rutas ahora son canónicas del paquete; no se relajó el cargador para tolerar código duplicado.

Artefacto alexcuadra-editorial-operating-0.1.0-rc.1.zip,32207 bytes, SHA256 ba76d9f36c020f069544af068beb09c999a570b2a912ad86f67d695dc4e0c13f. Constructor con allowlist de11 módulos, bootstrap, README y files.json; excluye SQLite, fixtures, credenciales y datos. Timestamps/orden/permisos ZIP fijos; reconstrucción produce el mismo hash. La versión sellada no puede sobrescribirse con bytes distintos. Manifiesto externo e inventario interno con tamaños/hashes; el runner verifica todas las entradas y rutas antes de extraer a carpeta privada.

Bootstrap: sin opt-in local no carga módulos propios ni añade hooks/abilities; no activation/uninstall hooks, grants, DDL, export ni scheduler. AC_EDITORIAL_OPERATING_LOCAL=true solo intenta el entorno local ya admitido; las guardas de origen/WP/cache/Polylang/LiteSpeed se mantienen. Factory del paquete usa lector bilingüe, schema ACF e invalidación vinculada propios, sin callbacks de negocio aportados por fixture. Seis abilities solo en opt-in local. No se añade admisión para el CMS real.

Ocho pruebas: seis abilities ausentes sin flag; controller desactivado; almacenamiento no creado. Con opt-in,405 regresiones pasan usando inclusiones del ZIP y factory del bootstrap por MCP HTTP. El bootstrap se carga desde MU fixture sintético: no se ha certificado todavía el instalador/activación WordPress como plugin normal ni una configuración del proveedor. Es candidato de empaquetado local, no entrega autorizada para producción.

El header para fallo sintético sigue exclusivamente en fixture; el paquete no incluye test routes o credenciales. Se mantiene read/proposals rc.2 separado. Archivos de paquete no incluyen manifests del respaldo ni wp-config/SQL/cuerpos de prueba.

Pendiente concreto para candidato de destino: admisión de runtime en CMS basada en perfil/verificación actual; preflight de solo lectura; instalador/activación y reversión ensayados; después revisión/aprobación del paquete/permisos y diff público. No ejecutar comandos de instalación en servidor con rc.1 local. Export privado ya cerrado localmente, sin repetirlo salvo cambio.

Almacenamiento: ZIP32KiB y manifiesto pequeño, extracción privada de código;32GiB libres antes/después. Imágenes cacheadas y red interna propia reutilizada, sin puertos/pulls/SSH/export o backup real nuevo. WP/DB tmpfs, Docker/swap pueden usar disco. Contenedores detenidos; recursos previos retenidos.
