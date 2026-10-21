# Contenido Gutenberg: soporte inicial y seguridad

Estado: renderer compartido en About, privacidad, Projects, Articles, Blog y LAB. Incremento local del20 de octubre: anchors seguros en encabezados y tabla de contenidos EN/ES en fichas Blog/LAB. El incremento todavía no está desplegado; staging conserva `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c`. No requiere cambiar fields, comprar ACF PRO ni modificar WordPress. El editor guarda HTML renderizado y el frontend aplica un contrato común antes de insertarlo en la página.

## Bloques soportados

| Contenido del editor | Tratamiento |
| --- | --- |
| Párrafos y headings | H2–H6, negrita, énfasis, tachado, sub/superíndice y alineación básica. H1 editorial se convierte a H2 porque el Hero ya aporta el H1 |
| Listas | Ordenadas, no ordenadas, anidadas y definiciones |
| Imágenes y captions | HTTPS o rutas relativas, alt y dimensiones válidas; tamaño responsive, carga diferida y decoding async |
| Columnas y grupos | Clases core acotadas, separación y columnas apiladas en móvil; anchos porcentuales flex-basis permitidos |
| Citas y pullquotes | Blockquote, cite y borde editorial; conservar autoría real desde el CMS |
| Tablas | Caption, cabeceras, celdas y contenedor con desplazamiento horizontal |
| Código | Pre/code, espacios preservados y clases language-*; no ejecuta el código ni añade resaltado de sintaxis todavía |
| Galería core | CSS grid responsive de imágenes; no utiliza el field Gallery de ACF PRO |
| Separadores | Regla visual acotada al contenido |
| Anchors e índice | IDs propios en H2–H6; enlaces a secciones existentes, duplicados desambiguados y tabla de contenidos nativa desplegable EN/ES en Blog/LAB cuando hay al menos dos títulos no vacíos |

Los estilos están bajo `.wordpress-content`; se retiraron reglas legacy duplicadas en las dos páginas de detalle y reglas de enlaces de About que ocultaban el subrayado. Las columnas se apilan en móvil incluso si el editor solicita no hacerlo. Alignwide/alignfull se limitan al ancho del contenedor actual; no se promete layout de ancho completo de la maqueta.

## Contrato de seguridad

`WordPressContent.astro` es el único componente que inserta HTML editorial del CMS. Usa `prepareWordPressDocument` en servidor: sanitización con la política existente, análisis del HTML ya saneado y serialización escapada antes de `set:html`. `sanitizeWordPressHtml` conserva su API previa y sigue retirando todos los IDs del HTML. Icon.astro utiliza el mapa SVG interno, no contenido de WordPress, y no pasa por este contrato.

Se añadió sanitize-html 2.18.1, fijado con versión exacta y lockfile. El paquete requiere Node >=22.12.0, compatible con el Node 22.23.2 configurado. Su repositorio anterior fue archivado por traslado al monorepo oficial de Apostrophe; se verificó que los metadatos npm apuntan a `apostrophecms/apostrophe/packages/sanitize-html`. La instalación reportó cero vulnerabilidades conocidas, lo que no equivale a una auditoría completa.

Lista explícita de tags, atributos y clases. Se eliminan scripts, eventos, formularios, contenido SVG/MathML, iframes, object/embed, plantillas, CSS activo y atributos arbitrarios como id/name/data-*. Las URLs de enlaces admiten HTTP/HTTPS, mailto/tel y rutas relativas; imágenes solo HTTPS o rutas relativas. Se rechazan esquemas javascript/data y URLs protocol-relative. El srcset se filtra con la librería. Los enlaces target=_blank incorporan noopener/noreferrer y conservan nofollow/ugc/sponsored permitidos. No se reescriben los enlaces normales de la ficha al idioma opuesto.

CSS inline limitado a alineación y flex-basis porcentual. Se eliminan colores personalizados, fondos, tamaños arbitrarios, position/display y CSS con URLs; no se garantiza reproducción exacta de todo el estilo del editor. Los IDs originales no se renderizan: el compilador solo lee los de encabezados para generar destinos propios. No permitir HTML arbitrario para recuperar un bloque que se haya eliminado.

## Anchors y tabla de contenidos — incremento local20 octubre

Los destinos usan `wp-content-{scope}-{section}-{slug}` y `tabindex=-1`; no pueden reemplazar `main-content` ni IDs del menú. Los slugs se generan desde el anchor editorial, si existe, o el texto visible decodificado. Normalizan acentos, conservan letras/números Unicode completos, limitan longitud y desambiguan duplicados/sufijos. Son deterministas para el mismo contenido; cambiar un título sin anchor editorial o alterar el orden de duplicados puede cambiar su URL de fragmento. Conservar un anchor editorial estable cuando deba compartirse una sección a largo plazo.

Solo se remapean enlaces locales `href="#fragmento"` que corresponden a un encabezado del documento. Se admiten fragmentos percent-encoded y relaciones entre secciones ACF. Los anchors editoriales explícitos tienen prioridad sobre aliases de texto; un ID editorial duplicado enlaza su primera aparición. Fragmentos desconocidos/mal codificados y URLs completas o externas se conservan. IDs de otros elementos siguen retirándose. No se promete reparar enlaces a elementos que nunca fueron admitidos.

Blog activa el índice sobre su cuerpo editorial. LAB compila una única unidad con cuerpo y secciones ACF no vacías; sus títulos visibles H2 forman parte del mismo índice y los encabezados interiores se desplazan un nivel (máximoH6). Los objetivos de aprendizaje siguen siendo texto plano escapado, conservando saltos de línea. Las demás vistas reciben anchors pero no un índice adicional por defecto.

El índice utiliza `details open`/`summary`, navegación etiquetada EN/ES y listas anidadas según el nivel real de encabezados. Se omite con menos de dos títulos no vacíos. Es desplegable en móvil y escritorio sin scripts; los enlaces tienen foco visible, wrapping y destinos con margen de scroll y señal de target. No se fuerza scroll animado. No equivale a una comprobación visual o de lector de pantalla: esas verificaciones siguen pendientes por el bloqueo vigente del navegador.

`htmlparser2`12.0.0 se declara como dependencia directa exacta, la misma versión ya instalada por sanitize-html2.18.1; se evita importar una dependencia transitiva sin declararla. Se usa para analizar el HTML ya saneado, no como sustituto del sanitizador. No se consultó ni modificó el CMS para esta entrega. [QA y límites](qa/EDITORIAL_OUTLINE_LOCAL_2026-10-20.md).

La autenticación GraphQL no convierte el HTML en confiable. Este control tampoco sanea automáticamente URLs de componentes Astro independientes (por ejemplo LinksToProjects), ni reemplaza CSP, autorización del CMS o las validaciones de visibilidad/contraseña de los DTOs. Esas superficies siguen en sus respectivos criterios del plan.

## Guía editorial de comprobación

En una ficha real que se esté editando, usar bloques core y datos reales. Comprobar H2/H3, párrafos, lista, imagen con alt/caption, cita con autor, dos columnas y tabla. No cargar ejemplos ficticios en producción solo para probar el render; las pruebas de seguridad usan fixtures locales.

Antes de publicar, comprobar lectura móvil/escritorio y tema claro/oscuro. Verificar que los enlaces externos siguen presentes y los bloques no soportados tienen una alternativa editorial explícita. Embeds de terceros, formularios, vídeo/audio, bloques interactivos y custom blocks necesitan una integración separada con allowlist; no se activan por defecto. Una revisión completa de fixtures visuales y accesibilidad está pendiente.

## Pruebas

`tests/wordpress-html.test.js` cubre preservación de bloques, HTML mal formado, idempotencia, H1 editorial, scripts y eventos, SVG/MathML, formularios, URLs ofuscadas, srcset peligroso, CSS/clases arbitrarias y protección opener. El total de la suite pasa de 92 a 98 pruebas. Validar además build y render en runtime Cloudflare; el resultado local no declara listo el rediseño visual ni un despliegue remoto.

Build de staging y smoke local correctos: `npm run test:smoke -- http://127.0.0.1:4321 --require-cms-auth --check-seo --check-content`. Confirma siete páginas, renderer compartido en los tres tipos de detalle y un único H1. Revisión visual puntual de la ficha de Medium en navegador: contenido y enlace conservados. Pendiente la matriz de bloques completa en móvil/escritorio y temas claro/oscuro.

## Fuente del paquete y opciones

- [Documentación oficial vigente de sanitize-html](https://github.com/apostrophecms/apostrophe/blob/main/packages/sanitize-html/README.md)
- [Traslado del repositorio anterior al monorepo](https://github.com/apostrophecms/sanitize-html)

Build de producción también correcto. Wrangler emitió durante el prerender un aviso sobre la ausencia de env.production en una configuración temporal generada. No se editó ese archivo generado: el resultado final dist/server/wrangler.json tiene name=alexcuadra-dev y main=entry.mjs, y .wrangler/deploy/config.json apunta a ese archivo. Revisar la advertencia al actualizar adapter/Wrangler; no se ejecutó ningún deploy.
