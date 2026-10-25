# Disponibilidad CMS y revisión editorial — 24 octubre 2026

## Incremento local

`SitemapReferences`, reutilizada por portada, canales y sitemap, admite ahora caché pública de servicio durante 60 segundos. Almacena exclusivamente slugs públicos, relaciones de categorías y cursores; reconstruye el objeto para descartar identidad y campos adicionales. Valida publicación, restricciones, contraseñas, rutas y paginación, también fuera de la caché. Las claves siguen separadas por credenciales, consulta, variables y host; se mantienen los bypass existentes. No hay reintentos ni contenido caducado de respaldo. Esta mitigación no elimina las consultas de identidad ni corrige el límite de infraestructura.

256 pruebas unitarias correctas; incluye 11 nuevas de proyección, rechazo y reutilización autenticada con rotación/aislamiento. Después del ajuste final de validación, 23 pruebas específicas correctas. Compilación Astro/Workers y regresión SSR de componentes reales correctas. Los primeros intentos dentro del sandbox no pudieron abrir puertos; se repitieron con autorización fuera del sandbox. Sin dependencias nuevas.

## Staging observado, antes de desplegar este incremento

Lectura puntual de `/articles/`: HTTP 200, X-CMS-Auth verified, noindex/nofollow y X-Request-ID `c0761ea8-fddc-4507-b827-32c0b9891a07`; fecha de respuesta 2026-10-25 04:00:28 UTC (24 octubre en Nicaragua). No se ejecutó una prueba de carga.

Navegador real: archivo con cuatro canales y conteos 1/3/2/4; canal Medium con una referencia y ficha Gutenberg con texto/enlace externo correctos. LAB EN y ES muestran QA Training Lab y su estado/propósito reales. Fichas EN/ES con secciones editoriales completas; Enter en Español navega a la traducción real; Enter en Avance actual lleva el foco al encabezado; retorno por teclado al archivo ES correcto. Vistas observadas sin un fallo visible de CMS; no equivale a disponibilidad continua ni a probar todas las referencias. Vista de navegador de unos 610 CSS px; no certificar 320px, zoom 200%, lector de pantalla ni preferencia de movimiento del SO.

## Información nueva aportada por Alex

Soporte Hostinger informa de una reparación de base de datos/LiteSpeed relacionada con errores 500 y aclara que no modifica el 429 previo a WordPress. Describe 2.208 solicitudes agregadas bajo la IPv6 sintética de Cloudflare (2.207 POST /graphql), pero no identifica la regla, su umbral/ventana ni el campo usado por el contador. No hay excepción configurable por ruta, método, autenticación o token en la cuenta. Estos son datos aportados por soporte, no una inspección independiente de infraestructura por el agente.

No repetir la petición de una excepción disponible en hPanel: soporte ya confirmó que no existe. Tampoco permitir globalmente la IPv6 ni cambiar User-Agent para evadir el límite. Un control en Cloudflare solo reduce tráfico hacia el origen: no sustituye una excepción anterior al rechazo de Hostinger. Resolver la causa exige intervención del equipo de infraestructura del proveedor o evaluar un origen con capacidad/política compatible; cualquier migración necesita un plan concreto y aceptación separada.

## Estado de entrega

Código preparado localmente; sin despliegue nuevo a staging o producción, sin edición CMS ni cambio de contacto. La causa del 429 sigue abierta. La revisión editorial remota avanzó en las rutas descritas; aceptación integral móvil/zoom/movimiento y publicación siguen pendientes. MCP editorial continúa aplazado.
