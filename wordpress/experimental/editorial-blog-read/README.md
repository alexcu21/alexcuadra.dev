# Editorial Blog Read — candidato local

Este primer incremento añade lectura de metadatos de entradas nativas. No reemplaza Editorial Read0.1.0 ni sus paquetes sellados. No está instalado ni disponible en el conector real.

Solo registra herramientas con `AC_EDITORIAL_BLOG_READ_LAB=true` y `wp_get_environment_type() === 'local'`. El guard se conserva hasta completar integración real y revisión del paquete de destino. No quitarlo para instalar el prototipo en el hosting.

## Contrato

| Ability | Entrada | Resultado |
| --- | --- | --- |
| `alexcuadra/list-public-posts` | locale en/es; page1–100; per_page1–20; search título≤100caracteres | items y next_page; orden ID ascendente |
| `alexcuadra/get-public-post` | id entero positivo y locale exacto | id/título/slug/idioma, traducciones recíprocas y clasificaciones asignadas |

Las clasificaciones usan exclusivamente category y post_tag; sus términos incluyen ID/nombre/slug/taxonomía/idioma y traducciones recíprocas. No se consultan taxonomías de proyectos ni se devuelven counts que puedan mezclar tipos/estados. Máximo100términos por taxonomía y entrada; si se supera, se rechaza la respuesta en lugar de entregar un inventario truncado.

Solo posts publicados, públicos, sin contraseña, idioma confirmado por Polylang y permiso read_post. Las traducciones de entradas deben cumplir esas mismas condiciones. Las taxonomías deben ser consultables públicamente y estar traducidas en Polylang; una asignación de otro idioma produce error de contrato. Un inventario contradictorio se rechaza por completo. ID ausente, privado, de otro tipo o idioma recibe el mismo404genérico.

No devuelve cuerpo ni contenido bruto de Gutenberg, extractos, ACF, opciones, autores, correos, metadatos privados o estados privados. No ejecuta filtros de contenido/shortcodes. No crea propuestas, traducciones, términos o relaciones; tampoco publica, aprueba, aplica o recupera fuentes.

## Permiso

Capacidad nueva y separada `alexcuadra_read_public_posts`, junto con usuario autenticado y read. El permiso existente de proyectos no implica este acceso. Tampoco lo implica ser administrador. No hay activation hook que asigne capacidades, migraciones, tablas u opciones nuevas. El candidato no llama a funciones de escritura.

## Pruebas reproducibles

```sh
/opt/homebrew/bin/php -l wordpress/experimental/editorial-blog-read/alexcuadra-editorial-blog-read.php
/opt/homebrew/bin/php tests/editorial-blog-read/check.php
/opt/homebrew/bin/php tests/editorial-blog-read/check.php no-opt-in
/opt/homebrew/bin/php tests/editorial-blog-read/check.php production
```

El ejecutable PHP puede variar en otra máquina. La prueba usa dobles explícitos de las APIs de WordPress/Polylang/Abilities: comprueba el contrato y los callbacks reales del candidato, **sin WordPress, BD, HTTP, Docker ni red**. No demuestra integración con WP_Query real, filtros de Polylang o MCP Adapter. Ese es el siguiente ensayo antes de empaquetar/instalar o solicitar un permiso nuevo.

## Siguientes fases

1. Integración WordPress/Polylang/Abilities/MCP en laboratorio aislado y rechazo de accesos por HTTP. Mantener la separación de permisos.
2. Revisar paquete y preflight de destino. La instalación y el primer grant real se autorizan sobre un resultado concreto; no se reutiliza el scope de proyectos ni se concede edit_posts.
3. Contrato de propuestas privadas para posts, cuerpos/extractos y taxonomías, creación explícita/idempotente de traducciones y separación de aprobación/aplicación/recuperación. Los mecanismos operativos previos para proyectos no certifican por sí solos este nuevo tipo.

Fuentes: [WordPress is_taxonomy_viewable](https://developer.wordpress.org/reference/functions/is_taxonomy_viewable/) y [referencia Polylang](https://polylang.pro/documentation/support/developers/function-reference/). Se comprueba la presencia de las funciones Polylang antes de usarlas.
