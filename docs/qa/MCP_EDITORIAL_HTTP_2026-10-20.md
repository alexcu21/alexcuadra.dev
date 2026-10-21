# MCP editorial: transporte HTTP — 20 octubre 2026

## Resultado

**36 comprobaciones HTTP correctas**, junto con las55 pruebas anteriores del framework en una ejecución final de `npm run test:mcp-read` (exit0,91 en total). Cliente PHP → HTTP Apache real → REST de WordPress → MCP Adapter oficial → abilities del paquete0.1.0. [Resultado saneado](MCP_EDITORIAL_HTTP_RESULT_2026-10-20.json), [cliente reproducible](../../tests/integration/wordpress-mcp-read/http.php).

Se confirma en este laboratorio que un usuario **subscriber con `read` puede autenticar el transporte mediante Application Password**. Para ejecutar las dos abilities editoriales necesita además `alexcuadra_read_public_projects`, asignada únicamente al usuario sintético durante las pruebas. Sigue sin `edit_posts` ni `manage_options`; no necesita convertirse en administrador. Esto no certifica todos los filtros/ACL del CMS compartido.

## Entorno y protocolo

Mismo Compose `alexcuadra-editorial-read-lab`, imágenes fijadas por digest, WordPress7.1.3/PHP8.5, Polylang3.8.11 y MCP Adapter0.7.1. Solo datos/configuración sintéticos. Peticiones a `http://wordpress/?rest_route=/mcp/mcp-adapter-default-server` dentro de la red Docker, sin publicar puertos del host, acceder al navegador ni consultar el CMS real. HTTP sin TLS pertenece exclusivamente a esta red de laboratorio con credenciales desechables; no valida TLS/proxy/hosting reales.

Flujo probado: `initialize` con revisión `2025-11-25`, recepción de `Mcp-Session-Id`, notificación `notifications/initialized`, `tools/list`, meta-tools oficiales y terminación con DELETE. Las solicitudes posteriores incluyen sesión y `MCP-Protocol-Version` coincidente. El primer intento del cliente omitía esa cabecera y recibió400; se corrigió el cliente, sin modificar el adaptador. Ahora también se comprueba el rechazo400 por cabecera ausente o diferente. Referencias: [guía del servidor0.7.1](https://github.com/WordPress/mcp-adapter/blob/v0.7.1/docs/guides/default-server.md), [validación HTTP del adaptador0.7.1](https://github.com/WordPress/mcp-adapter/blob/v0.7.1/includes/Transport/Infrastructure/HttpRequestHandler.php), [permisos de transporte](https://github.com/WordPress/mcp-adapter/blob/v0.7.1/docs/guides/transport-permissions.md).

## Cobertura

- Anónimo y contraseña incorrecta rechazados; suscriptor autenticado aceptado sin capacidad editorial implícita.
- Tres meta-tools oficiales, descubrimiento de las dos abilities propias y schema restringido.
- Sesión ausente/desconocida rechazada; otro usuario no puede reutilizarla. Cabecera de protocolo obligatoria y coincidente.
- Lista paginada por título, ficha EN/ES exacta y traducciones públicas recíprocas; salida limitada a ID/título/slug/locale/traducciones.
- Sin cuerpo/meta privados; proyectos privados, protegidos, ausentes o de otro idioma rechazados. Campos arbitrarios y paginación excesiva rechazados.
- Revocación de la capacidad bloquea una sesión ya abierta; desactivar el plugin propio en el laboratorio retira descubrimiento/ejecución y reactivarlo restaura la lectura autorizada.
- DELETE cierra sesión; no puede reutilizarse y se puede iniciar otra. Revocar la Application Password bloquea la sesión existente.
- Hash antes/después de todas las filas de posts, postmeta y relaciones confirma ausencia de cambios editoriales durante las operaciones HTTP. Las sesiones y el uso de Application Password sí generan estado de autenticación en usuarios, como parte del protocolo.
- Limpieza en finally: se solicita cierre de las sesiones, se restaura el plugin y se retiran capacidades/contraseñas sintéticas. Se verifica que ambos usuarios de prueba no conservan contraseñas de aplicación ni la capacidad editorial. No se guardan contraseñas, Basic headers ni IDs de sesión en la evidencia.

## Reproducción y cierre

```sh
npm run test:mcp-read   # 55 framework + 36 HTTP
npm run test:mcp-http   # solo HTTP, con bootstrap del mismo laboratorio
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml stop
```

El runner deja el laboratorio iniciado para inspección; detenerlo al terminar. Los volúmenes tmpfs pueden perder los fixtures al detener/reiniciar contenedores, y el bootstrap reinstala datos sintéticos si es necesario. No importar datos/configuración del CMS real.

PHP lint, sintaxis shell y `git diff --check` correctos. Se verificaron de nuevo las huellas del ZIP, sus dos archivos y el PHP instalado/probado. Paquete0.1.0 intacto: SHA256 `72f1fa195c04ff1da690d2788e287451d52cc210d69d67eb453c869b8922f302`. Los avisos previos de WP-CLI Colors/PHP8.5 y de la prueba negativa WordPress siguen sin parchear; no impiden91/91 aserciones. No se repitieron build/SSR/237JS porque no cambió frontend ni sus módulos.

Al terminar se detuvieron únicamente los servicios de este laboratorio. No se reinició Astro4325 ni Docker Desktop globalmente.

## Estado real y siguiente paso

El paquete **no se instaló en el CMS compartido**; no se modificaron permisos reales ni se comprobó de nuevo su inventario MCP en este incremento. La última inspección registrada continúa siendo3 abilities core y roles-only subscriber. La nueva evidencia corresponde al laboratorio, no al conector nativo de este chat contra el sitio real. Sin escrituras/ACF/cuerpos añadidos a la extensión.

Siguiente paso de esta etapa: revisar ACL/capacidades efectivas y presentar instalación0.1.0 más asignación de `alexcuadra_read_public_projects` exclusivamente a la cuenta MCP dedicada; requiere decisión expresa antes de aplicarlas al CMS compartido, según el [plan](../WORDPRESS_MCP_EDITORIAL_PLAN.md). Instalación/activación administrativa es distinta del rol que necesita la cuenta de ejecución. Después verificar descubrimiento, lectura y rechazos reales; rollback por revocación de capacidad y desactivación del plugin propio. No modificar el rol global subscriber.

Contacto sigue desactivado, sin envíos ni cambios CMS/staging/producción. Cambios locales preservados. Navegador no reintentado/eludido; TOC local pendiente de aceptación visual y429 del hosting continúan abiertos.
