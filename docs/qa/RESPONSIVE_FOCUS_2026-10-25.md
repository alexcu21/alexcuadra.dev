# Accesibilidad: foco al cambiar de formato — 25 octubre 2026

## Hallazgo y corrección

La revisión de navegación encontró pérdida de foco reproducible: con un enlace del menú de escritorio o del índice lateral enfocado, cambiar a320CSS ocultaba esa zona y dejaba document.activeElement en BODY. El usuario de teclado perdía su posición.

Ahora el menú transfiere el foco al botón visible al estrechar la vista y al primer enlace del menú al volver a escritorio. El índice transfiere el foco al summary móvil y al enlace «Leer contenido» en escritorio. No mueve el foco al inicializar ni si estaba fuera de la zona que desaparece. Descarta posiciones obsoletas al pulsar fuera o perder foco la ventana; conserva la interacción con el control ya enfocado. Los listeners del menú se retiran al desconectar su custom element.

El nuevo helper compartido solo observa foco y cambios de media query. Sin consultas CMS, dependencias nuevas, animaciones, cambios editoriales ni modificaciones de permisos/contacto. Las anclas y el uso sin JavaScript se conservan.

## Revisión realizada

| Escenario | Resultado |
| --- | --- |
| Menú de escritorio enfocado → móvil | Foco visible en botón Menú, aria-expanded=false |
| Botón móvil enfocado → escritorio | Foco en primer enlace visible |
| Índice largo (40 secciones), escritorio → móvil | Foco en summary, no en BODY |
| Summary móvil enfocado → escritorio | Foco en enlace Leer contenido |
| Ratón sobre summary ya enfocado + cambio de tamaño | Foco conservado en equivalente visible |
| Foco en buscador + cambio de tamaño | Buscador conserva foco y resultado |
| Menú Enter/Tab/Escape | Cierra y devuelve foco al botón |
| Tema claro/oscuro, móvil320CSS | Foco sólido visible, scrollWidth320 |
| Ficha real stagingES, escritorio → móvil | Foco en En esta página, ancho320/scrollWidth320 |
| Zoom real200% | Pendiente: control nativo interrumpido por actividad del usuario |
| Movimiento reducido del SO | Pendiente de observación real; pruebas existentes del controlador pasan |
| Lector de pantalla y matriz integral | No certificados en este incremento |

Los escenarios locales usan fixtures sintéticos y componentes reales. No equiparar viewport estrecho con zoom real. Se abrió una pestaña propia de pruebas en Chrome para intentar acceder al zoom nativo; el control se interrumpió antes de cambiarlo. No se insistió ni se tocaron otras pestañas o ajustes del sistema. Se cerró la pestaña propia. No se guardó contenido ajeno al portafolio en la evidencia. Tema local restaurado al original.

## Pruebas y entrega

292 unitarias correctas, incluidas siete regresiones nuevas de foco/blur/interacción/disposición. Suite SSR y builds staging/producción correctos. Primer build detectó firma TypeScript del callback por defecto; corregida antes del artefacto final. Las pruebas de movimiento reducido verifican el controlador de forma sintética y no certifican preferencias del SO.

Staging `b3f98891-7269-48a1-b4a7-8c9f8f946572`; previa `0355b8b7-eb69-4fa8-9eff-85d3956dc059`. HTTP fichasEN/ES200, identidad CMS verificada, CSP y noindex correctos.

[Recibo staging](responsive-focus-staging-2026-10-25.json), [staging realES](responsive-focus-staging-es-2026-10-25.png), [local oscuro](responsive-focus-local-2026-10-25.png), [local claro](responsive-focus-local-light-2026-10-25.png).

Disponibilidad sostenida del CMS/429 y aceptación integral siguen abiertas. Sin CMS/DNS/grants/contacto/MCP operativo ni push. No se afirma que todas las páginas o ayudas técnicas hayan sido auditadas.

## Producción y reversión

Versión `afef64bc-4bb1-44f2-9cd8-eacd3030e426`; previa `8c3e82c6-1065-46fd-ae50-618a90538f0c`. [HTTP EN/ES](responsive-focus-production-2026-10-25.json): ambas200/identidad CMS verificada/CSP y sin noindex. Navegador producciónES confirma: índice escritorio→móvil enfoca SUMMARY a320CSS sin overflow; móvil→escritorio enfoca editorial-reader-link a1280; menú escritorio→móvil enfoca BUTTON con aria-controls=menu-content/expanded=false. [Observaciones del navegador](responsive-focus-production-browser-2026-10-25.json), [captura de foco visible](responsive-focus-production-es-2026-10-25.png). Viewport temporal restaurado.

Para revertir solo este incremento, restaurar la versión previa del Worker. No requiere mutaciones CMS ni cambios de esquema. No se ejecutó rollback.
