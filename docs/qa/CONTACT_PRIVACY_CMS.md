# Aviso de privacidad: borradores guardados en WordPress

## Actualización: páginas publicadas

Alex confirmó revisión manual diaria de Gmail. Se documentó el procedimiento en [CONTACT_RETENTION_RUNBOOK.md](../CONTACT_RETENTION_RUNBOOK.md), sin ejecutar borrados. Se sustituyó la nota de logs/copias por los plazos documentados de Cloudflare, distinguiendo logs de aplicación e invocación. Se fijó fecha 18 octubre 2026 y se publicaron EN 180 / ES 182. Contenido final EN comprobado contra export y estado publicado confirmado en el editor. [Captura de publicación EN](privacy-wordpress-published-en.png). La conexión al frontend, ID runtime y enlace Home aún no se modificaron. Los párrafos inferiores conservan la evidencia histórica de la carga inicial como borrador.

18 de octubre de 2026. Tras iniciar sesión Alex, se crearon mediante Gutenberg dos páginas normales y se guardaron sin publicar:

| Idioma | ID | Título | Estado |
| --- | --- | --- | --- |
| EN | 180 | Contact form privacy | Draft |
| ES | 182 | Privacidad del formulario de contacto | Draft |

Polylang muestra idiomas correctos y relación recíproca 180 ↔ 182. Se reabrieron ambas páginas para verificar persistencia; el cuerpo EN coincide exactamente con el export local y el cuerpo ES conserva las secciones, enlaces y notas pendientes. Evidencia: [EN](privacy-wordpress-draft-en.png), [ES](privacy-wordpress-draft-es.png). Registro de IDs en [cms-drafts.json](../wordpress/privacy/cms-drafts.json).

Se añadió `npm run privacy:export`: genera bloques nativos Gutenberg, vistas previas HTML locales y manifest con SHA-256/notas pendientes desde CONTACT_PRIVACY_DRAFT.md. Archivos en `docs/wordpress/privacy`. No utiliza APIs ni escribe en WordPress; la carga se realizó desde el editor autenticado. No se creó un importador que pudiera duplicar páginas ni se modificaron Home/About/categorías.

Conservan marcadores de fecha de publicación y revisión de logs/copias. El plazo de 30 días del buzón es el confirmado por Alex; este incremento no crea reglas de borrado de Gmail. No se publicó contenido, no se configuró CONTACT_PRIVACY_PAGE_ID ni se cambió el enlace de Home. Las rutas públicas y el formulario siguen como antes de este incremento.

## Referencias técnicas comprobadas para el próximo cierre

- [Workers Logs](https://developers.cloudflare.com/workers/observability/logs/workers-logs/): la documentación vigente distingue retención de 3 días en Free y 7 en Paid. Falta verificar el plan/configuración efectivos y acceso de cuenta; no se asignó un plazo al azar al aviso.
- [SQLite Durable Objects y recuperación](https://developers.cloudflare.com/durable-objects/api/sqlite-storage-api/): documenta recuperación de estado dentro de los últimos 30 días. La limpieza lógica de las huellas a las 24 horas no equivale a borrar inmediatamente la historia recuperable del proveedor.

Estas referencias no certifican por sí solas configuración de cuenta, exportaciones externas de logs ni eliminación efectiva del buzón. No se tocaron ajustes de cuenta ni retención para obtener el borrador.

## Siguiente paso

Cerrar las notas técnicas y el procedimiento de borrado del correo, fijar fecha al publicar y publicar la página EN revisada. Solo después configurar su ID 180 en el frontend y comprobar `/privacy/` antes de actualizar el enlace editorial. ES queda preparada como traducción real, sin activar aún una ruta española del frontend.
