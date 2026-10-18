# Ejecución del rediseño

## Fase 0.1 — en curso

Baseline: main, commit `8cea9f341a4de6602d487b9c8f959169fdfb9b3e`, conservando los cambios locales previos del plan, pruebas y correcciones. Node 22.23.2, npm 10.9.8. Sin publicaciones ni cambios en WordPress/Cloudflare.

- 14/14 pruebas pasan antes de actualizar.
- Build SSR correcto; 0 errores de tipos, 7 sugerencias de símbolos sin uso y avisos existentes de sharp/Cloudflare y Browserslist.
- `npm ls --depth=0` correcto antes de actualizar.
- Auditoría inicial npm: 45 paquetes con avisos (1 crítico, 33 altos, 10 moderados, 1 bajo). Clasificación de npm, no confirmación de explotabilidad de todas las funciones en este sitio.
- Disponibles en los rangos actuales: Astro 5.18.2, adapter Cloudflare 12.6.13, integración Tailwind 5.1.5, Tailwind 3.4.19, checker 0.9.10, TypeScript 5.9.3, graphql-request 7.4.0, dotenv 16.6.1 y tipos Node 22.20.5.
- Metadatos npm comprobados: Cloudflare 12.6.13 requiere Astro ^5.7.0; integración Tailwind 5.1.5 admite Astro 3/4/5 y Tailwind ^3.0.24; adapter Cloudflare 14.4.0 requiere Astro ^7.4.0 y Wrangler ^4.125.0.

Primer lote instalado mediante npm update dentro de los rangos actuales. Resultado: 14/14 pruebas pasan, build SSR pasa y npm ls --depth=0 no muestra conflictos. Auditoría posterior: 16 paquetes con avisos (1 crítico, 9 altos, 5 moderados, 1 bajo). Es un paso intermedio; no asumir que elimina todos los avisos. Hay avisos de Astro cuyo rango afecta también a Astro 5.18.2, por lo que el salto mayor debe evaluarse antes de declarar segura/completa la fase.

Copias locales previas a la instalación en `/tmp/alexcuadra-phase-01/` (temporales; no sustituyen un backup duradero ni un commit). Informes crudos de auditoría también temporales, sin credenciales.

## Información solicitada a Alex

Cloudflare → Workers & Pages → proyecto del sitio:

1. Tipo de proyecto: Pages o Workers.
2. Comando de compilación y directorio de salida configurados.
3. Existencia de staging/preview y su modalidad.

No compartir tokens, contraseñas ni valores de variables secretas. Esta información determina el adapter y el procedimiento de despliegue del siguiente lote mayor. No cambiar aún la restricción GraphQL ni desactivar el plugin de CPT.

## Pendiente tras el primer lote

- Validación del build en runtime Cloudflare, smoke de rutas y comparación visual.
- Matriz objetivo del salto mayor y revisión de guías de migración.
- Comprobación de staging antes de dar por cerrada la fase.
- Después: inventario del plugin CPT actual, registros ACF y contenido bilingüe con guía asistida.

El adapter actualizado emite un aviso de binding KV SESSION para sesiones. El sitio no usa sesiones explícitas; revisar su configuración y necesidad antes de desplegar, sin pedir crear KV por defecto. Persisten avisos de sharp y 7 sugerencias de símbolos sin uso. No se ha desplegado.

## Hosting confirmado por Alex

Cloudflare Pages conectado a GitHub, dominio `alexcuadra-dev.pages.dev`; build `npm run build`, salida `/dist`, raíz `/`. Staging todavía sin confirmar. No se ha cambiado esta configuración.

Smoke tras primer lote: servidor Astro 5.18.2 inicia y Home responde HTTP 200 con título Alex Cuadra y contenido del hero; no basta para dar por comprobadas todas las rutas.

Decisión requerida para el siguiente lote: el adapter moderno elimina soporte de Pages y requiere Workers, según https://docs.astro.build/en/guides/integrations-guide/cloudflare/ . Recomendación: preparar actualización y Worker de staging paralelo, mantener Pages y dominio de producción hasta validar y acordar el cambio. No crear recursos ni cambiar DNS en esta etapa. Si se decide permanecer en Pages, documentar las restricciones de versión y los avisos sin resolver; no declarar cerrada la actualización de seguridad.

## Lote Workers aprobado y preparado

Alex autorizó preparar Workers paralelo conservando Pages en producción. Rama local: `codex/workers-upgrade`.

| Componente | Versión validada localmente |
| --- | --- |
| Node | 22.23.2, mínimo Astro >=22.22.0 |
| Astro | 7.4.0 |
| @astrojs/cloudflare | 14.4.0 |
| Wrangler | 4.151.1 |
| Tailwind / plugin Vite | 4.3.3 / 4.3.3 |
| TypeScript / checker | 5.9.3 / 0.9.10 |
| graphql-request | 7.4.0 |

Retirada @astrojs/tailwind por incompatibilidad con Astro 7. Tailwind 4 usa plugin Vite y carga la configuración de colores legacy; actualizadas utilities incompatibles del botón de enlaces. Colección Markdown migrada a content.config.ts con glob loader, conservando archivos. Sesiones desactivadas para no necesitar KV; imágenes en modo compile, sin aprovisionar Cloudflare Images. Configuración Wrangler dedicada a `alexcuadra-dev-staging`, sin rutas/dominio de producción. Middleware noindex en SSR de workers.dev.

Validaciones realizadas: 14/14 pruebas unitarias/contrato; instalación limpia npm ci; build Astro/Workers correcto; npm ci reporta 0 vulnerabilidades conocidas. npm ls no presenta peers inválidos; muestra @img/sharp-wasm32 como extraneous incluso tras npm ci (paquete opcional de la cadena de imágenes, sin error de instalación/build). Revisar si afecta tamaño final antes de publicar; no se lo elimina a mano.

Preview del build activo con workerd en http://127.0.0.1:4321/. `npm run test:smoke` verifica HTTP 200, título, H1 y ausencia de errores conocidos en las siete rutas: Home, Work, About, Articles, Christianity Today, categoría Medium y su artículo. Estos smoke usan el CMS publicado y complementan las pruebas deterministas; no están en CI para evitar dependencia externa. Los controles nuevos 404/503 se implementarán en la fase de datos, no se declaran verificados todavía.

Revisión visual automática pendiente: el acceso al navegador fue rechazado por su política de URL; no se intentó eludir ese bloqueo. La verificación de HTML por HTTP y los logs de workerd son correctos, pero no sustituyen comparar capturas o revisar manualmente ambos temas.

No se ha creado Worker ni publicado en Cloudflare. Instrucciones concretas en WORKERS_STAGING.md. Falta validar staging real, revisión visual y autenticación antes de cerrar fase 0.1. No fusionar en main mientras su despliegue siga en Pages.

Rama publicada en origin/codex/workers-upgrade, commit inicial 394262c. main sin cambios. Worker de staging todavía no creado.

Alex inició la configuración desde repo con comandos de build/deploy; no se mostró selector de ramas y Preview command quedó por defecto. Pendiente confirmar nombre/URL del Worker y resultado del build. Selección de rama guiada desde Settings → Build/Builds → Branch control según documentación actual. No se declara desplegado ni validado staging.

Diagnóstico del log de Cloudflare aportado por Alex: Wrangler 3.99.0, CSS anidado y consulta redundante de PortfolioPreview siguen presentes; el build no corresponde al código migrado actual. Sin rama/SHA en el log no se identifica el commit exacto. Añadido prebuild con SHA y versiones del lockfile para el siguiente intento. No se imprimen variables de entorno ni secretos.

## Verificación del staging publicado

URL proporcionada por Alex: https://alexcuadra-dev-staging.alexcu21.workers.dev . La petición GET remota devuelve HTTP 500 con cuerpo vacío; HEAD también 500. Prueba explícita `npm run test:smoke -- https://alexcuadra-dev-staging.alexcu21.workers.dev` falla en Home. Corregido el runner para aceptar e imprimir destino por argumento: la primera ejecución con variable de entorno no constituye evidencia válida del remoto. Staging desplegado pero NO funcional/aceptado aún. Se requieren logs de ejecución del Worker para identificar la excepción; el log de build no basta. No avanzar al cambio de dominio ni restricción GraphQL.

Alex informa que staging ya es visible. Activados logs e invocation_logs en configuración Wrangler de staging para registrar errores y solicitudes tras el siguiente despliegue. Se mantiene pendiente comprobar respuestas remotas y noindex.

Comprobación posterior: smoke remoto pasó siete rutas, pero solicitudes directas GET/HEAD también devolvieron 500 con cuerpo vacío y sin X-Robots-Tag. Se confirma intermitencia y no se declara resuelta. La configuración logs.enabled/invocation_logs es válida según el schema y código de Wrangler 4.151.1; no se atribuye el panel vacío a falta de enabled raíz. Pedir versión activa y logs sin filtros; correlacionar CF-Ray a4c43429bccaccbc-MIA (petición GET fallida).

## Diagnóstico de errores aportados por Observability

Los eventos correlacionan fallos de Home/proyectos/categorías con TypeError al acceder a homePageFields; la misma versión de Worker también devuelve 200 después. No prueba un problema de cold start. Alex confirma que la restricción de autenticación WPGraphQL sigue desactivada. Los errores actuales no conservan HTTP status ni respuesta GraphQL útil, por lo que la causa del fallo upstream sigue pendiente.

Corrección: transporte central con timeout de 10 s y errores tipados/clasificados, sin volcar respuestas/headers; eliminar fallback de arrays vacíos ante fallo; validar Home antes de renderizar. Scope AsyncLocalStorage por petición para deduplicar consultas de globales sin compartir caché entre visitantes. Middleware convierte fallos de CMS registrados en 503 no-store con Retry-After y noindex; no disfraza errores de programación ajenos al CMS. Respuestas de staging se clonan antes de añadir cabeceras para admitir headers inmutables. Retirados enlaces a manifest e iconos inexistentes; se conserva favicon.svg.

25/25 pruebas pasan, incluyendo rechazos HTTP/GraphQL, ausencia de Home, timeout, logs redactados, aislamiento/deduplicación y 503. Build Astro/Workers pasa. La clasificación de la causa upstream debe comprobarse en los nuevos eventos cms_failure tras despliegue; esta entrega no implementa aún autenticación ni afirma resolver la disponibilidad de WordPress.
