# Primer piloto privado del blog — 27 octubre 2026

**Completado:** propuesta1, versión1, propietario2; creación, lectura, inventario y replay exacto mediante el conector MCP real. El artículo publicado conserva su estado observado. [Petición preparada antes de crear](blog-proposal-pilot-request-2026-10-27.json), [recibo y comparaciones](blog-proposal-pilot-live-2026-10-27.json).

## Cambio seleccionado

Alex pidió «continuemos» después de anunciar el primer piloto privado. El asistente eligió una corrección mecánica de acentuación encontrada en el inventario publicado y explicitó el diff antes de crear el plan. No se atribuye a Alex una aprobación de publicación o del texto exacto.

| Campo | Antes | Propuesto |
| --- | --- | --- |
| Título de post228ES | Como contribuir a WordPress \| Parte 1 | Cómo contribuir a WordPress \| Parte 1 |

Operación update-post con fields.title únicamente; no se envían content/excerpt/categories/tags, slug, estado o cambios en traducción239EN. Se conserva el separador y «Parte 1» exactamente como estaban. La propuesta declara approval=not-requested y source_write_performed=false. No hay herramienta de aprobar/aplicar/publicar incluida en el paquete.

## Evidencia real

Diez llamadas: una de información de esquema, dos listados públicos ES, dos contextos privados de la fuente publicada, dos solicitudes create con la misma petición exacta, un get y dos inventarios privados.

- Primera solicitud create devuelve proposal_id1/version1, dueño2 y un único cambio title. Documento SHA256 `54945c849ee3fea44d56c640a63f335961f68577d2ca1fb41b5d7248337f6b3d`.
- get de versión1 devuelve exactamente el mismo documento.
- Replay con mismo request_id/payload devuelve exactamente propuesta1/versión1/documento original; no se genera otra propuesta. Inventario posterior contiene únicamente1/v1 y next_after=null. No se afirma un recuento SQL independiente de revisiones/recibos.
- La proyección completa de post228 (campos, términos, idiomas vinculados y base_hash) coincide antes/después. Huella de base `5ed93f35b76e6200ed9004a5b66b775a8f24bdfd78e4aa6b152fd0ff3e088c85`.
- El lector público devuelve el mismo título sin corregir, slug, categorías, etiquetas y relación239EN antes/después. La corrección existe solo en el plan.

Se leyó el contexto del post publicado para obtener la base; su cuerpo no se guardó en los archivos de QA. El recibo conserva solo la petición/diff mecánico de título y metadatos/hashes. No hay huella global de BD ni prueba de conservación de todas las fuentes o de campos de la traducción inglesa no leídos. Tampoco se ejecutaron update, revocaciones, otro propietario, restauración o publicación reales.

## Conservación y siguiente fase

No borrar la propuesta para repetir los preflights iniciales: exigen propuestas vacías y dejan de ser aplicables a ese estado. No repetir el piloto con otra request_id. Conservar petición/recibo para replay y revisión.

El respaldo inicial del servidor contiene solo la fila de propietario; **no incluye esta propuesta posterior**. Para un respaldo nuevo, pausar los escritores y retirar solo alexcuadra_prepare_post_changes; exportar/verificar sin sobrescribir el respaldo inicial y revisar después la restauración del acceso. El agente no retira grants o restaura tablas por continuidad de esta prueba. Las herramientas de recuperación ya se ensayaron en laboratorio, pero no hubo respaldo/restauración nuevos del hosting en este piloto.

Se cierra el piloto privado. La aprobación/escritura pública de posts continúa pendiente como fase distinta. La siguiente prioridad del inventario es ampliar las taxonomías propias de proyectos en el MCP; contacto/producción/CMS429 y aceptación final permanecen abiertos. Sin SSH del agente, permisos nuevos, credenciales, correo, despliegue o modificación de fuentes públicas.
