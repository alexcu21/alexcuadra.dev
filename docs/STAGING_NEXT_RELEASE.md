# Próxima entrega frontend a staging

Estado20 octubre2026: **entrega de revisión desplegada**, versión `1b12660e-22b5-4e0d-8120-cf89c4ab60e5`, tráfico100%, deployment `bf288cd4-3920-4183-bacf-49b9cea04c02`. [QA/resultado](qa/EDITORIAL_OUTLINE_STAGING_2026-10-20.md). El trabajo del MCP no fue una dependencia del despliegue frontend. Los pasos inferiores quedan como procedimiento; no repetir esta misma entrega sin nuevos cambios.

## Diferencia concreta por entregar

Versión anterior `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c`, con Blog/LAB y mejoras de consultas CMS. El compilador de anchors y el índice editorial de Blog/LAB EN/ES ya están también en staging1b12660e. [QA técnico previo y límites](qa/EDITORIAL_OUTLINE_LOCAL_2026-10-20.md). No recrear entradas/idiomas del CMS para subir este componente. La aceptación visual sigue pendiente; producción no recibe este deploy.

La próxima entrega frontend debe llevar ese delta a staging para revisión, conservando datos reales, rutas, autenticación CMS, noindex y contacto desactivado. Inspeccionar primero cambios concurrentes del árbol/configuración: hay numerosos archivos sin consolidar, no resetear ni asumir que todos pertenecen al TOC. No empaquetar ni instalar los plugins de laboratorio en el Worker o en el CMS.

## Orden de la siguiente ejecución

1. Identificar el delta frontend desde la última entrega y preparar el artefacto con configuración staging. No leer/imprimir `.dev.vars` ni destinatario privado; preservar dev4325 y sus cachés separadas.
2. Ejecutar `npm test`, `npm run test:references-ssr`, `npm run build` y dry-run del Worker staging. Revisar artefacto/configuración/contacto false, sin mezclar producción. Las290 pruebas WordPress no sustituyen estas comprobaciones del frontend; no presentarlas como recién ejecutadas.
3. Consultar el deployment activo y bindings por una proyección saneada antes de subir; conservar su ID exacto como destino de rollback y comprobar que no hay despliegue concurrente. La versión bbb registrada es una referencia histórica, no una garantía del estado vivo. El ensayo anterior de rollback ya está completado: no repetirlo sin necesidad.
4. Publicar únicamente en staging el paquete verificado y registrar versión/deployment. No tocar CMS/producción/permisos/contacto. Es una entrega de revisión; no una aceptación visual ni promoción a producción.
5. Smoke focalizado sobre rutas afectadas EN/ES: índice/anchors presentes y coherentes, identidad CMS, H1/idioma/alternates/CSP/noindex y ausencia de formulario. Espaciar peticiones y no repetir inventarios correctos ajenos al delta. Ante429/503, detener y registrar la evidencia; no relajar seguridad ni martillar el hosting.
6. Revisar visual/teclado/fragmentos/zoom/lector mediante acceso efectivamente permitido o evidencia manual. Mantener pendientes explícitos: el bloqueo del navegador no se elude con staging, otros puertos/orígenes o herramientas. No usar el deploy para conseguir acceso automático por una superficie alternativa.

Este orden permite actualizar staging para revisión antes de cerrar el MCP. No exige dar por resuelta la causa histórica del429 ni completar primero todo el QA visual de producción; exige un artefacto técnicamente verificado y reportar sus límites. Si el preflight o smoke falla, detener/promover recuperación según el caso y no declarar lista la entrega. [Rollback vigente](STAGING_RECOVERY.md), [diagnóstico429](qa/CMS_429_DIAGNOSTIC_2026-10-20.md).

## Cuándo se modifica el CMS

El CMS es compartido: una actualización de un Project publicado llega también a producción sin deploy del Worker. Editorial Read0.1 ya está instalado y no necesita otra aprobación. El módulo de aplicación requiere implementar/probar el [contrato nuevo](WORDPRESS_MCP_SOURCE_APPLICATION.md), reconciliar esquema/efectos de hooks, preparar paquete/permisos y revisar/aprobar el diff exacto antes de habilitarlo/aplicarlo. Subir el TOC a staging no requiere modificarlo.

Esta entrega ya se completó con237 unitarias, suite SSR, build/dry-run, baseline, postflight/rollback compatible y cuatro fichas EN/ES verificadas por HTTP. La prioridad frontend siguiente es su aceptación visual/accesible y diagnóstico429 pendiente; el siguiente incremento técnico MCP es implementar el contrato en laboratorio. Son dos secuencias independientes.
