# Ensayo de recuperación staging —20 octubre2026

## Resultado

**Correcto y restaurado al estado inicial.** Se cambió tráfico del Worker `alexcuadra-dev-staging` al100% desde `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c` a `8783d505-39d8-4287-8779-7b3423044dc1` y después de vuelta a `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c`. No se subió una versión nueva ni código local. TOC/anchors continúan solo en local, pendientes de aceptación visual.

La API confirma los dos deployments del ensayo:

| Fase | Deployment | Versión al100% |
| --- | --- | --- |
| Retorno anterior | `2192c384-d1b0-4cfc-8f69-e82b634c417a` | `8783d505-39d8-4287-8779-7b3423044dc1` |
| Restauración inicial | `8a1ca668-17a7-4740-823f-163fe660a738` | `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c` |

Comprobación posterior independiente del historial: exactamente esos dos nuevos deployments frente al inventario previo y versión final esperada al100%. Resultado saneado en [STAGING_RECOVERY_RESULT_2026-10-20.json](STAGING_RECOVERY_RESULT_2026-10-20.json).

## Procedimiento y verificaciones

Primero se inspeccionaron las versiones y configuración sin modificar tráfico. Metadatos de ambas: contacto false, autenticación CMS activa, misma clase/binding del coordinador, bindings idénticos, runtime/exports/compatibilidad y named handlers idénticos. No se expusieron valores privados ni salida cruda del CLI. La igualdad de metadatos no sustituye las comprobaciones runtime posteriores.

Se ejecutó el [procedimiento acotado a staging](../STAGING_RECOVERY.md):

```sh
node scripts/staging-recovery.mjs --current bbb91792-89ba-4fac-88fb-6b67d1eb7b6c --target 8783d505-39d8-4287-8779-7b3423044dc1 --execute --output docs/qa/STAGING_RECOVERY_RESULT_2026-10-20.json
```

Baseline, versión anterior y versión restaurada pasaron los mismos checks: Home200 con H1/identidad CMS verificada/noindex, ausencia del formulario/widget, recurso CSS/JS del propio HTML200 con tipo correcto y endpoint de contacto404/`CONTACT_DISABLED`/no-store. Se hicieron tres peticiones Home respaldadas por CMS, espaciadas≥30s; assets y contacto no consultan CMS. No hubo reintentos ni envíos/challenges. Intervalo del script18:52:08–18:53:12UTC (64s, no un RTO garantizado).

237 pruebas unitarias pasan. Nueve nuevas cubren rechazo de producción/tráfico dividido/targets desconocidos, diferencias de metadatos privados sin filtrarlos, contacto/autenticación/clase incompatibles, secuencia normal, restauración tras fallo de salud o confirmación perdida, ausencia de cambios ante fallo inicial, detección de despliegue concurrente y diferencia entre restauración de versión y salud final. `git diff --check` correcto. No se repitió build/SSR: cambiaron herramientas operativas/docs, no la aplicación. El build local TOC validado anteriormente permanece preparado.

## Límites

Este ensayo verifica recuperación de código/configuración/asset y lectura CMS del Worker staging; no restaura BD/medios/ACF/Polylang, estado SQLite, secretos revocados, DNS o producción. Cloudflare crea un nuevo deployment al volver a una versión y no revierte los datos de los recursos conectados; puede rechazar rollbacks que crucen cambios de ciclo de vida de Durable Objects. [Fuente oficial](https://developers.cloudflare.com/workers/versions-and-deployments/rollbacks/).

El guard restaura en `finally` tras errores detectados, inspecciona estado antes de cada cambio y evita sobrescribir una tercera versión o tráfico dividido que detecte. No hay bloqueo atómico contra despliegues externos ni garantía ante terminar el proceso/equipo o perder acceso al proveedor; el runbook incluye recuperación manual. Este ensayo no certifica compatibilidad con cualquier versión histórica ni sustituye una prueba de restauración de almacenamiento.

No hubo fallo CMS visible en las tres comprobaciones Home; no se atribuye resolución al429 histórico ni estabilidad permanente. No se repitió toda la matriz Blog/LAB. Contacto desactivado en cada fase y al terminar, sin CMS/roles/MCP ni producción modificados. Cambios locales conservados, sin commit/reset/limpieza. Navegador no reintentado ni eludido; visual/zoom/lector siguen pendientes.

## Continuidad

El ensayo de recuperación de este par de versiones staging está completado: no repetirlo en el siguiente turno por confundir los pendientes antiguos con el estado actual. Sigue prioritaria la aceptación visual del TOC/Gutenberg EN/ES cuando haya acceso efectivo o evidencia manual; después preparar su revisión staging sin publicar producción. Trabajo independiente disponible: iniciar el contrato y la extensión de lectura MCP editorial en un laboratorio aislado, según su plan, sin instalarla ni ampliar permisos en el CMS compartido. La etapa editorial MCP y el diagnóstico429 siguen separados.
