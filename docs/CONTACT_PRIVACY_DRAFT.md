# Aviso de privacidad del portfolio — borrador editorial

Destino acordado por Alex: página propia de alexcuadra.dev. Texto actualizado y publicado en el CMS el 18 de octubre de 2026. La conexión al frontend sigue pendiente.

**Páginas publicadas en WordPress:** EN ID 180 y ES ID 182, vinculadas recíprocamente en Polylang. Export Gutenberg y vistas previas en `docs/wordpress/privacy`; regenerar con `npm run privacy:export`. [Evidencia y pendientes](qa/CONTACT_PRIVACY_CMS.md).

Datos confirmados por Alex el 18 de octubre de 2026: contacto público alex@alexcuadra.dev, proveedor del buzón Gmail y conservación de consultas durante 30 días. Se usa la identidad pública Alex Cuadra y el alcance se limita al formulario. No confundir la dirección pública con el destinatario privado configurado en Workers. Alex confirmó la revisión manual diaria para aplicar la eliminación a los 30 días; procedimiento en CONTACT_RETENTION_RUNBOOK.md. No se han eliminado mensajes ni verificado una ejecución de ese procedimiento. Los plazos documentados de logs y recuperación del proveedor se explican explícitamente en el aviso; no se declara verificado el plan ni todos los permisos de la cuenta. No se ha configurado automatización de borrado en Gmail; el plazo es una política indicada por Alex, no una función implementada por este repositorio.

## English — proposed WordPress body

### Contact form privacy

Last updated: October 18, 2026

This notice explains how Alex Cuadra handles information sent through the contact form on alexcuadra.dev. For questions about this notice or your information, contact [alex@alexcuadra.dev](mailto:alex@alexcuadra.dev).

### Information you send

The form asks for your name, email address and message. We use this information to read and respond to your enquiry. Your email address is included as the reply address so we can respond directly. Please avoid including sensitive information that is unnecessary for your enquiry. The form does not accept attachments and does not subscribe you to marketing emails.

### Processing and service providers

Cloudflare hosts the form backend, provides the Turnstile bot check and sends the message to our private contact inbox. Turnstile may process technical information about your browser to check for abuse. The backend also uses the connection IP address for rate limiting; the application does not put it in the email or application result logs. The form backend does not pass that IP address to Turnstile Siteverify as an additional verification field.

The application does not save your message in WordPress or in a message queue. Cloudflare processes information to operate the form services, and Google provides the Gmail inbox where messages are received. These providers may process information in other countries under their own policies: [Cloudflare privacy](https://www.cloudflare.com/privacypolicy/), [Turnstile privacy](https://www.cloudflare.com/turnstile-privacy-policy/) and [Google privacy](https://policies.google.com/privacy).

### Duplicate prevention and technical records

To prevent repeated sends and limit abuse, the application stores a keyed fingerprint derived from the form fields, together with the time of the send attempt. It does not store the name, email, message, IP address or Turnstile token in that record. The fingerprint is not presented as anonymous data. Duplicate records stop being used after 24 hours and are removed by application cleanup and scheduled alarms. Cleanup can be delayed, and the provider's backup/recovery retention is separate.

Application result logs contain a request identifier, result category, status and duration. The application deliberately excludes the message, name, email, IP address, Turnstile token and secrets from these result logs. Cloudflare documents Workers Logs retention of 3 days on its Free plan and 7 days on its Paid plan. Platform invocation logs may also include request and response metadata, separate from our application result logs. SQLite recovery history can retain earlier fingerprint records for up to 30 days. Access is managed through Cloudflare account permissions. These provider retention periods are separate from the 24-hour application cleanup and the inbox policy below.

### How long messages are kept

Enquiry messages are kept in the inbox for 30 days from receipt and then deleted as part of inbox management. The 24-hour duplicate-prevention window does not delete messages from the inbox or override service-provider retention.

### Questions and requests

You can contact [alex@alexcuadra.dev](mailto:alex@alexcuadra.dev) with questions or requests about information you sent.

## Español — borrador para una traducción real en WordPress

### Privacidad del formulario de contacto

Última actualización: 18 de octubre de 2026

Este aviso explica cómo Alex Cuadra trata la información enviada mediante el formulario de alexcuadra.dev. Para consultas sobre este aviso o tu información, escribe a [alex@alexcuadra.dev](mailto:alex@alexcuadra.dev).

### Información que envías

El formulario solicita nombre, correo electrónico y mensaje. Usamos estos datos para leer y responder tu consulta. Tu correo se incluye como dirección de respuesta para poder contestarte directamente. Evita incluir información sensible que no sea necesaria para la consulta. El formulario no admite adjuntos ni te suscribe a mensajes de marketing.

### Tratamiento y proveedores

Cloudflare aloja el backend, proporciona la verificación contra bots Turnstile y envía el mensaje a nuestro buzón privado de contacto. Turnstile puede tratar información técnica del navegador para comprobar posibles abusos. El backend utiliza además la IP de conexión para limitar intentos; la aplicación no la incluye en el correo ni en los logs de resultados. El backend no envía esa IP como campo adicional a Siteverify de Turnstile.

La aplicación no guarda tu mensaje en WordPress ni en una cola. Cloudflare trata información para operar los servicios del formulario y Google proporciona el buzón Gmail donde se reciben los mensajes. Estos proveedores pueden tratar información en otros países conforme a sus propias políticas: [privacidad de Cloudflare](https://www.cloudflare.com/privacypolicy/), [privacidad de Turnstile](https://www.cloudflare.com/turnstile-privacy-policy/) y [privacidad de Google](https://policies.google.com/privacy).

### Prevención de duplicados y registros técnicos

Para prevenir reenvíos y limitar abusos, la aplicación almacena una huella con clave derivada de los campos y la hora del intento. Ese registro no guarda nombre, correo, mensaje, IP ni token Turnstile. La huella no se presenta como información anónima. Deja de utilizarse tras 24 horas y se elimina mediante limpieza de la aplicación y alarmas programadas. La limpieza puede retrasarse; las copias de recuperación del proveedor tienen su propia retención.

Los logs de resultados contienen identificador de solicitud, categoría de resultado, estado y duración. La aplicación excluye intencionalmente mensaje, nombre, correo, IP, token Turnstile y secretos. Cloudflare documenta una retención de Workers Logs de 3 días en su plan Free y de 7 días en Paid. Los logs de invocación de la plataforma también pueden incluir metadatos de solicitud y respuesta, separados de los logs de resultados de nuestra aplicación. El historial de recuperación SQLite puede conservar registros anteriores de las huellas hasta 30 días. El acceso se gestiona mediante permisos de la cuenta de Cloudflare. Estos plazos del proveedor son independientes de la limpieza de la aplicación a las 24 horas y de la política del buzón indicada a continuación.

### Conservación del correo

Los mensajes de consulta se conservan en el buzón durante 30 días desde su recepción y después se eliminan como parte de la gestión del correo. La ventana de 24 horas para duplicados no borra correos del buzón ni sustituye la retención de los proveedores.

### Consultas y solicitudes

Puedes escribir a [alex@alexcuadra.dev](mailto:alex@alexcuadra.dev) para consultas o solicitudes sobre los datos enviados.

## Integración prevista

1. Crear una página EN normal en WordPress con el texto revisado; no asignar un ID supuesto ni reutilizar ValexLinks. Gutenberg admite el cuerpo sin ACF adicional.
2. Revisar en borrador, comprobar el procedimiento de eliminación de mensajes a los 30 días y los pendientes técnicos de logs/copias, y publicar solo tras completar esas comprobaciones. Obtener su ID real.
3. Configurar `CONTACT_PRIVACY_PAGE_ID` en el entorno correspondiente. `/privacy/` solo renderiza esa página si está publicada, sin contraseña/restricción, en EN y con título/cuerpo completos. Sin ID válido responde 404 sin consultar CMS; fallo del CMS conserva la respuesta temporal de la infraestructura.
4. Tras comprobar la URL frontend, cambiar `contactForm.privacy_url` de Home EN a `https://alexcuadra.dev/privacy/` y su aviso/enlace si corresponde. No apuntar al frontend antes de que la ruta funcione.
5. ES ya tiene borrador real vinculado (ID 182); su publicación y ruta propia del frontend siguen pendientes, sin fallback EN.

Las páginas EN/ES están publicadas en el CMS. No se configuró aún el ID en Workers ni se cambió el enlace de Home.
