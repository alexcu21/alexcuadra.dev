# Página de contacto y campos — 26 octubre 2026

## Cambio

El formulario deja de incluirse en ContactCTA/Home. Nuevas rutas `/contact/` y `/es/contacto/`, enlaces desde el CTA compartido y el menú principal, canónicas/hreflang y selector de idioma propios. El sitemap incluye ambas rutas. La página mantiene correo directo y enlace a proyectos; mientras el formulario esté desactivado muestra la alternativa por correo, sin widget ni campos activos.

No se cambia `/api/contact`, el destinatario, el envío, las cuotas ni la condición CONTACT_FORM_ENABLED. La página puede renderizar el formulario EN/ES cuando se habilite y sus textos sean válidos; Home no lo renderiza aunque el flag esté activo. No se habilitó ni se envió correo para esta revisión.

## Campos actuales

| Campo visible | Nombre técnico | Obligatorio | Máximo |
| --- | --- | --- | --- |
| Tu nombre / Your name | name | Sí | 100 caracteres |
| Tu correo electrónico / Your email | email | Sí | 254 caracteres; validación de dirección |
| Tu mensaje / Your message | message | Sí | 5.000 caracteres |

Se anuncia que todos son obligatorios. Idioma y token Turnstile se añaden automáticamente; no son preguntas al visitante. El servidor rechaza campos desconocidos. No hay asunto libre, teléfono, empresa, presupuesto ni adjuntos. No se añadieron esos campos sin una decisión del autor. La privacidad se enlaza junto al formulario.

## Textos editoriales

Hay 17 campos ACF de textos/etiquetas/estados, distintos de los tres datos solicitados al visitante. Se conservan en WordPress, asociados a Home19 e Inicio193; separar la URL pública no exige crear otra página CMS ni duplicar estos datos.

Se encontró que Inicio devolvía los defaults ingleses: la ubicación del grupo Contact form160 solo permitía editar Home19. Desde la UI se añadió un grupo OR limitado a Page193 (sin ampliar a todas las páginas) y se guardaron los 17 textos españoles, incluido `https://alexcuadra.dev/es/privacidad/`. Inglés intacto. No se editaron título, cuerpo o campos de presentación de Inicio. La fuente JSON ACF local refleja las dos ubicaciones.

[Antes y traducción](../wordpress/contact-copy-review-2026-10-26.json), [postflight de lectura](contact-copy-postflight-2026-10-26.json). Esta corrección de textos CMS es pública, pero el formulario sigue desactivado y producción no renderiza sus campos.

## Dónde quedan los mensajes

- Contenido: envío a la dirección privada configurada y recepción en Gmail, ya verificada en el piloto anterior. La aplicación no inserta nombre/email/mensaje en WordPress, D1 o una bandeja propia. Conservarlos en una bandeja sería otra función con permisos, retención y recuperación propios.
- Durable Object SQLite: huella HMAC de nombre/email/mensaje/idioma y fecha, durante24horas, para duplicados/cuotas. No almacena texto, correo, token ni IP. Limpieza por alarma y durante nuevas reservas.
- Logs de la aplicación: resultados técnicos/requestId/códigos/duraciones; sin cuerpos, direcciones, tokens ni respuestas completas. Los logs automáticos de invocación de Cloudflare pueden incluir metadatos de petición/IP, como mostraban los exports del usuario; no se confunden con el registro saneado de la aplicación.
- Gmail: política actual de30días con revisión/borrado manual a cargo de Alex; no existe borrado automático configurado por esta aplicación. La huella de24horas no equivale a conservar mensajes.
- Proveedor: Cloudflare puede conservar previews del correo durante unos7días si Email preview está activo. No se verificó ni se cambió ese ajuste de cuenta. Por tanto, no se promete que la única copia sea Gmail. [Documentación](https://developers.cloudflare.com/email-service/observability/logs/).

## Validación

- 320 pruebas unitarias y build correcto. Regresión SSR real EN/ES con formulario desactivado/activo sintético, campos/límites/idioma, un soloH1, alternativa de correo y Home sin formulario aun con flag activo. Los fallos iniciales de la prueba fueron expectativas antiguas de mailto en CTA y coincidencias CSS; actualizadas al comportamiento solicitado, la suite pasa.
- Fixture local usa textos editoriales reales, correo ficticio y verificación simulada sin token/callback de éxito. No hay secrets ni bindings de envío en ese entorno. La simulación solo se inyecta en el fixture, nunca en producción.
- Navegador: español con tres campos y límites correctos, teclado Nombre→Correo, sin overflow a320CSS y1163CSS. Viewport restaurado. No envío. [Vista escritorio](contact-page-es-desktop-2026-10-26.png), [móvil320](contact-page-es-320-2026-10-26.png).

## Despliegue y pendientes

Staging publicado desde `aab4166`, versión **`04955ff1-65c3-4e9a-84a4-d08ea5d2ac0a`**, previa `f1caee3b-a837-4db2-ae1e-4680cd8635e6`, con CONTACT_FORM_ENABLED=false. [Postflight](contact-page-staging-2026-10-26.json): contactoEN/ES y HomeEN200, canónicas/CSP, enlaces correctos, sin formulario; API404CONTACT_DISABLED. Build final0errores/0advertencias. Sin reintentos ni envíos. Servidor de preview detenido y pestañas temporales cerradas. Producción sigue sin cambios frontend, en `feba64f8-5406-4c69-8522-6c3753681a37`. No se promete disponibilidad permanente del CMS ni entrega de un nuevo mensaje. Sin push.

Siguiente decisión de producto: mantener los tres campos o añadir solo los que ayuden a responder a las consultas. No se implementa almacenamiento adicional sin definir finalidad, acceso y retención. MCP editorial sigue en alcance.

Reversión: restaurar el Worker previo para deshacer rutas/enlaces; el backend de correo no cambia. Si se solicitan revertir textos españoles, usar el respaldo del JSON y retirar únicamente la ubicación OR Page193 del grupo160; no eliminar campos ni modificar Home19.
