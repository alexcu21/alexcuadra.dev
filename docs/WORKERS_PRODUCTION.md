# Migración de producción de Pages a Workers

Estado al 18 de octubre de 2026: Worker `alexcuadra-dev` creado, identidad de servicio confirmada y dominio `alexcuadra.dev` trasladado por Alex. Smoke público: favicon y siete rutas correctas; Home HTTP 200 con identidad verificada, canonical público correcto y sin noindex. Restricción global de WPGraphQL activa y validada: rechazo anónimo y smoke autenticado correcto en producción, staging y local. Pages se conserva como respaldo. Pendientes revisión visual, seguimiento de disponibilidad y coordinación de main.

Los pasos siguientes describen el procedimiento de migración y reversión. La ruta custom_domain se guarda solo en env.production; staging no recibe dominios de producción.

## Destinos separados

| Entorno | Worker | Build | Deploy en Workers Builds |
| --- | --- | --- | --- |
| Staging | alexcuadra-dev-staging | npm run build | npx wrangler deploy |
| Producción propuesta | alexcuadra-dev | npm run build:production | npx wrangler deploy |

El entorno Wrangler `production` se selecciona durante el build mediante `CLOUDFLARE_ENV=production`. Astro genera la configuración que Wrangler despliega; no usar el artefacto de staging para producción ni añadir `--env` después de compilar otro entorno. CI valida ambos builds sin desplegar ni utilizar secretos.

El dominio `alexcuadra.dev` está declarado únicamente en `env.production.routes`. La autenticación está habilitada en `env.production.vars` y usa credenciales propias de runtime. Los secretos de staging no se transfieren automáticamente al otro Worker.

## Paso actual: crear el Worker sin trasladar el dominio

1. Cloudflare → Workers & Pages → Create application → conectar `alexcu21/alexcuadra.dev`.
2. Crear un **Worker nuevo** llamado `alexcuadra-dev`. Si está ocupado, comunicarlo antes de elegir otro nombre: debe coincidir con `env.production.name` de Wrangler. No renombrar staging ni el proyecto Pages.
3. Configurar raíz `/`, build `npm run build:production` y deploy `npx wrangler deploy`.
4. En Settings → Build/Builds → Branch control seleccionar `codex/workers-upgrade` para este Worker mientras se valida la migración. No cambiar todavía el Pages de main.
5. En WordPress, usar la cuenta de servicio Suscriptor y crear **otra contraseña de aplicación**, llamada `Portfolio Worker production`, para poder revocarla sin afectar staging.
6. En el nuevo Worker → Settings → Variables and Secrets de runtime, guardar `WPGRAPHQL_USERNAME` y `WPGRAPHQL_APPLICATION_PASSWORD` como secretos. No guardar sus valores en Builds ni compartirlos por chat. `WPGRAPHQL_AUTH_ENABLED=true` ya forma parte de la configuración del entorno.
7. Guardar/desplegar según indique Cloudflare. Sin credenciales el Worker devolverá 503: no dar permisos administrativos ni desactivar el control para resolverlo.
8. Compartir únicamente la URL `workers.dev`, estado del despliegue y SHA. Todavía no añadir el dominio público.

## Validación antes de trasladar el dominio

- Verificar identidad y siete rutas: `npm run test:smoke -- https://URL-DEL-NUEVO-WORKER --require-cms-auth`.
- Comprobar que el build generado se dirige a `alexcuadra-dev`, no a staging.
- Revisar imágenes, enlaces, tema, navegación móvil, contenido, metadatos/canonical y formularios si los hay. Las pruebas HTTP no sustituyen esta revisión visual.
- Revisar Observability con navegación normal; una pasada correcta no prueba disponibilidad sostenida ni elimina el límite de Hostinger.
- Mantener `noindex` en workers.dev. El middleware lo aplica por hostname; verificar indexación correcta al trasladar el dominio público.
- Inventariar dominios asociados a Pages (apex y posible www), redirecciones y configuración DNS actual, sin compartir secretos. Confirmar los dominios exactos antes de ejecutar el traslado.

## Traslado coordinado: pendiente, no ejecutarlo aún

Con el Worker validado y la configuración actual inventariada, preparar una ventana y el procedimiento exacto para retirar la asociación de Pages y añadir el Custom Domain al Worker. Cloudflare no permite crear un Custom Domain sobre un hostname que conserva un CNAME existente. No borrar registros de WordPress, correo ni otros servicios. Registrar el destino DNS anterior para poder restaurarlo y conservar el proyecto Pages y su despliegue como respaldo.

Tras configurar el dominio, sincronizar las rutas `custom_domain` en `env.production` antes del siguiente despliegue y verificar HTTPS, canonical, robots/noindex, rutas y redirecciones. El host `content.alexcuadra.dev` no se cambia en esta migración.

Antes de fusionar en main, desactivar los builds automáticos del proyecto Pages que siguen main: la versión nueva del adapter no despliega en Pages. Después coordinar el cambio de rama del Worker de producción a main. No dejar que dos proyectos intenten desplegar main con destinos incompatibles.

## Activación final de la restricción GraphQL

Solo cuando el dominio público ya sirva el Worker autenticado, desarrollo tenga sus credenciales y se hayan inventariado otros consumidores, activar en WordPress «Limit the execution of GraphQL operations to authenticated requests». Validar rechazo anónimo y continuidad del sitio autenticado. La autenticación no asegura una excepción al rate limit de Hostinger.

## Reversión

Antes del traslado, basta conservar Pages y corregir el Worker. Después del traslado, restaurar primero la configuración de acceso del CMS que requiera Pages (si ya se restringió, desactivar esa restricción), retirar el Custom Domain del Worker y volver a asociarlo correctamente a Pages con su DNS anterior. No basta con crear un CNAME aislado sin registrar el dominio en Pages. Confirmar TLS y respuestas después de restaurar. No eliminar Pages ni revocar sus accesos hasta cerrar el período de validación acordado.

## Fuentes oficiales

- [Astro: entornos de Cloudflare](https://docs.astro.build/en/guides/integrations-guide/cloudflare/)
- [Cloudflare Workers: Custom Domains](https://developers.cloudflare.com/workers/configuration/routing/custom-domains/)
- [Cloudflare Pages: Custom domains](https://developers.cloudflare.com/pages/configuration/custom-domains/)
