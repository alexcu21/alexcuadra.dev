# Piloto de contacto — 19 octubre 2026

**25 octubre — evidencia histórica del piloto recuperada:** export aportado por Alex contiene37registros y el POST del19octubre10:31:18 Nicaragua(16:31:18UTC), versión e4db309c,503/CONTACT_CHALLENGE_UNAVAILABLE/requestId38a947b2-6487-42ee-b47e-938b3f6ad353/duración0ms. Falló Siteverify antes de reserva/email; explica ausencia de actividad de envío para ese intento. Causa exacta no registrada: código agrupa HTTPnoOK/red/timeout/JSON;0ms no identifica causa. [Recibo mínimo sin IP/cabeceras](contact-pilot-historical-result-2026-10-25.json). Siguiente: diagnóstico acotado de Siteverify, no investigar rebotes de un correo que esta petición no llegó a enviar. Sin nuevo envío ni activación.

Alex autorizó una ventana temporal y un único mensaje ficticio con Reply-To público. Se realizó un solo clic de envío en staging; no se reintentó. Turnstile habilitó automáticamente el botón sin interacción manual con un challenge.

- Versión temporal habilitada: `e4db309c-7ae9-4811-84de-99b07ba55d61`.
- Cierre de ventana, contacto false: `4cf12aa2-2576-4b52-bd61-53e809f30038`.
- Corrección desplegada con contacto false: `eaebec0c-8fc5-4f1c-a75d-3158a79c283b`; navegador confirma ausencia del formulario.
- La interfaz mostró «Sending your message…», conservó los campos y después quedó sin aviso. No se capturó el código HTTP ni se confirmó aceptación o entrega. La conservación de los campos no prueba ausencia de envío.
- Encontrado defecto: callbacks de Turnstile tras reset borraban resultados conocidos (el resultado incierto ya estaba protegido). Corregido conservando todos los resultados tras respuesta; ampliadas pruebas reales del controlador para aceptación y rechazos.
- 133 pruebas unitarias pasan. Build staging pasa. Readiness posterior al cierre pasa; activación permanente sigue pendiente.
- Alex confirmó que el correo no ha llegado y decidió probar más tarde. Reply-To no verificable sin recepción. Nueva prueba aplazada; no realizar reenvíos ni programar pruebas automáticamente.
- Producción no se desplegó ni habilitó durante el piloto.

También se verificó la persistencia en CMS de los textos EN: aceptación para envío sin prometer entrega, estado de envío, validación, challenge, límite, indisponibilidad, entrega incierta y alternativa por correo.

Evidencia de la interfaz tras el intento: [captura](contact-pilot-result.png). No se almacenan tokens, credenciales ni el destinatario privado en este informe.

[Ventana cerrada en navegador](contact-pilot-closed.png).

Próximo paso cuando Alex retome el piloto: inspeccionar el resultado del servidor y preparar una nueva prueba con observación del resultado HTTP antes del envío. No declarar runtime o entrega verificados y no repetir automáticamente un intento de resultado no confirmado.
