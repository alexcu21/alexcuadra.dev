# Lecturas por MCP real — resultado y límites

[Recibo saneado](MCP_OPERATING_NATIVE_READS_2026-10-23.json).9 llamadas execute:2core,4lecturas operativas,2lecturas públicas y1lectura de propuesta existente. Además se consultaron los esquemas de las4abilities core/legacy usadas. Ninguna ability de mutación fue invocada; no se solicitaron registros/revisiones/aprobaciones nuevos ni ediciones públicas.

Confirmado:

- core/get-user-info, filtrado a id/roles:usuario2,subscriber.
- Lectura pública146EN y196ES correctas, traducciones recíprocas146↔196.
- Propuesta227 propia, Project146EN, draft/applied=false, versión737a055…0b44/basebbca3d…4d85 iguales a la evidencia anterior; patch con tagline solamente. Valores privados omitidos.
- inspect-project-review(review1) y get-project-effect-state(proposal227), cada uno en EN/ES:4respuestas isError=true con el texto exacto `Editorial operation rejected.`. Son rechazos de negocio saneados y compatibles con almacenamiento operativo vacío. No son recibos de inspección/estado válidos ni prueba de aplicación/recuperación.

core/get-site-info devolvió `Permission denied`; no amplió permisos ni devolvió una URL que pueda certificarse. El conector no expuso código detallado para los4rechazos operativos:solo content/isError. No afirmar contract_not_found/404 exacto ni que haya pasado una inspección positiva. El código del paquete permite inferir la ruta esperada para tablas vacías; se mantiene la distinción entre inferencia y respuesta observada.

No se probaron otras cuentas/anonimato ni revisión/prepare/apply/recover positivos. No se vuelve a certificar preservación de la BD completa después de HTTP:la autenticación puede llevar su propia contabilidad. Las callbacks solicitadas son de lectura, con alcance verificado en el código; no se solicitaron escrituras editoriales. No se muestra patch/documento/cuerpo/credenciales.

La siguiente fase propuesta, todavía no autorizada, es preparar y leer una única revisión privada de la227, sin aprobación/aplicación. [Plan concreto](../WORDPRESS_MCP_OPERATING_PRIVATE_REVIEW.md). No hace falta repetir grants ni la aceptación inicial, que exige storage vacío y no es una habilitación persistente. La incidencia histórica postmeta sigue abierta.

Sin SSH, Docker, descargas, respaldo nuevo o modificaciones del ZIP. Solo metadata/evidencias pequeñas locales. No se repiten tests sintéticos del paquete sin cambios; esta evidencia procede de llamadas reales.
