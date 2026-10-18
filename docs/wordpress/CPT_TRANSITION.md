# Sustitución del plugin de CPT por ACF: configuración exacta

## Decisión de ejecución

Alex autorizó trabajar directamente en content.alexcuadra.dev y acepta interrupciones. No se exige staging para esta operación. Conservar backup de base de datos y archivos y el plugin original para revertir; el export ACF solo contiene configuración y no reemplaza el backup de contenido.

El PHP aportado registra únicamente project y articles mediante init. No contiene hooks de desactivación/desinstalación, migraciones, taxonomías nuevas ni resolvers custom. No se observa lógica adicional que trasladar. Esto describe el archivo recibido, no otros plugins o modificaciones del servidor.

**Conclusión:** la ruta prevista es cambiar el proveedor de registro conservando post_type, sin copiar ni convertir entradas. No usar importación de posts ni SQL de reasignación. La conclusión se valida comprobando contenido y consultas tras el cambio.

## Referencias conservadas

- [Export original ACF](acf-legacy-2026-10-18.json): cuatro grupos, mismas claves de grupo/campo y ubicaciones.
- [Baseline de publicados](content-baseline.json): 7 proyectos y 10 artículos, IDs/slugs y hashes del contenido, fechas, medios, términos y campos seleccionados. No contiene los cuerpos editoriales. No incluye borradores, privados, contraseñas o meta ajeno a las consultas; sus conteos deben revisarse desde el admin/backup.
- `npm run cms:baseline -- --verify` compara contra esa referencia sin sobrescribirla. Editar contenido durante la transición cambia los hashes: evitarlo hasta terminar la comparación.

## Grupos y campos existentes: conservar sin recrear

| Grupo | Clave del grupo | Ubicación | Nombre GraphQL configurado |
| --- | --- | --- | --- |
| Home_page | group_676ca62b35b45 | page + página ID 19 | home_page_fields (consulta actual homePageFields) |
| About_Page | group_67707e6a6a0df | página ID 68 | about_page (consulta actual aboutPage) |
| Projects | group_676d40f7bb646 | post_type = project | projects |
| Articles Fields | group_67708756478c5 | post_type = articles | articlesFields |

Projects conserva project_tagline, githuburl y websiteurl. Articles Fields conserva quote. Home usa text, image, URLs, email y group con subcampos; About text/textarea. Son tipos compatibles con ACF gratuito; no hay Repeater, Flexible Content, Gallery ni Options Page en este export.

Los IDs fijos 19 y 68 son dependencias legacy. No cambiarlos durante esta sustitución. La futura traducción necesitará reglas que incluyan páginas traducidas; se resolverá después. El arreglo graphql_types es amplio, pero map_graphql_types_from_location_rules está activo: no asumir exposición efectiva a todos esos tipos ni modificarlo ahora.

## Tabla de equivalencia plugin → ACF

En ACF → Post Types → Add New, activar Advanced Configuration. Conservar etiquetas en inglés para mantener el admin existente; los idiomas editoriales se resuelven en otra fase.

| Ajuste | Projects | Articles |
| --- | --- | --- |
| Plural Label | Projects | Articles |
| Singular Label | Project | Article |
| Post Type Key | **project** | **articles** |
| Public | Sí | Sí |
| Hierarchical | No | No (default del registro original) |
| Supports | Title, Editor, Featured Image, Excerpt | Title, Editor, Featured Image, Excerpt, Comments, Revisions |
| Linked Taxonomies | Categories (category), Tags (post_tag) | Categories (category), Tags (post_tag) |
| Show UI / Admin Menu / Admin Bar / Navigation Menus | Sí | Sí (defaults derivados de public) |
| Menu Position | 5 | Sin posición personalizada |
| Menu Icon | dashicons-portfolio | dashicons-welcome-write-blog |
| Exclude From Search | No | No |
| Archive | Sí | Sí |
| Permalink Rewrite | Clave del tipo o slug project | Slug personalizado articles |
| Front URL Prefix / with_front | Sí (default original) | Sí (default original) |
| Publicly Queryable | Sí | Sí |
| Query Variable Support | Clave del tipo | Clave del tipo |
| Show in REST API | Sí | Sí |
| REST Base / Namespace / Controller | Defaults de WordPress, sin personalizar | Defaults de WordPress, sin personalizar |
| Can Export | Sí | Sí |
| Show in GraphQL | Sí | Sí |
| GraphQL Single Name | **project** | **article** |
| GraphQL Plural Name | **projects** | **articles** |

### Permisos: no igualar ambos CPT

Projects declara capability_type=page. En Permissions, activar Rename Capabilities e indicar singular **page**, plural **pages**. No usar project/projects, que crearía capacidades diferentes y requeriría asignación de roles.

Articles no personaliza capability_type: usa el default post. Dejar Rename Capabilities desactivado para heredar capacidades de entradas. No ampliar permisos de la cuenta Suscriptor para corregir una configuración del CPT.

Comprobar el PHP exportado por ACF después de guardar: Projects debe basarse en page/pages y Articles en post/posts, conservando el mapeo estándar de capacidades de WordPress. No fijar map_meta_cap=false por deducción de que el plugin no lo declara: WordPress resuelve defaults durante el registro. Si la pantalla difiere o el PHP resulta distinto, revisar antes de continuar.

## Secuencia guiada en producción

1. Confirmar versiones ACF/WPGraphQL for ACF, backup reciente y disponibilidad de ACF → Post Types. Anotar conteos en admin por cada estado, no solo publicados. Conservar una copia del PHP del plugin.
2. Preparar esta tabla en otra pestaña. El plugin contiene ambos registros: al desactivarlo desaparecerán temporalmente ambos menús y la API podría fallar. Es la interrupción aceptada, no una señal para volver a crear el contenido.
3. Desactivar el plugin **Custom Post Type: Projects**, sin eliminarlo. No desactivar ACF, WPGraphQL ni WPGraphQL for ACF.
4. Crear y guardar Projects en ACF usando todos los valores de la tabla, incluyendo GraphQL y permisos. No crear otro grupo de fields ni importar de nuevo los grupos existentes.
5. Crear y guardar Articles con la segunda columna. Evitar tener el plugin original activo junto a los nuevos registros ACF; ambos registrarían las mismas claves.
6. Abrir Projects y Articles: deben aparecer las entradas existentes y sus campos. Comparar IDs/conteos por estado y revisar imágenes/categorías/tags, sin editar valores todavía.
7. Ajustes → Enlaces permanentes → Guardar cambios una vez, sin cambiar la estructura. Esto actualiza reglas de WordPress; no cambia las rutas Astro del portfolio.
8. Avisar para ejecutar comparación del baseline, inventario de esquema y smoke de frontend. Comprobar también una entrada en el editor y guardar una prueba controlada solo después de la comparación sin cambios.
9. Exportar desde ACF los nuevos registros CPT para revisar sus argumentos efectivos y guardarlos versionados junto a la configuración de fields. No crear todavía LAB, idiomas ni campos del rediseño: primero cerrar esta equivalencia.

Si ACF no permite una clave por estar registrada, comprobar que el plugin esté desactivado y que no exista otro registro en ACF/tema. No elegir otra clave para esquivar el aviso.

## Reversión

Si faltan entradas o se rompe el esquema, marcar inactivos los nuevos CPT en ACF y reactivar el plugin original; no mantener ambos proveedores activos. Guardar enlaces permanentes una vez y comprobar consultas. Las entradas no se borran por el archivo recibido al desactivar: no vaciar papelera, no eliminar posts y no desinstalar plugins. Usar el backup si hubo modificaciones de datos ajenas al simple registro.

## Fuentes

- [ACF: ajustes de registro y permisos](https://www.advancedcustomfields.com/resources/registering-a-custom-post-type/)
- [WPGraphQL for ACF: ajustes GraphQL de CPT](https://www.wpgraphql.com/docs/acf/adding-acf-post-types-and-taxonomies-to-the-graphql-schema)
- [WordPress: argumentos de register_post_type](https://developer.wordpress.org/reference/functions/register_post_type/)
