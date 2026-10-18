# Revisión del export ACF tras registrar los CPT

Fecha: 18 de octubre de 2026. Fuente: [export recibido](acf-after-cpt-registration-2026-10-18.json). Comparado con los cuatro grupos originales y el PHP del plugin aportado por Alex.

## Correcto

- Cuatro grupos ACF idénticos por comparación JSON estructural: mismas keys, tipos, nombres, reglas de ubicación y ajustes GraphQL.
- CPT project, GraphQL project/projects; CPT articles, GraphQL article/articles.
- Public, no jerarquía, archivos habilitados, REST y GraphQL activos; categorías y tags vinculados.
- Soportes originales presentes, incluido Excerpt restaurado en Articles. Se añadió custom-fields en ambos por la necesidad señalada por Alex; conservarlo.
- Iconos, posición 5 de Projects y rutas por claves del tipo correctas. Usar post_type_key en Articles resuelve el mismo slug articles que el rewrite explícito del plugin.
- Articles mantiene capacidades post/posts, como el registro original.
- Baseline posterior ya verificado: 7 proyectos y 10 referencias externas publicados sin cambios en IDs/slugs/hashes de los campos consultados. Frontend autenticado y rutas comprobados.

## Ajustes pendientes

### 1. Projects: capacidades

El export tiene rename_capabilities=false y nombres post/posts. El plugin usaba capability_type=page. El acceso publicado puede funcionar igualmente, pero no conserva los permisos editoriales del registro anterior.

ACF → Post Types → Projects → Advanced Configuration → Permissions:

- Rename Capabilities: activado.
- Singular Capability Name: page.
- Plural Capability Name: pages.

Guardar; Articles mantiene Rename Capabilities desactivado. No crear capacidades project/projects ni ampliar permisos del usuario de servicio.

### 2. Feeds: ambos CPT

El export contiene rewrite.feeds="0" en ambos. El plugin tenía has_archive=true sin fijar feeds, cuyo default en WordPress hereda has_archive, por lo que habilitaba las estructuras de feed.

Para conservar ese comportamiento, activar Feed URL en URLs de Projects y Articles. Esto afecta las URLs de feeds del CMS; no implementa un RSS nuevo en Astro ni mezcla references con el blog. Si más adelante se decide retirarlos por el diseño headless, documentarlo como cambio separado, no equivalencia exacta.

Guardar los tipos y después Ajustes → Enlaces permanentes → Guardar cambios una vez sin modificar la estructura. Volver a exportar los dos CPT para comprobar los valores y repetir baseline. Mantener el plugin original desactivado.

## Diferencias sin impacto funcional observado

Descripción y algunas etiquetas del admin fueron regeneradas por ACF. No cambian las claves de contenido ni las consultas. Las opciones can_export, delete_with_user y REST defaults deben mantenerse; no eliminar entradas ni usuarios como prueba de esta transición.

Fuente del default de feeds: [WordPress register_post_type](https://developer.wordpress.org/reference/functions/register_post_type/).
