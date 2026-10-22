# Aceptación HTTP/MCP/GraphQL y carreras — 22 octubre 2026

**140 comprobaciones correctas/exit0:**86 HTTP/MCP/concurrencia y54 GraphQL/ACF/Polylang. [Resultado exacto](MCP_PROPOSALS_TRANSPORT_RESULT_2026-10-22.json). Candidato0.1.0-rc.1 y ZIP SHA256 `cd990139d7915567d76012e53060def452e8f2b43796918415d5b1f82da38e14` intactos; no instalación real ni scopes reales. No sumar estas comprobaciones a531 del laboratorio como una sola suite; las165 CLI/archivo anteriores conservan su evidencia aparte.

## Perfil y aislamiento

WP7.1.3/PHP8.2.34/Polylang3.9.1, mismos seis paquetes públicos/SHA256 del ensayo previo. Caché temporal había desaparecido: se reconstruyó12,1MiB comprimidos/38MiB extraídos, cotejados con los hashes anteriores; no nuevas versiones ni imágenes. Docker estaba apagado; se abrió Desktop por el mecanismo normal de permisos.42GiB libres observados; solo caché pública y texto local, sin CMS descargado ni SSH por el agente.

Proyecto `alexcuadra-editorial-proposals-transport-v2`, red interna/sin puertos, seed sin red. Fixture HTTP permite únicamente `http://wordpress` puerto80 sin userinfo; bloquea el resto de APIs HTTP de WP y correo. Red interna bloquea egress de transports directos también. No se usa el navegador ni se sortea su bloqueo. Las URLs home/site exactas pertenecen a opciones de la copia sintética; nunca se solicitan al hosting.

Primer intento de transporte-v1 falló al comprobar denegación del suscriptor (4 comprobaciones al cerrar,1 fallo) y se detuvo con credenciales/scopes sintéticos retirados. La hipótesis fue el contexto HTTP local frente al guard exacto HTTPS; no se capturó un valor site_url del primer intento como evidencia de esa causa. Se corrigió exclusivamente el fixture para simular un frontend que termina TLS en las solicitudes internas Host=wordpress, manteniendo el paquete/guard intactos. Nueva ruta de contexto solo del arnés confirmó home/site https exactos, is_ssl=true y ability registrada antes de iniciar la suite.

**No es una prueba TLS:** el transporte entre contenedores es HTTP; el contexto HTTPS se simula en fixture MU bajo marcador sintético y hostname interno. No certifica certificados, proxy real, Hostinger/LiteSpeed, ni todos los hooks del hosting. Instrumentación/barrera/ruta de diagnóstico no se incluyen en el ZIP del plugin.

## Cobertura HTTP/MCP

Inicialización/sesiones independientes del adaptador oficial y seis abilities; denegación anónima, suscriptor read-only, otro dueño y permiso revocado. Patch/schema cerrados; DTO privado, fuente y meta privados ausentes, idioma exacto y fuentes draft denegadas. Lectura/escritura/restauración de patch, replays, versiones obsoletas e historial paginado. REST ordinario no permite editar la fuente y no expone endpoint de propuestas.

Desactivar solo el plugin retira las seis abilities; reactivar conserva propuesta/historial/recibo entre requests. Cerrar una sesión no termina la otra; revocar Application Password bloquea sesiones existentes. Al finalizar se solicitaron cierres y se comprobaron credenciales/scopes ficticios retirados. Se preservaron huellas de fuente en las operaciones de propuesta; el harness también introduce y restaura deriva deliberada sintética para comprobar invalidación.

## Seis carreras reales

Barrera de instrumentación antes del lock de admisión, visible por conexión SQL autocommit separada. Dos PIDs distintos y solapamiento temporal verificados en cada escenario; no una secuencia de llamadas presentada como concurrencia.

| Escenario | Resultado |
| --- | --- |
| update/update de misma versión | 1 éxito,1 proposal_changed; solo ganador en cabeza/historial |
| update/restore de patch | 1 éxito,1 proposal_changed; otros campos preservados |
| create duplicado | 2 DTO iguales,1 propuesta y1 recibo |
| update duplicado | 2 DTO iguales,1 revisión nueva |
| misma clave, distinto patch | 1 éxito,1 request_reused |
| último cupo de propuestas | 1 éxito,1 owner_quota; límite nunca excedido |

Estas carreras son de propuestas privadas; no certifican atomicidad de edición de Projects ni efectos externos, que no se incluyen en este paquete.

## Cobertura GraphQL/ACF

Perfil de versiones, grupo/key ACF originales, traducción recíproca EN/ES y campos reales. Endpoint anónimo restringido y autenticación por Application Password. Sin tipos/colecciones privados en schema. Dueño, otro y admin no obtienen propuestas por node/contentNode/colecciones; prueba negativa usa el nombre del CPT nuevo, no solo el del laboratorio anterior. Campos source/meta privados no añadidos al schema. Scope prepare no permite crear/editar Projects por mutaciones genéricas. Actualizar/restaurar patch no modifica fuente/ACF; deriva real de APIs ACF/referencia/relación idiomática invalida propuesta y se restaura por el harness.

## Cierre y siguientes puertas

Finalmente wordpress/db del transporte-v2 quedaron confirmados Exited(0). Se preservaron intentos y laboratorios anteriores; sin prune/reset/down-v. No se repitieron165 CLI/archivo sin cambio del candidato. El runner ahora ofrece `--transport --resume` solo para este proyecto y con identidad DB/paquete/perfil coincidentes si la DB persiste; ese modo **no se ejerció en esta aceptación**. No usarlo para afirmar durabilidad de tmpfs; preferir revisar recursos antes de reanudar.

Permanece HOLD por esquema exhaustivo de tablas preexistentes (tipos/índices/privilegios), perfil real actualizado y respaldo privado real/restauración/archivo durable antes de instalar. Alex eligió guardar respaldo en disco externo o carpeta privada del equipo, conservar hasta validar instalación/restauración y luego decidir. [Siguiente preflight de respaldo, sin exportar datos](../WORDPRESS_MCP_PRIVATE_BACKUP_PLAN.md). Contacto, Workers, permisos y CMS real sin cambios; Astro–WordPress sigue última etapa.
