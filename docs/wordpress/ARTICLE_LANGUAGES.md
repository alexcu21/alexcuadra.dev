# Idiomas de referencias externas existentes

El idioma corresponde al texto de la ficha en WordPress, no necesariamente a su fuente externa. No inferirlo del slug. Inglés seguirá siendo el predeterminado del sitio. La asignación inicial del asistente fue inglés; Projects y Articles siguen sin marcar como traducibles según Alex. No cambiar idiomas de CPT hasta comprobar el conector y preparar filtros frontend.

## Español confirmado por Alex

IDs comprobados contra la referencia previa, sin modificar el CMS:

| ID | Slug | Idioma objetivo |
| --- | --- | --- |
| 97 | la-experiencia-valex-en-el-wordcamp-bogota-2024 | es |
| 95 | episodio-4-especial-wordcamp-guatemala | es |
| 91 | talkcreando-un-bloque-de-gutenberg-dinamico-a-partir-de-un-shortcode | es |
| 84 | podcast-hablemos-de-opensource-y-comunidad | es |
| 82 | podcast-hablemos-del-2024 | es |

## Pendiente de confirmar idioma del texto local

| ID | Slug |
| --- | --- |
| 78 | como-dar-mantenimiento-a-tus-sitios-wordpress-con-wp-cli |
| 80 | charla-el-camino-del-desarrollador-wordpress |
| 87 | talkno-todo-en-wordpress-es-codigo |
| 93 | descubriendo-playground-la-herramienta-para-hacer-demos |

ID 75, how-to-build-a-custom-dynamic-gutenberg-block-step-by-step: conservar idioma hasta completar clasificación. No convertir referencias españolas existentes en contenido inglés solo porque sea el idioma principal. Si se traduce una ficha, conservar su registro y URL legacy y vincular otro registro para la traducción revisada. La correspondencia exacta entre URLs legacy y rutas localizadas debe probarse antes de mover publicaciones a nuevos archivos por idioma.

## Paso técnico siguiente

Probar WP GraphQL Polylang v0.7.1 desde la release oficial de Valu Digital antes de marcar CPT o crear contenido ES nuevo. Se comprobó la cabecera del plugin y la inclusión de vendor/autoload.php en el tag; no equivale a auditoría completa ni garantía de compatibilidad. Tras activar, ejecutar cms:languages y comprobar baseline/frontend. Todavía no hay consultas localizadas ni rutas españolas implementadas.
