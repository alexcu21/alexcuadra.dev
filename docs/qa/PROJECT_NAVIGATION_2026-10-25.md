# Navegación entre proyectos — 25 octubre 2026

## Entrega

Las fichas EN/ES añaden anterior/siguiente con el título del destino y conservan «Volver a proyectos». Siguen el mismo orden por fecha de modificación que el archivo. No hay navegación circular: el primero solo ofrece siguiente y el último solo anterior. Los enlaces son HTML nativo y funcionan sin JavaScript.

Se reutiliza la colección que cada ruta ya carga. Sin consultas nuevas al CMS, dependencias, campos ni mutaciones WordPress. El helper devuelve solo título/ruta y excluye contenido no publicado, restringido, con contraseña, de otro idioma, sin título o con slug inválido/ambiguo. No inventa destinos para una ficha ausente o un inventario de un solo proyecto.

En escritorio los enlaces reparten dos columnas; en móvil se apilan. Hay foco visible y textos de dirección EN/ES, sin exigir interpretar únicamente iconos.

## Validación

- 285 pruebas unitarias correctas. Cuatro pruebas nuevas cubren orden/primer/último/sin mutación, privacidad/idioma/destinos, rutas ES y casos vacío/único/ambiguo.
- Suite SSR correcta: sigue los enlaces reales generados en fixtures EN/ES hasta sus destinos200, verifica los extremos y conserva el resto de regresiones. Una primera aserción esperaba rutaES en una fichaEN; corregida a/work/minimal, sin cambio de aplicación.
- Builds staging y producción correctos. Destinos/configuración contrastados: autenticación CMS=true, contacto=false, keep_vars=true.
- Navegador local: Enter desde la ficha española a minimal y enlace de regreso español correctos. Viewport temporal solicitado320; ancho efectivo observado291CSS/scrollWidth291, sin desbordamiento. No se infiere zoom real ni la causa de esa diferencia. Captura adjunta. Viewport restaurado.
- Staging real: fichas Christianity Today EN/ES200 con identidad CMS verificada, noindex y CSP. Anterior=Makers Unite/siguiente=valexweb en ambos idiomas, con rutas correspondientes. Enter en siguienteES carga valexwebES, que ofrece anteriorCT/siguienteNorceca. Ambas tarjetas1451CSS sin overflow y foco visible en retorno al archivo.

## Despliegue y evidencia

Staging `0355b8b7-eb69-4fa8-9eff-85d3956dc059`; previa `c1c1c7b7-c2be-484a-b7a4-36d2be4d1381`.

[HTTP staging](project-navigation-staging-2026-10-25.json), [captura staging real](project-navigation-staging-es-2026-10-25.png), [vista estrecha sintética](project-navigation-local-320-2026-10-25.png).

No se cambiaron CMS/DNS/credenciales/permisos/contacto ni el MCP editorial aplazado. La disponibilidad sostenida del CMS/429 y la aceptación integral/zoom real200% permanecen abiertos; muestras200 no los cierran. Sin push.

## Producción y reversión

Versión `8c3e82c6-1065-46fd-ae50-618a90538f0c`, previa `712986be-0b19-4474-95b6-ab5816e0a91e`. [Recibo HTTP EN/ES](project-navigation-production-2026-10-25.json): ambas fichas200/identidad verificada/CSP, sin noindex, con enlaces del idioma correcto. Navegador producciónES valexweb: anteriorCT/siguienteNorceca y foco en «Volver a proyectos», ancho1451/scrollWidth1451. [Captura publicada](project-navigation-production-es-2026-10-25.png).

Reversión: restaurar la versión previa del Worker; no requiere cambios de CMS ni esquema. No ejecutada.
