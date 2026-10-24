# Revisión de primera entrega — 19 octubre 2026

Actualización24octubre: [navegación real en staging](HOME_NAVIGATION_STAGING_2026-10-24.md) comprobada en EN/ES, móvil y escritorio, incluidos teclado de menú, salto al contenido y temas. Alcance parcial; no sustituye la matriz integral. Por decisión de prioridad de Alex, sus variantes restantes se agrupan en aceptación final y no bloquean retomar listado/ficha de proyectos. MCP queda aplazado.

Actualización posterior: QA real EN/ES a320px detectó recorte de menciones Home, corregido con columna única en anchos pequeños y verificado sin overflow. Añadido salto de contenido y main que incluye el hero. Revisadas páginas compartidas en matriz parcial; [resultados/capturas](BILINGUAL_ACCESSIBILITY_LOCAL.md). Se conservan pendientes de variantes restantes,zoom y accesibilidad integral.

Actualización: acceso integrado al origen4325 funcional en la sesión más reciente; servidor estaba apagado y se reinició. Menú móvil en ambos temas y Tab/Escape/foco comprobados; tema persiste al navegar. Ver [evidencia y límites](REFERENCE_NESTED_SEO_BROWSER.md). Las menciones de bloqueo inferiores describen el estado anterior; la matriz completa sigue abierta.

Estado: abierta. Revisión estática realizada; aceptación visual pendiente de capturas aportadas por Alex o acceso de navegador efectivamente autorizado. No se intentó acceder al navegador ni usar otra superficie para sortear el bloqueo.

## Hallazgo corregido localmente

En `Nav.astro`, los enlaces sociales cambiaban a `--accent-text-over` al recibir hover/foco. En claro ese token es blanco, pero el fondo del menú es `--gray-999` (#f8fafc), sin superficie de acento detrás. Ahora usan `--link-color` (#006b80 en claro, #4cd7f6 en oscuro). El contorno global de foco se conserva. Corrección derivada del código; todavía no observada visualmente ni desplegada.

## Matriz de aceptación pendiente

### Capturas manuales recibidas

Alex aportó dos capturas de Home EN local en tema oscuro. La captura superior muestra marca, navegación de escritorio, selector de tema, enlace Español, hero con nombre/presentación/roles/acciones y retrato. En el área visible no se aprecian superposiciones entre esos elementos. La segunda muestra parte de las tarjetas de categorías, CTA de correo y pie; no documenta toda la página ni permite inferir el ancho CSS del viewport. El borde inferior de ambas capturas incluye la barra de desarrollo y contenido cortado por el encuadre.

Hallazgo: el cohete del crédito Astro aparece aislado en otra línea. Se agrupan enlace e icono con inline-flex para que permanezcan juntos y alineados, permitiendo que el grupo completo salte de línea. Cambio solo local, pendiente de nueva captura. La presencia del CTA de correo no implica activación del formulario.

Evidencia parcial, sin cierre de ninguna fila completa: faltan Home completa, claro, ES, móvil con ancho conocido, menú abierto y comprobaciones de teclado/tema. No hubo acceso automático al navegador.

Actualización: Alex aportó otras dos capturas de Home EN en claro, una con distribución de escritorio y otra estrecha con hero apilado. En lo visible, título, descripción, roles y acciones resultan legibles; las acciones pasan a líneas separadas y el retrato queda debajo en la vista estrecha, sin recortes laterales aparentes. El ancho CSS y zoom no están confirmados. La captura de escritorio corta parte de la cabecera por el encuadre; la estrecha no muestra la cabecera, el menú ni la página completa. No se detectan nuevos defectos en estas áreas visibles y no se introducen ajustes de diseño adicionales por estas capturas.

Pendiente vigente: menú móvil abierto/cerrado, foco/teclado y persistencia del tema; pie tras la corrección; resto de Home en ambos temas; ES y páginas compartidas de la matriz. Las capturas claras amplían la evidencia del hero, sin cerrar la fila de Home completa ni demostrar funcionamiento del selector de tema.

Actualización de menú: captura manual posterior muestra Home EN con menú móvil abierto en claro. Marca y botón, cuatro enlaces, cuatro redes y selector de tema caben sin recortes laterales visibles. El panel se superpone al hero como corresponde a su posición absoluta; la captura no demuestra un defecto por esa superposición. No se aprecian colisiones entre controles. Queda comprobada visualmente esta variante abierta; siguen pendientes variante oscura, ancho mínimo conocido, cierre/Escape, recorrido de foco y persistencia del tema. La imagen no muestra estados hover/foco de redes ni el pie corregido.

Actualización de pie: nueva captura manual EN/claro confirma el ajuste del crédito: Astro y cohete permanecen juntos y alineados al saltar el grupo completo a otra línea. CTA de correo, copyright y enlaces Twitter/GitHub aparecen completos, sin colisiones visibles. Hallazgo del cohete cerrado para esta vista; no se requieren más capturas de esta misma variante. Continúan pendientes oscuro/ES y demás superficies e interacciones de la matriz. No se repiten pruebas por esta actualización documental.

Revisar cada fila en claro y oscuro, EN y ES cuando exista traducción real. Usar escritorio (1440 px), móvil (390 px) y ancho mínimo (320 px); revisar también transición del menú alrededor de 64em y zoom 200 %. No confundir ausencia de scroll horizontal con ausencia de recorte: el CSS global oculta overflow-x.

| Superficie | Evidencia/criterios | Estado |
| --- | --- | --- |
| Home `/`, `/es/` | Hero completo, retrato sin recorte inadecuado, tipografía, roles, acciones, especialidades, tarjetas y pie | Pendiente |
| Menú compartido | Cerrado/abierto en móvil, sin solapar controles ni recortar contenido; escritorio sin colisiones entre marca, enlaces y tema | Pendiente |
| Teclado | Tab/Shift+Tab con foco visible en enlaces, redes y tema; Enter/Espacio abre menú; Escape lo cierra y devuelve foco; enlaces ocultos fuera del recorrido | Pendiente; requiere interacción, las capturas no bastan |
| Tema | Ambos estados legibles, selector coherente, preferencia conservada al navegar y recargar | Pendiente; requiere interacción |
| About `/about/`, `/es/sobre-mi/` | Título, imagen, cuerpo, formación, habilidades y CTA sin cortes | Pendiente |
| Privacidad `/privacy/`, `/es/privacidad/` | Título, enlaces y texto largo legibles, sin desbordamiento | Pendiente |
| Proyectos `/work/`, `/es/proyectos/` | Tarjetas, títulos y subtítulos reales, alturas y enlaces legibles | Pendiente |
| Ficha Christianity Today EN/ES | Título largo, etiquetas, descripción, imagen, enlaces, cuerpo y selector de idioma | Pendiente; usar slugs ya registrados en el traspaso |
| Referencias `/articles/` y ficha existente | Tokens compartidos, tarjetas, HTML editorial y enlaces conservados | Pendiente |

Para empezar con capturas manuales: Home completa en escritorio y móvil, ambos temas; menú móvil abierto y un enlace social enfocado en claro. Después completar páginas compartidas y comprobaciones de interacción. Registrar origen (local o staging), URL, ancho, tema y hallazgos junto a cada evidencia: la corrección local del foco social aún no está en staging.

## Límites de esta sesión

Validación: `npm run check` correcto en 119 archivos, 0 errores, 0 warnings y 3 hints de código sin usar en la ficha legacy de artículos. No se repitieron pruebas runtime ni smoke CMS para este cambio de color.

Sin cambios CMS, staging o producción. Contacto sigue desactivado; sin envíos ni challenges. Se conserva el árbol local existente. No se cierra el QA visual ni se inicia el siguiente rediseño de proyectos antes de obtener la evidencia pendiente.
