# Cierre del adaptador y edición operativa — alcance restante

Estado23 octubre2026. Proposals rc.2 ya está instalado y validado: no reinstalar, repetir grant o recrear propuesta227. La autorización existente no incluye aplicar cambios a Projects reales. El trabajo nuevo sigue separado en `wordpress/experimental/editorial-source-adapter`.

## Adaptador: validación local cerrada

Perfil completo del código verificado:18 plugins originales,13 activos/5 inactivos, Proposals rc.2 adicional,2 MU y Twenty Twenty-Four1.6. Pasan58 checks CLI+50 HTTP+24 bilingües,132 en este perfil. [Resultado y límites](qa/MCP_SOURCE_EFFECTS_FULL_PROFILE_2026-10-23.md).

Cuerpo/tagline/ambos y recuperación preservan las filas EN/ES vinculadas. Idioma, sync/filtros y conflictos se validan antes de escribir; una mutación ajena dentro de la operación fuerza rollback de ambos Projects y limpia cachés. Se conservaron vínculos, IDs y referencias ACF. Cuatro SIGKILL prueban rollback/persistencia/reanudación y cero nueva ejecución del writer al reintentar. La cabecera nativa de purga se comprobó desde el consumidor después del COMMIT. Preview Hostinger cierra la operación sin cambios; la petición normal posterior recupera el contexto. Versiones/activación/MU/tema se mantienen iguales antes/después.

39 tests sintéticos de frontera y54 del protocolo SQLite también pasan, sin sumarlos como integración WP real ni sumar runners repetidos. No repetir esta etapa ni añadir funcionalidades al laboratorio sin fallo nuevo concreto.

Límite: se reprodujo código completo y sync vacío observado, no opciones privadas/configuración completa del proveedor. HTTP interno simula terminación TLS. El preflight del candidato debe comprobar la configuración admitida en destino; la entrega real de caché del hosting se informa aparte de la llamada a su API. El cierre local no autoriza instalación ni edición pública.

## Trabajo pendiente para edición operativa

Aplicación/revisión/aprobación CLI independiente ya integradas sobre rc.2:52 controles nuevos+132 regresiones, con cuotas propias, reservas y recibos v1 separados del estado del efecto. [Contrato actual](WORDPRESS_MCP_OPERATING_CONTRACT.md). No hay paquete ni scopes reales nuevos. Falta el executor de recuperación consumible y el cierre de esquema/candidato.

| Entrega pendiente | Iteraciones estimadas | Resultado verificable |
| --- | --- | --- |
| Recuperación, formalización y candidato | 1–2 | Recuperación con nueva aprobación y source_after exacto; protección y prueba de pérdida/reconexión DB (bloqueante), migración/decoder estrictos, schema/transporte MCP, export privado y ZIP/manifiesto probado con este perfil, preflight de configuración y reversión concreta |
| Instalación y aceptación autorizadas | 1 | Paquete/permisos/diff revisables y aprobados; Alex ejecuta SSH por fases; verificación real dentro del alcance autorizado |

**Estimación vigente restante:2–3 incrementos de trabajo.** Son entregas, no un número garantizado de mensajes o comandos. Esperas de resultados/aprobación o un fallo nuevo pueden aumentar las vueltas. Si cambia la estimación, registrar el bloqueo y el trabajo concreto adicional antes de sumar otra fase. No volver a presentar el perfil completo como pendiente.

Una edición pública requiere su diff concreto aprobado; no se programa ahora ni se considera autorizada por «continúa». No pedir autorización antes de tener candidato/plan concretos y revisables. Los fixtures REST de manage_options no son el autorizador operativo final.

## Almacenamiento y límites conservados

36GiB libres al inicio y después de la prueba completa. Imágenes cacheadas, red interna sin puertos, tmpfs512+768MiB por proyecto nuevo; RAM/swap posible. Contenedores detenidos al finalizar. Sin descargas de respaldos, SQL/config real, dependencias nuevas, SSH del agente, borrados de laboratorios/respaldo, cambios Workers o contacto. Credenciales HTTP solo sintéticas dentro del laboratorio; no se piden ni copian credenciales reales.
