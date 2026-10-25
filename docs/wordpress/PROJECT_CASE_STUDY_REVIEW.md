# Christianity Today — revisión editorial EN/ES

**Resultado posterior de esta revisión:** Alex aprobó ambos cuerpos y abrió sesión en el editor. Publicados EN146 y ES196 desde el editor Gutenberg; [verificación de conservación y contenido](../qa/PROJECT_CASE_STUDY_CMS_2026-10-24.json) correcta. La prosa EN se conservó, ambas fichas tienen cuatroH2 y ES coincide con la traducción revisada. Se obtuvo el código RAW desde el editor antes de escribir, sin usar el HTML renderizado como sustituto del original. No reaplicar el borrador automáticamente. Los apartados siguientes conservan la propuesta que fue aprobada.

Estado: propuesta local sin aplicar. Lectura pública del CMS: 2026-10-25T05:06:08.077Z. La ficha EN ya contiene el caso ampliado; la ES conserva dos párrafos breves. El snapshot es contenido GraphQL renderizado, no el código RAW del editor Gutenberg. No es un respaldo de base de datos.

## Cambios propuestos

| Destino | Cambio | Se conserva |
| --- | --- | --- |
| EN146, christianitytoday-com | Cuatro encabezados H4 pasan a H2 | Toda la redacción inglesa vigente, título, subtítulo, enlaces, imagen y traducción |
| ES196, christianitytoday-com-2 | Sustituir el cuerpo breve por la traducción siguiente con cuatro secciones H2 | Título, subtítulo, enlaces, imagen y traducción |

La comparación automática del texto extraído confirma que la propuesta EN no cambia su prosa. Los enlaces de sección conservan su namespace previo. El único cambio de frontend habilita el índice existente cuando hay al menos dos encabezados; fichas breves no lo muestran.

Las afirmaciones sobre fidelidad, experiencia editorial y rendimiento proceden de la descripción de Alex y de la ficha EN ya publicada. No constituyen una medición ni una auditoría independiente del agente. No se añadieron porcentajes, fechas o pruebas no aportadas.

## Español propuesto — texto completo

**Rol:** Desarrollador frontend (contratista externo)

**Tecnologías:** Editor de bloques de WordPress (Gutenberg), React, Svelte, Advanced Custom Fields (ACF Pro), API REST de WordPress, CSS moderno y SCSS.


## Resumen

Desarrollé un conjunto de bloques personalizados listo para producción para la plataforma de publicación digital de Christianity Today, convirtiendo especificaciones de diseño complejas y detalladas en componentes editoriales flexibles y reutilizables dentro del editor de bloques de WordPress.


## El reto

La publicación necesitaba herramientas editoriales modernas para crear reportajes extensos y páginas de destino con una presentación visual elaborada. El objetivo principal era reproducir con precisión las especificaciones del sistema de diseño en Figma y, al mismo tiempo, mantener una experiencia de edición intuitiva, eficiente y resistente a errores de composición en distintos tamaños de pantalla.


## La solución

Como contratista externo de frontend, desarrollé bloques personalizados con React, Svelte y ACF Pro, integrados con la API de bloques de WordPress. Establecí una arquitectura modular de estilos y propiedades estrictas de los componentes para aplicar los tokens de diseño —tipografía, espaciado y comportamiento de la rejilla adaptable— directamente en el editor. Esto ofrecía a los creadores de contenido una experiencia WYSIWYG que reflejaba la presentación del sitio sin desviaciones de composición.


## Puntos clave

- **Fidelidad al diseño:** Convertí sistemas de diseño editorial detallados en componentes fluidos y adaptables que reproducían las especificaciones de espaciado y las escalas tipográficas.
- **Arquitectura híbrida:** Combiné React y componentes ligeros de Svelte con la API de bloques de WordPress para favorecer el rendimiento del editor y generar un marcado frontend limpio y desacoplado.
- **Edición de contenido más sencilla:** Los equipos editoriales podían componer diseños dinámicos de forma independiente, con controles programáticos para conservar la coherencia de marca y la integridad de la composición.
- **Integración con el tema:** Construí componentes modulares y desacoplados para integrarse con la arquitectura del tema de WordPress existente y su proceso de despliegue.

## Aplicación y límites

El JSON [project-case-study-drafts.json](project-case-study-drafts.json) conserva ambos cuerpos propuestos. [project-case-study-current.json](project-case-study-current.json) registra la lectura pública usada para esta comparación. Antes de escribir se debe volver a comprobar el origen y obtener el contenido RAW o trabajar desde el editor para preservar correctamente los bloques Gutenberg. No aplicar el snapshot renderizado como si fuera el original RAW. Revisar el diff exacto en ese momento y limitar la edición a los cuerpos indicados.

Esta revisión no activa ni amplía el sistema editorial MCP aplazado. El conector disponible expone lectura pública de identidad, sin cuerpo editable ni escritura directa. No se crearon propuestas/revisiones operativas, grants ni mutaciones CMS.

Makers Unite y alextecuenta mantienen el alcance breve de [PROJECT_CONTENT_CONFIRMED.md](PROJECT_CONTENT_CONFIRMED.md); faltan datos para atribuirles decisiones o resultados nuevos.

La vista local usa componentes reales y el texto de esta propuesta, con imagen/etiquetas y relación de rutas sintéticas del fixture. No representa una publicación ni una captura exacta del CMS.
