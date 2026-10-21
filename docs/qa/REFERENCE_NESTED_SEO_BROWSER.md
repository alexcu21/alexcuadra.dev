# Referencias: categorías anidadas, SEO y navegador — 19 octubre 2026

## Incremento local

getArticleBySlug completa la conexión anidada de categorías de100 en100 antes de decidir pertenencia a una ruta legacy. Las páginas adicionales usan una consulta limitada a visibilidad y categorías, conservan validación pública y guardas de cursor/límite. Si el artículo desaparece, se vuelve privado o falla upstream durante ese recorrido, se obtiene503 en lugar de404 falso o datos parciales. No altera relaciones CMS ni slugs.

Archivo de categoría y ficha añaden BreadcrumbList con URLs/nombres de navegación real. Serialización de JSON-LD escapa `<` para que los títulos CMS no cierren el script. No se infiere autor, fecha, resultados ni tipo Article de una referencia externa. La ficha transmite su imagen opcional a Open Graph/Twitter. Canonical y URLs legacy se conservan.

## Validación automatizada

167 unitarias correctas: nuevas pruebas de categoría en página posterior, fallos anidados por desaparición/privacidad/upstream/cursor y breadcrumbs/escape. Suite HTTP SSR real ampliada: ficha con categoría en segunda página, error anidado503, JSON-LD e imagen social. Build correcto y git diff --check correcto. Sin cambios desplegados.

## Acceso al navegador recuperado

Por petición de Alex se reintentó en el mismo origen127.0.0.1:4325. Primero respondió ERR_CONNECTION_REFUSED, sin rechazo de política. No había proceso escuchando; se inició Astro con Cloudflare en4325. Después el navegador integrado permitió leer y capturar Home. No se usó otro hostname/puerto/navegador como alternativa al bloqueo. Esto demuestra acceso funcional en esta sesión; no explica por qué antes la política denegaba la URL.

Comprobado en UI:

- Home EN con contenido CMS actualizado de Makers Unite y alextecuenta.
- Menú móvil oscuro/claro legible en las áreas capturadas. Tab desde botón abierto avanza a Home; Escape desde enlace y desde tema cierra menú y devuelve foco al botón.
- Tema claro persiste al navegar Home → archivo → categoría Medium → ficha real. Se restauró oscuro al terminar; override temporal de viewport retirado.
- Archivo/categoría y ficha Medium reales cargan. Ficha muestra título largo legible, cita, cuerpo y enlace externo; un H1, canonical y BreadcrumbList verificados desde el DOM.
- No se enviaron mensajes ni activó formulario.

La vista desktop inicial fue1280×720. Para móvil se solicitó override390×844, pero una lectura DOM posterior informó innerWidth354; no presentar esa captura como prueba exacta de390/320. La herramienta y la ventana cambiaron la medida efectiva. No se declara matriz completa de accesibilidad,320px,zoom200%,EN/ES o todas las páginas. No hubo nuevos hallazgos que motivaran cambios visuales.

Evidencia: [menú oscuro](browser-home-menu-dark.png), [menú claro](browser-home-menu-light.png), [ficha clara](browser-reference-mobile-light.png), [ficha oscura](browser-reference-dark.png).

Sigue pendiente QA visual restante, validación de SEO/CSP en Workers y desarrollo de Blog/LAB con modelo/contenido confirmado. MCP editorial sigue etapa separada. Sin CMS, deploy o cambios de contacto.
