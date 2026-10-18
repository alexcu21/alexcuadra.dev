# Caché del CMS y diagnóstico de solicitudes

## Alcance implementado

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
