# Incidente CMS429: evidencia para correlación — 20 octubre 2026

## Hecho confirmado

En la pasada registrada en [CMS_LOAD_STAGING_2026-10-20.md](CMS_LOAD_STAGING_2026-10-20.md), el Worker staging `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c` recibió429 al consultar el endpoint GraphQL del CMS. La ruta frontend era `/blog/__missing_entry__` y respondió503, sin fingir que el CMS había confirmado una ausencia404.

Registro saneado del fallo:

```json
{
  "event": "cms_failure",
  "operation": "BlogPost",
  "code": "CMS_HTTP",
  "httpStatus": 429,
  "durationMs": 46,
  "requestId": "e85df46d-a5e3-4b3c-997e-48beee29d9c8"
}
```

El transporte no registró un Retry-After upstream válido; el503 usa entonces la recomendación local de60s. No representa una ventana confirmada del hosting. La duración de46ms tampoco identifica por sí sola si respondió una regla de borde, servidor web o plugin.

## Dato necesario para localizar la causa

Correlacionar el identificador anterior, que el frontend envía como `X-Request-ID`, con el acceso a `/graphql` en el hosting de `content.alexcuadra.dev` y el evento de límite429 del20 de octubre. Si el servidor no conserva esa cabecera, usar la hora de la pasada desde Observability del Worker y los logs de acceso/seguridad correspondientes. Buscar qué capa emitió el429, nombre/ID de regla, ventana y umbral efectivos, criterio de agrupación y Retry-After si existe. Conservar únicamente esos campos saneados; no exportar Authorization, cookies, cuerpos GraphQL, contraseñas ni datos de clientes al repositorio/chat.

Este chat no dispone de una herramienta de diagnóstico del panel del hosting ni de esos logs. No se han leído ajustes administrativos a través de otra superficie del navegador bloqueado. Sin esa evidencia no se puede atribuir el429 a un plugin concreto ni elegir responsablemente un cambio de umbral. No se desactivaron protecciones, cambiaron roles ni realizaron pruebas de carga.

## Incidente distinto: temporizador del smoke

La continuación inicial desde Blog404 sufrió un TimeoutError antes de LAB con separación30s. La herramienta creaba el timeout antes de esperar esa separación; se reprodujo y corrigió localmente. Una petición LAB aislada devolvió200 en1207ms, con identidad CMS verificada. Este fallo de herramienta no explica ni invalida el429 upstream registrado arriba. [Continuación y pruebas](EDITORIAL_FOLLOWUP_2026-10-20.md).
