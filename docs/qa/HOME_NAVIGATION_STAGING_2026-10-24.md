# Navegación de la portada en staging — 24 octubre 2026

Revisión acotada del sitio existente mediante navegador integrado. Se visitaron la Home EN y su enlace Español; los cambios de tema, viewport y fragmentos no recargaron rutas del CMS. Sin nuevas publicaciones ni cambios de producto.

## Resultado observado

| Comprobación | Resultado |
| --- | --- |
| Abrir menú EN con Enter y Espacio | Abierto; aria-expanded=true |
| Tab desde el botón abierto | Foco en el enlace Home |
| Escape desde enlace y control de tema | Menú cerrado; foco devuelto al botón |
| Menú a 320 píxeles CSS efectivos | Controles y seis enlaces visibles; redes pueden repartirse en dos filas |
| Cambiar claro/oscuro | Cambio visible y estado del control actualizado |
| Enlace Skip to content | Fragmento #main-content y document.activeElement.id=main-content |
| Navegación a Español | Home ES real, lang=es, una H1; tema claro conservado |
| Menú ES y transición a escritorio | Enlaces localizados; navegación de escritorio visible a 1440 píxeles CSS |
| Explorar proyectos en ES | Fragmento #selected-work; sección situada en top=0 |

El DOM registró innerWidth/scrollWidth=320/320 en móvil y 1440/1440 en escritorio. Esto no basta para excluir recortes: la revisión visible cubrió los controles/hero y la sección de proyectos mostrada, no toda la matriz de elementos. El navegador tenía devicePixelRatio≈1.1: se ajustó el viewport solicitado a352/1584 para obtener320/1440 CSS, medidos en la página. No confundir esas medidas con el tamaño raster de las capturas, que el proveedor escala. El intento de normalizar el zoom con teclado no produjo un cambio verificable y no se contó como prueba de zoom.

AX nativo presenta el control de tema como checkbox; el DOM de Playwright lo expone correctamente como botón con estado pressed. Se utilizó ese botón después de que el selector checkbox no encontrara coincidencia. No es un fallo de interacción del producto.

## Evidencia

- [Menú EN oscuro móvil](home-menu-en-dark-320-2026-10-24.png)
- [Menú EN claro móvil](home-menu-en-light-320-2026-10-24.png)
- [Menú ES oscuro móvil](home-menu-es-dark-320-2026-10-24.png)
- [Home EN clara, captura completa](home-en-light-320-2026-10-24.png)
- [Home ES clara, captura completa](home-es-light-320-2026-10-24.png)
- [Hero ES oscuro escritorio](home-es-dark-1440-2026-10-24.png)
- [Hero ES claro escritorio](home-es-light-1440-2026-10-24.png)

Las capturas completas no fuerzan la carga de imágenes lazy fuera del viewport. Sus espacios vacíos no prueban un fallo del CMS ni certifican que todas las imágenes cargaron. No se introdujeron cambios para corregir diferencias de captura del proveedor.

## Decisión y límites

No apareció un bloqueo nuevo en las interacciones comprobadas. Esta pasada basta para retomar el diseño del listado y la ficha de proyectos; no se convierte el cierre de cada variante visual en otra cadena de incrementos previa a trabajar en producto. La matriz integral sigue para aceptación final: zoom200%, lector de pantalla, recorrido completo de teclado y páginas restantes no certificados aquí. No se repiten las suites MCP ni se investiga postmeta por inercia.

La Home española promociona fichas inglesas con un aviso y CTA «Ver todos (EN)». Se corroboró que es una decisión explícita de HomePage.astro, no un fallo nuevo de resolución de idiomas. Revisar esa experiencia al trabajar el listado/ficha; no inventar traducciones ni modificar publicaciones para esta prueba.

Se restauraron tema oscuro inicial y viewport predeterminado. Siete imágenes: aproximadamente448KiB asignados localmente. Sin SSH, Docker, backups nuevos, envío de formularios ni despliegue. No se ejecutaron pruebas de código porque el producto no cambió. Estas dos visitas correctas no resuelven el incidente histórico429 del CMS.
