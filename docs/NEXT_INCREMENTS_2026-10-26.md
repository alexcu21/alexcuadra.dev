# Incrementos pendientes — estado consolidado al 26 octubre 2026

**Actualización 26 octubre:** paquete MCP del blog 0.1.0-rc.1 y preflight de destino preparados; 75 contratos, nueve guards, cuatro fases válidas y retirada correctos, además de 34 pruebas funcionales y 37 HTTP con el ZIP instalado en laboratorio. [Evidencia](qa/MCP_BLOG_READ_PACKAGE_2026-10-26.md). Siguiente: ejecutar únicamente el diagnóstico de destino `2 before`, siguiendo el [procedimiento](WORDPRESS_MCP_BLOG_READ_PACKAGE.md); revisar el recibo antes de instalación y primer scope reales.

Este inventario sustituye las listas de próximos pasos históricas de los informes anteriores. No declara resueltos los429delCMS ni acepta cambios de producción por el hecho de estar en staging.

## Ya completado

- Rediseño principal y filtros de proyectos/referencias compartibles en producción.
- Dos artículos españoles con traducciones inglesas publicadas y relaciones recíprocas. Clasificaciones de proyectos separadas del blog; casos Christianity Today EN/ES ampliados.
- Contacto: causa reproducida del fallo Siteverify corregida; piloto único real recibido y Reply-To confirmado por Alex. No volver a pedir esa confirmación ni reenviar para repetir evidencia.
- En staging: caché pública del blog, diagnóstico de contacto, páginas `/contact/` y `/es/contacto/`, traducción de17textosES, y validación reforzada de campos. Último staging **b8e868a5-95d2-40a8-95b9-6ffd0101cb5d**; formulario desactivado.
- 324 pruebas unitarias/siete integraciones de contacto y builds correctos para la última aplicación. No se ejecutaron otra vez al añadir este candidato PHP separado.

Producción permanece en **feba64f8-5406-4c69-8522-6c3753681a37**, sin los últimos cambios frontend de contacto/caché y con formulario desactivado. Los cambios editoriales del CMS sí son compartidos por SSR; no confundir publicación CMS con despliegue del Worker.

## Incremento realizado ahora: lectura editorial MCP del blog

Candidato local [Editorial Blog Read](../wordpress/experimental/editorial-blog-read/README.md): implementa list-public-posts y get-public-post. Incluye ID/título/slug/idioma, relaciones públicas recíprocas y categorías/etiquetas asignadas con sus idiomas/traducciones. Solo tipo nativo post; conserva la separación respecto a proyectos y artículos/menciones.

Separa la capacidad `alexcuadra_read_public_posts` de la de proyectos. Rechaza acceso anónimo, administrador sin scope, permisos revocados, otros tipos/idiomas/estados, contraseñas y clasificaciones contradictorias. No incluye cuerpo, ACF, datos de contacto ni escritura. Guard local opt-in y entorno local; no registra herramientas en producción.

Verificación inicial: **75 comprobaciones de contrato y dos guards**, PHP8.5.11, lint correcto; [recibo](qa/editorial-blog-read-contract-2026-10-26.json). APIs simuladas explícitamente: sin WordPress real, BD, red, Docker, HTTP ni instalación. Esa primera fase no se presentó como integración real. La integración posterior de71casos está documentada en la actualización superior; la capacidad todavía no está disponible en el conector real. No se modifican los plugins/ZIP sellados existentes, la propuesta227 ni la revisión privada1.

Descubrimiento nativo de solo lectura actual:11abilities visibles (tres core, dos de lectura de proyectos y seis de propuestas privadas). No aparecen herramientas nativas de posts, traducciones o taxonomías. No se ejecutaron propuestas, grants, apply, recover ni approve.

## Qué falta, en orden de trabajo

| Prioridad | Incremento concreto | Criterio de cierre |
| --- | --- | --- |
| 1 | Comprobar el destino e incorporar el lector MCP del blog | Paquete y preflight ya preparados y probados. Recibir diagnóstico `before` del hosting; revisar instalación del ZIP exacto y primer scope separado. Comprobar las fases y lecturas del conector real sin permisos amplios. |
| 2 | Propuestas editoriales para posts y traducciones | Diff privado de cuerpo/título/extracto/clasificaciones; control de versión/base e idempotencia. Creación de traducciones explícita, sin duplicar grupos. Separar proponer/aprobar/aplicar/recuperar. No reciclar la revisión1 caducada. |
| 3 | Ampliar clasificación editorial MCP de proyectos | Lectura y propuestas para categorías/tecnologías propias de proyectos, con IDs/idiomas exactos y sin reutilizar categorías del blog. El nuevo lector de posts solo cubre category/post_tag asignados. |
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

No se desplegó frontend ni se cambió CMS/DNS/contacto/permisos en este incremento de inventario/candidato. Siguiente trabajo concreto: prioridad1, diagnóstico de solo lectura `before` del destino; el paquete y sus pruebas están completados.
