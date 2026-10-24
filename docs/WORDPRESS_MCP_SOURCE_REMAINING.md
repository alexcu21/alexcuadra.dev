# Cierre del adaptador y edición operativa — alcance restante

Último incremento24octubre:una revisión privada real preparada/inspeccionada con12comprobaciones correctas y propuesta227 sin cambios/sin aplicar. [Resultado real](qa/MCP_OPERATING_PRIVATE_REVIEW_LIVE_2026-10-24.md). Fase autorizada completada; no se aprobó ni aplicó contenido público.

**Estado vigente — aceptación de almacenamiento completada:**Alex ejecutó la auditoría autorizada de rc.3; accepted=true, snapshot READ ONLY/REPEATABLE READ, totales operativos cero,6 permisos permitidos/12denegados y conservación antes/después sin escritura editorial. [Resultado real](qa/MCP_OPERATING_LIVE_STORAGE_ACCEPTANCE_2026-10-23.json). Los scopes exactos ya estaban corroborados;14Projects coinciden con huella histórica y propuesta227 con su evidencia/ledger. La diferencia global histórica de postmeta **sigue abierta**, con causa/filas/valores anteriores sin reconstruir. No certificar el postflight global fallido ni reemplazar su baseline. Esta aceptación no aprueba un diff público ni certifica apply/recover/MCP HTTP en el hosting. Puede levantarse la pausa de esta auditoría; cualquier operación posterior requiere evaluar/coordinar su propia pausa. No repetir instalación/activación/grants/aceptación sin una razón nueva.

Estado23 octubre2026. Proposals rc.2 ya está instalado y validado: no reinstalar, repetir grant o recrear propuesta227. La autorización existente no incluye aplicar cambios a Projects reales. El trabajo nuevo sigue separado en `wordpress/experimental/editorial-source-adapter`.

## Adaptador: validación local cerrada

Perfil completo del código verificado:18 plugins originales,13 activos/5 inactivos, Proposals rc.2 adicional,2 MU y Twenty Twenty-Four1.6. Pasan58 checks CLI+50 HTTP+24 bilingües,132 en este perfil. [Resultado y límites](qa/MCP_SOURCE_EFFECTS_FULL_PROFILE_2026-10-23.md).

Cuerpo/tagline/ambos y recuperación preservan las filas EN/ES vinculadas. Idioma, sync/filtros y conflictos se validan antes de escribir; una mutación ajena dentro de la operación fuerza rollback de ambos Projects y limpia cachés. Se conservaron vínculos, IDs y referencias ACF. Cuatro SIGKILL prueban rollback/persistencia/reanudación y cero nueva ejecución del writer al reintentar. La cabecera nativa de purga se comprobó desde el consumidor después del COMMIT. Preview Hostinger cierra la operación sin cambios; la petición normal posterior recupera el contexto. Versiones/activación/MU/tema se mantienen iguales antes/después.

45 tests sintéticos de frontera y54 del protocolo SQLite también pasan, sin sumarlos como integración WP real ni sumar runners repetidos. No repetir esta etapa ni añadir funcionalidades al laboratorio sin fallo nuevo concreto.

Límite: se reprodujo código completo y sync vacío observado, no opciones privadas/configuración completa del proveedor. HTTP interno simula terminación TLS. El preflight del candidato debe comprobar la configuración admitida en destino; la entrega real de caché del hosting se informa aparte de la llamada a su API. El cierre local no autoriza instalación ni edición pública.

## Trabajo pendiente para edición operativa

Aplicación, revisión, aprobación CLI independiente y recuperación consumible integradas sobre rc.2; seis tablas con preparación conjunta reanudable, validadores y auditoría de relaciones. Aceptación global en snapshot READ ONLY con barrera cooperativa; requiere pausa externa declarada en destino. Seis abilities probadas por MCP HTTP local y export privado por propietario probado únicamente con datos sintéticos. [Contrato actual](WORDPRESS_MCP_OPERATING_CONTRACT.md), [QA de transporte](qa/MCP_OPERATING_TRANSPORT_2026-10-23.md), [QA del export](qa/MCP_PRIVATE_EXPORT_2026-10-23.md).

Resultado vigente:597 comprobaciones del candidato rc.3, que incorpora admisión productiva explícita y export compatible con link deshabilitado. [QA](qa/MCP_PRODUCTION_ADMISSION_2026-10-23.md), [paquete/plan](WORDPRESS_MCP_OPERATING_PACKAGE.md). Instalador/activación/desactivación normal probados; perfil productivo simulado verifica scopes/registro/consulta/auditoría/export por CLI. El circuito completo apply/recover/MCP HTTP sigue ensayado en modo local. Scopes reales exactos corroborados y aceptación de almacenamiento completada, sin edición pública.

| Alcance | Estado verificable |
| --- | --- |
| Instalación, activación, seis tablas y scopes | Completados; mapas directos exactos corroborados, rol subscriber y demás permisos conservados |
| Auditoría inicial de almacenamiento | Completada; accepted=true, cero registros operativos,6 permisos permitidos/12denegados, sin escritura editorial |
| Incidencia histórica global postmeta | Abierta; lecturas estables y corroboración editorial correctas no recuperan el valor anterior ni atribuyen la causa |
| Descubrimiento MCP del hosting | Verificado:6operaciones presentes con esquemas/metadatos iguales al paquete sellado;0ejecuciones editoriales |
| Lecturas MCP del hosting | Usuario2/subscriber, lecturas públicas EN/ES y propuesta227 confirmados;4rechazos operativos saneados sin código detallado |
| Preparación/inspección positiva de revisión privada | Completada por MCP:revisión1/owner2/227/146EN,12comprobaciones correctas, versión/base/propuesta conservadas |
| Aplicación/recuperación productivas | No certificadas; circuito completo ensayado localmente. Sin diff público autorizado |

**Auditoría de almacenamiento:0 iteraciones pendientes.** La prueba acotada de lock terminó inconclusa(0candidatos/0intentos). La investigación de postmeta tiene un alcance distinto y no se estima sin evidencia nueva de la causa; no convertir accepted=true en una afirmación de cierre de esa incidencia ni de toda la edición operativa productiva.

Una edición pública requiere su diff concreto aprobado; no se programa ahora ni se considera autorizada por «continúa». No pedir autorización antes de tener candidato/plan concretos y revisables. Los fixtures REST de manage_options no son el autorizador operativo final.

## Almacenamiento y límites conservados

Medición histórica anterior:35GiB libres al inicio y33GiB al final del volumen completo; variación no atribuida exclusivamente al ensayo. Registros/configuraciones privados160KiB. Una red interna propia vacía se reutilizó tras agotarse el pool, sin borrar redes/volúmenes. Imágenes cacheadas, red interna sin puertos, tmpfs512+768MiB por proyecto nuevo; RAM/swap posible. Contenedores detenidos al finalizar. Sin descargas de respaldos, SQL/config real, dependencias nuevas, SSH del agente, borrados de laboratorios/respaldo, cambios Workers o contacto. Credenciales HTTP solo sintéticas dentro del laboratorio; no se piden ni copian credenciales reales.

Preflight recibido: la restricción PHP link afecta al export del rc.1 local. Se aborda en este incremento como incompatibilidad concreta antes de admitir producción, conservando el ZIP sellado. [Revisión y compatibilidad](qa/MCP_OPERATING_DESTINATION_PREFLIGHT_2026-10-23.md). Esta corrección adicional ya queda cerrada con412 comprobaciones. La admisión y el paquete se completan en rc.3; la estimación actual figura arriba.

Último incremento:32GiB libres antes/después redondeados; sin nuevo backup real ni limpieza de recursos. La nueva lectura de destino solo exporta metadata al terminal de Alex.

Preflight real pasa11/11. Fase inactiva:9 escenarios nuevos del script exacto correctos, [comandos/alcance listos para autorización](WORDPRESS_MCP_OPERATING_DEPLOYMENT.md). ZIP/código rc.3 no cambia. No contar las597 comprobaciones del candidato como ejecutadas de nuevo. Alex ya autorizó y completó la fase inactiva; la evidencia histórica mantiene sus campos originales. Activación/almacenamiento ya autorizados y completados; scopes/aceptación ya autorizados y ejecutados; no solicitar la misma autorización ni repetir comandos.

Estado real más reciente:rc.3 activo; aceptación inicial completada cuando las seis tablas estaban vacías, y revisión privada1 creada/inspeccionada después, scopes corroborados y6 permisos permitidos/12denegados, sin escritura editorial durante la auditoría. [Recibo real](qa/MCP_OPERATING_LIVE_STORAGE_ACCEPTANCE_2026-10-23.json), [alcance e incidencia abierta](WORDPRESS_MCP_OPERATING_SCOPES.md). Puede levantarse la pausa coordinada para esta lectura. El cierre de la auditoría no certifica apply/recover/MCP HTTP del hosting ni aprueba una edición pública.

**Fase privada:0iteraciones pendientes.** Preparar/publicar un cambio real requiere su contenido concreto y aprobación independiente de la revisión/hash vigentes; no se cuenta como autorizado por esta verificación.
