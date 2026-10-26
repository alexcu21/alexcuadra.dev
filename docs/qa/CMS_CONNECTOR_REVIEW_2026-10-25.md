# Astro, CMS, contacto y MCP — 25 octubre 2026

## Diagnóstico y fuentes

SSR no genera un 429 por sí mismo. `output: 'server'` renderiza durante la navegación: si el contenido necesario no está en caché, depende del CMS. El conector convierte sus fallos en un 503 público y conserva Retry-After; por ello no se debe equiparar todo 503 a un 429 sin correlacionar el evento `cms_failure` y su `httpStatus`.

- [Astro: renderizado bajo demanda](https://docs.astro.build/en/guides/on-demand-rendering/): permite combinar rutas dinámicas y prerenderizadas, y definir cabeceras de caché.
- [Cloudflare: cabeceras de subpeticiones de Workers](https://developers.cloudflare.com/fundamentals/reference/http-headers/#cf-connecting-ip-in-worker-subrequests): la IPv6 `2a06:98c0:3600::103` se usa en subpeticiones entre zonas por seguridad. Esto explica una identidad compartida posible; no demuestra qué campo utiliza el limitador de Hostinger ni identifica su regla.
- [Cloudflare: funcionamiento de caché](https://developers.cloudflare.com/workers/reference/how-the-cache-works/): Cache API opera por centro de datos y no participa en Tiered Cache. Una caché caliente en una ubicación no garantiza otra.
- [WPGraphQL: rendimiento](https://www.wpgraphql.com/docs/performance): seleccionar solo campos necesarios, equilibrar consultas y considerar caché e invalidación. GET y consultas persistidas pueden facilitar caché, pero aquí no se cambia el método para sortear un límite desconocido ni se exponen credenciales en URLs.

### Estado observado en el código

- Deduplicación por petición con AsyncLocalStorage; timeout de 10 segundos; sin reintentos automáticos; diagnóstico por operación/requestId/duración/status/Retry-After, sin cuerpos ni secretos.
- Caché pública de 60 segundos para proyectos/referencias y metadatos de navegación. La autenticación de servicio se particiona por credenciales y solo se comparten proyecciones públicas explícitas. Cookies, autorización del visitante y previews deshabilitan caché compartida.
- Listados/fichas del blog, LAB completo, fichas de referencias, páginas localizadas y privacidad todavía tenían lecturas sin caché compartida. El Home localizado comprueba la identidad del servicio en vivo; no se elimina esa comprobación.
- No hay deduplicación global entre peticiones, presupuesto global de concurrencia ni circuito compartido para 429. Añadir reintentos sin estos controles puede aumentar la carga.

## Incremento implementado

Archivo y ficha del blog optan ahora por la caché pública existente de 60 segundos. La proyección admite únicamente contenido publicado sin restricciones/contraseña, metadatos públicos de traducción y campos necesarios para las páginas. No guarda extras autenticados. Valida paginación antes de almacenar, mantiene el idioma real y el resultado ausente para un slug de otro idioma. Un resultado `post:null` válido también puede mantenerse 60 segundos. Las relaciones, fechas y contenido pueden reflejar una edición con hasta ese retraso en una entrada de caché existente; no se sirve contenido caducado deliberadamente.

Las claves separan documentos/variables/origen/credenciales. La consulta del archivo incluye EN/ES; el detalle por slug admite compartir el mismo contenido público, comprobando el idioma solicitado después de recuperarlo. Un error de CMS no se almacena ni se reintenta.

Limitación: cada render aún puede requerir otras lecturas. Esto reduce consultas de blog con caché caliente; no independiza el HTML completo del origen, no ofrece coordinación entre centros de datos y no demuestra que el limitador se haya resuelto.

Validación: 311 pruebas unitarias correctas y build con 0 errores/0 advertencias. Dos regresiones nuevas prueban reutilización entre peticiones, separación EN/ES, expiración, filtrado de extras y traducciones, rechazo de borradores/contraseñas/paginación inválida. La regresión SSR de streaming también pasa; el primer intento fue bloqueado por EPERM al abrir el puerto local del fixture y se repitió fuera del sandbox, no por un fallo del producto.

## Formulario: requisito activo

El formulario es parte del alcance. Auditoría remota de solo lectura actualizada: bindings/secrets por nombre/tipo/variables de staging correctos, endpoint 404 CONTACT_DISABLED/no-store, privacidad staging/producción 200 y enlace editorial correcto. [Recibo](contact-readiness-2026-10-25.json). Esto no certifica los valores de secrets, el challenge ni la entrega.

El piloto anterior no confirmó recepción; no se ha reenviado ni habilitado producción. El código actual usa `send()` estructurado y exige `messageId`, consistente con la [API Workers de Email Service](https://developers.cloudflare.com/email-service/api/send-emails/workers-api/).

Siguiente incremento: inspección de solo lectura del evento del piloto en el [Activity log](https://developers.cloudflare.com/email-service/observability/logs/), distinguiendo Sent/Delivered/Failed/Rejected y rebote. Sent significa aceptado/encolado, Delivered llega al servidor destinatario; ninguno prueba lectura ni bandeja de entrada. Conservar solo estado/hora/identificador técnico, sin copiar destinatarios o mensajes al repositorio. Si el evento histórico no está disponible, registrar ese límite. Preparar después una ventana staging de un único mensaje, con captura de respuesta HTTP y confirmación de recepción/Reply-To por Alex; esa nueva prueba requiere su autorización específica porque la anterior quedó aplazada. No programar ni reenviar automáticamente. Producción necesita además sus bindings/configuración: no basta con cambiar el flag.

## MCP editorial: requisito activo

Descubrimiento nativo actual de solo lectura: 11 abilities visibles, 3 core, 6 de propuestas privadas de proyecto y 2 de lectura de proyectos públicos. No aparecen abilities para entradas nativas, traducciones, categorías o tecnologías; tampoco aplicación/recuperación operativa en el catálogo actual. Esto describe la interfaz expuesta a esta sesión, no prueba desinstalación ni justifica regrants.

Siguiente incremento local: diseñar y probar abilities de lectura de posts EN/ES con pares recíprocos y un DTO de propuesta para cuerpo/título/extracto y asignaciones taxonómicas por ID/idioma; creación de traducciones debe ser explícita, idempotente y con comprobación de original/grupo existente. No mezclar categorías del blog con taxonomías de proyectos. Preservar separación entre proponer, aprobar y aplicar, comprobación de versión/base, diff revisable y recuperación. Evitar grants generales como `edit_posts` al usuario MCP por conveniencia.

Reutilizar la infraestructura de propuestas existente después de verificar su compatibilidad. La certificación real de aplicación/recuperación y privacidad entre propietarios sigue pendiente; la revisión privada 1 caducada y la propuesta 227 se conservan como evidencia, sin regenerarlas para una demostración. La incidencia histórica de postmeta no está resuelta y no se reemplaza su baseline.

## Secuencia de trabajo

1. Caché pública del blog y medición de consultas en staging.
2. Diagnóstico del envío de contacto y preparación de un piloto observable; no depende del CMS en el backend de envío.
3. Contrato y pruebas locales MCP para posts, traducciones y taxonomías; luego instalación/alcance mínimo y un cambio público concreto revisado.
4. Extender proyecciones de caché a los lectores restantes y medir HIT/MISS/BYPASS por operación, peticiones frías/calientes y caché por ubicación. Decidir con esos datos entre coordinación central de carga o páginas públicas prerenderizadas/snapshots renovados tras publicación.
5. Para una arquitectura desacoplada del CMS: renderizar el contenido público al publicar y mantener SSR para contacto/preview. Requiere invalidación fiable al editar, retirar, proteger o traducir una entrada; no introducir stale indefinido que siga sirviendo contenido retirado. No cambiar a SSG por intuición sin definir publicación e invalidación.

No se instalaron plugins ni se cambiaron DNS, credenciales, permisos o contenido CMS en este incremento. Disponibilidad de Hostinger y aceptación visual integral permanecen pendientes.
