# Lector de clasificación de proyectos — 27 octubre 2026

Candidato local con dos abilities de solo lectura: clasificación asignada por proyecto y catálogo de términos usados por proyectos públicos. Usa las taxonomías propias y el scope de lectura de proyectos ya existente, sin modificar paquetes o permisos del CMS. [Contrato/plan](../WORDPRESS_MCP_PROJECT_CLASSIFICATION.md), [recibo con casos y hashes](project-classification-reader-2026-10-27.json).

## Resultado

**88 comprobaciones correctas:**57nativas WordPress/Polylang/MCP Adapter,22HTTP con Apache y aplicación passwords sintéticos,9subprocesos de guard.

Se verificaron EN/ES, IDs y traducciones recíprocas, categorías/tecnologías asignadas, cursor ordenado/terminal, DTOs cerrados, rechazo de category/post_tag/campos extra/idioma ajeno, administración sin scope y scopes de blog/planes sin permiso de proyectos. Se excluyen draft/private/protected/future/trash/page/post/sin idioma, términos sin uso o exclusivos de blog/borrador, metadatos/cuerpos/descripciones y traducciones sin testigo público. Publicar/retirar un testigo sintético añade/retira su traducción en la siguiente lectura. Asignación con idioma contradictorio, taxonomía compartida con posts o ausente y límites100/200rechazan sin devolver datos parciales.

La prueba de100asignaciones se verifica con101; el límite global de200términos se supera distribuyendo201entre tres proyectos y el límite de200proyectos con201públicos. Se restaura el fixture antes del ensayo HTTP. El fingerprint de tablas editoriales se compara durante las lecturas, separando las mutaciones deliberadas del fixture.

HTTP real comprueba contraseña de aplicación, sesión, catálogo/esquema, clasificaciones/traducciones/cursor, privacidad, permisos, usuario cruzado, revocación de scope/credencial y cierre de sesión. Las credenciales y scopes sintéticos quedan retirados. No hubo credenciales/correos/solicitudes al hosting.

Los primeros dos ensayos se detuvieron por un fixture que añadía la capacidad a una instancia distinta de WP_User y no recargaba el usuario actual. El error real era ability_invalid_permissions, con dependencias/inventario listos. Se corrigió el fixture forzando el cambio de usuario antes de volver a cargarlo; no se relajó la autorización. Un ensayo completo pasó57+22; después se reforzó el guard también en la función de permiso y el ensayo final pasó57+22más9guards. Los guards verifican que no se registran hooks ni se permite una llamada fuera del entorno local.

## Entorno, conservación y límites

WP7.1.3/PHP8.5.11/Polylang3.9.1/Adapter0.7.1/MariaDB11.8.9, imágenes/dos ZIP fijados por hashes. Base/configuración/cuentas/objetos sintéticos nuevos, sin SQL/wp-config/datos del hosting. Reutiliza la definición de imágenes del laboratorio anterior con proyecto/red propios. Ocho contenedores propios confirmados detenidos y cuatro redes internas vacías, sin puertos de host o prune. [Limpieza y hashes](project-classification-reader-2026-10-27.json).

No se declara compatibilidad con el perfil completo de17plugins del CMS ni con sus opciones/cachés. No hay snapshot global atómico de publicación concurrente. El catálogo se acota a términos con testigo público: no sirve como catálogo editorial de términos no publicados. No se hicieron mutaciones reales, llamadas MCP reales, deploys, grants o respaldos nuevos del hosting. Propuesta1/v1, paquetes sellados y lectores instalados intactos.

Siguiente: empaquetado/versionado y ensayo de coexistencia/admisión del perfil completo antes de instalación. rc.2 bloquea cualquier plugin activo desconocido, de modo que no debe subirse el candidato o actualizar el lector real por continuidad. Las propuestas de asignación y las escrituras públicas no se implementan en este incremento.
