# Diagnóstico de contacto — 25 octubre 2026

## Resultado

El handler mantiene su contrato público y añade diagnóstico estructurado al evento `contact_result`. No se envió correo real ni se habilitó el formulario. La causa histórica del piloto del 19 de octubre sigue sin determinarse.

Desplegado en staging desde `3bb638f`, versión `53c8a833-af98-44f5-91a6-45bf6b7df7f7`, previa `c8c8c2f3-9153-4a17-819d-31e1c9153cdd`. Build final 0 errores/0 advertencias. [Postflight GET](contact-diagnostics-staging-2026-10-25.json): 404 CONTACT_DISABLED/no-store; sin POST ni envío. No se afirma verificación remota de los diagnósticos de entrega mientras el endpoint está desactivado. Producción continúa en `feba64f8-5406-4c69-8522-6c3753681a37`. Rollback: restaurar la versión previa de staging; no cambia reservas ni secretos. Sin push.

La pestaña auxiliar de Cloudflare en IAB llegó al acceso, sin sesión disponible. Alex eligió iniciar Cloudflare por su cuenta y pidió instrucciones; se cerró la pestaña auxiliar, sin usar sus otras pestañas ni obtener credenciales. La revisión del registro histórico queda a cargo de Alex. No afirmar evento ausente, rebote ni fallo de autenticación sin su resultado.

## Evidencia que debe buscar Alex

1. Cuenta de Cloudflare correspondiente a `alexcuadra.dev` → Email Service → Activity log. Consultar **envío saliente**, no Email Routing.
2. Rango que incluya el 19 de octubre de 2026; remitente `contact@forms.alexcuadra.dev`, asunto `Portfolio contact`. Revisar también el día UTC adyacente si la fecha del piloto se anotó en hora local.
3. Abrir el evento y recoger hora/zona horaria, estado, código técnico o motivo de rebote y referencia técnica. No compartir destinatario, cuerpo, tokens ni credenciales.
4. Si no hay coincidencias, registrar el rango y filtros consultados. La ausencia de evento no prueba que la petición no se iniciara: pudo fallar antes del proveedor o no quedar disponible en ese registro.

Estados oficiales: Sent = aceptado y en cola; Delivered = entrega al servidor destinatario; Failed = fallo de envío/configuración/autenticación; Rejected = destinatario suprimido; Delivery failed = rebote. Ninguno acredita lectura o ubicación en bandeja de entrada.

Fuentes consultadas: [Activity log](https://developers.cloudflare.com/email-service/observability/logs/) y [Workers Email Sending API, códigos de error](https://developers.cloudflare.com/email-service/api/send-emails/workers-api/). Los rótulos exactos del panel pueden variar.

## Diagnóstico implementado

Además de requestId, HTTP status, código público y duración, se registran:

| Campo | Valores y significado |
| --- | --- |
| `stage` | request, configuration, rate_limit, validation, challenge, reservation, delivery. Fase alcanzada por esta petición. |
| `deliveryStatus` | not_attempted: esta petición no llegó al envío; unknown: se inició y no confirmó aceptación; accepted: el binding devolvió un messageId válido. No significa Delivered. |
| `providerCode` | Solo códigos fijos documentados de Email Service; un error no reconocido se reduce a OTHER. Ausente si no hubo excepción del proveedor, por ejemplo timeout local. |

No se guardan messageId, direcciones, IP, cuerpos, tokens, respuesta del proveedor ni mensajes de excepción. El código del proveedor tampoco se devuelve al visitante. La lectura de su propiedad se hace una sola vez y tolera getters que fallen. El logger sigue siendo opcional y no modifica el resultado si falla.

La respuesta a un error del proveedor sigue siendo 503 CONTACT_DELIVERY_UNKNOWN, sin reintentos. No se libera la reserva ni se interpreta un rechazo como prueba de que nunca hubo aceptación. En una reserva duplicada, not_attempted solo describe **esta** petición: otra anterior puede haber enviado el correo.

## Piloto siguiente, preparado pero pendiente

Primero revisar la evidencia histórica aportada por Alex. Si hace falta otro intento, presentar una única ventana temporal de staging con un mensaje ficticio identificable, nombre Alex Cuadra y Reply-To público `alex@alexcuadra.dev`, destinatario exclusivamente del servidor. Asunto fijo `Portfolio contact`, mensaje propuesto: «Prueba única de entrega del formulario del portafolio. Referencia CONTACT-PILOT-2. No requiere respuesta.» No transmitir ni activar esa ventana sin autorización específica del nuevo intento; el piloto anterior fue aplazado.

Antes del clic: confirmar bindings/configuración actuales, privacidad EN/ES y observación de HTTP más `contact_result`. Después de un único clic: recoger requestId, hora, status/code, stage/deliveryStatus/providerCode; contrastar con el Activity log y pedir confirmación de recepción y Reply-To. Ante resultado incierto no reenviar. Cerrar la ventana y verificar CONTACT_DISABLED/no-store. No activar producción antes de confirmar entrega y configurar sus bindings.

## Validación y límites

- 315 pruebas unitarias pasan; cuatro regresiones nuevas cubren etapas de fallo, códigos permitidos/redacción, getters de error y aceptación/logger opcional. El timeout existente verifica ahora delivery/unknown sin código de proveedor.
- Build con 0 errores y 0 advertencias. Tras el ajuste final de lectura única del getter, las 15 pruebas del handler pasan otra vez.
- Las cinco pruebas de integración del runtime local pasan, con simulador nativo de email y egreso externo bloqueado. El primer intento quedó bloqueado por permisos de puertos del sandbox y se repitió fuera de él. No acredita envío remoto ni entrega real.
- No cambia CMS, MCP, DNS, permisos, secretos ni cuotas. El incremento MCP de entradas/traducciones/taxonomías continúa como siguiente trabajo de implementación; no se ejecutan operaciones productivas por continuidad.
