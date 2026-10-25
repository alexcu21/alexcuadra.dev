# Diferencias de diseño y siguiente entrega

Actualización posterior24octubre: conjunto visual desplegado en staging desde6ea5414, versióna35f2c0d-b218-44bf-8ae9-72c156f9abad. SmokeEN/ES/proyectos/CSP/contacto desactivado correcto; Motion observado en el Worker. Aceptación parcial por indisponibilidad posterior503 y CMS429 observado; artículos/LAB y matriz restante pendientes. [Entrega y límites](qa/DESIGN_STAGING_RELEASE_2026-10-24.md). Producción sin cambios.

Actualización posterior24octubre: revisión local con contenido CMS real completada en alcance acotado. Corregida HomeES para usar fichas españolas existentes, sin fallback inglés ni consultas adicionales. La selección mantiene el orden reciente de cada idioma, por lo que EN y ES pueden destacar proyectos diferentes. [QA y preparación agrupada de staging](qa/REAL_CONTENT_RELEASE_REVIEW_2026-10-24.md).

Actualización posterior24octubre: tarjetas de canales editoriales compartidas por Home/archivo y panel CTA implementados localmente, con conteos existentes, acceso a correo y proyectos del idioma. [QA y capturas](qa/CHANNELS_CTA_2026-10-24.md). Búsqueda, filtros de publicaciones y una lectura editorial más elaborada siguen siendo alcance posterior; esta entrega conserva el archivo por canales. Siguiente: revisión conjunta con contenido real y preparación agrupada para staging.

Actualización posterior24octubre: especialidades y panel LAB implementados localmente usando el contenido disponible, sin cuarta especialidad ni métricas inventadas. [QA y coste de datos](qa/HOME_LAB_DISCIPLINES_2026-10-24.md). Siguiente prioridad:artículos/menciones y CTA.

Actualización posterior24octubre: hero con proyecto real/perfil compacto y Motion14.6.0 ya implementados localmente. [QA, tamaños, CSP y vista interactiva](qa/HOME_MOTION_2026-10-24.md). Las comparaciones siguientes describen la revisión inicial; los estados nuevos se precisan aquí.

Revisión del24octubre2026. Fuentes: las cuatro capturas originales y DESIGN.md de Obsidian Flow en docs/design-proposal (referencias locales, excluidas de Git), componentes actuales, Home real revisada en staging y una visita nueva a la ficha Christianity Today en staging. Para artículos se comparó maqueta con código; no se declara una revisión visual remota de esas rutas en esta sesión.

La maqueta establece dirección visual, no hechos editoriales. Incluye resultados, proyectos, tecnologías, citas y datos profesionales que no están confirmados. La ficha publicada de Christianity Today contiene el alcance confirmado de bloques WordPress; no el proyecto de plataforma/SDET descrito en la imagen de referencia.

## Comparación y orden de trabajo

| Área | Diferencia respecto a la referencia | Decisión / estado |
| --- | --- | --- |
| Identidad visual | Colores y Plus Jakarta Sans ya próximos; faltaba contraste tipográfico técnico y superficies con cabeceras | Añadida familia monospace del sistema para etiquetas y cabeceras de proyectos. JetBrains Mono exacta pendiente de evaluar como fuente local; no se descargó |
| Home: hero | Retrato grande domina la segunda columna; la referencia usa panel técnico y tarjeta de autor compacta | Próxima prioridad: compactar el retrato y equilibrar presentación/acciones con una muestra real del trabajo. Un panel de capacidades puede usar hechos confirmados; no inventar una ejecución de tests ni una disponibilidad laboral |
| Home: especialidades | Tres bloques frente a cuatro en la maqueta, sin agrupación de chips ni acentos de iconos equivalentes | Revisar distribución visual usando el contenido real; una cuarta especialidad requiere texto confirmado en CMS |
| Home: proyectos | Tarjetas simples, sin cabecera técnica ni etiquetas visibles | Primera mejora implementada localmente: marco compartido, etiquetas reales hasta4, superficies y hover. La selección española aún anuncia fichas inglesas; revisar experiencia sin sustituir traducciones silenciosamente |
| Archivo de proyectos | Cabecera y rejilla correctas pero poco carácter visual | Implementado localmente: kicker técnico, conteo real del idioma, jerarquía y separación del archivo. No hay una maqueta dedicada del archivo: se deriva del sistema y tarjetas de Home |
| Ficha de proyecto | Título excesivamente grande y grupos de información poco definidos | Implementado localmente: título más compacto/pesado, banda condicional de etiquetas/enlaces, marco para imagen e inicio narrativo numerado. Sin campos inventados ni queries nuevas |
| Ficha: profundidad | La maqueta tiene desafío, solución, arquitectura, pruebas, subsistemas y siguiente/anterior; las fuentes actuales suelen ser breves | Falta contenido editorial real y, si se desea estructurarlo, decidir campos. No extraer roles/métricas de texto libre ni renderizar secciones vacías. Navegación siguiente/anterior queda para una necesidad concreta, no bloquea esta entrega |
| LAB | Existen archivo/fichas; falta la banda integrada en Home que muestra la referencia | Incorporar una vista de LAB con propósito/estado reales. Las cifras de cobertura/defectos/tiempos de la maqueta quedan excluidas hasta tener mediciones atribuibles |
| Artículos y menciones | El archivo actual lista categorías; la referencia muestra buscador, filtros, destacado y tarjetas de publicaciones | Segunda fase visual: tarjetas de contenido real y filtro por canal. Preservar rutas/categorías; evaluar búsqueda según volumen. No copiar publicaciones ni métricas de audiencia ficticias |
| Lectura editorial | Blog/LAB ya tienen índice; no es necesario recrearlo. La referencia añade columna lateral, compartir y autor | Adaptar composición y herramientas de lectura al blog propio. Las referencias externas conservan su naturaleza; no convertirlas en artículos escritos por Alex |
| CTA y pie | Implementación más simple que las superficies con degradado y acciones de la maqueta | Unificar panel de CTA, espaciado y enlaces reales. Contacto permanece desactivado; los textos no deben anunciar disponibilidad no confirmada |
| Navegación | Funciona EN/ES; faltan algunos detalles compactos del mockup | Conservar acceso, teclado y foco. No añadir un badge «available» sin confirmación |

## Biblioteca recomendada

**Motion para JavaScript**, usando inicialmente `animate` de `motion/mini` y `inView` de `motion`. Se integra con scripts de Astro sin introducir React. La documentación describe la variante mini como animación de estilos HTML/SVG con APIs nativas y `inView` sobre IntersectionObserver. El tamaño anunciado de mini es3.2KB; es una cifra del proveedor, no la medición del bundle de este proyecto ni del conjunto de imports. [animate](https://motion.dev/docs/animate), [inView](https://motion.dev/docs/inview), [instalación](https://motion.dev/docs/quick-start).

GSAP ofrece timelines y ScrollTrigger para secuencias de scroll más complejas. No se identifica una necesidad de pinning/scrubbing para este portfolio que justifique elegirlo ahora. [ScrollTrigger oficial](https://gsap.com/docs/v3/Plugins/ScrollTrigger/). La elección de Motion es un juicio de encaje con el alcance, no un benchmark comparativo de rendimiento.

La biblioteca está **instalada con versión exacta14.6.0**. El chunk de mini+inView/controlador mide3876B gzip y se sirve como asset propio. Los hovers simples conservan CSS. La ejecución del chunk compilado con reglas CSP equivalentes fue comprobada en fixture local; el postflight del Worker desplegado sigue pendiente. No se cambiaron las protecciones del producto.

## Animaciones propuestas

| Elemento | Efecto y duración orientativa | Prioridad |
| --- | --- | --- |
| Tarjetas | Elevación4px y flecha2px durante200ms; borde y sombra suaves | Implementado con CSS en puntero fino/hover; sin desplazamiento con reduced-motion |
| Hero | Entrada única de título, descripción y acciones: opacity+translateY de12px a0,350–450ms, separación60ms | Implementado:420ms y escalonado60ms, con opacity1 durante toda la entrada; no se anima cada letra ni se oculta el título |
| Rejillas | Entrada única al aparecer:12px y opacity,300ms; escalonado limitado a180ms por fila | Implementado en Home/archivo:320ms y delay limitado180ms, una vez por elemento |
| Menú móvil | Aparición breve de panel, opacity+translateY de6px,150–180ms | Mantener foco/Escape inmediatos y estado hidden correcto; nunca retrasar acceso al teclado |
| Bloques de lectura/CTA | Entrada discreta de8px,250–300ms una vez | Opcional; conservar la lectura estable de textos largos |
| Tema | Transición corta de colores del control/superficies | Conservar200ms existentes; evitar animar todos los descendientes |

Con prefers-reduced-motion: reduce se omiten desplazamientos y secuencias; todo contenido queda visible por defecto si falta JS. Al implementarlas, cancelar listeners/animaciones al cambiar de página y al activar reducción de movimiento. No añadir contadores ficticios, terminales de tests simulados, escritura letra a letra, scroll interceptado, cursores especiales ni fondos en movimiento permanente: no ayudan a evaluar el trabajo.

## Criterio de cierre

El hero/Home ya recibió una primera adaptación con proyecto real y perfil compacto. Especialidades y LAB también están implementados localmente. Las tarjetas editoriales por canal y el CTA también están implementados localmente. El siguiente paso útil es revisar el conjunto con contenido real y preparar una entrega agrupada a staging. No es necesario terminar el sistema MCP ni certificar todas sus variantes para avanzar con el diseño. El presente código se conserva local, listo para revisión; staging y producción no recibieron cambios. Publicar una edición en WordPress sigue siendo una acción distinta del diseño frontend.
