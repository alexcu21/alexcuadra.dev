# HTML completo ante fallos CMS tardíos — 19 octubre 2026

Staging: `765e7f60-1a72-416a-9bfb-daa440ef560e`.

## Problema y comportamiento

`withCmsRequest` revisaba scope.failure después de next(), antes de consumir el cuerpo HTML. Un componente anidado puede consultar el CMS mientras Astro produce el stream posteriormente. El estado200 ya podía estar decidido cuando se registraba el error, dejando un documento parcial o vacío. Esta reproducción confirma el mecanismo; no se afirma que explique todos los incidentes históricos sin logs correlacionados.

Middleware activa bufferHtml: consume el HTML dentro del scope antes de revisar error/identidad y entregar la respuesta. Errores CMS, incluso capturados por el renderizador, producen503, no-store, noindex y Retry-After seguro. No hay reintentos ni respuesta parcial con detalles internos. Errores de programación ajenos al CMS siguen propagándose. Respuestas no HTML (incluido JSON de contacto) conservan su flujo.

Coste: HTML completo se mantiene en memoria durante el renderizado y no se transmite progresivamente; el primer byte espera a que termine la página. No se modifica caché, límites de Hostinger ni autorización CMS.

## Verificación

- 139 pruebas unitarias pasan. Nuevas regresiones: fallo429 tardío con stream abortado o error capturado, éxito completo/cabeceras, excepción ajena al CMS y JSON no consumido. Antes del ajuste, los fallos tardíos devolvían200.
- `npm run test:cms-streaming` pasa con Astro7.4 SSR real y un componente anidado asíncrono. Control sin guarda confirma200 incorrecto; con guarda ambos errores generan503 seguro y éxito sigue200. Fixture aislado en tests, sin llamadas CMS ni secretos; incorporado a CI.
- Se corrigieron dos ajustes del harness durante su preparación: ruta relativa del middleware y output=server para que Astro conservara query params del caso de prueba. No se atribuyen esos fallos a la aplicación.
- Build staging correcto. Smoke focalizado Home EN/ES + una ficha enlazada, identidad de servicio, SEO/CSP y contrato contacto desactivado pasan. No se provocó un fallo del CMS remoto deliberadamente.
- Navegador verifica Home ES completa y tarjetasEN con slugs originales válidos. Captura adjunta.

## Regresión de enlaces encontrada

HomePage aún usaba getProjectsInfo() sin idioma; nuevas traducciones podían entrar en sus cuatro tarjetas y formar `/work/<slug-ES>`, cuyo detalleEN devuelve404. Ahora consulta getProjectsInfo('en') explícitamente. Se conservan los destinosEN identificados de HomeES; su archivoES mantiene todas las traducciones publicadas.

Producción no desplegada, CMS sin cambios en este incremento y contacto false. El429 upstream sigue pendiente de diagnóstico/configuración del hosting; esta corrección mejora la respuesta al fallo sin afirmar disponibilidad permanente.

![Home verificada](cms-streaming-home-staging.png)
