# Perfil real del candidato privado — 26 octubre 2026

**Preflight real revisado: bloqueado correctamente por compatibilidad; no instalar rc.1.** [Recibo de Alex](blog-proposal-destination-before-2026-10-26.json). El único check principal falso es testedProfile; los otros14correctos. Dentro del perfil difieren exactamente testedActivePlugins/testedMuPlugins/testedTheme.

WP7.1.3/PHP8.5.12, hash wpdb, dependencias, caché estándar, ausencia de drop-ins y query filter core coinciden. Usuario2 conserva las ocho capacidades previas y16denegaciones amplias, sin nuevo scope. Paquete/guard/registro/tablas/propietario nuevos ausentes: es el estado inicial esperado. schema.ready=false con present=0 no es un fallo de migración; expectedSchema=true confirma ausencia esperada.

El hosting declara16plugins activos, dos MU de Hostinger y Twenty Twenty-Four1.6. rc.1 admite el perfil mínimo ensayado y Twenty Twenty-Five1.5. No se cambian versiones, tema o plugins del hosting para pasar esa política ni se relaja la admisión sin pruebas.

## Código disponible y procedencia

[Inventario](blog-proposal-site-code-inventory-2026-10-26.json):13.636archivos/207.193.629bytes de código y recursos, verificados por archivo contra el manifiesto del respaldo privado con SHA13f20d…d62. Se extrajeron únicamente plugins/temas/MU, sin el volcado backup/database.sql o wp-config.php. Caché privada ignorada por Git, carpeta0700/archivos0600. Respaldo original intacto, sin SSH, nueva descarga, restauración de datos o configuración del hosting.

Hay código candidato para las16versiones activas:13desde el respaldo y tres ZIP sellados posteriores (lector blog rc.1, propuestas de proyectos rc.2, operativo rc.3). MU y las dos entradas del tema coinciden exactamente con los hashes aportados. El recibo real solo da versiones de plugins, no sus hashes principales o árbol completo: no se certifica igualdad integral del código actual del servidor. El primer intento de extracción detectó cero archivos porque el tar usa ./; se normalizó la ruta conservando el rechazo de absolutos/../backslash y se completó la verificación, sin extraer datos privados.

## Arranque del perfil ampliado

[Recibo del laboratorio](blog-proposal-site-profile-bootstrap-2026-10-26.json): **46comprobaciones correctas**. Carga de los16main files, versiones/hashes candidatos, dos MU y tema coincidentes; núcleo/caché/drop-ins, ausencia del nuevo scope/paquete/tablas y bloqueo de correo/HTTP externo. El mismo preflight reproduce el bloqueo exclusivamente por testedProfile.

Datos sintéticos nuevos. Código PHP del respaldo sí se ejecutó en esta fase, exclusivamente dentro de contenedores aislados; la fase anterior de inventario no ejecutaba PHP. No se importó SQL, wp-config, opciones, usuarios, contraseñas o contenido reales. Un MU propio de seguridad bloquea HTTP/correo y se distingue de los dos MU del sitio; por ello no se afirma identidad íntegra del entorno. Se configura solo el contexto local de prueba y las ocho capacidades sintéticas previas, sin scope nuevo.

Los intentos de preparación corrigieron escape de variables PHP en Compose, espacio tmpfs insuficiente, copia recursiva y rutas de archivos de control no compartidas con la VM. Son fallos del harness; solo la pasada final completa se cuenta. Configuración/overlays privados pasan ahora por la caché del proyecto y se copian al volumen con propietario del contenedor. Runtime limitado a768MiB, BD a256MiB, sin puertos; se reutiliza una red interna propia previamente vacía para evitar crear redes innecesarias. Cinco proyectos propios confirmados detenidos; red vuelve a estar vacía. No prune ni borrado de respaldos.

## Límite y siguiente paso

El arranque no prueba CRUD de propuestas con este perfil. **Destino no aceptado y rc.1 intacto**, SHAca33eb…635a. Los273checks anteriores siguen perteneciendo al paquete/perfil mínimo; no se atribuyen al perfil ampliado ni se cuentan como repetidos aquí. No se reabre la incidencia histórica de postmeta, la propuesta227 o revisión1 ni se ejecutan sus operaciones/auditorías.

Siguiente incremento: candidato distinto de rc.1 para ensayar propuestas/MCP/fallos con el perfil ampliado; después capturar/contrastar las huellas de código necesarias y revisar aceptación. No añadir la lista de16plugins solo por coincidir versiones. No se requieren más comandos de Alex ahora; no repetir el before actual ni instalar/grantar el paquete retenido. Contacto, producción y CMS429 siguen pendientes por separado.
