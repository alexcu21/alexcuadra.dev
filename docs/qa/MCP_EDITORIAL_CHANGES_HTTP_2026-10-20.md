# Propuestas editoriales: HTTP y concurrencia real — 20 octubre 2026

Actualización posterior: [historial/recuperación0.2-lab completados](MCP_EDITORIAL_HISTORY_2026-10-20.md),181 checks combinados, incluidos68 HTTP. Este informe y su JSON conservan la evidencia histórica0.1 de117; el prototipo actual cambió para incorporar revisiones.

## Resultado

**48 comprobaciones HTTP correctas** del prototipo Editorial Changes0.1.0-lab. Runner completo final `npm run test:mcp-changes` exit0:67 framework +2 guards +48 HTTP, **117 correctas**. [Resultado saneado](MCP_EDITORIAL_CHANGES_HTTP_RESULT_2026-10-20.json), [cliente reproducible](../../tests/integration/wordpress-mcp-changes/http.php), [contrato](../WORDPRESS_MCP_PROJECT_CHANGES.md).

Plugin de propuestas y paquete de lectura sin cambios de fuente, verificados por huella contra el incremento anterior. No se instalaron propuestas ni se cambiaron permisos en el CMS real. Sigue sin requerirse crear un usuario Editor: las pruebas usan dos suscriptores sintéticos con capacidades específicas.

## Recorrido HTTP y permisos

Cliente CLI → Apache real → REST WordPress → MCP Adapter0.7.1 → tres abilities de propuestas. WordPress7.1.3/PHP8.2.34/Polylang3.9.0 en el mismo proyecto aislado `alexcuadra-editorial-change-lab`, imágenes fijadas por digest, sin puertos host ni datos/credenciales reales. Destino exclusivo `http://wordpress/?rest_route=/mcp/mcp-adapter-default-server`. No replica TLS/proxy/hosting del sitio compartido.

Application Passwords desechables generadas en memoria, initialize con protocolo2025-11-25, notificación inicial y cabeceras de sesión/protocolo. Dos sesiones independientes del propietario y una de otro usuario. Sin secretos, Basic headers o IDs de sesión en el informe.

Casos verificados: anónimo denegado; transporte autenticado/read scope no concede preparar; schemas cerrados y descubrimiento; create/get/update reales; misma propuesta accesible desde otra sesión del dueño; otro usuario no reutiliza su sesión ni lee/actualiza la propuesta; edición convencional por REST del proyecto devuelve403/rest_cannot_edit; CPT de propuestas sin endpoint público; campos arbitrarios, token antiguo, origen borrador o idioma equivocado rechazados; ES exacto y salida sin cuerpo/meta privados del origen.

## Dos carreras entre procesos HTTP independientes

En cada ronda, el cliente envía dos solicitudes paralelas mediante [Requests::request_multiple](https://github.com/WordPress/Requests/blob/v2.0.12/src/Requests.php), usando sesiones distintas del mismo propietario y el mismo token de versión.

[Instrumentación MU exclusiva del laboratorio](../../tests/integration/wordpress-mcp-changes/http-barrier.php) registra llegada/liberación en una tabla sintética y espera a que **dos PIDs Apache diferentes** alcancen el límite previo al UPDATE compare-and-swap. Solo se activa con el nonce de prueba y contexto local. Así ambas solicitudes ya cargaron la versión previa antes de ejecutar sus escrituras. No se modifica el plugin, el SQL de la propuesta ni los plugins oficiales.

| Ronda final | Procesos independientes | Solapamiento en barrera | Escrituras correctas | Conflictos de aplicación |
| --- | --- | --- | --- | --- |
| Primera | 2 | Verificado | 1 | 1 |
| Segunda | 2 | Verificado | 1 | 1 |

Se comprueba después por HTTP que persistió exactamente la versión/valor del ganador y que el otro campo propuesto se conservó. Se acepta solo el error específico `proposal_changed`, no un fallo genérico de red/servidor. El callback representa ese conflicto con409; el transporte MCP puede envolverlo en un resultado de herramienta de HTTP200. No confundir ambos estados.

La barrera verifica el solapamiento de la sección crítica: no es una prueba de carga, equidad o rendimiento, ni una garantía sobre cualquier escritor ajeno al prototipo. Las dos sesiones del dueño siguen válidas después de las carreras.

## Conservación y cierre

Hash de posts/postmeta/relaciones de las tres fuentes sintéticas coincide después de las operaciones HTTP. El harness cambia temporalmente el subtítulo de una fuente para verificar `source_changed`; lo restaura y vuelve a comprobar la huella original. Esa mutación de fixture no procede del plugin y no alcanza el CMS real.

Revocar la capacidad impide consultar desde una sesión abierta; desactivar el plugin retira definitions/ejecución y reactivarlo conserva la propuesta privada. DELETE cierra una sesión manteniendo la otra válida; revocar la Application Password bloquea la sesión restante. Finalmente se solicita cierre de sesiones con credenciales de limpieza, se eliminan/verifican todas las contraseñas y capacidades sintéticas, se restaura el plugin y se eliminan las filas de la barrera.

PHP lint, shell y `git diff --check` correctos. Sin cambios del prototipo ni de frontend: no se repiten build/SSR ni las91 pruebas de lectura independientes. La pasada completa117 verifica la integración del runner con la nueva instrumentación, además de la primera ejecución HTTP aislada. Avisos conocidos de bootstrap/plugin ya activo y de las pruebas negativas WordPress no se parchearon para declararlos inexistentes.

```sh
npm run test:mcp-changes-http  # solo HTTP y bootstrap del laboratorio
npm run test:mcp-changes       # framework, guard y HTTP
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml \
  -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml \
  -f tests/integration/wordpress-mcp-changes/compose.yaml stop
```

Al terminar se detuvo únicamente este laboratorio. Sin consultas/instalaciones/permisos/credenciales reales, cambios de contenido CMS, Workers/contacto/envíos ni navegador. Dev4325 y cambios locales preservados. Editorial Read0.1.0 instalado sigue siendo la única extensión propia del MCP real.

## Próximo incremento

Historial durable de revisiones y recuperación de propuestas propias en laboratorio; después idempotencia/cuotas y diseño separado de aplicación sobre fuentes. WPGraphQL real sigue pendiente: este laboratorio verifica configuración no pública/REST, no un servidor GraphQL instalado. No existe apply/publicación/creación de traducciones ni paquete listo para el sitio. No ampliar permisos reales ni crear un Editor por este resultado. TOC visual y429 del hosting siguen abiertos.
