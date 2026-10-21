# Privacidad y contacto desactivado: despliegue staging

18 de octubre de 2026. Worker `alexcuadra-dev-staging`, versión `8851255a-6072-4cf7-943b-23f5c783ed49`.

[Privacidad en staging](https://alexcuadra-dev-staging.alexcu21.workers.dev/privacy/) renderiza el aviso EN publicado, ID 180, mediante el renderer editorial compartido. `CONTACT_FORM_ENABLED=false`: `/api/contact` devuelve 404 JSON con `CONTACT_DISABLED` y `Cache-Control: no-store`; Home no muestra formulario ni carga el widget. No se enviaron mensajes ni se ejecutaron challenges reales.

Validación: 133 pruebas unitarias, build staging, dry-run y smoke local/remoto de ocho páginas correctos. El smoke verifica autenticación CMS, SEO, CSP/hashes, renderer y privacidad sin marcadores, además de favicon y dos controles 404. Navegador remoto muestra el contenido y enlaces del aviso. [Captura](privacy-staging-render.png), [registro resumido](CONTACT_STAGING_DEPLOY.json).

Wrangler confirma los bindings CONTACT_EMAIL, CONTACT_RATE_LIMITER (5/60s) y CONTACT_COORDINATOR. La auditoría remota confirma tipos, clase local, remitente restringido y variables esperadas, incluido ID 180; las secrets de destinatario y Turnstile siguen presentes. Falta CONTACT_DEDUP_SECRET. Coincidir metadatos no certifica envío, límite global remoto, permisos efectivos o entrega: esos controles continúan pendientes con el formulario desactivado.

El comando readiness devuelve exit 2 por ese secret pendiente. Su apartado `privacy` todavía consulta el enlace editorial de Home (ValexLinks); la ruta propia recién desplegada se valida en el smoke. No interpretar el HTTP 200 de ValexLinks como revisión del aviso propio.

No se desplegó producción, configuró un plan de pago, cambió CONTACT_TO_EMAIL ni modificó privacy_url de Home. Se conserva ese enlace hasta que exista la ruta propia en producción; staging mantiene canonical de producción y noindex según su política de SEO. ES está publicada en el CMS, sin ruta frontend ES nueva.

Referencia previa guardada: versión `8b4f9ce7-7f41-4133-a0ab-8d2540547727`, deployment `57757f22-8115-4009-922d-c540974083af`. No se ensayó rollback; introducir una clase Durable Object requiere revisar compatibilidad del historial antes de intentar volver a una versión que no la exportaba. No se declara recuperación probada por guardar un identificador.

Próximo incremento: configurar HMAC exclusivo de staging sin mostrarlo en chat/Git, auditar configuración y preparar piloto de challenge/entrega autorizada. Mantener el formulario desactivado hasta cerrar los criterios. Para producción, preparar ID/artefacto y desplegar por separado antes de cambiar el enlace editorial.
