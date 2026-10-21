# About y referencias — incremento local, 19 octubre 2026

Continuación posterior: paginación legacy implementada y prueba HTTP de los templates reales de referencias correcta con datos sintéticos; ver [SSR](REFERENCES_SSR_LOCAL.md) y [paginación](REFERENCES_PAGINATION_LOCAL.md). No cierra QA visual, verificación CMS/Workers reales, idiomas ni SEO editorial.

## Alcance

About EN/ES: cabecera responsive con texto e imagen reales, secciones editoriales con ancho de lectura y separadores, sin display:contents. Trayectoria, formación y habilidades se ocultan si falta el campo. Cabecera sin segunda columna cuando falta imagen. Conserva fuentes localizadas, traducciones y metadatos existentes.

Referencias: archivo, categoría y ficha adaptados a tokens compartidos; tarjetas con enlace de toda la superficie, títulos fluidos, regreso visible en móvil y cuerpo con WordPressContent. Son referencias externas/menciones del CPT articles; no se añaden posts, LAB, filtros o autorías supuestas. Nombres/categorías/URLs e idioma editorial no se modifican.

Correcciones de ficha: lectura de featuredImage.node.sourceUrl en lugar del campo inexistente imageUrl; cita opcional con acceso seguro a articlesFields; título real como descripción cuando falta cita. Categoría inexistente y ficha inexistente o ajena a la categoría solicitada devuelven 404/no-store/noindex. Errores CMS se propagan a la protección común de 503, sin convertirlos en errores genéricos locales.

## Validación y límites

145 pruebas existentes pasan y build correcto, astro check sin errores/warnings/hints. Estas pruebas no verifican por sí mismas los nuevos guards de rutas mediante peticiones SSR ni constituyen revisión visual. El ajuste final de CSS :has(img) evita columna vacía y requiere revisión visual junto al resto. Avisos de Shiki/CSP de build siguen presentes.

Sin navegador por bloqueo, sin smoke remoto ni cambios CMS. Pendiente QA manual local: `/about/`, `/es/sobre-mi/`, `/articles/`, categoría y ficha reales en claro/oscuro y móvil/escritorio; comprobar teclado, imagen y cuerpo largo. Verificar además 404/503 en runtime antes de publicar.

La paginación de lectores legacy de referencias, asignación idiomática y SEO editorial avanzado siguen pendientes; esta entrega no cierra el bloque editorial entero. Los lectores localizados paginados existentes no sustituyen automáticamente los lectores legacy que incluyen referencias sin reclasificar. No cambiar idiomas de referencias ni URLs sin resolver ese alcance.

Se solicitaron rol/trabajo/tecnologías para alextecuenta, PlayYellow y Makers Unite; completar contenido espera datos de Alex. No se inventó ni publicó texto.

Todo local. Sin despliegues, cambios CMS o envíos. Contacto desactivado; QA anterior sigue abierto.
