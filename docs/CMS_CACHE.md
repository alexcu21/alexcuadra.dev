# Caché del CMS y diagnóstico de solicitudes

## Alcance implementado

**24 octubre 2026 — siguiente incremento editorial:** la consulta de inventario añade el título público para búsqueda/filtros de `/articles/`, sin nueva operación CMS. La proyección conserva ese texto junto a los slugs/cursores; excluye cuerpos e identidad. El documento GraphQL cambiado genera claves nuevas automáticamente. [Validación y estado de entrega](qa/REFERENCE_DIRECTORY_2026-10-24.md).

**24 octubre 2026 — publicado en producción, staging conserva la versión anterior:** inventario `SitemapReferences` con caché de servicio 60s, proyección exclusiva de slugs/relaciones públicas/cursores y rechazo de contenido no público o paginación incompleta. Reutilizado en portada, canales y sitemap. No resuelve el limitador del hosting. [Validación](qa/CMS_AVAILABILITY_INCREMENT_2026-10-24.md) y [recibo de producción](qa/PRODUCTION_RELEASE_2026-10-24.md).

**Actualización20 octubre2026:** `PageNavigation` cachea únicamente visibilidad/idioma del origen y metadatos de traducciones públicas para los enlaces del menú. No solicita ni almacena cuerpos, ACF o imágenes. Home ES del menú se decide desde la relación pública de Home EN ya leída, sin otra consulta editorial. Identidad Home sigue comprobándose en origen. TTL60s, aislamiento por credenciales/variables y bypass de peticiones entrantes se mantienen. Detalles/pruebas en [CMS_LOAD_STAGING_2026-10-20.md](qa/CMS_LOAD_STAGING_2026-10-20.md).

**Actualización19 octubre2026:** disponibilidad de menú de Blog/LAB (`BlogAvailability` / `LabAvailability`) también admite caché de servicio. Proyección estricta de cero o un nodo publicado del idioma: únicamente status, isRestricted, hasPassword y language.code. Sin IDs, slugs, contenido, ACF ni objetos privados. Cache hit vuelve a proyectarse/validarse; claves separadas por consulta/idioma/credenciales y TTL60s.201 pruebas totales correctas; regresiones nuevas cubren aislamiento, rotación, campos extra, inventarios privados/protegidos/incorrectos y proyección idempotente. Los lectores completos Blog/LAB siguen consultando origen. Home sigue verificando identidad en origen. Las descripciones históricas inferiores de «solo proyectos» no incluyen esta ampliación posterior.

Caché de datos para tres consultas anónimas y explícitamente autorizadas: Home, lista de proyectos y categorías. Con autenticación de servicio, **solo la lista de proyectos** tiene caché compartida mediante un contrato público explícito. Home continúa verificando la identidad en origen; categorías, About y artículos autenticados también consultan origen. Cada consulta debe declarar publicCache y un validador; no hay caché indiscriminada de documentos GraphQL.

Se usa Cache API de Workers, sin KV ni nuevos recursos. TTL: 60 segundos; expiración como mecanismo de actualización editorial. Una edición puede tardar hasta un minuto en verse. El namespace public-cms-v1 puede incrementarse para invalidar por versión: las entradas previas dejan de consultarse, aunque no se borran globalmente al instante. No existe webhook de purga desde WordPress en esta entrega.

La caché es local a cada centro de datos, puede expulsarse antes de expirar y no evita todos los misses simultáneos. No es un contador ni limitador global. Se conservan la deduplicación por petición y el timeout de 10 segundos, sin reintentos automáticos que amplifiquen 429.

## Seguridad y autenticación futura

Solo se reutilizan datos anónimos publicados, con validación básica de forma y sin errores GraphQL. La clave incluye origen del frontend, endpoint CMS, documento completo y variables, con hash SHA-256. El nombre de la operación por sí solo nunca identifica una respuesta.

Requests entrantes con Authorization, cualquier Cookie, parámetros de preview/draft/nonce/token o métodos distintos de GET/HEAD no usan caché compartida. Cualquier cabecera custom en opciones del transporte saliente también desactiva el uso compartido. Los errores no se almacenan y no hay fallback a contenido caducado.

La excepción para proyectos autenticados exige credenciales inyectadas por el servidor y el proyector `publicProjects`. La consulta filtra `status: PUBLISH, hasPassword: false`; cada nodo debe confirmar `status=publish`, `hasPassword=false` e `isRestricted=false`. Si faltan indicadores o hay contenido no público, la consulta falla con 503 sin almacenar ni renderizar esos datos. Se reconstruye un objeto con solo título, contenido, slug, fecha, campos editoriales del proyecto, nombres de tags y URL de imagen. Campos extra, identidad y metadatos ajenos se descartan antes de almacenar o renderizar. La validación se repite al leer caché.

Las claves separan respuestas anónimas de las de servicio e incluyen la credencial en el material hasheado para cambiar de clave al rotarla. Ni la credencial ni su codificación Basic aparecen en URLs o logs. La revocación puede tardar hasta el TTL en afectar a una entrada existente de proyectos; Home sigue comprobando identidad en origen. Un cambio de publicación a privado también puede tardar hasta 60 segundos en reflejarse: usar invalidación por versión si se requiere retirada inmediata.

La restricción GraphQL está activa y validada en producción Workers, staging y local. El antiguo Pages queda como respaldo y necesita desactivar la restricción antes de una reversión a su código anónimo. Nuevas operaciones autenticadas quedan sin caché hasta definir y probar su propio contrato público.

## Observability

Cada petición genera X-Request-ID y lo envía también al CMS como cabecera de correlación. El User-Agent AlexCuadraPortfolio/1.0 (SSR; Cloudflare Workers) identifica la aplicación; no constituye autenticación ni excepción de firewall.

Eventos cms_query: requestId, operation, durationMs, cache (HIT/MISS/BYPASS), attempt (0 en HIT, 1 en llamada al CMS). Eventos cms_failure: código, operación, estado HTTP, duración, identificador e información Retry-After cuando el servidor la proporciona. No se registran consultas, variables, cuerpos de respuesta, credenciales ni cabeceras completas.

Si Hostinger envía Retry-After válido, se conserva en la respuesta 503 y el diagnóstico. Si no lo envía, el 503 mantiene la recomendación local de 60 segundos; esto no indica que conozcamos la ventana real del limitador. No se duerme ni se reintenta dentro de la petición.

## Comprobación

40 pruebas deterministas pasan: expiración, claves por variables/host, errores no cacheados, bypass de credenciales/previews, disponibilidad ante fallo de Cache API y respeto a Retry-After, entre otras. Build Astro/Workers correcto. Comprobar en preview/staging dos visitas normales sin cookies dentro de 60 segundos: la primera puede mostrar MISS y la siguiente HIT para las tres operaciones, sin nuevas llamadas al origen para esas consultas. La respuesta debe seguir incluyendo contenido real. Verificado en workerd local: primera visita con tres MISS y segunda con tres HIT, ambas HTTP 200; segunda sin llamadas al CMS.

No ejecutar ráfagas ni pruebas de carga sobre el CMS mientras Hostinger investiga. Si hay 429 antes de poblar la caché, se seguirá devolviendo 503: esta mejora reduce tráfico, no elimina el límite de infraestructura ni lo evade.

Referencia: https://developers.cloudflare.com/workers/runtime-apis/cache/ .
