# Workers staging: preparación y configuración

Estado actual: Worker creado y desplegado, autenticación de servicio confirmada y smoke remoto correcto. Persiste el seguimiento de la intermitencia de Hostinger. Pages sigue siendo producción; la preparación del traslado está en [WORKERS_PRODUCTION.md](WORKERS_PRODUCTION.md). Los pasos de creación posteriores se conservan como referencia del procedimiento.

## Preparación local

Node 22.23.2 (`.nvmrc`), Astro 7.4, adapter Cloudflare 14.4 y Tailwind 4 mediante su plugin Vite. `wrangler.jsonc` apunta al nombre propuesto `alexcuadra-dev-staging`, sin dominio personalizado ni rutas de producción. Sesiones desactivadas: no se necesita KV. Imágenes en modo compile para no provisionar Cloudflare Images. Los archivos de secretos y el estado local de Wrangler están excluidos de Git.

`npm run build` genera la aplicación; `npm run preview -- --host 127.0.0.1 --port 4321` sirve el build con workerd. `npm run deploy:staging` compila y despliega al Worker configurado: no ejecutarlo antes de revisar cuenta y destino.

## Pasos guiados en Cloudflare, cuando la rama esté publicada

1. Mantener el proyecto Pages actual sin modificar y no cambiar DNS ni el dominio alexcuadra.dev.
2. En Workers & Pages, crear una aplicación de **Workers** conectada al repositorio `alexcu21/alexcuadra.dev`. La interfaz puede iniciar directamente el flujo de Workers sin un selector Workers/Pages. Si la creación no muestra ramas, completar la creación del Worker separado y seleccionar la rama después en sus ajustes; la rama ya está publicada.
3. Nombre del nuevo Worker: `alexcuadra-dev-staging`, coincidiendo exactamente con `wrangler.jsonc`. Si no está disponible o prefieres otro nombre, actualizar juntos dashboard y archivo antes de desplegar.
4. En el Worker creado: **Settings → Build (o Builds) → Branch control**, cambiar la production branch a `codex/workers-upgrade` y guardar. Cloudflare toma por defecto la rama predeterminada del repo si no se ofrece selector durante creación. Seleccionar `codex/workers-upgrade` como rama de build de este proyecto de staging. No seleccionar main: main todavía sirve el proyecto Pages. La etiqueta de “production branch” en ese Worker se refiere a ese Worker de staging, no al dominio del sitio público.
5. Directorio raíz: raíz del repositorio. Build command: `npm run build`. Deploy command: `npx wrangler deploy`. Workers no usa el campo de output `/dist` como Pages; el adapter/Wrangler resuelve el artefacto generado. No configurar simultáneamente el script que vuelve a compilar y el build command si ello duplica la compilación.
6. Confirmar que el entorno de build respeta `.nvmrc` (22.23.2). Si ofrece variable de versión Node, usar el mismo valor. No pegar tokens en Git ni en el chat; usar la integración gestionada por Cloudflare para autorizar el repositorio.
7. Revisar el despliegue y abrir la URL del nuevo Worker `*.workers.dev`. No asociar dominios personalizados ni rutas de producción. No conectar recursos de escritura de producción.
8. Comprobar Home, Work, About, Articles, un proyecto, una categoría y un artículo; navegación móvil, tema, imágenes y metadatos. Compartir URL y, si falla, mensaje de error sin secretos.

El middleware añade `X-Robots-Tag: noindex, nofollow` a respuestas SSR bajo workers.dev. No es autenticación ni protección de contenido privado. Antes de incorporar borradores o datos privados, configurar control de acceso de staging y validar sus reglas. Esta fase solo utiliza el contenido que el sitio público ya consulta.

Todavía no activar la restricción GraphQL: falta preparar y validar el transporte autenticado. No desactivar el plugin custom de CPT: su investigación/transición pertenece a la fase siguiente.

## Publicación y rollback

La subida de la rama debe excluir `src/pages/.claude/` y cualquier secreto; revisar el diff y el lockfile primero. Publicarla puede activar previews del proyecto Pages existente si tiene builds de otras ramas habilitados; revisar esa configuración antes de subir. No fusionar esta migración en main mientras el despliegue de producción siga configurado para Pages.

Si staging falla, conservar Pages y corregir el Worker sin cambiar DNS. El cambio definitivo a Workers se hará como una operación separada después de completar pruebas, SEO, autenticación y revisión de dominios.

Documentación: [Workers Builds](https://developers.cloudflare.com/workers/ci-cd/builds/), [configuración de builds](https://developers.cloudflare.com/workers/ci-cd/builds/configuration/), [adapter Astro Cloudflare](https://docs.astro.build/en/guides/integrations-guide/cloudflare/).

## Aclaración del flujo observado por Alex

La creación mostró selección de repo, build/deploy command y Preview command, sin selector de ramas. Esto es compatible con el flujo Workers Builds. Después de crear el Worker separado, cambiar la rama en Settings → Build/Builds → Branch control. Si arrancó un build de main, no demuestra que esté usando esta migración: inspeccionar rama y commit y lanzar un build de codex/workers-upgrade después de guardar.

Preview command puede conservarse por defecto en esta etapa. La documentación actual usa `wrangler preview` para proyectos nuevos; no asumir su valor exacto sin leer el campo. Ese comando gestiona previews y no selecciona la rama del despliegue principal del Worker de staging. Si hay error, compartir mensaje de build, nombre del Worker, rama/commit y URL workers.dev, sin credenciales.

Fuente: https://developers.cloudflare.com/workers/ci-cd/builds/build-branches/ y https://developers.cloudflare.com/workers/ci-cd/builds/configuration/ .
