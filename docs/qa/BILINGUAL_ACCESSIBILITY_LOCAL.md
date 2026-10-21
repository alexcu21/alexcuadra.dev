# Revisión bilingüe y accesibilidad — 19 octubre 2026

## Hallazgos corregidos en local

1. Home EN/ES: a320px las tarjetas de categorías mantenían dos columnas y ensanchaban la sección de menciones. overflow-x:hidden ocultaba parte de los enlaces. Grid small ahora usa una columna por debajo de30em, dos columnas con minmax(0,1fr) a partir de ahí y conserva flex en escritorio. Se permite partir etiquetas largas y la sección limita su ancho mínimo. Comprobación DOM antes: encabezado/tarjetas de menciones fuera de320; después: ningún descendiente main fuera de los márgenes, tanto ES como EN. Captura de tarjetas corregidas incluida.
2. Layout compartido: enlace Skip to content/Saltar al contenido como primer control, visible al foco. Todos los templates con BaseLayout apuntan a main-content con tabindex=-1. Home reúne hero/especialidades/secciones en un solo main, conservando separaciones visuales, para que el salto incluya H1 y presentación.
3. Archivo de proyectos: títulos de tarjetas en H2 bajo H1. En Home siguen H3 dentro de las secciones H2. Misma apariencia y componente compartido.

## Navegador local

Se revisaron páginas reales sin escribir al CMS, sin resolver challenges ni enviar correo. Servidor4325 activo. El override de tamaño requiere compensar el zoom de la ventana:352×928 solicitados → innerWidth320 verificado;1584×1100 →1440×1000 efectivos. Se retiró el override y se restauró el tema oscuro al terminar.

| Página/vista | Evidencia observada |
| --- | --- |
| About EN, oscuro, escritorio efectivo1309px | Cabecera con imagen y texto, inicio de trayectoria sin colisiones; captura completa guardada |
| Sobre mí ES, oscuro,320px | Imagen/cuerpo sin elementos fuera del viewport; sección de trayectoria legible |
| Menú ES, claro,320px | Enlaces/tema visibles; redes pueden envolver a segunda línea sin solaparse |
| Proyectos ES, claro,320px | Seis tarjetas reales, miniaturas completas y texto sin overflow |
| Christianity Today ES, claro,320px | Título largo parte en líneas, navegación/imagen/cuerpo reales legibles; Enter en salto lleva foco a main-content |
| Privacidad ES/EN, claro,320px | Ambos idiomas cargan, canonical EN correcto; sin overflow de contenido ES; Tab inicial muestra salto y Enter enfoca main EN |
| Proyectos EN, claro,1440px | Dos columnas, tarjetas H2; cero links PlayYellow, seis proyectos; sin descendientes main fuera de márgenes |
| Home ES/EN, claro,320px | Un main contiene H1/hero; menciones corregidas a una columna sin overflow; cero formularios en Home ES |

La comprobación de límites usa rectángulos de elementos, no solo scrollWidth, porque overflow-x global puede ocultar un problema. No constituye auditoría de lector de pantalla, zoom200%, todos los breakpoints/temas/idiomas de cada página ni dispositivos físicos.

## Validación técnica

167 unitarias correctas, suite SSR de referencias correcta con destino de salto focusable en templates completos, build correcto y diff --check limpio. No se crean pruebas que repliquen CSS; el defecto responsive se reprodujo y verificó directamente en navegador.

## Evidencia y pendientes

- [About EN escritorio](qa-about-en-desktop-dark.png)
- [Sobre mí ES320](qa-about-es-320-dark.png)
- [Ficha ES320](qa-project-es-320-light.png)
- [Privacidad ES320](qa-privacy-es-320-light.png)
- [Archivo EN1440](qa-projects-en-1440-light.png)
- [Home ES antes del ajuste de menciones, vista hero](qa-home-es-320-light.png)
- [Menciones ES320 corregidas](qa-home-es-320-mentions-fixed.png)

Pendiente: completar variantes restantes de la matriz visual, foco de todos los controles,zoom200%, rendimiento/SEO/CSP en Workers y aceptación final. Los nuevos textos y la retirada de PlayYellow están verificados en inventarios frontend real EN/ES en esta sesión. La etapa MCP editorial y Blog/LAB no se implementan en esta revisión.

Solo cambios locales de UI y accesibilidad. Sin despliegue, escritura CMS, cambios de permisos o contacto; formulario continúa desactivado.
