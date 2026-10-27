# Incrementos pendientes — estado consolidado al 26 octubre 2026

**26 octubre — huellas del servidor recibidas y contrastadas:** las11comprobaciones son correctas; coinciden versiones y archivos principales de16plugins, dosMU, entradas del tema y wpdb. PHP del hosting8.5.12 frente a8.5.11 del laboratorio; no se ignoró ningún fixture en el servidor. Las ocho capacidades anteriores se conservan y el nuevo scope está ausente. [Recibo aportado por Alex](qa/blog-proposal-code-fingerprint-destination-2026-10-26.json). Se cierra la espera del diagnóstico: no volver a pedirlo ni repetir instalaciones/grants del lector. Siguiente: candidato distribuible separado de rc.1 y ensayo de admisión/respaldo/restauración por fases. Las huellas de entradas no verifican árboles completos; installationApproved=false. Sin cambios remotos.

**26 octubre — operaciones de propuestas con perfil ampliado verificadas:** candidato0.1.0-lab.site.1 solo local separado de rc.1;72CRUD+34fallos+43MCP/HTTP pasan, además46arranque y7IPC.16plugins/dosMU/TW4 con código verificado; scopes/credenciales sintéticos retirados y laboratorios detenidos/redinterna vacía. [QA/límites](qa/MCP_BLOG_PROPOSAL_SITE_FUNCTIONAL_2026-10-26.md). rc.1 sigue intacto y no instalable en ese perfil. Falta huella real de main files (el before daba versiones): diagnóstico autocontenido blog-proposal-code-fingerprint.php probado con16coincidencias, sin mutaciones/aprobación. Siguiente recibir ese resultado y preparar candidato distribuible+admisión/respaldos, sin instalar experimento/grants/SSH del agente.

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

El nuevo [planificador de propuestas](WORDPRESS_MCP_BLOG_PROPOSALS.md) prepara un diff local de título/cuerpo/extracto/clasificaciones y una intención explícita de traducción.82pruebas sintéticas correctas. El repositorio SQLite ya guarda propuestas privadas con versiones/replay y pasó93casos reales, además de82regresiones del planificador. La adaptación WordPress/MariaDB/Polylang ya pasó72casos nativos CLI, con grupos completos/privacidad/replay/concurrencia/rollback. Transporte y fallos ya pasan43+34casos con72regresiones nativas; cinco abilities solo en fixture. El candidato0.1.0-rc.1 ya tiene DTOs cerrados/guard/preflight/conservación y273checks con9recibos del ZIP instalado. No añade herramientas al CMS real ni aplica contenido; el before real ya detectó diferencias y46checks de arranque las reproducen. CRUD/MCP/fallos ampliados ya pasan149casos en candidato local separado. Huellas principales del servidor ya contrastadas; faltan candidato distribuible y admisión/respaldos antes de instalar o conceder scope. La propuesta227/revisión1 y los paquetes existentes permanecen separados.

## Qué falta, en orden de trabajo

| Prioridad | Incremento concreto | Criterio de cierre |
| --- | --- | --- |
| 2 | Preparar candidato distribuible y ciclo operativo |149CRUD/MCP/fallos ampliados correctos y11checks de huellas reales coincidentes. Preparar candidato separado de rc.1 y verificar admisión/respaldo/restauración antes de instalación/primer scope. Sin publicación ni cambios para forzar coincidencia. |
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

No se desplegó frontend ni se cambió CMS/DNS/contacto/permisos en este incremento de inventario/candidato. Siguiente trabajo concreto: prioridad2, candidato distribuible separado y ensayo de admisión/respaldo/restauración; huellas reales y pruebas ampliadas ya completas. No repetir instalación/grants del lector ni instalar rc.1 o el experimento local.
