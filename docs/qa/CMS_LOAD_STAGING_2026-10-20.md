# Menor carga CMS y QA de staging — 20 octubre 2026

## Entrega

Worker `alexcuadra-dev-staging`, versión `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c`; previa `8783d505-39d8-4287-8779-7b3423044dc1`. Paquete comprobado con build/dry-run y configuración staging sin rutas de producción, keep_vars=true y contacto false. Producción y CMS sin modificaciones. Árbol local conservado sin commit/reset/limpieza.

## Hallazgos y correcciones

El menú ES consultaba cuerpos/ACF de Home ES, About EN y About ES únicamente para decidir enlaces. Ahora reutiliza la relación pública de Home EN, que ya verifica identidad en origen, y consulta `PageNavigation` para la relación de About. Esta consulta no solicita cuerpos, ACF ni imágenes. Guarda solo visibilidad/idioma y metadatos de traducciones públicas en la caché existente de60s, con validación/proyección en cache hit y claves separadas por credenciales/documento/variables. Respeta bypass por cookies/autorización/previews de la petición entrante. Las traducciones privadas/protegidas/ausentes no se anuncian ni se consulta su contenido.

Para el menú ES, tres lecturas editoriales adicionales se sustituyen por una lectura de metadatos en frío y ninguna en un cache hit válido. Los lectores de contenido de las propias páginas conservan su validación e idioma. No se cachea la verificación de identidad Home ni se sustituye contenido ausente por inglés.

El sitemap lanzaba13 lectores simultáneos, aunque cada uno podía requerir varias consultas/páginas. Ahora completa un lector antes de iniciar el siguiente. La deduplicación de fuentes Home/About dentro del request permanece. Si falla una lectura, no se consultan las colecciones posteriores ni se emite XML parcial. Esta limitación corresponde a un sitemap individual; no es un limitador global entre visitantes.

Estas medidas reducen carga evitable. El429 upstream ya estaba confirmado; no se ha identificado ni cambiado el módulo o la configuración del limitador del hosting. No se agregaron reintentos, excepciones de firewall ni fallback a datos caducados. Metadatos del menú pueden tardar hasta60s en reflejar una retirada por el TTL existente; el contenido de destino sigue validándose en origen.

## Pruebas locales

- 211 unitarias correctas. Casos nuevos: metadatos sin cuerpo/ACF, traducciones reales/ausentes/no públicas, fuentes privadas, proyección mínima en cache hit, inputs inválidos y sitemap con concurrencia máxima de una consulta upstream y parada ante429 sin XML parcial.
- Suite SSR real de Blog/LAB/referencias correcta. Incluye menú ES desde relaciones públicas sin consultar cuerpos traducidos y503 seguro si falla la lectura de navegación.
- Build correcto:151 archivos,0 errores/advertencias/hints Astro. Avisos conocidos de configuración Shiki/CSP permanecen.
- `git diff --check` correcto. Servidor4325 reiniciado porque estaba apagado.

## Validación remota

Una única pasada con 10 segundos entre peticiones terminó con exit 1. Comando:

```sh
node scripts/smoke.mjs https://alexcuadra-dev-staging.alexcu21.workers.dev --require-cms-auth --check-seo --check-content --check-csp --check-sitemap --check-contact-disabled --check-blog-lab --editorial-only --request-gap-ms 10000 --save-sitemap docs/qa/cms-load-staging-sitemap-2026-10-20.xml
```

Correctos: favicon/404 técnica, sitemap de 43 URLs, robots/noindex de staging, Home con identidad de servicio, SEO/CSP y contacto desactivado. Archivos Blog EN/ES y ambas fichas reales respondieron 200 con canonical, CSP y renderer compartido/una H1. El menú ES enlaza Inicio y Sobre mí reales, sin fallback etiquetado EN.

La pasada se detuvo en `/blog/__missing_entry__`: esperaba 404, recibió 503. El log confirma `cms_failure`, operación `BlogPost`, código `CMS_HTTP`, estado upstream 429. Sin una respuesta del CMS no se puede afirmar que la entrada no exista: conservar 503 es el comportamiento correcto. No se debilitó esa guarda para hacer pasar el smoke. La comprobación restante de idioma incorrecto y el bloque LAB no se ejecutaron en esta pasada; no confundirlo con un fallo demostrado de LAB ni dar por completada su QA remota.

En el mismo intervalo se observaron `PageNavigation` MISS (attempt 1) y HIT (attempt 0), evidencia de reutilización real de los metadatos. Esto verifica el ajuste, pero el 429 persiste pese a reducir lecturas y espaciar peticiones; no identifica el producto, regla o ventana del limitador. No se lanzó una nueva pasada ni se modificaron protecciones. Tail cerrado al terminar. Resultado saneado en [CMS_LOAD_STAGING_RESULT_2026-10-20.json](CMS_LOAD_STAGING_RESULT_2026-10-20.json), XML en [cms-load-staging-sitemap-2026-10-20.xml](cms-load-staging-sitemap-2026-10-20.xml).

Logs temporales solo se usan para extraer operaciones/caché/fallos saneados. No copiar headers, cuerpos, direcciones de clientes ni secretos al repositorio. No interpretar la recuperación de una página como garantía de disponibilidad continua ni resolución del 429 del hosting.

## Navegador y límites

El navegador integrado rechazó adquirir la pestaña local abierta con un error de política de protocolo. No se observó su contenido ni se buscó otra superficie/puerto/hostname para eludirlo. La verificación HTTP de staging ya prevista continúa como prueba técnica; no sustituye la revisión visual bloqueada. Este incremento no cambia CSS ni comportamiento de teclado.

Zoom200%, lector de pantalla y matriz visual integral siguen abiertos. Contacto desactivado; sin challenge ni correo. Producción no autorizada para salida por estas pruebas focalizadas.

## Continuación

Identificar la regla responsable mediante diagnóstico del hosting y su correlación con estos fallos, sin ampliar permisos del lector CMS ni desactivar protecciones. Al recuperar disponibilidad, ejecutar únicamente los casos remotos pendientes (ausencia/idioma y LAB); después continuar la matriz visual cuando el navegador conceda acceso efectivo. MCP editorial sigue siendo una etapa independiente, según su plan.
