# Contacto: integración local de runtime — 18 octubre 2026

Comando: `npm run test:contact-runtime`. Cinco pruebas ejecutan el handler de producción en workerd mediante Miniflare, con Durable Object SQLite, rate limit y binding nativo de correo en modo local. Siteverify está simulado. El fixture está en `tests/fixtures/contact-runtime`, fuera de rutas públicas y del entrypoint de despliegue.

No usa `.dev.vars`, credenciales ni CMS. Todo nombre, correo, mensaje y token es ficticio. No hay remote bindings. La salida de red del Worker está bloqueada y cada prueba comprueba que no se intentó usarla. El simulador de correo es el de Cloudflare, no un reemplazo de `send()` que devuelve éxito sin validar el mensaje. Los wrappers de prueba cuentan llamadas y permiten simular pérdida de confirmación después de la aceptación local.

| Prueba | Resultado |
| --- | --- |
| Ocho solicitudes concurrentes del mismo contenido, IP y token distintos | Una aceptación y un intento al simulador; siete entregas inciertas por reserva existente |
| Veinte mensajes distintos desde IP distintas | Veinte aceptaciones; el intento 21 recibe 429 antes del correo |
| Proveedor acepta localmente y se pierde su confirmación | 503 incierto; un token/IP nuevos no provocan otro envío del mismo contenido |
| Seis intentos desde una IP | El sexto recibe 429 sin otro Siteverify ni correo |
| Flag desactivado, Origin incorrecto, destinatario inyectado o challenge fallido | Ningún correo; flag y Origin tampoco activan challenge |

Se verifica contenido de texto plano, Reply-To, cabecera no-store y redacción de PII/tokens/secretos en respuestas y logs de resultados. Los controles de reserva se ejercitan desde el handler completo, no solo llamando directamente al coordinador.

Las dos suites workerd (`test:contact-coordinator` y `test:contact-runtime`) quedan incluidas en CI después de las pruebas unitarias. No requieren secretos de GitHub ni envían correo desde CI. Miniflare y esbuild provienen de las dependencias de Wrangler fijadas por package-lock; no se descargan herramientas durante los tests.

## Límites de esta evidencia

No certifica entrega a Gmail, DNS, cuenta o permisos de Cloudflare Email Sending, challenge real, plan/cuota remota ni consistencia multirregión del rate limit nativo. No prueba un reinicio físico de infraestructura del proveedor. Las ventanas diaria/expiración/limpieza se comprueban adicionalmente en las pruebas SQLite con reloj controlado; la integración runtime verifica concurrencia, envío y el tope horario en tiempo real.

La política de 30 días del buzón es independiente de estas pruebas y de la reserva HMAC de 24 horas. El formulario sigue desactivado en Wrangler. No se desplegó ningún Worker ni se configuró eliminación automática en Gmail.

Siguiente cierre operativo: publicar el aviso propio revisado en WordPress, obtener su ID y conectar `/privacy/`; después configurar HMAC y bindings de staging y ejecutar el piloto autorizado de challenge/entrega real.

Referencia oficial: [Cloudflare: simulación local de Email Sending](https://developers.cloudflare.com/email-service/observability/local-development/sending/). El modo local simula entrega; `remote: true` enviaría correos reales y no se utiliza aquí.
