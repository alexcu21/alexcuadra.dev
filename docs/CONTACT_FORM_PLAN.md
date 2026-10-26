# Formulario de contacto: Turnstile, validación y entrega

**26 octubre — inventario consolidado y MCP blog implementado en candidato local:** list/getpublicposts con traducciones recíprocas y category/post_tag por idioma, capacidad nueva separada, sin cuerpos/escritura y guard local.75contratosPHP8.5.11+2guards/lint correctos; dobles de APIs, NO integraciónWP/HTTP ni instalación. Conectorreal sigue11abilities. Auditoría producción de sololectura confirma faltanbindings/coordinador/ratelimiter/3secretos/origen/remitente/sitekey; privacidad y flagfalse correctos. [Estado vigente y prioridades](NEXT_INCREMENTS_2026-10-26.md). Sin deploy/grants/CMS/envíos/push; etapas históricas inferiores no sustituyen este inventario. Siguiente: integraciónWP/Polylang/MCP real aislada del lector blog; luego propuestas/traducciones/taxonomías, promociónstaging y contacto producción, CMS/QA integral.

**26 octubre — validación de campos revisada y reforzada:** servidor valida esquema/tipos/tamaños/origen/JSON/UTF8; salida emailtext sinHTML/cabeceras dinámicas y avisostextContent. Nuevo rechazoC1/surrogatesaislados/control antesdetrim; normaliza separadores multilínea, preserva acentos/emoji/alfabetos/código literal.324unitarias/7runtime/build correctos, sin correo real. [Recorrido, evidencias y límites](qa/CONTACT_INPUT_VALIDATION_2026-10-26.md). Sin campos nuevos/DB/grants/CMS; flagfalse; stagingb8e868a5-95d2-40a8-95b9-6ffd0101cb5d/GET404CONTACT_DISABLED/no-store y producción intacta. MCP editorial siguiente dentro del alcance, sin repetir piloto recibido.

**26 octubre — contacto en página separada:** /contact/ y /es/contacto/, enlacesCTA/menú/sitemap/hreflang; Home sin formulario incluso con flagactivo. Mantiene3campos obligatorios100/254/5000 e idioma/tokenautomáticos. ES193 contenía defaultsEN: ACF160 limitado a19 ampliado solo conORPage193,17textosESguardados/privacyES;ENsin cambios/postflight correcto.320unitarias/build/SSR correctos, vista local real320CSS/teclado sin envío. Correo sinbandejaDB;HMAC24h/logs técnicos;Gmail30díasmanual;posiblespreviewsCloudflare7días segúnconfig noverificada. [Recibo, campos y almacenamiento](qa/CONTACT_PAGE_2026-10-26.md). Staging04955ff1-65c3-4e9a-84a4-d08ea5d2ac0a:contactoEN/ES/Home200/canónicas/CSP/API404CONTACT_DISABLED,flagfalse; producción frontend intacta. MCP sigue en alcance.

**26 octubre — piloto real de contacto completado:** Alex autorizó un envío único y confirmó recepción/Reply-To correcto. Siteverifyverified200/15ms;202CONTACT_ACCEPTED/1959ms/requestIddf7eb098-f915-4d25-9e04-7734fa71b0b0 a09:25:10Nicaragua. Ventana stagingc8e5a165 cerrada conf1caee3b-a837-4db2-ae1e-4680cd8635e6;GET404CONTACT_DISABLED/no-store, Home real sinform/widget, observador detenido. [Recibo/captura/límites](qa/CONTACT_PILOT_2_2026-10-26.md). Producción siguefeba64f8/contactofalse. No pedir recepción de nuevo ni reenviar; siguiente contacto preparar bindingsproducción/revisiónES, sin interpretar el piloto como activaciónpermanente. MCP editorial sigue en alcance.

**26 octubre — fallo Siteverify reproducido y corregido:** redirect:error no soportado por workerd lanza TypeError antes de fetch, reproduciendo transport/0ms/0egreso. Cambiado a manual y rechazo3xxsinseguirLocation. Diagnóstico seguro separa red/timeout/HTTP/JSON/contrato/rechazo/hostname/action; sinsecretos/PII/bypass/retries.320unitarias/6runtime correctas; nueva prueba usa fetch nativo con egreso interceptado y fallaantes/pasadespués. [Hallazgo, límites y piloto pendiente](qa/SITEVERIFY_RUNTIME_FIX_2026-10-26.md). Staging843026f8-91b5-42b1-90e0-111d83501c76/GET404CONTACT_DISABLED/no-store/buildfinalcorrecto. No confirma tokenreal/entrega; formulariofalse, producción sin cambios. Piloto anterior aplazado: nuevo envío único requiere autorización específica. MCP sigue en alcance.

**25 octubre — evidencia histórica del piloto recuperada:** export aportado por Alex contiene37registros y el POST del19octubre10:31:18 Nicaragua(16:31:18UTC), versión e4db309c,503/CONTACT_CHALLENGE_UNAVAILABLE/requestId38a947b2-6487-42ee-b47e-938b3f6ad353/duración0ms. Falló Siteverify antes de reserva/email; explica ausencia de actividad de envío para ese intento. Causa exacta no registrada: código agrupa HTTPnoOK/red/timeout/JSON;0ms no identifica causa. [Recibo mínimo sin IP/cabeceras](qa/contact-pilot-historical-result-2026-10-25.json). Siguiente: diagnóstico acotado de Siteverify, no investigar rebotes de un correo que esta petición no llegó a enviar. Sin nuevo envío ni activación.

**25 octubre — diagnóstico de contacto preparado:** handler registra fase, aceptación/incierto/sinintento y código fijo del proveedor, sinPII/messageId/mensajesdeerror y sin cambiar respuesta pública ni reintentos/reservas.315unitarias/build y15handler tras ajuste final correctos; 5integraciones runtime local correctas con email simulado/egreso bloqueado. Alex revisará Cloudflare aparte: instrucciones para evento del piloto19octubre entregadas, resultado histórico pendiente; no pedirle sesión en IAB otra vez. Staging53c8a833-af98-44f5-91a6-45bf6b7df7f7: GET404/CONTACT_DISABLED/no-store verificado. Sin envío ni activación; producción sin cambios. [Recibo y piloto concreto pendiente](qa/CONTACT_DIAGNOSTICS_2026-10-25.md). Siguiente: evaluar estado/hora/código aportados; MCP entradas/traducciones/taxonomías sigue en alcance.

**25 octubre — prioridad retomada por Alex:** contacto y capacidad editorial MCP forman parte de los siguientes incrementos. [Estado actual, investigación y secuencia](qa/CMS_CONNECTOR_REVIEW_2026-10-25.md). Las pausas inferiores son históricas; no implican nuevas autorizaciones de envío, grants o aplicación pública.

Requisito añadido por Alex el 18 de octubre de 2026. Forma parte del MVP del rediseño; sustituye la exclusión inicial del formulario. Mantener email/perfiles como alternativa accesible cuando el formulario no esté disponible. Categorías e idiomas del CMS no se modifican para implementarlo.

## Estado y decisiones pendientes

Alex confirmó que el correo del piloto no ha llegado y aplazó la siguiente prueba. Contacto desactivado; entrega y Reply-To sin verificar. No reintentar ni programar envíos automáticamente.

Actualización 19 octubre 2026: piloto autorizado de un único mensaje intentado y ventana staging cerrada. No hay confirmación de aceptación ni recepción; el aviso fue borrado por el reset de Turnstile. Defecto corregido y cubierto por regresión; 133 pruebas y build staging pasan. Pendiente confirmar recepción/Reply-To con Alex e investigar resultado antes de proponer otra prueba. No repetir automáticamente ni activar producción. Ver [informe](qa/CONTACT_STAGING_PILOT_RESULT.md).

**Conexión de privacidad completada:** `/privacy/` en producción devuelve 200 con ID 180 y Home EN enlaza ese destino; aviso y etiqueta editoriales actualizados. Producción conserva contacto desactivado y sin bindings de envío/coordinador de staging. Readiness exit 0 y smoke remoto ocho páginas correcto. Próximo paso: piloto real autorizado, no activación permanente. [Informe](qa/CONTACT_PRODUCTION_PRIVACY.md).

**Configuración staging completa por metadatos:** HMAC añadido sin mostrar/guardar valor; todos los checks de bindings, vars y nombres de secrets pasan. Formulario desactivado. Auditoría ahora distingue privacidad propia de staging, producción y enlace editorial de Home: staging 200, producción 404 y enlace viejo. Pendiente cerrar esas dos conexiones y ejecutar [piloto autorizado](CONTACT_STAGING_PILOT.md). No se probó challenge ni entrega real.

**Staging actualizado:** `/privacy/` conectado al ID EN 180 y desplegado; CONTACT_FORM_ENABLED=false, sin carga de widget. Bindings y vars remotas confirmados; falta secret HMAC. Smoke remoto de ocho páginas correcto. No se activó contacto ni envió correo. Producción y privacy_url editorial de Home pendientes de su despliegue separado. [Informe](qa/CONTACT_STAGING_DEPLOY.md).

**Privacidad publicada en el CMS:** EN 180 / ES 182, tras completar texto técnico y confirmar Alex revisión manual diaria de Gmail. Aviso fechado 18 octubre 2026. No se ha eliminado correo, automatizado Gmail ni activado el formulario. Pendiente conectar CONTACT_PRIVACY_PAGE_ID=180 y validar/desplegar frontend antes de sustituir privacy_url de Home. Procedimiento y fuentes en [CONTACT_RETENTION_RUNBOOK.md](CONTACT_RETENTION_RUNBOOK.md).

**Borradores CMS reales:** páginas EN 180 y ES 182 creadas y vinculadas en Polylang; guardadas Draft y reabiertas para verificar persistencia. Fuente Markdown, export Gutenberg y previews locales preparados con `privacy:export`. Continúan sin publicar, sin CONTACT_PRIVACY_PAGE_ID y sin cambiar privacy_url de Home. [Evidencia](qa/CONTACT_PRIVACY_CMS.md).

**Integración completa en runtime local:** cinco pruebas nuevas con handler real, binding nativo de email simulado por Cloudflare, rate limiter y Durable Object SQLite; pasan concurrencia, cuotas, confirmación perdida y rechazos previos al envío. Egreso externo bloqueado y datos ficticios. Suites runtime/coordinador incorporadas a CI. No sustituye la entrega real ni el piloto remoto. Evidencia en [qa/CONTACT_RUNTIME_QA.md](qa/CONTACT_RUNTIME_QA.md).

**Privacidad: datos recibidos.** Alex confirmó alex@alexcuadra.dev como contacto público, Gmail y conservación de consultas durante 30 días. Incorporados al borrador EN/ES; la huella de duplicados conserva su ventana independiente de 24 horas. Falta comprobar el procedimiento efectivo de eliminación del correo y logs/copias antes de publicar. No se configuró borrado automático ni se modificó CONTACT_TO_EMAIL.

**Incremento de auditoría remota:** confirmado que staging no tiene todavía bindings de contacto ni secreto HMAC; el endpoint nuevo no está desplegado. Política externa accesible HTTP 200, pero cubre ValexLinks; Alex eligió página propia en alexcuadra.dev. Preparados ruta CMS `/privacy/`, borrador EN/ES e instrucciones en [CONTACT_PRIVACY_DRAFT.md](CONTACT_PRIVACY_DRAFT.md). ID de página real, publicación CMS y sustitución del enlace siguen pendientes. Auditoría y evidencia: [qa/CONTACT_STAGING_READINESS.md](qa/CONTACT_STAGING_READINESS.md). La ausencia de ID mantiene `/privacy/` en 404; no se publica texto incompleto.

**Incremento de navegador/CSP:** revisión local con componente real y simulaciones de challenge/envío; contraste y ancho mínimo corregidos, CSP nativa Astro integrada. Informe reproducible y captura en [qa/CONTACT_BROWSER_QA.md](qa/CONTACT_BROWSER_QA.md). La URL EN es valexlinks.com/privacy, aún sin validar disponibilidad ni cobertura. Siguen pendientes challenge real, móvil/dispositivo/zoom/lector de pantalla, secreto HMAC y bindings remotos, privacidad y entrega autorizada. Sin activación ni despliegue.

**Continuación local:** coordinador Durable Object SQLite implementado para staging; límite global y deduplicación por contenido verificados localmente (127 pruebas unitarias y una de integración workerd). Aún sin recurso remoto ni secreto HMAC configurado. Las menciones históricas inferiores a estos controles como no implementados se refieren a versiones anteriores; su activación/verificación remota continúa pendiente. Detalles y limitaciones al final.

- [x] Revisar el contacto actual: ContactCTA solo genera mailto con email del CMS; no hay backend de formulario.
- [x] Definir el flujo y contrato de seguridad; consultar la documentación oficial de Turnstile.
- [x] Preparar validación, Siteverify y endpoint POST /api/contact con envío estructurado de Cloudflare; pruebas con binding/token simulados, sin correo real. Endpoint desactivado por defecto.
- [x] Alex confirma su correo personal como destino. Dirección exacta pendiente de configurar únicamente en el servidor; no publicarla ni asumir que coincide con el email público del CMS.
- [x] Alex confirma que no tiene un servicio de envío configurado.
- [x] Ruta de envío elegida mediante la configuración guiada: Cloudflare Email Sending para forms.alexcuadra.dev; Alex reporta Sending status Enabled y los registros se verificaron en DNS autoritativo.
- [x] Destino personal figura Verified según Alex. No se recibió ni publicó la dirección en el repositorio/chat.
- [x] Alex confirma sitekey y secreto Turnstile guardados en Cloudflare staging. Sitekey pública sincronizada en Wrangler junto con las variables de autenticación existentes; secreto fuera del repositorio. Challenge real aún no probado.
- [ ] Completar bindings, configuración privada, límites, Turnstile e interfaz antes de una prueba autorizada de envío. Resend queda como alternativa sin cuenta ni contratación.
- [x] Fields editoriales EN importados y completos, backend y UI/widget locales integrados (desactivados).
- [ ] Completar textos/reglas ACF ES al existir Home traducida; controles globales/idempotencia y verificación de accesibilidad en navegador.
- [ ] Configurar claves y hostnames por entorno, verificar remitente/DNS y probar entrega controlada.
- [ ] Validar E2E, privacidad, accesibilidad, fallos y observabilidad antes de activar.

Los módulos `contact-security.js` y `contact-handler.js` están conectados a `src/pages/api/contact.ts`, pero el endpoint devuelve 404 salvo que CONTACT_FORM_ENABLED sea exactamente true. La interfaz está implementada localmente y permanece oculta mientras el flag esté desactivado; no hay envío habilitado. Si faltan configuración o bindings devuelve 503 sin llamar al proveedor. No activar hasta completar los controles pendientes.

Verificación actual: 120 pruebas unitarias correctas; envío y challenge simulados. Incluye orden de validación/límites/challenge/envío, errores/redacción, deadline del proveedor y ausencia de reintentos automáticos. Cinco pruebas adicionales ejecutan el controlador TypeScript transpilado con DOM/Turnstile simulados: validación, challenge expirado/fallido, envío concurrente, aceptación, conservación del texto, estados EN/ES y bloqueo tras entrega incierta/red/respuesta inválida. No equivalen a E2E de navegador ni a prueba de correo/challenge reales.

Las excepciones del binding de correo también devuelven CONTACT_DELIVERY_UNKNOWN: un error no demuestra que el proveedor no aceptó el envío. Los callbacks posteriores del widget conservan ese aviso y el bloqueo. Esto reduce reenvíos accidentales en la sesión, pero no sustituye idempotencia duradera en servidor.

## Selección de envío: revisión del 18 octubre 2026

La revisión inicial partió sin proveedor. Actualmente se eligió Cloudflare: forms.alexcuadra.dev está Enabled con DNS verificado y Alex confirma su destino personal Verified. Su dirección no se ha recibido ni se publica. El binding send_email y la configuración privada de esa dirección siguen pendientes. La documentación actual permite enviar a direcciones de destino verificadas gratis en todos los planes, incluso con solo Email Routing configurado. No confundirlo con envío a destinatarios arbitrarios, que requiere Workers Paid. La disponibilidad/configuración de cuenta y dominio todavía debe verificarse; no se asegura que baste con añadir un binding.

| Opción evaluada | Encaje y límites verificados |
| --- | --- |
| Cloudflare nativo | Aprovecha Workers existente y destino fijo verificado. Requiere alta/configuración de dominio y binding; comprobar disponibilidad y registros requeridos. Funciones actuales de Email Sending están marcadas beta |
| Resend | Alternativa HTTP con tutorial oficial de Cloudflare. Free: 3.000 emails/mes, máximo 100/día y tres dominios según tarifa consultada. Requiere cuenta y verificar dominio/remitente; no se seleccionó ni creó cuenta |
| Brevo | Alternativa transaccional con API y plan Free de 300 envíos/día. No se seleccionó ni creó cuenta |

Los límites/precios son los publicados al revisar; verificar de nuevo al configurar. No se activa un plan de pago ni overages. Para Cloudflare, el remitente debe pertenecer a un dominio dado de alta; el destino personal debe estar verificado. El binding y el backend deben limitar los destinos. Si la restricción del binding requiere el correo personal en configuración, no publicarlo en Wrangler/Git: resolver la configuración privada del despliegue antes de añadirla. No habilitar remote bindings con envío real durante tests locales.

Historial del alta inicial, ya superado: el botón Onboard Domain no permitía distinguir el flujo de recepción del de envío. No aplicar la propuesta inicial del apex; la configuración vigente utiliza forms.alexcuadra.dev. La consulta DNS pública del 18 octubre detectó cinco MX eforward1–5.registrar-servers.com y SPF v=spf1 include:spf.efwd.registrar-servers.com ~all. No se obtuvo TXT DMARC en esa consulta puntual. Esto prueba configuración publicada de reenvío, no uso efectivo ni disponibilidad del servicio. Conservar MX/SPF existentes; comparar el resumen de registros cf-bounce/DKIM/DMARC propuestos y cualquier requisito de plan antes de aplicar. No asumir que se necesita reemplazar los MX raíz para enviar a un único destinatario verificado. No instalar un plugin SMTP en WordPress ni usar la cuenta de servicio GraphQL para enviar correo.

Fuentes de la comparación: [Cloudflare: tarifas y destinos verificados](https://developers.cloudflare.com/email-service/platform/pricing/), [binding y restricciones](https://developers.cloudflare.com/email-service/configuration/send-bindings/), [dominio y envío](https://developers.cloudflare.com/email-service/get-started/send-emails/), [Resend: tarifa vigente](https://resend.com/pricing), [Cloudflare: integración Resend](https://developers.cloudflare.com/workers/tutorials/send-emails-with-resend/), [Brevo: plan gratuito](https://help.brevo.com/hc/en-us/articles/208589409-About-Brevo-s-pricing-plans).

## Arquitectura propuesta

Navegador → POST same-origin `/api/contact` en Astro/Worker → límites y validación → Siteverify en servidor → proveedor de correo mediante integración compatible con Workers → respuesta de aceptación o error controlado.

El visitante no ejecuta GraphQL ni recibe credenciales. El endpoint no utiliza la cuenta WPGraphQL para enviar mensajes ni crea posts/CPT con los envíos. No sumar llamadas al CMS por cada POST. Usar binding nativo si se valida Cloudflare, o API HTTP transaccional compatible con Workers si se elige proveedor externo; si solo existe SMTP Hostinger, evaluar su compatibilidad y una integración adecuada antes de elegir. No asumir que disponer de buzón equivale a disponer de API de envío.

Destinatario, remitente verificado y asunto base definidos exclusivamente por configuración del servidor. Nunca aceptar `to`, `from`, cabeceras arbitrarias, URLs de retorno ni asunto libre como autoridad de envío del cliente. El email validado del visitante se usa como Reply-To mediante campos estructurados del proveedor, sin concatenar cabeceras. El email público puede seguir siendo editorial en WordPress; la dirección de entrega es configuración técnica autorizada del backend.

No guardar mensajes en WordPress ni añadir almacenamiento permanente por defecto. Si se introduce cola/almacenamiento, documentar propósito, acceso, cifrado disponible y retención con Alex. La respuesta de éxito indicará aceptación del envío, sin prometer recepción en la bandeja del destinatario.

## Campos y validación

Campos iniciales: nombre (1–100 caracteres), email (máximo 254 y local part máximo 64), mensaje (1–5000), locale EN/ES y token Turnstile (máximo 2048). Sin adjuntos en MVP. La política inicial del módulo admite direcciones ASCII y dominios punycode; no pretende aceptar todos los formatos RFC o email internacional con Unicode en el local part. Explicar el error y ofrecer el contacto alternativo.

Normalización Unicode NFC, trim y saltos de línea del mensaje. Rechazar caracteres de control y CR/LF en campos de una línea; rechazar campos inesperados y objetos en lugar de strings. No interpolar HTML ni cabeceras. **Mensaje como texto plano**: el código del visitante se trata como texto literal; no eliminar arbitrariamente `<`/`>` si está describiendo un problema técnico. Escapar al mostrar valores o errores en HTML y usar el campo text/plain del proveedor. Si se añade plantilla HTML, aplicar escaping al interpolar cada valor, sin reutilizar el sanitizador de HTML editorial para interpretar el mensaje como markup.

Validación de cliente para usabilidad, repetida en servidor como autoridad. Cuerpo JSON máximo 16 KiB, contando bytes del stream aunque Content-Length falte o mienta; tiempo máximo de lectura de cinco segundos. POST únicamente, Content-Type explícito y rechazo de JSON/UTF-8 inválidos. Módulo actual: `validateContactInput`, `readContactRequest`, `verifyContactTurnstile`, errores tipados sin contenido personal.

## Turnstile

Crear widget con modo Managed y action `contact`, hostnames exactos por entorno y sitekey pública. `TURNSTILE_SECRET_KEY` solo como secreto runtime; nunca PUBLIC_*, HTML, bundle o Git. Preferir claves/widgets separados para producción y staging. Configuración de desarrollo con claves de prueba oficiales; nunca activar un bypass universal ni promover claves de prueba a producción.

Validar el token con Siteverify **en cada envío** y exigir success=true, hostname esperado de la configuración y action=contact. No confiar en el callback de cliente. Los tokens duran cinco minutos y son de un solo uso; al expirar, fallar o reintentar tras un envío incierto se necesita gestionar un token nuevo. El módulo no implementa reintentos ciegos de Siteverify. Un timeout, respuesta no JSON o configuración incompleta no permite enviar correo.

Comparar contra hostnames configurados, no aceptar automáticamente el Host/Origin proporcionado por el visitante como allowlist. No enviar IP a Siteverify por defecto; evaluar necesidad y privacidad antes de añadir remoteip. Añadir permisos mínimos de CSP para script/frame de challenges.cloudflare.com según la documentación y cargar el script oficial, sin proxy ni copia local.

## Controles de abuso y privacidad

- Límite distribuido antes de operaciones costosas y un límite de volumen del proveedor. Elegir binding/servicio tras comprobar disponibilidad y alcance del plan Cloudflare; documentar umbrales y ajustar con tráfico real. No usar Map en memoria como protección de producción ni presentar Turnstile como rate limiter.
- Origin exacto configurado por entorno, sin CORS abierto; el backend actual preparado no usa sesiones ni cookies de autenticación. Origin no sustituye challenge ni límites y puede falsificarse fuera de un navegador. Si se añaden cookies/sesiones, revisar CSRF explícitamente.
- No permitir destinatarios o reenvíos abiertos. No autorresponder al visitante por defecto: evita usar el formulario para enviar spam a direcciones introducidas por terceros.
- Control de duplicados e idempotencia con capacidades del proveedor/almacenamiento elegidos; no reintentar correo ante timeout sin saber si fue aceptado. Deshabilitar temporalmente envío repetido en UI sin confiar en ello como control servidor.
- Respuestas no-store y errores genéricos, con 400/403/405/413/415/429/503 según causa. No devolver errores internos de Cloudflare o del proveedor. No registrar nombre/email/mensaje/token/secret; solo ID de solicitud, estado, categoría de error, duración y resultado técnico.
- Aviso de privacidad claro con finalidad y terceros involucrados; no mezclar contacto con suscripción o marketing. No marcar consentimiento comercial por defecto ni retener IPs/mensajes sin necesidad documentada.

## Experiencia bilingüe y contenido CMS

ACF gratuito: grupo de campos simples en la página normal de ajustes existente/propuesta, con registros EN/ES relacionados. Campos previstos: título/introducción, etiquetas y ayuda de nombre/email/mensaje, botón, mensajes de validación/envío/éxito/error/challenge expirado, texto/enlace de privacidad y contacto alternativo. Son textos publicables y deben cargarse en content.alexcuadra.dev; no introducirlos como copia editorial permanente en componentes. Claves técnicas, límites y secretos son configuración del backend.

Labels visibles, autocomplete=name/email, inputmode=email, límites de longitud, instrucciones, errores asociados con aria-describedby y resumen/foco accesible. Estado de envío anunciado con aria-live; conservar texto al fallar, resetear challenge según corresponda y limpiar campos solo tras aceptación confirmada. Probar teclado, zoom, lectores de pantalla y anchura móvil. Sin challenge disponible, mostrar alternativa de contacto; no anunciar éxito ni omitir la verificación. El fallback mailto no debe incluir automáticamente contenido personal del formulario en query strings.

## Guía de configuración durante ejecución

1. Confirmar destinatario/proveedor; preparar integración y UI desactivadas por configuración hasta estar completas.
2. Guiar Turnstile en Cloudflare: widgets, dominios exactos de producción/staging, sitekeys públicas y secretos runtime separados. No pedir claves en el chat.
3. Guiar verificación de remitente y DNS requeridos por el proveedor (SPF/DKIM/DMARC según sus instrucciones), revisando registros existentes antes de cambiar. No inventar valores ni alterar correo/DNS de WordPress por ensayo.
4. Añadir secrets/vars por entorno, CSP y controles distribuidos; nunca añadir secretos al archivo Wrangler versionado.
5. Pruebas deterministas con proveedor simulado y claves oficiales de prueba; staging con destinatario autorizado y prueba de entrega explícita. No enviar desde CI normal.
6. Activar solo con backend/fields/challenge/límites completos y prueba de entrega. Documentar desactivación inmediata del formulario y fallback email ante incidente.

## Pruebas y criterio de aceptación

Validación de campos/tipos/longitudes, Unicode/saltos de línea, XSS tratado como texto, inyección de cabeceras, cuerpo chunked sobredimensionado, Origin/media type/método, JSON inválido, token ausente/inválido/expirado/reutilizado, hostname/action incorrectos y timeout de Siteverify. Probar límites distribuidos, cuotas, idempotencia, fallos del proveedor y ausencia de envío cuando falle cualquier control. Verificar que logs/bundles/respuestas no exponen PII ni secretos; CSP y E2E EN/ES con teclado y móvil. Confirmar aceptación por proveedor y recepción real en prueba autorizada; no afirmar entrega por un simple HTTP 200 del endpoint.

Pruebas de challenge automatizadas con claves de prueba o respuestas simuladas; no resolver challenges reales en CI ni introducir un bypass de producción. Las pruebas iniciales del módulo no cubren todavía entrega, widget, rate limiter o E2E.

## Fuentes oficiales

- [Turnstile: validación servidor, expiración, uso único y campos de respuesta](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/)
- [Turnstile: integración del widget](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/)
- [Turnstile: Content Security Policy](https://developers.cloudflare.com/turnstile/reference/content-security-policy/)

## Revisión de la propuesta DNS recibida

Alex compartió tres MX en el apex alexcuadra.dev hacia route1/route2/route3.mx.cloudflare.net (prioridades 38/38/25), DKIM cf2024-1._domainkey y SPF raíz con include:_spf.mx.cloudflare.net. Esa propuesta configura recepción/reenvío hacia Cloudflare, no únicamente los registros cf-bounce de un alta de envío. La identificación inicial del flujo a partir del botón Onboard Domain era insuficiente; mandan los registros concretos.

La consulta pública posterior sigue mostrando los cinco MX eforward*.registrar-servers.com y el SPF de ese servicio. Es una comprobación puntual, no garantía de propagación global ni confirmación de uso efectivo. No se aplicó ningún cambio desde las herramientas. No recomendar Done/Confirm sobre esta propuesta hasta saber si Alex utiliza direcciones @alexcuadra.dev y revisar la conservación de sus alias/destinos. Se planteó esa pregunta.

Si utiliza el correo del dominio, preservar la recepción actual y evaluar Email Sending separado/subdominio o Resend antes de plantear una migración de MX. Si no lo utiliza, todavía revisar destinatario verificado, rutas, plan y registros finales; no tratar la respuesta sobre uso como autorización automática para cambiar DNS o publicar el endpoint. El DKIM compartido es una clave pública DNS, no una credencial del backend; no se necesita una clave privada en el chat.

## Decisión de preservar la recepción existente

Alex confirmó que utiliza direcciones @alexcuadra.dev. Se descarta aplicar el alta propuesta con MX de Cloudflare en el apex o sustituir el SPF raíz: la recepción y sus reenvíos deben conservarse. No se está autorizando una migración de correo.

La documentación oficial permite dar de alta un subdominio como dominio separado de Email Sending. Ruta propuesta: forms.alexcuadra.dev exclusivamente para el formulario, remitente técnico a definir en ese subdominio y destino personal verificado en Cloudflare. Comprobar el formulario de alta real en Compute → Email Service → Email Sending, la posibilidad de introducir el subdominio y los registros finales; no confirmar la propuesta de Email Routing del apex. La disponibilidad, DNS y alta de esta ruta siguen pendientes. No crear una zona Cloudflare nueva ni un Worker separado solo por este paso.

Los registros de envío deberán corresponder al subdominio y su cf-bounce/DKIM/DMARC, preservando MX/SPF de alexcuadra.dev. Antes de guardar comparar cada hostname exacto, incluidos posibles cambios de DMARC, y comprobar que no se activa recepción del apex. Si el panel solo admite migrar el apex o requiere un plan de pago para la modalidad disponible, evaluar Resend con dominio de envío separado en vez de improvisar sustitución de MX.

Fuente: [Cloudflare: subdominios de Email Sending](https://developers.cloudflare.com/email-service/configuration/subdomains/). Resend también permite verificar subdominios de envío, sin activar su recepción: [dominios verificados](https://resend.com/docs/dashboard/domains/introduction).

## Alta de forms: propuesta DNS revisada

Alex compartió la nueva propuesta limitada al subdominio: tres MX en cf-bounce.forms.alexcuadra.dev (route1/2/3, prioridades 38/38/25), SPF en ese mismo nombre, DKIM en cf-bounce._domainkey.forms.alexcuadra.dev y DMARC p=reject en _dmarc.forms.alexcuadra.dev. Coincide con el alta separada documentada de Email Sending. No propone sustituir MX/SPF del apex; la política DMARC indicada corresponde al dominio de envío forms.

La consulta pública previa a confirmar no devolvió registros para esos cuatro nombres/tipos; MX/SPF de alexcuadra.dev siguen en eforward/registrar-servers.com. No se detectaron conflictos en esa consulta puntual. Esta revisión corresponde al momento previo al alta. Posteriormente se confirmó el envío habilitado y el destino Verified, como se registra abajo. Bindings, rate limit, Turnstile y entrega siguen pendientes. No se declara enviado ningún mensaje.

## Estado confirmado: envío habilitado en forms

Alex reportó Configuration summary → Sending status Enabled para forms.alexcuadra.dev. Consulta directa a jasper.ns.cloudflare.com confirmó los tres MX cf-bounce.forms, SPF, DKIM y DMARC p=reject propuestos. La consulta recursiva inicial todavía no mostraba los registros nuevos; se distinguió esa caché de la publicación autoritativa. MX/SPF de alexcuadra.dev siguen en el proveedor previo de reenvío; no se migró la recepción existente.

Paso completado por Alex: destino personal añadido y Verified en Destination Addresses. Las direcciones verificadas son de cuenta y se pueden usar para envío; no hace falta publicar el correo personal en Git ni crear rutas de recepción/catch-all para el apex. Registrar únicamente el estado Verified, no copiar dirección ni enlace de verificación al chat. Si el panel exige configurar recepción antes de acceder a destinos, detener ese flujo y revisar la pantalla sin tocar MX del apex.

La configuración de envío habilitada y el destino Verified no prueban entrega. Configuración privada de entrega, bindings, widget, rate limit y prueba real de correo continúan pendientes.

## Endpoint local integrado, aún desactivado

Alex confirmó el destino Verified. `handleContactRequest` implementa la cadena: flag → método/configuración → Origin/media type/tamaño declarado → limitador → lectura acotada → Siteverify → envío estructurado. No consulta GraphQL ni registra PII/tokens. Comparte requestId generado por middleware; respuesta no-store/noindex y cuerpo con código técnico. Success devuelve 202 (aceptación del proveedor), no confirmación de recepción.

El envío usa CONTACT_EMAIL.send con to/from fijos de servidor, Reply-To validado, subject técnico fijo y text/plain. CONTACT_FROM_EMAIL debe pertenecer a forms.alexcuadra.dev. Los errores del binding se redactan, no se reintenta; deadline de 10 segundos produce CONTACT_DELIVERY_UNKNOWN, porque el envío podría terminar después. No activar una UI que invite a reintentar ciegamente ese caso. Deduplicación duradera y prueba de entrega siguen pendientes.

Contrato de configuración runtime: CONTACT_FORM_ENABLED, CONTACT_ORIGIN exacto por entorno, CONTACT_FROM_EMAIL (propuesto contact@forms.alexcuadra.dev), CONTACT_TO_EMAIL privado, TURNSTILE_SECRET_KEY secreto y TURNSTILE_SITE_KEY público. Alex confirma las dos claves Turnstile guardadas en staging; sitekey sincronizada en Wrangler y CONTACT_FORM_ENABLED fijado a "false". CONTACT_ORIGIN y CONTACT_FROM_EMAIL también están definidos en Wrangler staging; Alex confirma CONTACT_TO_EMAIL guardado como Secret en staging; su valor no se leyó ni registró. Bindings CONTACT_EMAIL y CONTACT_RATE_LIMITER añadidos al Wrangler de staging; despliegue y verificación remota pendientes. Guardar el destino privado en secrets, no en vars del repo. Mantener CONTACT_FORM_ENABLED=false/ausente.

Se eligió una interfaz compatible con el binding de rate limit de Workers. La clave incluye el origen del entorno y CF-Connecting-IP suministrada por Cloudflare; se utiliza solo en el contador y no se registra. Los límites nativos son por ubicación, permisivos y eventualmente consistentes: no equivalen a un presupuesto global exacto ni evitan por sí solos abusos desde múltiples IP/ubicaciones. La identidad anónima puede compartir NAT; definir umbral razonable y revisar errores antes de activar. No inventar un contador global en Map; para presupuesto o idempotencia estricta evaluar almacenamiento coordinado.

Paso guiado completado según Alex: claves Turnstile guardadas en el Worker staging. El hostname previsto es alexcuadra-dev-staging.alexcu21.workers.dev, modo Managed sin pre-clearance; falta comprobarlo con un challenge real. El frontend usará action=contact al renderizar. Producción tendrá su widget/keys separados y se configurará después. Próximo trabajo: completar bindings/límites y configuración privada, manteniendo el formulario desactivado.

Fuentes de implementación: [API estructurada de email](https://developers.cloudflare.com/email-service/api/send-emails/workers-api/), [Rate Limiting y alcance](https://developers.cloudflare.com/workers/runtime-apis/bindings/rate-limit/).


## Bindings de staging preparados en el repositorio

- [x] `CONTACT_EMAIL` mediante `send_email`, restringido al remitente técnico `contact@forms.alexcuadra.dev`. La dirección personal no se incluye en Git; el handler solo acepta el destinatario configurado en servidor.
- [x] `CONTACT_RATE_LIMITER`: umbral inicial de 5 solicitudes por 60 segundos, por IP/origen/ubicación. Namespace propuesto `2026101801`; antes de desplegar comprobar que no esté reservado por otro Worker de la cuenta. No es un identificador de recurso que haya que crear previamente.
- [x] Origen exacto de staging y remitente técnico configurados. `CONTACT_FORM_ENABLED` sigue en `"false"`.
- [x] Alex confirma `CONTACT_TO_EMAIL` como Secret del Worker staging; no se recibió el valor.
- [ ] Desplegar y comprobar bindings remotos, sin enviar correo automáticamente. Producción aún no tiene bindings ni claves de contacto.

El límite inicial cuenta intentos antes de Turnstile y lectura del cuerpo. Puede afectar a usuarios que compartan NAT; se revisará antes de activación. El namespace debe ser exclusivo o compartiría contadores con otros bindings que usen el mismo identificador y clave. No garantiza presupuesto global exacto; idempotencia/presupuesto coordinado siguen pendientes. Añadir un binding de envío no prueba que la cuenta permita usarlo ni que el correo llegue.

Fuente: [restricciones del binding de correo](https://developers.cloudflare.com/email-service/configuration/send-bindings/) y [rate limiting de Workers](https://developers.cloudflare.com/workers/runtime-apis/bindings/rate-limit/).


## Campos editoriales: importación preparada

Archivo nuevo `docs/wordpress/acf-contact-form.json`: grupo independiente Contact form con 17 campos simples compatibles con ACF gratuito. Se asocia solo a Home (ID 19) y no modifica los grupos existentes. GraphQL: grupo contactForm; confirmar nombres de campos mediante introspección después de importar. No se añaden consultas al frontend antes de confirmar el esquema.

Paso guiado: WordPress → ACF → Tools/Herramientas → Import Field Groups → seleccionar el JSON → Import. Después abrir Home y completar los textos en inglés, guardarlos y confirmar que aparece el grupo. Sin valores editoriales por defecto: Alex introduce el contenido real en el CMS. El aviso de éxito debe describir aceptación, sin prometer recepción; el de entrega incierta debe evitar invitar a reenviar inmediatamente. Privacy URL debe apuntar a una política real publicada, no a una URL inventada. No guardar datos del destinatario privado en estos campos públicos.

Campos: heading, introduction, name_label, email_label, message_label, submit_label, sending_message, success_message, validation_message, challenge_message, rate_limit_message, unavailable_message, unknown_delivery_message, privacy_text, privacy_link_label, privacy_url, alternative_label. Los mensajes son compartidos para cada categoría de error, sin detalles técnicos internos.

No se adivina el ID de Home en español: cuando se cree su traducción, añadir un grupo OR de ubicación con el ID real y rellenar textos ES. No activar copia/sincronización automática de textos editoriales entre idiomas. Alex confirmó importación y textos EN guardados. Se verificaron por GraphQL los 17 campos completos; sus nombres reales conservan guiones bajos. Interfaz implementada localmente, pendiente de pruebas de navegador y despliegue.


## Integración de textos e interfaz local

- [x] Home expone `contactForm` con sus 17 campos; consulta autenticada HTTP 200, sin errores, todos completos y privacy_url HTTPS válida. No se imprimió el contenido editorial ni credenciales.
- [x] `contact-content.js` resuelve Home y sus traducciones públicas reales, valida idioma/campos y proyecta únicamente los textos previstos. No hay fallback editorial entre idiomas.
- [x] `ContactForm.astro` y `contact-form.ts`: labels/autocomplete, campos acotados, estado anunciado, foco tras respuesta, widget Managed explícito con action contact, expiración/error, envío JSON same-origin, texto conservado al fallar y limpieza solo tras aceptación. Sin HTML editorial ejecutable.
- [x] La carga de Turnstile y el render del formulario dependen del flag; no se cargan para visitantes mientras esté desactivado. El mailto existente permanece disponible.
- [x] Reenvío bloqueado ante resultado incierto (timeout/red/CONTACT_DELIVERY_UNKNOWN), sin reintento automático. Un reset de widget puede volver a habilitar tras otros resultados; rate limit del servidor continúa aplicando.
- [x] 115 pruebas pasan y build completo sin errores (6 hints preexistentes de símbolos no usados).
- [ ] Pruebas del flujo de navegador, teclado/móvil y fallos del challenge con simulación controlada; todavía no se declara E2E ni accesibilidad verificadas.
- [ ] Idempotencia/presupuesto global, verificación de bindings remotos y entrega real antes de activar. No se envió correo ni se desplegó.

La política de privacidad referenciada está configurada, pero la consulta solo valida formato HTTPS; revisar que la página exista y describa procesamiento/terceros antes de publicar el formulario. Los textos ES se cargarán al existir la traducción real de Home y su regla ACF.

## Coordinación global y duplicados — implementación local

`ContactCoordinator` usa un único Durable Object SQLite por Worker/entorno, identificado por `contact-global-v1`. Tras límites por IP, lectura/validación y Siteverify, el handler solicita una reserva atómica antes de llamar al proveedor. La transacción elimina registros vencidos, comprueba duplicados y ventanas móviles de 20 reservas/hora y 100/24 horas, e inserta la reserva. No se libera ante fallo, timeout o reinicio: el proveedor podría haber aceptado el mensaje. Si el coordinador falla o no está configurado, no se llama al correo.

Se persisten solo HMAC-SHA-256 de `[nombre, email, mensaje, locale]` ya normalizados y timestamp de reserva. El token queda fuera para reconocer reenvíos con challenge nuevo. No se guardan mensajes, direcciones, IP, tokens ni respuestas del proveedor; no hay cola. El secreto `CONTACT_DEDUP_SECRET` debe ser aleatorio, de al menos 32 caracteres y exclusivo del entorno, guardado como secreto de Workers; no se solicitó ni generó un valor remoto. Rotarlo cambia las huellas y rompe la deduplicación con reservas anteriores: desactivar envíos y respetar la ventana de 24 horas antes de volver a activar si se rota.

Retención lógica: 24 horas desde la reserva. Cada reserva purga vencidos y una alarma elimina registros al vencer aun sin tráfico; la ejecución de alarmas puede retrasarse. Esto no garantiza borrado físico de backups/PITR del proveedor; revisar políticas y cifrado de Cloudflare antes de activación remota y reflejar el tratamiento en privacidad. El binding solo es accesible desde código del Worker y usuarios con acceso administrativo a la cuenta; no se añade ruta pública del coordinador. HMAC reduce exposición de contenido, pero sigue siendo metadato derivado: no se presenta como anonimización absoluta.

Una reserva existente devuelve `CONTACT_DELIVERY_UNKNOWN`, incluso si el intento anterior fue aceptado: se evita anunciar aceptación sin confirmación del proveedor en esa solicitud. La UI conserva el texto y bloquea reenvíos de esa sesión. Se garantiza como máximo un intento por contenido idéntico normalizado durante la ventana; no entrega exactamente una vez, no deduplicación semántica ni permanente. Cambiar el contenido o esperar 24 horas permite otra reserva. Las cuotas limitan intentos reservados, no gastos totales de Workers/Turnstile ni entrega en bandeja. Los límites son iniciales y deben revisarse con tráfico real.

Wrangler staging declara clase SQLite y binding; el entrypoint personalizado reexporta el handler oficial de Astro y la clase. Producción mantiene el entrypoint oficial y `exports: {}`: este paso no prepara un recurso de contacto de producción. Formulario desactivado. No desplegar sin revisar capacidad/coste de cuenta, privacidad y secreto; no se activó un plan ni se contrató servicio.

Verificación: `npm test` (127 pruebas), `npm run test:contact-coordinator` (una integración con Miniflare/workerd SQLite, sin recursos remotos). La integración ejecuta 12 solicitudes concurrentes: una reserva y 11 rechazos de duplicado, más el tope horario. Pruebas SQLite locales adicionales cubren ventanas horaria/diaria, recreación de instancia, alarmas, expiración e inputs inválidos; pruebas del handler cubren fallos del coordinador, cuotas y deduplicación con token renovado. No sustituyen una prueba multirregión remota, entrega real ni revisión de navegador.

Pendiente inmediato: revisión de navegador/teclado/móvil, CSP y privacidad; configurar `CONTACT_DEDUP_SECRET` en staging, desplegar con flag desactivado y verificar bindings. Prueba real de entrega requiere autorización explícita. No activar por tener únicamente estas pruebas locales.

Referencias oficiales: [Astro: entrypoint personalizado](https://docs.astro.build/en/guides/integrations-guide/cloudflare/#changed-custom-entrypoint-api), [Cloudflare: transacciones SQLite](https://developers.cloudflare.com/durable-objects/api/sqlite-storage-api/#transactionsync), [declaración de clases](https://developers.cloudflare.com/durable-objects/reference/durable-objects-migrations/), [precios y límites de plan](https://developers.cloudflare.com/durable-objects/platform/pricing/).
