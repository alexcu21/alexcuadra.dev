# Proyectos: primera adaptación a Obsidian Flow

Código local24octubre2026. [Comparación completa y propuesta de movimiento](../DESIGN_REVIEW_2026-10-24.md).

Implementados ProjectMedia compartido, cabeceras decorativas sin estados de pruebas ficticios, imágenes completas, etiquetas reales, tarjetas con borde/sombra/elevación, archivo con conteo real por idioma y ficha con título más compacto, banda condicional de enlaces/etiquetas y encabezado narrativo. Las tarjetas compartidas también cambian la Home. Se mantienen fuente CMS, rutas, relaciones de idiomas y campos opcionales. No se copia el contenido inventado de las maquetas.

## Verificación

- `npm test`:237 correctas,0 fallos.
- Suite SSR existente ampliada con las rutas reales de proyectos: archivos/fichas EN/ES, una H1, contenido sanitizado, traducción de regreso, ficha mínima sin cuerpo/imagen/metadatos, archivo ES vacío y404. Suite correcta. El primer intento falló por EPERM al abrir4398 en sandbox; repetición autorizada correcta, sin cambiar producto para ocultarlo.
- `npm run build`:correcto;163 archivos,0 errores/warnings/hints de Astro check. Se conservan avisos conocidos de Shiki/CSP; no se debilitó CSP. El build utiliza configuración local existente sin imprimir valores de secretos y no despliega.
- Navegador: ficha EN en oscuro a1280CSS, archivo ES en claro/oscuro, archivo/ficha ES a320CSS. Imagen de fixture cargada; una H1. En320CSS, innerWidth=scrollWidth=mainBounds=320 para la ficha y archivo sin desbordamiento observado en la vista. No se presenta esto como matriz integral ni prueba de lector/zoom200%.
- El fixture de vista previa usa consultas GraphQL sintéticas sin red CMS y sirve el inicializador de tema real. Detectada una omisión del fixture al navegar (no servía theme-init.js); se añadió la ruta al archivo de producción sin cambiar el control de tema del producto. Navegación posterior archivo ES→ficha ES conserva oscuro.

Las imágenes y el título de demostración son **sintéticos**. No representan un proyecto nuevo, resultados de cliente ni una edición de Christianity Today:

- [Ficha oscura escritorio](project-redesign-local-dark-2026-10-24.png)
- [Archivo claro](projects-redesign-local-light-2026-10-24.png)
- [Archivo oscuro](projects-redesign-local-dark-2026-10-24.png)
- [Archivo ES320](projects-redesign-local-es-320-2026-10-24.png)
- [Ficha ES320](project-redesign-local-es-320-2026-10-24.png)

Las capturas completas no fuerzan carga lazy fuera del viewport; no interpretar espacios vacíos de otras imágenes como fallo de producto. El hover se verificó en reglas CSS, con media queries para puntero fino/no-preference y reducción de movimiento; no se certifica aquí una sesión de OS reduced-motion ni una animación Motion instalada. La nueva biblioteca sigue siendo propuesta.

## Alcance y almacenamiento

Sin SSH, Docker, instalaciones de dependencias, backups nuevos, edición CMS, contacto/envíos ni deploy. Solo una visita remota nueva a la ficha publicada para comparación, y pruebas/local preview posteriores con datos sintéticos. El servidor aislado se detiene al terminar. No se inició ni reinició el servidor principal4325.

Cinco capturas≈375KiB de archivos; build dist5.2MiB y caché Vite existente50MiB observados al final, no son todos incremento nuevo. El volumen registra38GiB libres; esa lectura global no permite atribuir la diferencia respecto a los45GiB registrados en la sesión anterior a este trabajo. No se hicieron limpiezas ni borrados de recursos.

Pendiente: revisión del diseño con datos reales antes de la siguiente entrega staging y aceptación accesible final. Las tarjetas sin imagen siguen siendo textuales: no se añade contenido de relleno para igualar la maqueta. Movimiento reducido, CSP de una futura biblioteca y rendimiento deben verificarse cuando se incorpore esa biblioteca, no antes por inercia.
