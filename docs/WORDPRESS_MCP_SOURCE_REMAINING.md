# Cierre del adaptador y edición operativa — alcance restante

Última evidencia:14Projects completos coinciden con huella histórica; propuesta227 válida con versiones/patches contrastados contra ledger intacto; permisos exactos. Corroboración real cerrada en ese alcance. Siguiente paso:auditoría de almacenamiento de solo lectura ya autorizada. La diferencia histórica global postmeta sigue abierta y no se da por aceptada ni se reemplaza su baseline. [Estado/recibo/comando](WORDPRESS_MCP_OPERATING_SCOPES.md).

Actualización real: activación completada; la concesión autorizada se detuvo en el postflight. El diagnóstico confirma los tres permisos exactos y deriva únicamente en postmeta. La aceptación sigue pendiente de conciliar esa diferencia; no repetir grants. [Estado/evidencia y siguiente lectura](WORDPRESS_MCP_OPERATING_SCOPES.md).

Estado23 octubre2026. Proposals rc.2 ya está instalado y validado: no reinstalar, repetir grant o recrear propuesta227. La autorización existente no incluye aplicar cambios a Projects reales. El trabajo nuevo sigue separado en `wordpress/experimental/editorial-source-adapter`.

## Adaptador: validación local cerrada

Perfil completo del código verificado:18 plugins originales,13 activos/5 inactivos, Proposals rc.2 adicional,2 MU y Twenty Twenty-Four1.6. Pasan58 checks CLI+50 HTTP+24 bilingües,132 en este perfil. [Resultado y límites](qa/MCP_SOURCE_EFFECTS_FULL_PROFILE_2026-10-23.md).

Cuerpo/tagline/ambos y recuperación preservan las filas EN/ES vinculadas. Idioma, sync/filtros y conflictos se validan antes de escribir; una mutación ajena dentro de la operación fuerza rollback de ambos Projects y limpia cachés. Se conservaron vínculos, IDs y referencias ACF. Cuatro SIGKILL prueban rollback/persistencia/reanudación y cero nueva ejecución del writer al reintentar. La cabecera nativa de purga se comprobó desde el consumidor después del COMMIT. Preview Hostinger cierra la operación sin cambios; la petición normal posterior recupera el contexto. Versiones/activación/MU/tema se mantienen iguales antes/después.

45 tests sintéticos de frontera y54 del protocolo SQLite también pasan, sin sumarlos como integración WP real ni sumar runners repetidos. No repetir esta etapa ni añadir funcionalidades al laboratorio sin fallo nuevo concreto.

Límite: se reprodujo código completo y sync vacío observado, no opciones privadas/configuración completa del proveedor. HTTP interno simula terminación TLS. El preflight del candidato debe comprobar la configuración admitida en destino; la entrega real de caché del hosting se informa aparte de la llamada a su API. El cierre local no autoriza instalación ni edición pública.

## Trabajo pendiente para edición operativa

Aplicación, revisión, aprobación CLI independiente y recuperación consumible integradas sobre rc.2; seis tablas con preparación conjunta reanudable, validadores y auditoría de relaciones. Aceptación global en snapshot READ ONLY con barrera cooperativa; requiere pausa externa declarada en destino. Seis abilities probadas por MCP HTTP local y export privado por propietario probado únicamente con datos sintéticos. [Contrato actual](WORDPRESS_MCP_OPERATING_CONTRACT.md), [QA de transporte](qa/MCP_OPERATING_TRANSPORT_2026-10-23.md), [QA del export](qa/MCP_PRIVATE_EXPORT_2026-10-23.md).

Resultado vigente:597 comprobaciones del candidato rc.3, que incorpora admisión productiva explícita y export compatible con link deshabilitado. [QA](qa/MCP_PRODUCTION_ADMISSION_2026-10-23.md), [paquete/plan](WORDPRESS_MCP_OPERATING_PACKAGE.md). Instalador/activación/desactivación normal probados; perfil productivo simulado verifica scopes/registro/consulta/auditoría/export por CLI. El circuito completo apply/recover/MCP HTTP sigue ensayado en modo local. Sin scopes reales nuevos ni edición pública.

| Entrega pendiente | Iteraciones estimadas | Resultado verificable |
| --- | --- | --- |
| Permisos y aceptación inicial en destino | 1 | Instalación/activación/seis tablas vacías completadas;12 escenarios de scopes/aceptación/revocación pasan. Falta autorización de tres capacidades, pausa efectiva y salidas de Alex por fases; revisar ambas antes de cerrar |

**Estimación vigente restante:1 entrega de trabajo**, sujeta a incompatibilidades/resultados/aprobación; no equivale a número de mensajes o comandos. No volver a presentar perfiles o engine locales como pendientes.

Una edición pública requiere su diff concreto aprobado; no se programa ahora ni se considera autorizada por «continúa». No pedir autorización antes de tener candidato/plan concretos y revisables. Los fixtures REST de manage_options no son el autorizador operativo final.

## Almacenamiento y límites conservados

Medición histórica anterior:35GiB libres al inicio y33GiB al final del volumen completo; variación no atribuida exclusivamente al ensayo. Registros/configuraciones privados160KiB. Una red interna propia vacía se reutilizó tras agotarse el pool, sin borrar redes/volúmenes. Imágenes cacheadas, red interna sin puertos, tmpfs512+768MiB por proyecto nuevo; RAM/swap posible. Contenedores detenidos al finalizar. Sin descargas de respaldos, SQL/config real, dependencias nuevas, SSH del agente, borrados de laboratorios/respaldo, cambios Workers o contacto. Credenciales HTTP solo sintéticas dentro del laboratorio; no se piden ni copian credenciales reales.

Preflight recibido: la restricción PHP link afecta al export del rc.1 local. Se aborda en este incremento como incompatibilidad concreta antes de admitir producción, conservando el ZIP sellado. [Revisión y compatibilidad](qa/MCP_OPERATING_DESTINATION_PREFLIGHT_2026-10-23.md). Esta corrección adicional ya queda cerrada con412 comprobaciones. La admisión y el paquete se completan en rc.3; la estimación actual figura arriba.

Último incremento:32GiB libres antes/después redondeados; sin nuevo backup real ni limpieza de recursos. La nueva lectura de destino solo exporta metadata al terminal de Alex.

Preflight real pasa11/11. Fase inactiva:9 escenarios nuevos del script exacto correctos, [comandos/alcance listos para autorización](WORDPRESS_MCP_OPERATING_DEPLOYMENT.md). ZIP/código rc.3 no cambia. No contar las597 comprobaciones del candidato como ejecutadas de nuevo. Alex ya autorizó y completó la fase inactiva; la evidencia histórica mantiene sus campos originales. Activación/almacenamiento ya autorizados y completados; la autorización nueva pendiente corresponde a scopes/aceptación.

Estado real más reciente:rc.3 **activo**, opt-in canónico, seis tablas listas/vacías,18 denegaciones,14 Projects/propuestas/permisos conservados. [Evidencia](qa/MCP_OPERATING_LIVE_ACTIVATION_2026-10-23.json). [Siguiente fase concreta](WORDPRESS_MCP_OPERATING_SCOPES.md):tres scopes directos y aceptación inicial de solo lectura,12 escenarios correctos, pendiente autorización y ejecución por Alex. Puede coordinar la pausa, pero aún no está iniciada. No reinstalar ni reactivar. El cierre de esta entrega no incluye una aplicación pública ni certifica MCP HTTP del hosting; cualquier diff requiere una autorización concreta posterior.
