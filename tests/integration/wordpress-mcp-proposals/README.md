# Aceptación CLI y archivo sintéticos del candidato

Actualización de transporte: [140 comprobaciones nuevas HTTP/MCP/GraphQL y seis carreras](../../../docs/qa/MCP_PROPOSALS_TRANSPORT_2026-10-22.md) pasaron con `run.py --transport`, proyecto separado `alexcuadra-editorial-proposals-transport-v2` y overlay `compose.transport.yaml`. HTTP solo dentro de Docker; contexto de terminación HTTPS simulado por fixture, sin TLS real. Guard y ZIP intactos, contenedores detenidos. Ruta REST de contexto y barrera son exclusivamente instrumentación. No repetir sobre recursos existentes; `--transport --resume` exige identidad DB coincidente si persiste, pero ese modo no fue parte de la ejecución aprobada. Mantener separados los165 CLI y140 de transporte; instalación aún HOLD.

**165 comprobaciones correctas con Polylang3.9.1; instalación real HOLD.** [QA y límites](../../../docs/qa/MCP_PROPOSALS_SYNTHETIC_ACCEPTANCE_2026-10-22.md). Este arnés usa datos ficticios, no SSH ni el CMS compartido. No crea bypass en el candidato.

## Dependencias verificadas y espacio

Alex confirmó Polylang3.9.1 activo; la captura3.9.0 anterior se conserva como histórica. `cache-plugins.py` obtiene solo fuentes públicas fijadas: Polylang3.9.1, MCP Adapter0.7.1, WPGraphQL2.24.2, ACF6.8.10, WPGraphQL ACF3.0.0 y WPGraphQL Polylang0.7.1. Usa TLS con CA del sistema y límites48MiB comprimidos/256MiB extraídos. Caché `/private/tmp/alexcuadra-proposals-public-plugins-v2`,12727828B descargados/39843103B extraídos, du56MiB incluyendo bloques/ZIPs. No es un backup del sitio. El script rechaza caché no vacía: no sobrescribirla para repetir.

El runner coteja manifiesto/versiones, SHA256 de los ZIPs y cada archivo extraído sin extras. El overlay `compose.public-cache.yaml` monta esa caché solo lectura en seed sin red. No reutiliza volúmenes anteriores; el intento inicial falló porque un seed tmpfs retenido estaba vacío. [Diagnóstico histórico](../../../docs/qa/MCP_PROPOSALS_ACCEPTANCE_PREPARATION_2026-10-22.md). La URL3.9.0 fallida fue superada por el perfil3.9.1 confirmado, sin reescribir evidencia anterior.

Requiere Docker Desktop, socket local e imágenes fijadas ya presentes (`--pull never`). Umbral8GiB libres; captura final31GiB. Proyecto `alexcuadra-editorial-proposals-acceptance-v2`; DB tmpfs512MiB para original+restaurada, WordPress tmpfs768MiB, SQL tmpfs64MiB. Suma configurada1344MiB; RAM/swap/logs/capas Docker añaden consumo y no se prometen límites totales de disco. No descarga imágenes ni copias del CMS de≈690,5MiB. Tmpfs detenido no es retención durable.

## Aislamiento y ejecución

Red `internal:true`, sin puertos host; seed `network_mode:none`. WP_HOME/WP_SITEURL/opt-in exactos solo dentro de esta copia sintética. No se llama al origen compartido. Fixture MU bloquea HTTP WordPress y correo; cron/actualizaciones/file mods deshabilitados antes de activar plugins. No se copian Hostinger/LiteSpeed ni se certifican todos sus efectos; el aislamiento de red protege también transports ajenos a los filtros WordPress.

El runner compara ZIP/manifiesto/fuentes del candidato byte por byte antes de copiar sus tres archivos. No modifica paquete/guard/versión. Instala administrador/usuarios ficticios y concede scopes solo a fixtures. Crea dos tablas explícitamente con operador CLI. Se ejecutó:

```sh
python3 tests/integration/wordpress-mcp-proposals/run.py
```

**No repetir ahora:** el runner exige proyecto nuevo y rechaza los contenedores/volúmenes v2 conservados; revisar resultados/recursos antes de decidir continuación. No reset/down-v/prune automático. Solo detiene wordpress/db de su propio proyecto en finally, sin tocar los otros laboratorios. La consulta posterior confirmó ambos Exited(0). Desde esta revisión, futuras ejecuciones también guardan el resultado en `/private/tmp/alexcuadra-editorial-proposals-acceptance-v2-result.json`; la primera ejecución exitosa se documentó desde su salida final observada.

## Cobertura lograda

- 67 framework/permisos/patch/idiomas/adaptador oficial en proceso,44 historial y37 idempotencia/cuotas/fallos SQL seleccionados. Tres suites existentes adaptadas únicamente en tests temporales de namespace/origen/tabla; originales y candidato intactos. No HTTP ni carreras multiproceso aquí.
- 7 preparación de archivo y10 restauración: crea propuesta/actualización, digest de filas fuente/propuesta/meta/relaciones/owners/recibos, DDL privado y versión de esquema; dump SQL consistente de12845477B dentro del contenedor y restauración en `wordpress_restore` separada.
- Replay create/update idéntico tras restaurar, cuota/filas sin cambio, cabeza actual conservada y fuente del fixture intacta. Se comprueba revocación del dueño restaurado y se retiran scopes del original al terminar; suites previas retiran sus scopes ficticios. Algunas suites alteran fuentes sintéticas deliberadamente para comprobar invalidación; no afirmar que todos los fixtures quedaron inmutables.

El marcador `PROPOSALS_ACCEPTANCE_RESULT` y exit0 confirmaron165 correctas. No sumar95 stubs anteriores ni531 del laboratorio a este resultado. No se afirma respaldo del hosting, archivo durable, exportador selectivo, privacidad HTTP/GraphQL, carreras ni esquema exhaustivo frente a tablas incompatibles. Siguen pendientes esas puertas, perfil real actualizado, destino/responsable/retención y aprobación concreta antes de instalar o conceder scopes reales.
