# Idiomas de referencias externas existentes

El idioma corresponde al texto de la ficha en WordPress, no necesariamente a su fuente externa. No inferirlo del slug. Inglés seguirá siendo el predeterminado del sitio. El conector y los CPT traducibles ya están activos y verificados. La auditoría del 18 de octubre confirmó **EN en los siete proyectos y language=null en las diez referencias**, mientras Home/About están en EN. La asignación de Projects se completó por Alex y verificó después; los hashes editoriales de transición siguen intactos. Las asignaciones de las tablas siguientes son objetivos editoriales, todavía no cambios aplicados.

## Español confirmado por Alex

IDs comprobados contra la referencia previa, sin modificar el CMS:

| ID | Slug | Idioma objetivo |
| --- | --- | --- |
| 97 | la-experiencia-valex-en-el-wordcamp-bogota-2024 | es |
| 95 | episodio-4-especial-wordcamp-guatemala | es |
| 91 | talkcreando-un-bloque-de-gutenberg-dinamico-a-partir-de-un-shortcode | es |
| 84 | podcast-hablemos-de-opensource-y-comunidad | es |
| 82 | podcast-hablemos-del-2024 | es |

## Español adicional confirmado por Alex

| ID | Slug |
| --- | --- |
| 78 | como-dar-mantenimiento-a-tus-sitios-wordpress-con-wp-cli |
| 80 | charla-el-camino-del-desarrollador-wordpress |
| 87 | talkno-todo-en-wordpress-es-codigo |
| 93 | descubriendo-playground-la-herramienta-para-hacer-demos |

Estas cuatro fichas también fueron confirmadas en español. Total: nueve referencias españolas, sin cambios aplicados todavía al CMS.

ID 75, how-to-build-a-custom-dynamic-gutenberg-block-step-by-step: conservar idioma hasta completar clasificación. No convertir referencias españolas existentes en contenido inglés solo porque sea el idioma principal. Si se traduce una ficha, conservar su registro y URL legacy y vincular otro registro para la traducción revisada. La correspondencia exacta entre URLs legacy y rutas localizadas debe probarse antes de mover publicaciones a nuevos archivos por idioma.

## Paso técnico siguiente

El conector pasó el laboratorio y está activo en el CMS; `cms:languages -- --require-ready --audit-content` confirmó fields/filtros en los cinco tipos. El baseline original sigue coincidiendo y producción pasó el smoke autenticado. Hay consultas localizadas implementadas, pero las rutas públicas todavía usan el modo compatible.

Auditoría de taxonomías completada: las nueve fichas españolas usan valexweb (IDs de ficha 97/78), podcast (95/84/82) o wordpress-tv (93/91/87/80); la ficha 75 usa medium. Los siete Projects publicados no tienen categorías y no hay Posts publicados visibles al servicio. Las diez referencias no tienen etiquetas. La cuenta de servicio no permite auditar todo el contenido no publicado; Alex confirmó que tampoco hay borradores, privados o protegidos en inglés que utilicen las tres categorías españolas.

### Propuesta anterior de cambio de categorías — NO APLICAR

**Decisión posterior de Alex:** conservar estas categorías en inglés por el momento. No se aplicó la propuesta siguiente; sus instrucciones y tabla se conservan solo como referencia de la revisión. Asignación de ES a las fichas diferida hasta resolver relaciones y rutas. No ejecutar los pasos de cambio de idioma descritos abajo.

La propuesta anterior conservaba los términos originales y cambiaba únicamente su idioma:

| ID de categoría | Slug que se conserva | Idioma actual | Objetivo | Referencias actuales |
| --- | --- | --- | --- | --- |
| 14 | podcast | EN | ES | 95, 84, 82 |
| 12 | valexweb | EN | ES | 97, 78 |
| 13 | wordpress-tv | EN | ES | 93, 91, 87, 80 |
| 10 | medium | EN | EN, sin cambio | 75 |

WordPress → Articles → Categorías (o la pantalla compartida Categorías que muestra esos slugs) → Editar cada una de las tres → Idioma Español → Actualizar. Mantener nombre, slug, descripción y jerarquía. No pulsar el signo + de traducción ni modificar Uncategorized/WordCamp, las etiquetas de Projects o la categoría Medium. Los tres términos no tienen traducciones vinculadas según la consulta actual; no se está desvinculando una pareja existente.

Después de guardar, comprobar `cms:languages -- --require-ready --audit-content`, `cms:baseline -- --language-transition --verify` y las tres URLs `/articles/category/podcast`, `/articles/category/valexweb` y `/articles/category/wordpress-tv`. No marcar este cambio como completado hasta contrastarlo con el CMS. Si desaparece una relación, recuperar la asociación ID ficha→ID categoría de esta tabla; no generar términos de reemplazo ni sobrescribir el baseline para ocultar la diferencia.

Solo tras esa verificación asignar ES a las nueve referencias, conservando su categoría correspondiente; la ficha 75 se clasifica por separado. Las rutas actuales siguen consultando todos los idiomas en modo compatible y no se cambian en este paso. Cuando haya referencias inglesas para esas mismas plataformas, crear sus equivalencias EN vinculadas y definir sus rutas, sin reutilizar incorrectamente un término ES para contenido EN.

**Completado:** asignación EN a los siete Projects publicados; auditada y validada contra hashes editoriales. Procedimiento utilizado, conservado como referencia: seleccionar los siete → Acciones en lote → Editar → Aplicar → Idioma English → Actualizar. Si no aparece el selector en edición masiva, usar Edición rápida o el panel Idiomas de cada proyecto. No crear traducciones, cambiar slugs ni modificar Articles en este paso.

Se guardó `content-language-baseline.json` antes de asignaciones. Verificar después con `npm run cms:baseline -- --language-transition --verify`: compara los mismos campos editoriales, términos, medios y ACF, excluyendo únicamente modified/uri, que pueden cambiar legítimamente al guardar el idioma. La referencia original `content-baseline.json` se conserva intacta.

Guía oficial: [Asignar idioma a contenido existente](https://polylang.pro/documentation/support/guides/assign-a-language-to-an-existing-content/), con edición individual, rápida y masiva en Polylang gratuito.
