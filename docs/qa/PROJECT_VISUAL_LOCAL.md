# Listado y ficha reutilizable — incremento local, 19 octubre 2026

Actualización24octubre: las maquetas originales ya están disponibles localmente y se han inspeccionado. La primera adaptación nueva y sus límites están en [QA actual](PROJECT_DESIGN_INCREMENT_2026-10-24.md); la comparación/prioridades en [revisión de diseño](../DESIGN_REVIEW_2026-10-24.md). La afirmación inferior sobre ausencia de HTML corresponde al19octubre.

Alex autorizó continuar los incrementos manteniendo abierta la aceptación visual restante de Home. Sin acceso al navegador ni alternativa para sortear el bloqueo.

## Implementación

- ProjectsPage EN/ES: encabezado responsive, cuadrícula regular de una/dos columnas sin desplazamiento escalonado, tarjetas compartidas y estado vacío existente. Sin filtros ficticios ni consultas adicionales.
- ProjectPage compartido: navegación de regreso visible también en móvil, selector solo de traducciones públicas reales, título fluido, subtítulo opcional, etiquetas con wrap, enlaces opcionales, imagen íntegra sin recorte forzado y cuerpo editorial en sección con ancho de lectura. Toda la ficha queda dentro de main.
- Imagen, cuerpo, etiquetas y enlaces se omiten cuando su campo está ausente. Sin imagen sustitutiva, métricas, fechas o responsabilidades inventadas. El cuerpo real de Gutenberg contiene el alcance/tecnologías; no se extraen datos de párrafos para fabricar campos estructurados ni se codifica contenido específico de Christianity Today.
- Enlaces externos de proyecto adaptados a tokens claro/oscuro y foco global; se conserva apertura en otra pestaña con rel seguro.

La referencia disponible es el alcance descrito en REDESIGN_PLAN.md; el HTML original de la maqueta no está en el repositorio. Esta entrega aplica el sistema ya establecido y la estructura reutilizable, sin afirmar reproducción exacta del mockup ni cierre del diseño final. No se añadieron consultas para proyectos anterior/siguiente; el regreso lleva al archivo del idioma actual.

## Validación

Capturas manuales posteriores: listado EN/oscuro con dos columnas y ficha Christianity Today EN/oscuro en vista estrecha. En el área visible de la ficha, navegación, título, subtítulo, enlace, imagen e inicio del cuerpo no presentan colisiones. El listado mantiene tarjetas de altura coherente aun sin subtítulo (alextecuenta); no se rellena ese vacío editorial. Ancho CSS/zoom no confirmados y cuerpo inferior fuera del encuadre.

Hallazgo en listado: object-fit cover recorta lateralmente las capturas y parte de los logotipos. Se cambia PortfolioPreview a contain centrado con fondo de token, manteniendo proporción reservada 16/10. Aplica también a tarjetas Home; pendiente de confirmar visualmente tras el ajuste. No cambia imágenes ni contenido CMS.

145 pruebas unitarias pasan. Build correcto; astro check: 0 errores, 0 warnings, 3 hints legacy. Persisten avisos de compilación de Shiki/CSP. El primer build falló por restricciones de escritura/puerto del sandbox; repetido con autorización fuera del sandbox y completado. Sin pruebas nuevas que repliquen CSS ni matriz remota contra CMS.

Pendiente: capturas locales de `/work/`, `/es/proyectos/`, `/work/christianitytoday-com/` y `/es/proyectos/christianitytoday-com-2/`, escritorio/móvil y ambos temas. Revisar también una ficha con cuerpo/imagen ausentes, teclado y enlaces. No se declara QA visual completado.

## Alcance de publicación

Solo código local. Sin despliegue a staging/producción ni escritura al CMS. Contacto sigue desactivado; sin correo/challenge. Los pendientes visuales anteriores permanecen en HOME_VISUAL_REVIEW.md.
