# Incrementos pendientes — estado consolidado al 26 octubre 2026

**26 octubre — propuestas de posts/traducciones, planificador local completado:** documento determinista before/after, propietario/versión1/base/request/document hashes, update-post y creación prevista de traducción con campos explícitos/idioma ausente.82pruebas PHP sintéticas correctas; permisos nuevos solo modelados, sin registrar/grant/guardar/escribir. [Contrato y siguiente capa](WORDPRESS_MCP_BLOG_PROPOSALS.md), [QA y límites](qa/MCP_BLOG_PROPOSAL_PLANNER_2026-10-26.md). No es plugin ni persistencia/idempotencia real. Siguiente repositorio privado/versionado/replay en laboratorio; lector MCP real permanece operativo. Sin CMS/SSH/deploy/envíos.

Este inventario sustituye las listas de próximos pasos históricas de los informes anteriores. No declara resueltos los429delCMS ni acepta cambios de producción por el hecho de estar en staging.

## Ya completado

- Rediseño principal y filtros de proyectos/referencias compartibles en producción.
- Dos artículos españoles con traducciones inglesas publicadas y relaciones recíprocas. Clasificaciones de proyectos separadas del blog; casos Christianity Today EN/ES ampliados.
- Contacto: causa reproducida del fallo Siteverify corregida; piloto único real recibido y Reply-To confirmado por Alex. No volver a pedir esa confirmación ni reenviar para repetir evidencia.
- En staging: caché pública del blog, diagnóstico de contacto, páginas `/contact/` y `/es/contacto/`, traducción de17textosES, y validación reforzada de campos. Último staging **b8e868a5-95d2-40a8-95b9-6ffd0101cb5d**; formulario desactivado.
- 324 pruebas unitarias/siete integraciones de contacto y builds correctos para la última aplicación. No se ejecutaron otra vez al añadir este candidato PHP separado.

Producción permanece en **feba64f8-5406-4c69-8522-6c3753681a37**, sin los últimos cambios frontend de contacto/caché y con formulario desactivado. Los cambios editoriales del CMS sí son compartidos por SSR; no confundir publicación CMS con despliegue del Worker.

## MCP: estado actual

El lector de metadatos del blog está instalado y verificado mediante el conector real:13abilities en catálogo, dos entradas por idioma y traducciones recíprocas239↔228/241↔204 con categorías/etiquetas. [Cierre real](qa/MCP_BLOG_READ_LIVE_2026-10-26.md). No repetir instalación/grants del lector.

El nuevo [planificador de propuestas](WORDPRESS_MCP_BLOG_PROPOSALS.md) prepara un diff local de título/cuerpo/extracto/clasificaciones y una intención explícita de traducción.82pruebas sintéticas correctas. No registra herramientas, guarda propuestas ni aplica contenido: falta repositorio privado, control durable de versiones/reintentos y adaptación WordPress/Polylang. La propuesta227/revisión1 y los paquetes existentes permanecen separados.

## Qué falta, en orden de trabajo

| Prioridad | Incremento concreto | Criterio de cierre |
| --- | --- | --- |
| 2 | Repositorio privado y versionado de propuestas de posts/traducciones | Planificador local ya probado. Falta persistencia con ownership/cuotas, compare-and-swap y replay durable; después adaptación WP/Polylang y transporte. Separar proponer/aprobar/aplicar/recuperar; no reciclar revisión1. |
| 3 | Ampliar clasificación editorial MCP de proyectos | Lectura y propuestas para categorías/tecnologías propias de proyectos, con IDs/idiomas exactos y sin reutilizar categorías del blog. El lector de posts ya operativo solo cubre category/post_tag asignados. |
| 4 | Promover el conjunto reciente de staging a producción | Revisar caché/contacto/EN/ES y publicar el código manteniendo contacto false; postflight real y reversión. No requiere activar el formulario ni reenviar un piloto. |
| 5 | Preparar contacto en producción | Añadir entrypoint/export del coordinador, bindings, rate limiter independiente, origen, remitente, widget/hostname y tres secretos. Revisar alcance/configuración antes de crearlos; no basta con activar el flag. |
| 6 | Activación permanente del contacto | Decisión explícita tras la configuración real, navegación/campos EN/ES y privacidad. El piloto de entrega ya está validado; no repetirlo automáticamente. |
| 7 | Disponibilidad CMS/SSR | Medir HIT/MISS/BYPASS por operación/ubicación y peticiones frías; extender proyecciones a lectores restantes. Decidir coordinación de carga/circuito o prerender/snapshot con invalidación fiable de contenido retirado. Regla/umbral Hostinger siguen sin identificar. |
| 8 | Aceptación final de accesibilidad y páginas compartidas | Zoom real200%, movimiento reducido del SO, lector de pantalla y pasada integrada móvil/teclado/Blog/LAB/fichas/componentes compartidos. Gran parte de móvil/teclado ya revisada; no reiniciar toda la QA sin causa. |

La integración/aplicación/recuperación reales de escritura MCP y privacidad entre propietarios siguen pendientes. La incidencia histórica de postmeta permanece como límite de evidencia, sin reconstrucción ni baseline nueva; no repetir auditorías ciegas o grants por continuidad.

## Auditoría actual de contacto de producción

[Metadatos leídos sin modificar](qa/contact-production-readiness-2026-10-26.json): Worker actual100% en feba64f8. No cumple los checks de email binding, coordinador, rate limiter, tres secretos, origen/remitente o sitekey. PrivacidadEN180/ES182 y flag false sí coinciden con lo esperado. **Dentro de checks, CONTACT_FORM_ENABLED:true significa que coincide con false; no significa activación.**

Los booleanos prueban coincidencia con la configuración requerida, no el valor de secrets ni su funcionamiento. No se imprimen destinatarios ni secretos. No se cambió el Worker, sus recursos o permisos y no se enviaron mensajes.

## Opcionales, pendientes de decisión

- Más casos de proyecto con problemas/decisiones/resultados aportados por Alex; portadas/extractos del blog.
- Búsqueda/filtros del blog cuando el volumen lo justifique.
- Bandeja interna de mensajes: **no es un requisito acordado**. Alex preguntó dónde se guardan; actualmente no existe esa bandeja. Añadirla exige definir finalidad, acceso y conservación antes de persistir mensajes.
- Comprobar ajuste Email preview de Cloudflare y mantener el procedimiento manual30días en Gmail; no se automatiza el borrado sin una petición específica.

No se desplegó frontend ni se cambió CMS/DNS/contacto/permisos en este incremento de inventario/candidato. Siguiente trabajo concreto: prioridad2, repositorio privado/versionado y replay durable en laboratorio. Planificador local completado y lector real operativo; no repetir instalación/grants.
