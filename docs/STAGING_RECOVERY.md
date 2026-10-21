# Recuperación del Worker staging

Este procedimiento ensaya volver a una versión anterior publicada y restaurar después la versión que estaba activa. Está fijado a `alexcuadra-dev-staging` y su dominio workers.dev; no acepta otro Worker ni un entorno de producción. No compila ni sube el árbol local. No instala ni modifica plugins, permisos o contenido WordPress. Mantiene el contacto false y no envía correo ni challenges.

## Versiones y compatibilidad

Identificar dos IDs de versión explícitos: `--current` es la versión activa al100% y `--target` una anterior ya desplegada. Los IDs son de versiones, no de deployments. No usar «última» como destino implícito. No repetir una retirada editorial del CMS como parte del rollback del frontend.

Primero ejecutar solo la inspección:

```sh
npm run staging:recovery -- --current VERSION_ACTUAL --target VERSION_ANTERIOR --output /private/tmp/staging-recovery-preflight.json
```

El script exige un único destino activo al100%, versiones distintas y metadatos disponibles para ambas. Verifica contacto desactivado, autenticación CMS y binding/clase del coordinador presentes. Compara exactamente bindings, configuración runtime y named handlers; se rechazan cambios de configuración, despliegues divididos o versiones que no figuren en el historial consultado. Es una comprobación conservadora: un rechazo no significa necesariamente que Cloudflare prohíba todo rollback posible.

Wrangler devuelve metadatos que pueden contener nombres/autores y valores privados. El script mantiene la salida y errores en memoria y solo guarda IDs de versión, checks booleanos y resultados HTTP proyectados; no copiar la salida cruda al repositorio/chat. El script no abre ni analiza `.dev.vars` ni exporta credenciales. La presencia de un secret binding no demuestra por sí sola que su valor funcione; la comprobación de identidad de Home se hace por HTTP en el ensayo.

Cloudflare restaura código/configuración de una versión mediante un nuevo deployment, sin revertir los datos de los recursos conectados. Además, puede rechazar un rollback si hubo cambios de ciclo de vida de Durable Objects o recursos eliminados. La decisión final sigue siendo del proveedor: el script no fuerza migraciones ni relaja esas restricciones. [Documentación oficial de rollbacks](https://developers.cloudflare.com/workers/versions-and-deployments/rollbacks/).

## Ensayo

Tras una inspección correcta, usar los mismos IDs explícitos:

```sh
npm run staging:recovery -- --current VERSION_ACTUAL --target VERSION_ANTERIOR --execute --output /private/tmp/staging-recovery-result.json
```

El script vuelve a inspeccionar estado/configuración y valida Home antes de cambiar tráfico. Después selecciona la versión anterior con `wrangler rollback ID --yes` y verifica en la API que está activa al100%. Comprueba el endpoint de contacto, Home autenticada y uno de los recursos CSS/JS enlazados por ese HTML. Por último restaura la versión inicial y repite esas comprobaciones. Las visitas a Home se separan al menos30s; assets y contacto desactivado no consultan CMS. No hay reintentos HTTP ni de rollback automáticos.

Los checks de cada fase son Home200 con H1/identidad CMS/noindex, ausencia del formulario/widget, asset CSS/JS200 con tipo correcto y `/api/contact`404/`CONTACT_DISABLED`/no-store. No constituyen una suite completa Blog/LAB, revisión visual, prueba de envío o diagnóstico de estabilidad del hosting. Se realizan tres consultas frontend respaldadas por CMS, una por fase, en una ejecución correcta.

La restauración se intenta en `finally` incluso si falla la salud de la versión anterior o se pierde la confirmación del primer cambio. Antes de restaurar se inspecciona otra vez: si se detecta una tercera versión o tráfico dividido, no se sobrescribe ese despliegue. La inspección y el cambio no son una operación atómica ni bloquean despliegues externos: coordinar una ventana sin otros despliegues para ejecutar el ensayo. Si sigue activa la versión inicial, no se crea otra restauración innecesaria. El resultado `restored=true` certifica la versión activa; solo `complete=true` incluye las verificaciones HTTP finales.

## Si el resultado queda incompleto

Leer `failure`, `failurePhase`, `restoreFailure` y `phases` del resumen. Una falla de Home antes del primer cambio deja `rollbackAttempted=false`; no insistir en el CMS si devuelve429. Si el estado final es desconocido o hay un error de restauración, inspeccionar primero el deployment activo y coordinar cualquier cambio concurrente. No lanzar a ciegas otro deploy del código local.

Para inspeccionar un destino conocido desde CLI, usando exclusivamente staging:

```sh
./node_modules/.bin/wrangler deployments list --name alexcuadra-dev-staging --config wrangler.jsonc --json > /private/tmp/staging-deployments-private.json
```

Extraer solo IDs/porcentajes con `latestActiveVersions` antes de mostrar o guardar evidencia. Si sigue activa la versión de ensayo y no hay otro cambio, se puede restaurar la versión inicial explícita con `wrangler rollback ID_INICIAL --name alexcuadra-dev-staging --config wrangler.jsonc --yes`; después comprobar de nuevo estado y HTTP. Un proceso terminado externamente o una pérdida total de acceso a Cloudflare requieren esta revisión manual: `finally` no garantiza recuperación tras detener el proceso o el equipo.

Este ensayo no restaura BD/medios/ACF/Polylang, estado SQLite del coordinador, secretos rotados/revocados ni DNS. La recuperación de esos componentes necesita sus copias y procedimientos propios. No ensayar cambios de esquema o restauraciones de almacenamiento en el CMS compartido como parte de este script.

Resultado del primer ensayo: [QA20 octubre2026](qa/STAGING_RECOVERY_2026-10-20.md).
