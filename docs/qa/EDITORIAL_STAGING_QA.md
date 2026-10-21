# Blog/LAB en staging — 19 octubre 2026

## Estado

**Desplegado; QA remoto parcial por429 upstream de WordPress. No aprobado para producción.**

Worker: `alexcuadra-dev-staging`. Versión actual: `8783d505-39d8-4287-8779-7b3423044dc1`. Punto anterior a este incremento: `36744d8c-bd3f-47f5-8162-e85ddc54f094`. Versión intermedia de esta sesión: `03f542d2-de3e-486c-8b08-8a8577ab1cec`, anterior a la reducción de consultas.

Se publicó el árbol local validado, conservando sus cambios sin commit/reset/limpieza. El build identifica el commit base; eso no convierte el despliegue del árbol modificado en una revisión de Git consolidada. Producción conserva la versión registrada `0739b280-1c56-436d-ab28-603970271293`; no se desplegó allí. No hubo cambios CMS, permisos, roles ni contacto en esta sesión.

## Paquete y pruebas locales

- 201 unitarias correctas, incluida caché pública de disponibilidad.
- Suite SSR de referencias/Blog/LAB correcta;200/404/503 y contenido real de templates con transporte sintético.
- Build correcto:149 archivos revisados,0 errores/advertencias/hints Astro. Avisos conocidos de configuración Shiki/CSP conservados.
- Dry-run correcto antes de cada publicación. Config generado comprobado: nombre staging, sin rutas de producción, keep_vars=true, CONTACT_FORM_ENABLED=false.
- SHA256 de `dist/server/entry.mjs` desplegado: `e8376a1cf0fdcbfe907fb966b1414316c35464139e25c4f3eb9e1233265e2d87`. El paquete incluye además módulos/recursos; este hash identifica solo el entrypoint.
- Smoke local de ambas secciones completo: archivos/fichas EN/ES, canonical/hreflang, renderer, sitemap43URLs, ausencia de fallback y contacto desactivado. Astro dev no emitió CSP; esa comprobación se reservó al Worker compilado.

## Cambio de carga al CMS

Los primeros intentos de QA remoto se detuvieron en503. Logs estructurados confirmaron `CMS_HTTP`, httpStatus429, en BlogAvailability, SitemapReferences, LocalizedHome/LocalizedAbout y posteriormente BlogPost. Una petición de diagnóstico a `/es/blog/` recuperó200 y `X-CMS-Auth: verified`; eso demuestra intermitencia, no resolución del hosting.

Disponibilidad de Blog/LAB ahora usa la caché pública de60s existente: cero o un nodo publicado, idioma explícito y proyección solo de status/isRestricted/hasPassword/language.code. Sin IDs, contenido o ACF. Valida también cache hits y separa claves por consulta/idioma/credenciales. Logs reales confirman MISS→HIT con attempt0; tests cubren campos extra, visibilidad incorrecta, rotación y proyección idempotente. Los cuerpos/archivos editoriales y la verificación de identidad Home siguen consultando origen.

No hay reintentos de aplicación, fallback a contenido caducado ni relajación de controles. La mejora reduce consultas repetidas; **no resuelve el429 del hosting**. Una retirada editorial puede tardar hasta60s en reflejarse en el menú por este TTL.

`scripts/smoke.mjs` incorpora `--check-blog-lab`, `--editorial-only` y `--request-gap-ms` (0–30000). Descubre las fichas desde archivos reales, reutiliza las páginas ya comprobadas dentro de la ejecución y acepta Blog/LAB publicados en sitemap. Comprueba noindex de páginas workers.dev, CSP/hashes de scripts y los contratos de idiomas/404/contacto. No reintenta fallos.

## Resultado remoto de la versión actual

Último smoke ejecutado con diez segundos entre peticiones. Pasó:

- Favicon308 y dos rutas técnicas404 sin consultar identidad CMS.
- Sitemap43URLs canónicas y robots sin anunciar sitemap en workers.dev; [XML capturado](editorial-staging-sitemap.xml).
- Home200 con identidad verificada, canonical/SEO, CSP y hashes ejecutables; staging noindex.
- `/api/contact`:404, no-store, CONTACT_DISABLED; Home sin formulario/widget.
- Archivos Blog EN/ES y ficha Blog EN:200, canonical/SEO, CSP/hashes y renderer compartido con unH1.
- Disponibilidad Blog/LAB cacheada con HIT real.

Se detuvo en `/es/blog/publicacion-de-ejemplo-como-estructurar-un-caso-de-prueba`:503 causado por BlogPost upstream429. Por ello **no se atribuye éxito a la secuencia remota completa**, ni a las comprobaciones posteriores LAB/404 de ese smoke. El XML correcto no prueba que todas sus URLs estén disponibles de forma estable. No se repitieron suites en bucle tras ese fallo.

## Navegador y accesibilidad

Revisión local adicional: LAB EN y Blog ES con viewport efectivo398px, sin descendientes de main fuera de márgenes. LAB: Enter abre menú; Escape desde enlaceLAB lo cierra y devuelve foco al botón. Cabecera, texto, listas y enlaces de idioma legibles.

**Precisión sobre zoom:** se observaron cambios de devicePixelRatio1/1.1/2 mientras cambiaban el viewport y la superficie nativa. Esa medición no certifica por sí sola el zoom200% del navegador. Corrige la actualización de conversación que lo daba por confirmado: se registra únicamente reflujo medido; **zoom200% queda pendiente de confirmación manual**, junto con lector de pantalla y matriz integral. Se retiraron overrides y se enviaron comandos de restauración; no se dispone de lectura independiente del control de zoom nativo.

- [LAB EN, reflujo398](lab-local-reflow-398.png).
- [Blog ES, reflujo398](blog-local-es-reflow-398.png).

El navegador integrado permitió abrir el origen staging; el primer acceso a la fichaLAB mostró el503 controlado durante el incidente. No hubo rechazo de permisos ni intento de sortear políticas.

Observación posterior: la pestaña staging estaba en la ficha española del Blog (URL/origen confirmados por DOM). Tras recargar renderizó todo el artículo y su pie sin error visible. [Captura de lectura](editorial-staging-blog-es.png). Es evidencia de recuperación puntual, sin convertir el smoke anterior en éxito ni certificar disponibilidad estable.

## Siguiente acción

Investigar el límite429 en el hosting de WordPress con logs/correlación y configuración real. No desactivar protecciones, ampliar permisos ni repetir ráfagas para terminar una matriz. Después completar el smoke remoto de Blog/LAB y la revisión visual de Workers, y seguir con QA integral/aceptación antes de proponer producción.

Rollback no ensayado: conservar versiones, configuración/secretos por entorno y export Durable Object existente. No asumir que una reversión a un Worker anterior a esa clase es compatible. No cambiar CMS ni contacto para revertir una entrega frontend.
