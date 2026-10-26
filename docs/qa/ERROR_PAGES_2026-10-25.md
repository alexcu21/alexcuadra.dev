# Pantallas de error EN/ES — 25 octubre 2026

## Hallazgo y entrega

La revisión de páginas compartidas detectó respuestas editoriales404 y fallosCMS503 en texto plano, sin navegación de recuperación ni idioma acorde a la URL. Se añadieron documentos HTML autónomos EN/ES y CSS local, sin consultas WordPress, fuentes remotas, scripts ni animaciones. Ofrecen inicio/proyectos y, en503, reintento manual con el tiempo de espera recibido. No hay reintento automático ni contenido de respaldo que simule éxito.

El middleware presenta únicamente404/503 de navegaciones HTML GET/HEAD. Conserva códigosHTTP/Retry-After/ID de petición; usa no-store/noindex, CSP restrictiva sin scripts, nosniff y no-referrer. Retira longitud/encoding/ETag/Last-Modified del cuerpo reemplazado. API, JSON/XML, sitemap/robots y métodos de escritura conservan sus respuestas originales; la protección REST de WordPress sigue retornando antes de esta presentación. No se reflejan ruta, query ni cuerpo/errores del origen.

La revisión visual de staging encontró otra ruta: Cloudflare servía el404.html prerenderizado antes del middleware, conservando inglés en URLs españolas. Se cambió404.astro a render dinámico puro, sin BaseLayout ni lectorCMS, conservando el idioma de la URL y permitiendo la presentación final. La compilación comprueba que ya no existe dist/client/404.html. No se añadió catch-all ni cambio de DNS/rutas manuales de Cloudflare.

## Validación

- 300unitarias correctas: idiomas/status, cabeceras, contratos API/metadata/métodos, HEAD sin cuerpo, ausencia de datos privados reflejados y esperas largas/HTTP-date sin acortarlas.
- Suite SSR correcta:404 dinámicas EN/ES incluso con todo el CMS sintético fallando;503 en Blog/LAB con Retry-After120/no-store y sin HTML parcial; regresiones normales conservadas.
- Builds staging/producción correctos. Destino/autenticación/contacto=false/keep_vars verificados. Sin nuevas dependencias.
- Navegador local:503ES a320CSS, scrollWidth320, foco visible y texto de espera120.404ES: Enter en Explorar proyectos recupera archivo con dos proyectos sintéticos. No se pulsó reintento durante la espera.
- Staging: BlogEN, LABES, AboutEN y privacidadES200/unH1; CSS200/text-css y contacto404/JSON/CONTACT_DISABLED. Los primeros chequeos de status pasaban pero la404 visual no estaba aceptada: el recibo inicial registra ese límite y el fallback inglés detectado.
- Staging final5ac24c2f:404EN/ES con idioma correcto/no-store/CSP; CSS y contratoAPI correctos. NavegadorES confirma lang=es, bodydisplay=grid y cero scripts. No se provocó un fallo real del CMS para generar503 en Workers; esa presentación se verificó con transporte sintético y la misma CSP en local. La404 verifica la entrega del CSS bajo CSP real.

## Evidencia y límites

Staging final `5ac24c2f-d949-4c44-8167-165e98910b47`; previa al incremento `b3f98891-7269-48a1-b4a7-8c9f8f946572`. La evidencia inicial pertenece a2d5f77e4 y documenta el problema de fallback antes de corregirlo; no equivale a aceptación de esa versión.

[Comprobaciones iniciales y hallazgo](error-pages-staging-initial-2026-10-25.json), [comprobaciones finales](error-pages-staging-2026-10-25.json), [404 stagingES](error-404-staging-es-2026-10-25.png), [503 localES320](error-503-local-es-320-2026-10-25.png), [404 localES](error-404-local-es-2026-10-25.png).

Esta revisión cubre respuestas y escenarios indicados; no es una auditoría integral de todas las páginas/anchos/ayudas técnicas. Zoom real200%, movimiento reducido del SO, lector de pantalla y estabilidad429 del hosting siguen abiertos. Sin CMS/DNS/grants/activación de contacto/MCP operativo ni push. No se generó carga para forzar un429 y no se hicieron reintentos ante error del origen.

## Producción y privacidad española

Versión final `7d26d563-47ac-4084-9597-d37233f244d9`; previa al incremento `afef64bc-4bb1-44f2-9cd8-eacd3030e426`. Una primera versión23f2bb92 se detuvo en la comprobación de privacidadES404: faltaba CONTACT_PRIVACY_ES_PAGE_ID en las vars de producción. Se añadió182, la página española pública ya verificada en staging. No se editó contenido legal ni se activó el formulario. El recibo inicial conserva el fallo; no se marca esa pasada como satisfactoria.

[Postflight final](error-pages-production-2026-10-25.json): BlogEN, LABES, AboutEN y privacidadEN/ES200; ambas privacidades enlazan su traducción.404EN/ES conservan404/no-store/noindex/CSP e idioma correcto. CSS200 y contacto404/JSON/CONTACT_DISABLED. Son nueve comprobaciones con pausas y sin reintentos. [Primer postflight incompleto](error-pages-production-initial-2026-10-25.json).

Navegador producciónES:404 a320CSS/scrollWidth320, recuperación con foco visible y CSS cargado. [Captura404](error-404-production-es-320-2026-10-25.png). PrivacidadES: título/idioma/h1 correctos, enlaceEN y ancho320 sin overflow observado. [Captura de privacidad](privacy-es-production-fixed-2026-10-25.png). Viewport restaurado.

El documento generado por la aplicación no incluye JavaScript. En producción se observó un beacon de Cloudflare insertado por la plataforma; la CSP restrictiva de errores no autoriza su ejecución. No se habilitaron scripts ni se modificó la configuración de analítica para resolverlo. La503 real del Worker no se forzó: se comprobó con transporte sintético y headers equivalentes. El429 del origen sigue sin resolver.

Reversión: restaurar la versión previa del Worker si fuera necesario, conservando la referencia182 para no reintroducir el404 de privacidadES. La corrección de configuración es independiente de la presentación de errores; los lectores anteriores ya conocen esa variable. No se ejecutó rollback.
