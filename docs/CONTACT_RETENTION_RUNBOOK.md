# Contacto: operación del buzón durante 30 días

Política indicada por Alex: conservar las consultas durante 30 días desde su recepción. Operación manual diaria elegida por Alex para su buzón Gmail; no se ha accedido al buzón, creado filtros, automatizado tareas ni eliminado mensajes.

## Procedimiento manual

Alex revisa diariamente los mensajes del formulario que alcanzan 30 días desde la recepción. Como ayuda de localización puede usar en Gmail:

```
in:anywhere from:contact@forms.alexcuadra.dev subject:"Portfolio contact" older_than:30d
```

La búsqueda es orientativa: verificar remitente técnico, asunto y fecha de cada mensaje; un asunto igual no prueba por sí solo que proceda del formulario. Revisar también respuestas/copias de esa consulta bajo control de Alex. No seleccionar conversaciones completas que contengan mensajes más recientes ni eliminar correo ajeno al formulario.

Para cumplir el plazo de eliminación, mover a la papelera no basta: Gmail normalmente conserva los mensajes allí otros 30 días. El responsable debe revisar y eliminar definitivamente solo los mensajes concretos vencidos, sin vaciar toda la papelera ni afectar otros correos. Cada ejecución de borrado definitivo por un agente requiere autorización en ese momento; este documento no la concede. No se ha ejecutado esta operación.

Registrar fecha y cantidad de mensajes revisados/eliminados, sin copiar nombres, direcciones ni contenido en Git o en este documento. Registrar cualquier incumplimiento del plazo y corregirlo. Este procedimiento no promete eliminar copias que estén bajo control de destinatarios externos ni backups internos del proveedor.

Alex confirmó la revisión manual diaria el 18 de octubre de 2026. Una política escrita y una búsqueda no demuestran que el borrado ya se esté realizando. No se ha creado un recordatorio recurrente porque no se ha solicitado.

## Retención técnica separada

- Huellas HMAC del coordinador: ventana lógica de 24 horas, limpieza por reserva y alarma; no contiene el mensaje ni el correo en claro.
- Workers Logs: documentación actual de Cloudflare, 3 días en Free y 7 en Paid. El aviso describe ambos; no se presupone el plan efectivo ni se certifican exportaciones/configuración externas.
- Historial de recuperación SQLite del proveedor: puede restaurar estado dentro de los últimos 30 días. El TTL lógico no equivale a borrado físico inmediato de ese historial.
- Gmail: la política de consultas es de 30 días, separada de los plazos técnicos anteriores y de la retención interna que establezca Google.

Fuentes revisadas el 18 de octubre de 2026: [Gmail: eliminación y papelera](https://support.google.com/mail/answer/7401?hl=es), [Cloudflare Workers Logs](https://developers.cloudflare.com/workers/observability/logs/workers-logs/), [SQLite y recuperación](https://developers.cloudflare.com/durable-objects/api/sqlite-storage-api/).
