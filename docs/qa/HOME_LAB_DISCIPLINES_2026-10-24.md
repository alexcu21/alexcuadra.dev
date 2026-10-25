# Especialidades y LAB en Home

Incremento local del 24 de octubre de 2026. La portada añade un panel LAB después de los proyectos destacados y antes de artículos, siguiendo la composición de la referencia. Muestra hasta 3 publicaciones recientes del idioma solicitado, propósito/resumen, estado editorial y enlaces a sus fichas reales. No representa métricas de QA, ejecuciones de pipelines ni resultados de cliente.

Las especialidades usan los tres campos existentes: icono en una superficie pequeña, índice decorativo, nombre y descripción. Se omiten nombres/descripciones vacíos; si no hay especialidades, desaparece la sección. Se eliminó el fallback interno que podía volver a consultar Home EN ante campos nulos. No se creó una cuarta especialidad ni se inventaron tecnologías.

## Datos y coste

HomeLabProjects consulta first:3, idioma fijo EN/ES y publicaciones públicas sin contraseña. No sigue paginación ni solicita cuerpo, repositorio o demo. Proyección explícita de título/slug/excerpt/propósito/estado; valida visibilidad, idioma, slug y máximo 3 antes de compartir datos. El bloque y enlace de menú se omiten si no hay publicaciones.

Home pasa a la navegación el resultado de esta lectura, sustituyendo su consulta LabAvailability. No suma una consulta LAB al camino frío de la portada; aumenta el contenido de esa lectura hasta el límite indicado. Conserva la caché pública de 60 s y particiones por idioma/credenciales, sin guardar campos extra. Las otras páginas mantienen la consulta ligera de disponibilidad. No se añaden enlaces externos de repositorio/demo sin datos previamente verificados.

Las tarjetas nuevas usan las marcas de Motion existentes. Su chunk continúa en9794B/gzip3876B; no se añadieron dependencias ni cambió el controlador de animación. No se repitió su batería específica ni se presenta como nueva certificación CSP de todo el sitio.

## Validación

- 16 pruebas de LAB correctas, incluidas 3 nuevas: consulta acotada/proyección, rechazo de registros no públicos/idioma incorrecto/exceso y caché de 60 s con particiones de idioma/credenciales sin campos extra.
- Suite SSR ampliada correcta: Home EN/ES, enlaces y estado traducido, reutilización de la lectura en menú con resultados positivos y vacíos, omisión de LAB y especialidades vacías, una sola especialidad sin error. El resto de rutas de la suite continúa correcto.
- Build correcto:173 archivos, 0 errores/warnings/hints de Astro check. Persisten los avisos conocidos de Shiki/CSP; no se modificó la política del producto.
- Navegador: especialidades en oscuro EN y claro ES, LAB oscuro EN y claro/oscuro ES; ficha sintética de LAB abierta desde Home.1280 píxeles CSS en escritorio;320 píxeles CSS en móvil con scrollWidth=320. No se observaron recortes en el panel revisado. [Medidas y enlaces](HOME_LAB_VISUAL_2026-10-24.json).

Una recarga en caliente mostró503 mientras se añadía el soporte del query nuevo al fixture. La versión completa y la suite devolvieron200; se recargó la misma pestaña y se verificó el resultado. Fue una respuesta del laboratorio, no evidencia nueva de un fallo del hosting WordPress.

El servidor de revisión4398 se conservó; la suite utilizó4399 y lo cerró al terminar. La opción REFERENCES_SSR_PORT permite este aislamiento sin cambiar el puerto por defecto4398 en CI. No se lanzó un segundo servidor persistente ni se reinició4325.

## Evidencia

- [Especialidades oscuras](home-disciplines-dark-2026-10-24.png)
- [Especialidades claras](home-disciplines-light-2026-10-24.png)
- [LAB oscuro escritorio](home-lab-dark-desktop-2026-10-24.png)
- [LAB ES oscuro320](home-lab-es-dark-320-2026-10-24.png)
- [LAB ES claro320](home-lab-es-light-320-2026-10-24.png)
- [Vista local de LAB](http://127.0.0.1:4398/preview/home#home-lab)

Las capturas usan datos sintéticos; no confundir los textos de prueba con nuevas publicaciones en CMS. La variante ES mantiene algunos textos EN de su fixture deliberadamente compartido. El tamaño solicitado del viewport se ajustó al zoom existente para obtener1280/320 píxeles CSS medidos, sin afirmar que el raster de captura tenga esas dimensiones. Se restauraron tema oscuro y tamaño predeterminado.

Cinco capturas≈174 KiB; 36 GiB libres al final. Sin nuevas dependencias, descargas, Docker, backups, SSH, consultas al CMS real, cambios de WordPress/contacto ni deploy. QA visual parcial; lector/zoom200% y postflight con datos reales quedan en la aceptación final.

Siguiente entrega: presentación de artículos/menciones y CTA. MCP continúa aplazado.
