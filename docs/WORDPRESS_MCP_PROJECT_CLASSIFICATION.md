# Clasificación propia de proyectos en el MCP

Se implementó un candidato local de lectura, separado del plugin0.1.0 instalado y de propuestas rc.2. [Código](../wordpress/experimental/editorial-project-classification/reader.php), [pruebas y límites](qa/MCP_PROJECT_CLASSIFICATION_READER_2026-10-27.md). No se modificó el CMS ni el catálogo real de18herramientas.

## Contrato de lectura

| Herramienta | Entrada | Salida |
| --- | --- | --- |
| alexcuadra/get-public-project-classification | id entero positivo, locale en/es | project_id, locale, categories y technologies asignadas |
| alexcuadra/list-public-project-terms | locale en/es, taxonomy project_category/project_technology; after>=0, limit1–20 opcionales | items y next_after por ID de término |

Término: id, name, slug, taxonomy, locale, translations. Traducción: id/name/slug/locale. Objetos cerrados, sin campos extra, anotaciones readonly=true/destructive=false/idempotent=true. Reutiliza autenticación/read y alexcuadra_read_public_projects; no concede capacidades ni amplía el acceso a cuerpos privados o escritura.

Las claves exactas se contrastaron con la migración aprobada: project_category (jerárquica, ACF237) y project_technology (ACF238). No reutiliza category/post_tag del blog, cambia registros o modifica asignaciones de Makers/aleXtecuenta/otros proyectos.

El catálogo público incluye únicamente términos asignados a proyectos publicados, sin contraseña y del idioma exacto. No incluye términos sin uso, exclusivos de borradores/privados o asignados solo a posts. No devuelve descripciones, counts de WordPress, padres, termmeta, cuerpos o ACF. Para proponer en el futuro términos nunca usados públicamente hará falta un contexto editorial privado distinto; no se amplía este permiso público por continuidad.

Las traducciones se incluyen solo con reciprocidad, misma taxonomía y un testigo público en su idioma. Una traducción existente usada únicamente por un proyecto privado no se revela. Las asignaciones de idioma incorrecto causan rechazo de la lectura completa; no hay fallback de idioma ni inventario parcial ocultando el conflicto.

## Límites y coherencia

Máximo200proyectos públicos por idioma,200términos distintos por taxonomía/idioma y100asignados por taxonomía en una ficha. Se validan los inventarios públicos EN/ES para construir traducciones seguras. Superar cualquier límite devuelve422; un problema en el catálogo de otro proyecto puede bloquear también una ficha válida. Estos límites son explícitos, no una garantía de soportar más inventario.

Las consultas están acotadas y no ofrecen conteos privados. No se declara snapshot transaccional de todas las consultas/cachés de WordPress ni se certifica ausencia de ediciones concurrentes. Se comprueba el estado observado; las garantías de una futura escritura requerirán su revalidación/huella propia.

## Estado y siguiente fase

Candidato exclusivo de laboratorio con guard de flag/local/DB/URL/single-site, también comprobado en permiso de cada llamada. Sin admisión de producción, ZIP, instalación o grant nuevos. Pasan57casos WP/Polylang,22HTTP y9guards. Perfil mínimo sintético; todavía no se ensayó con todos los plugins/opciones del hosting.

El módulo activo de propuestas rc.2 fija exactamente la lista/versiones/main hashes del perfil. Instalar un plugin adicional o actualizar el lector existente invalidaría ese perfil y cerraría sus herramientas de planes. Antes de desplegar:

1. Diseñar paquete versionado y admisión coordinada con el perfil completo; conservar los ZIP sellados.
2. Ensayar coexistencia con lectores/propuestas/recuperación y la propuesta1/v1, sin cambios de fuentes/roles/historia.
3. Preparar respaldo actualizado con pausa del scope privado, ya que el inicial owner-only no contiene el piloto.
4. Preparar preflight de actualización para almacenamiento no vacío: no reutilizar los iniciales ni borrar el plan para hacerlos pasar.
5. Revisar y presentar el paquete y pasos reales antes de instalación. La lectura no necesita un grant adicional si se mantiene el alcance público documentado.

La preparación/aplicación de cambios en asignaciones de proyectos es otra fase pendiente: debe usar IDs/idiomas/taxonomías exactos, before/after y propiedad/versiones/replay del módulo adecuado. Este incremento no prepara planes de clasificación, crea términos o aplica cambios. El escritor público de posts continúa pendiente por separado.
