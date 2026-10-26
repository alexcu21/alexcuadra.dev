# Siteverify: incompatibilidad del runtime y diagnóstico — 26 octubre 2026

## Hallazgo reproducido

El piloto del 19 de octubre quedó localizado en el export aportado por Alex: POST /api/contact, 503 CONTACT_CHALLENGE_UNAVAILABLE, duración0ms, versión e4db309c. [Recibo mínimo del evento](contact-pilot-historical-result-2026-10-25.json). No llegó a la reserva ni al envío.

Al probar la función con el fetch **nativo de workerd**, sin sustituirlo por un mock JavaScript, se reprodujo el fallo antes de cualquier petición saliente. `redirect: 'error'` lanza TypeError en este runtime:

> Invalid redirect value, must be one of "follow" or "manual"

La prueba temporal comparó fetch extraído y fetch llamado sobre globalThis: ambos fallaron por el mismo valor de redirect. Se descartó ese enlace de contexto como causa en el ensayo. El diagnóstico resultó transport/duración0 y el contador de peticiones salientes quedó en cero. El sondeo temporal se retiró; no se registra el mensaje crudo de errores en producción.

Esto confirma un defecto del código y reproduce el patrón histórico. El registro antiguo no conserva la excepción original, por lo que no se presenta como una lectura recuperada de aquella excepción ni como validación remota completa.

## Corrección

Siteverify usa ahora `redirect: 'manual'`. Se rechaza toda respuesta HTTP no exitosa, incluidas redirecciones, sin seguir Location ni reenviar secretos/tokens. No se añade retry ni bypass de Turnstile. El endpoint, parámetros, timeout5s y exigencias success/hostname/action se conservan.

La [documentación oficial de Siteverify](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/) exige validación del servidor y describe sus respuestas/códigos; el comportamiento concreto de redirect se constató ejecutando el runtime instalado.

## Diagnóstico seguro

`contact_result.siteverify` incluye reason, durationMs y, si existe respuesta, httpStatus. Motivos: transport, timeout, http, invalid_json, invalid_response, rejected, hostname_mismatch, action_mismatch, verified. Para rejected se conserva solo la lista deduplicada de los siete códigos oficiales permitidos. No se almacenan tokens, secretos, hostname recibido, cdata, cuerpos, errores completos ni URLs de redirección.

Diagnóstico opcional: si el observador falla, no cambia la validación. No se devuelve al visitante. HTTP/red/timeout/JSON inválido producen el 503 genérico; respuesta válida de rechazo o discrepancia de hostname/action mantiene403. Un JSON sin boolean success pasa a503 de servicio en lugar del403 anterior: nunca habilita el envío.

## Verificación

- 320 pruebas unitarias: cinco regresiones nuevas sobre categorías Siteverify, lectura del cuerpo, códigos permitidos, discrepancias/observador y conexión al log del handler.
- Seis integraciones de runtime correctas. La nueva usa fetch/URLSearchParams/AbortSignal nativos, con **todo el egreso interceptado** y valores sintéticos; verifica éxito, HTTP502, JSON inválido y redirección302 rechazada. Cuatro intentos interceptados exactos para cuatro verificaciones, sin reintentos ni seguimiento de redirects.
- Antes de la corrección esa integración falló con503, diagnóstico transport y cero intentos interceptados; después pasa. Las cinco integraciones anteriores siguen pasando con email simulado.
- Build final correcto con0errores/0advertencias. Sin pruebas de envío real, validación de token real ni recepción/Reply-To confirmados.

## Estado y siguiente acción

Formulario desactivado; producción sin cambios. No se han cambiado claves, hostname del widget, DNS, cuotas, permisos, CMS o MCP. Este defecto de Siteverify es independiente de los429delCMS.

Staging publicado desde `63a6403`, versión `843026f8-91b5-42b1-90e0-111d83501c76`, previa `53c8a833-af98-44f5-91a6-45bf6b7df7f7`. [GET posterior](siteverify-staging-2026-10-26.json):404 CONTACT_DISABLED/no-store. Este GET no valida Siteverify remoto; confirma que el formulario no quedó abierto. Producción permanece en `feba64f8-5406-4c69-8522-6c3753681a37`. Reversión: restaurar la versión previa de staging, sin cambiar secretos ni reservas. Sin push.

Después del despliegue de staging: una única prueba autorizada con el formulario temporalmente activo, observación del HTTP/requestId y log Siteverify. Mensaje ficticio CONTACT-PILOT-2, nombre Alex Cuadra, Reply-To público alex@alexcuadra.dev, destinatario exclusivamente configurado en servidor; no modificarlo. Solicitar confirmación de recepción y Reply-To, cerrar ventana/desactivar. El piloto anterior fue aplazado; no interpretar continuidad genérica como autorización para ese nuevo envío ni reintentar resultados inciertos.

La ampliación MCP para posts/traducciones/taxonomías sigue en alcance después de este incremento; no se instala ni amplía acceso por esta corrección.
