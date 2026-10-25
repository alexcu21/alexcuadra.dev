# Índice lateral de lectura — 25 octubre 2026

Alex pidió mover la tabla de contenidos a un aside izquierdo y repartir el espacio con el contenido para evitar recorrer un índice extenso antes de leer.

## Implementación

El renderer compartido de WordPress organiza índice y lectura en dos columnas desde64rem: lateral de12–17rem y columna flexible con texto limitado a70ch. El lateral permanece fijo a2rem del borde superior dentro del artículo; si el índice es largo, su navegación tiene altura máxima y desplazamiento propio. Un enlace «Leer contenido» permite saltar toda la lista con teclado.

A menor anchura se muestra un details nativo cerrado por defecto, con lista acotada a60svh cuando se abre. Funciona sin JavaScript nuevo. Las variantes escritorio/móvil comparten el mismo outline y destinos; CSS solo expone una de las dos a la vez. Los IDs de encabezado existentes no cambian; el contenido tiene un destino de lectura adicional no incluido en el orden normal de Tab.

Se aplica a proyectos, Blog y LAB. Proyectos elimina el antiguo espacio lateral «Sobre el proyecto» como columna independiente; ese título precede a la rejilla. Blog/LAB amplían el contenedor para dejar sitio al lateral. Páginas sin al menos dos encabezados no generan índice.

Se corrigió `overflow-x:hidden` en html/body a `clip`: el contenedor de desplazamiento implícito impedía el sticky. La comprobación inicial de staging detectó esa limitación; la versión final sí conserva el lateral a32px después de navegar a un encabezado intermedio. No se añadió JavaScript ni consulta CMS.

## Validación

- 279 unitarias correctas, suite SSR EN/ES y compilaciones staging/producción sin errores Astro.
- Fixture nuevo con40secciones confirma destinos, lector y HTML completos. SSR conserva cobertura de proyectos sin índice, Blog/LAB, IDs únicos, encabezados y saneamiento.
- Navegador local1280CSS: dos columnas y sin overflow. Con40secciones, salto con teclado a sección20: lateral top32, lista desplazada815px y foco en encabezado correcto; última sección40 también alcanzable, desplazamiento interno1602px. Estas cifras son observaciones, no expectativas universales de tamaño.
- Móvil320CSS: lateral oculto, índice cerrado, scrollWidth320. Enter abre/cierra; desplegado tiene altura480px frente a2680px de lista; enlace a sección3 coloca hash/foco correctos. LAB640CSS también refluye a índice cerrado sin overflow. Esta comprobación no certifica zoom real200%.
- Blog y LAB con índice revisados a1280CSS, dos columnas/sin overflow. Sin cambios en contenido WordPress, taxonomías, enlaces externos ni credenciales.
- Staging inicial08279df8: fichas EN/ES200, identidad verificada, noindex y CSP. Versión final corregida0cf469e9-670d-43e6-98a9-f5e40ce0a269: caso real ES observado en navegador; overflow clip/sticky32/foco en «El reto» correctos. Previa al incrementoa67ef581-766b-46d3-997d-aa1d18e55b1e.

## Evidencia

[HTTP staging](toc-sidebar-staging-2026-10-25.json), [caso real staging](toc-sidebar-staging-es-2026-10-25.png), [índice largo local](toc-sidebar-long-2026-10-25.png), [móvil local](toc-sidebar-mobile-2026-10-25.png).

Las capturas locales usan contenido sintético; la de staging usa el caso real aprobado. El límite429/estabilidad CMS y la matriz integral de aceptación siguen abiertos. No se hicieron mutaciones CMS, DNS, permisos, activación de contacto ni operaciones del MCP editorial aplazado.

## Producción y reversión

Publicado Worker `alexcuadra-dev`, versión `a20d636d-93df-4be8-af8e-8140c393dc0a`; anterior `43c88691-2f4c-4e68-b045-56398276b4c6`. Configuración generada verificada: dominio real, autenticación CMS=true, contacto=false, keep_vars=true. Fichas EN/ES200/identidad/CSP correctos, sin noindex; [recibo HTTP](toc-sidebar-production-2026-10-25.json). Navegador real ES1280: salto con teclado a «El reto», lateral top32px, foco en encabezado correcto y scrollWidth1280. [Captura de producción](toc-sidebar-production-es-2026-10-25.png). Viewport temporal restaurado.

Para revertir solo este incremento, restaurar la versión43c88691 del Worker; ya usa las taxonomías propias, por lo que no requiere cambios de CMS. No se ejecutó reversión ni push. Las comprobaciones200 son muestras puntuales, no una resolución del429 del hosting.
