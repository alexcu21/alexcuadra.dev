# Contacto: piloto de staging preparado, no ejecutado

18 de octubre de 2026. Configuración remota de staging completa según metadatos: email, rate limit, coordinador SQLite declarado, destinatario privado, Turnstile y HMAC. El HMAC se creó con 32 bytes aleatorios en memoria y stdin a Wrangler; no se imprimió ni escribió localmente. No rotar por rutina: cambiarlo invalida la correspondencia con las huellas reservadas durante las 24 horas anteriores.

`CONTACT_FORM_ENABLED=false`, `/api/contact` devuelve 404 `CONTACT_DISABLED` y no-store. No se ha enviado correo ni ejecutado challenge real. Los nombres/tipos correctos de secrets y bindings no prueban sus permisos, el widget remoto ni la entrega.

## Antes de la prueba

1. Completado: `/privacy/` de producción conectado al ID 180 y validado HTTP 200/render; contacto desactivado. Ver qa/CONTACT_PRODUCTION_PRIVACY.md.
2. Completado: privacy_url de Home EN apunta a `https://alexcuadra.dev/privacy/`, después de verificar destino. Aviso breve y etiqueta sustituyen textos provisionales. Volver a comprobar el conjunto de textos del formulario antes del piloto.
3. Confirmar configuración del widget: hostname exacto `alexcuadra-dev-staging.alexcu21.workers.dev`, modo Managed sin pre-clearance y action `contact`. No copiar secrets al chat ni usar claves de prueba en el piloto remoto.
4. Autorizar explícitamente una prueba real al destino verificado y, si el agente va a interactuar con el challenge, confirmar esa interacción en el momento requerido. No interpretar este documento ni el anterior alta del destinatario como autorización para enviar.

## Ejecución acotada

La activación temporal se limita a staging y a la ventana autorizada. Revisar que el paquete no cambie producción y que se conserve todo lo ya auditado. Habilitar el formulario solo durante esa ventana; su URL workers.dev es pública, no un entorno privado por sí mismo.

Usar un único mensaje de prueba identificable, sin datos sensibles ni adjuntos; nombre y Reply-To públicos autorizados, destinatario y asunto tomados exclusivamente del servidor. Completar el challenge y enviar una vez. Registrar solo requestId, hora, código de respuesta y resultado técnico; no copiar email privado, texto, token o secretos a logs de proyecto.

Comprobar 202/CONTACT_ACCEPTED y luego pedir a Alex confirmar recepción en Gmail y funcionamiento de Reply-To. Una respuesta 202 no prueba entrega en bandeja. No reenviar automáticamente ante timeout o CONTACT_DELIVERY_UNKNOWN: la reserva puede corresponder a un correo aceptado. Si el navegador o Siteverify fallan, registrar el estado técnico antes de decidir cualquier nuevo intento.

Tras la prueba, volver a `CONTACT_FORM_ENABLED=false` y verificar GET 404/CONTACT_DISABLED/no-store y ausencia del formulario/widget. Actualizar el repositorio y los metadatos de operación para no dejar activa una ventana temporal por accidente. No modificar producción ni cuotas para forzar la prueba.

## Evidencia necesaria para cerrar

- Challenge real validado con hostname/action correctos y sin bypass.
- Binding real acepta el mensaje, Alex confirma recepción y Reply-To.
- Prueba de navegador con teclado, viewport móvil/zoom y estados; no atribuir al mock la validación de iframe real.
- Endpoint nuevamente desactivado después del piloto.
- Revisión manual diaria de Gmail a cargo de Alex según CONTACT_RETENTION_RUNBOOK.md; ninguna eliminación se ejecuta como parte del piloto.

La activación permanente requiere una decisión posterior. Las pruebas locales y esta configuración no la sustituyen.
