# Clasificación propia de proyectos en el MCP

**27 octubre — actualización coordinada de clasificación cerrada:** after-scope real ready=true/19checks, nueve capacidades originales restauradas y hashes/conteos1/1/1/1 conservados. [Recibo CLI](qa/project-classification-upgrade-destination-after-scope-2026-10-27.json). Conector5llamadas: get/replay exactos del documento original1/v1, inventario único tras replay y metadatos públicos228 idénticos al piloto inicial; ninguna propuesta nueva o publicación. [Cierre y límites](qa/MCP_PROJECT_CLASSIFICATION_UPGRADE_COMPLETE_2026-10-27.md). Catálogo20 y lecturas EN/ES4categorías/20tecnologías ya verificadas; respaldo con piloto validado por helpers rc.2/rc.3. No más instalación/activación/grant/backups por esta aceptación. Pendientes planes/escritura de clasificación y escritor público de posts, frontend/contacto/CMS429/QA final.

**27 octubre — artefacto rc.2 verificado con helpers rc.3 en el hosting:** Alex aporta verify ready=true/integrityVerified=true, conteos1/1/1/1 y sin escrituras de BD; no restauración sobre el sitio vivo. [Recibo](qa/project-classification-upgrade-backup-verify-rc3-2026-10-27.json). Siguiente restituir únicamente alexcuadra_prepare_post_changes al usuario2 y after-scope del nuevo preflight con historySHA75a0aa5f…60c7835a; ocho capacidades previas/roles/historia deben conservarse. Después get/replay del piloto existente por MCP, sin crear un plan nuevo. No repetir instalación, activación, backup o DDL. Restitución real todavía pendiente de Alex.

**27 octubre — lector activo y lecturas MCP reales verificadas:** classifier-enabled de Alex ready=true/19checks; rc.3+lector exactos/activos, historia/documento/conteos originales y ocho capacidades previas, scope privado pausado. [Recibo](qa/project-classification-upgrade-destination-classifier-enabled-2026-10-27.json). Conector: catálogo20, get146EN/196ES con1categoría/8tecnologías y reciprocidad, catálogos4categorías/20tecnologías por idioma con IDs del mapa aprobado;9llamadas sin mutaciones. [Evidencia/límites](qa/MCP_PROJECT_CLASSIFICATION_LIVE_2026-10-27.md). Siguiente: subir dos helpers rc.3 a private-tools/project-classification-upgrade y verificar el respaldo rc.2 deb1593d…ab76110 con ellos antes de restaurar solo el scope privado. No reinstalar, crear planes o cambiar clasificaciones.

**27 octubre — carpeta del lector conciliada, instalación inactiva verificada:** classifier-installed de Alex ready=true/19checks; tres archivos exactos de rc.1, lector inactivo/sin sus herramientas, rc.3 activo y misma historia/documento/conteos1/1/1/1. [Recibo](qa/project-classification-upgrade-destination-classifier-installed-2026-10-27.json). Se cierra el estado desconocido de la carpeta, no se identifica cuándo se instaló; no reinstalar/forzar/borrar. Siguiente opt-in AC_EDITORIAL_PROJECT_CLASSIFICATION_SITE exacto + activar solo lector + classifier-enabled con historySHA original. Mantener scope privado pausado y ocho capacidades anteriores; sin nuevos grants/DDL/asignaciones.

**27 octubre — instalación del lector detenida por carpeta existente:** SHA del ZIP48d0734c…a4dfe2d correcto; WP-CLI rechaza destino ya existente sin --force y la cadena no ejecuta preflight. [Observación](qa/project-classification-install-existing-directory-2026-10-27.json). No se conocen aún integridad/activación de esa carpeta ni su origen; no asumir instalación correcta o parcial. Siguiente solo classifier-installed de lectura con historySHA75a0aa5f…60c7835a, manteniendo scope pausado. No reintentar, forzar, borrar, activar o restaurar permisos. Se observó mensaje de purga de cachés; no se afirma ausencia global de efectos del instalador/plugins.

**27 octubre — propuestas rc.3 activas y conservadas en el hosting:** Alex aporta proposal-upgraded ready=true/19checks, ZIP exacto SHA581dc9a0…cb00df. HistorySHA75a0aa5f…60c7835a/documentoSHA54945c84…7f6b3d/conteos1/1/1/1 intactos; ocho capacidades previas y scope privado pausado. [Recibo](qa/project-classification-upgrade-destination-proposal-upgraded-2026-10-27.json). Siguiente: instalar inactivo solo lector rc.1 SHA48d0734c…a4dfe2d, sin --force/activación, y classifier-installed con SHA de historia original. No repetir actualización de propuestas, DDL, enroll o backup.

**27 octubre — respaldo actualizado real verificado:** Alex aporta verify ready=true/integrityVerified=true y conteos1/1/1/1 del artefacto deb1593d…ab76110. [Recibo](qa/project-classification-upgrade-backup-verify-2026-10-27.json). Mantener scope privado pausado. Siguiente: revisar/subir ZIP exacto de propuestas rc.3, comprobar SHA581dc9a0…cb00df, desactivar únicamente propuestas rc.2, sustituir código mediante actualización explícita de esa versión conocida, reactivar y proposal-upgraded con historySHA75a0aa5f…60c7835a. Aún sin lector nuevo, DDL, enrolamientos o restauración de permisos. Actualización real pendiente de Alex.

**27 octubre — respaldo actualizado real recibido:** export ready=true de Alex, backup-20261027T200039Z-1e211a74ec3e7502, manifestSHAdeb1593d…ab76110;1propietario/1propuesta/1revisión/1recibo, sin escrituras de BD. [Recibo](qa/project-classification-upgrade-backup-export-2026-10-27.json). El helper exige scope privado pausado y ocho capacidades previas, sin grant a roles/otros usuarios. Siguiente verify del mismo artefacto/SHA con helper rc.2; mantener pausa y no instalar paquetes todavía. Conservar historySHA75a0aa5f…60c7835a, sin recapturar before; no restaurar el CMS vivo para demostrar respaldo.

**27 octubre — before real del paquete coordinado correcto:**19checks, rc.2 exacto/activo, lector nuevo ausente y nueve capacidades originales. [Recibo](qa/project-classification-upgrade-destination-before-2026-10-27.json). Documento SHA54945c84…7f6b3d coincide con pilotoMCP; historySha75a0aa5f…60c7835a también coincide con el derivado de su petición/recibo originales.1propietario/1propuesta/1revisión/1recibo. Conservar este SHA en todas las fases; no recapturarlo. Siguiente: Alex retira solo alexcuadra_prepare_post_changes y exporta un respaldo nuevo con los helpers rc.2 existentes; sin instalar ZIPs o cambiar otros permisos todavía. Pausa/exportación real aún pendientes.

**27 octubre — paquete coordinado de clasificación preparado y probado:** lector0.1.0-rc.1 + perfil de propuestas0.1.0-rc.3; ZIPs separados/reproducibles, sin cambios de las cinco unidades de negocio/tablas. Conservación de plan sintético1/v1/get/replay/roles/fuente y rollback rc.2 correctos;33checks/8preflights/6ops,257regresiones,39recuperación/4CLI y14guards. [Paquete/paso vigente](WORDPRESS_MCP_PROJECT_CLASSIFICATION_PACKAGE.md), [QA/límites](qa/MCP_PROJECT_CLASSIFICATION_BUNDLE_2026-10-27.md). CMS real sigue rc.2/catálogo18/propuesta1v1. Siguiente: Alex sube solo preflight.php nuevo y ejecuta --use-include before, sin instalar/grants todavía; verificar hash del piloto e historia no vacía. Respaldo nuevo real pendiente antes de actualización.

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

## Estado actual: lectura instalada y actualización cerrada

Clasificación rc.1 y propuestas rc.3 ya están activos en el CMS. Catálogo20, asignaciones146EN/196ES y catálogos4categorías/20tecnologías por idioma verificados por MCP. After-scope conservó las nueve capacidades originales y la historia del piloto1/v1; get/replay exactos y metadatos públicos del artículo sin cambios. [Cierre real](qa/MCP_PROJECT_CLASSIFICATION_UPGRADE_COMPLETE_2026-10-27.md). No repetir instalación o permisos.

Esta fase completa la lectura propia de proyectos. Propuestas de asignaciones, creación de términos y aplicación siguen pendientes; el catálogo público no amplía acceso a términos usados solo en contenido privado. El escritor público de posts es una fase separada.

## Histórico: preparación del paquete coordinado

El prototipo conserva sus fuentes. Ya se derivaron los ZIP del lector rc.1 y de propuestas rc.3, con admisión exacta coordinada y recuperación de artefactos rc.2. Coexistencia, historia sintética no vacía y rollback ensayados con el perfil completo. [Procedimiento vigente y primer preflight real](WORDPRESS_MCP_PROJECT_CLASSIFICATION_PACKAGE.md), [QA](qa/MCP_PROJECT_CLASSIFICATION_BUNDLE_2026-10-27.md). Instalación del hosting y respaldo actualizado de su plan real pendientes. No instalar el prototipo ni repetir controles iniciales que exigían tablas vacías.

## Histórico: lector local y plan que motivó el paquete

Candidato exclusivo de laboratorio con guard de flag/local/DB/URL/single-site, también comprobado en permiso de cada llamada. Sin admisión de producción, ZIP, instalación o grant nuevos. Pasan57casos WP/Polylang,22HTTP y9guards. Perfil mínimo sintético; todavía no se ensayó con todos los plugins/opciones del hosting.

El módulo activo de propuestas rc.2 fija exactamente la lista/versiones/main hashes del perfil. Instalar un plugin adicional o actualizar el lector existente invalidaría ese perfil y cerraría sus herramientas de planes. Antes de desplegar:

1. Diseñar paquete versionado y admisión coordinada con el perfil completo; conservar los ZIP sellados.
2. Ensayar coexistencia con lectores/propuestas/recuperación y la propuesta1/v1, sin cambios de fuentes/roles/historia.
3. Preparar respaldo actualizado con pausa del scope privado, ya que el inicial owner-only no contiene el piloto.
4. Preparar preflight de actualización para almacenamiento no vacío: no reutilizar los iniciales ni borrar el plan para hacerlos pasar.
5. Revisar y presentar el paquete y pasos reales antes de instalación. La lectura no necesita un grant adicional si se mantiene el alcance público documentado.

La preparación/aplicación de cambios en asignaciones de proyectos es otra fase pendiente: debe usar IDs/idiomas/taxonomías exactos, before/after y propiedad/versiones/replay del módulo adecuado. Este incremento no prepara planes de clasificación, crea términos o aplica cambios. El escritor público de posts continúa pendiente por separado.
