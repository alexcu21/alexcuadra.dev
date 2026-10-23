# Admisión del journal y efectos — 23 octubre

120 controles operativos y132 regresiones pasan:252 integraciones,9 casos nuevos. Perfil completo y rc.2 con datos sintéticos, WordPress/MariaDB/ACF/Polylang/LiteSpeed reales.

Journal: filas con claves exactas, IDs/hashes/operación válidos y recibo JSON array acotado a64KiB/profundidad32. Se valida al repetir el journal y antes de consumir un efecto. El journal genérico conserva recibos de fixtures existentes; los recibos operativos usan además su decoder cerrado al consultar estado.

Efectos: estados cerrados, enteros SQL canónicos, límites de intentos, ID/horario y coherencia de lease/token/outcome. Se validan antes de reclamar y antes de exponer estado. Un evento ausente sigue sin ser ejecutable; un evento corrupto o Project distinto se rechaza. La política de leases, reintentos y8 intentos no cambia.

Estado operativo: comprueba relación de último evento apply/recover con Project, aplicación, revisión de la operación, aprobación consumida y recibo; la aplicación enlaza su source_after y la recuperación su evento original. No vuelve a escribir contenido ni reinterpreta el recibo original.

Nueve casos nuevos: estado desconocido, token inválido,9 intentos, cero llamadas de caché en los tres casos; recibo del journal null; Project del efecto cruzado rechazado tanto por consumidor como por consulta de estado; revisión del recibo cruzada; estado válido conservado.

Límites: son comprobaciones al leer/consumir y en consulta de estado, no una auditoría exhaustiva de todas las filas o relaciones. Faltan auditoría global de requests/event_hash/recibos/orígenes, migración/admisión finales reanudables, schema/transporte MCP, export privado y paquete/manifiesto/preflight/reversión; después instalación y diff autorizados. No se certifica un atacante con escritura DB privilegiada, la configuración privada completa ni entrega de caché hosting. No hay candidato instalable nuevo.

Una ejecución completa con imágenes existentes y red interna propia reutilizada, sin puertos/pulls/SSH/SQL o config real/grants/CMS/Workers/contacto/backups nuevos. WP/DB tmpfs; Docker/swap pueden usar disco.32GiB libres al inicio. Contenedores detenidos, recursos previos retenidos.
