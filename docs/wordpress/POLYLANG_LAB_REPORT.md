# Prueba aislada del conector Polylang

Fecha: 18 de octubre de 2026. No se modificó producción, no se copiaron datos ni credenciales reales y el conector de producción permanece instalado sin activar según Alex.

## Entorno ejecutado

WordPress 7.1.3, PHP Apache y CLI 8.5.11, ACF gratuito 6.8.10, WPGraphQL 2.24.2, WPGraphQL for ACF 3.0.0, Polylang 3.8.11 y conector WP GraphQL Polylang 0.7.1. Imágenes oficiales fijadas por digest en el compose. Se verificaron checksums de los cuatro plugins procedentes del directorio WordPress.org; el conector se descargó del tag de GitHub.

Se probaron 20 pares sintéticos EN/ES repartidos entre post, page, project y articles. Cada tipo incluye traducción publicada, borrador, privada, programada y con contraseña. Se añadió un field ACF ficticio, categorías traducidas y usuario Suscriptor autenticado mediante Application Password local. Projects usa capacidades page, Articles post. CPT/grupos se registran mediante código de fixtures: no es una copia de la base de producción ni una prueba de toda la UI de ACF.

El laboratorio solo escucha en 127.0.0.1:8087. El disco virtual de Docker estaba lleno, por lo que se usaron volúmenes temporales en memoria con límites; no se podaron imágenes, contenedores ni volúmenes ajenos. También se ensayó el runner desde un laboratorio nuevo. Hubo avisos de deprecación de WP-CLI en Colors.php; no se detectó un fatal del conector en las consultas ejecutadas. Esto no prueba ausencia de otros errores de PHP.

Al terminar se detuvieron/eliminaron únicamente los contenedores, red y volúmenes del laboratorio y se retiró el manifiesto local de credenciales ficticias. Las imágenes descargadas permanecen en Docker para reproducir la prueba; no se limpiaron recursos ajenos.

## Resultado

**33 de 37 comprobaciones pasan; cuatro fallan por nuestra política de campos ACF en contenido protegido con contraseña.** El runner conserva exit code distinto de cero para no ocultarlas. Los 60 tests normales del frontend también pasan.

Pasan:

- Rechazo anónimo de contenido y de metadata de idiomas con restricción WPGraphQL configurada.
- Identidad del Suscriptor reconocida por HTTP con Application Password.
- Consultas publicadas, filtros de idioma y relaciones EN/ES en los cuatro tipos.
- Borradores, privados y programados no exponen los marcadores sintéticos por translation, translations o consulta directa. El nodo público padre debe resolver para que la prueba sea válida.
- Suscriptor no puede crear contenido ni actualizarlo/cambiar su idioma mediante mutaciones base extendidas.
- Categorías: filtros, traducciones y mutaciones denegadas al Suscriptor.
- El predicado público existente del portfolio rechaza los nodos con contraseña devueltos por el laboratorio.

## Los cuatro fallos: alcance y control

En cada tipo, un registro publicado con contraseña oculta el cuerpo nativo, pero su field ACF ficticio resulta accesible al Suscriptor en la consulta del laboratorio. La prueba exige que ese field tampoco aparezca y por eso falla. No se interpreta como una vulnerabilidad confirmada del conector.

Se repitió el acceso directo con el conector **desactivado**: el resultado es el mismo para los cuatro tipos. Las pruebas de control son estructuralmente idénticas. La integración base/semántica de permisos de los campos ACF debe considerarse por separado; no basta una contraseña de post para tratar esos campos como secretos.

El portfolio ya excluye hasPassword=true y exige status=publish/isRestricted=false en sus controles de contenido autenticado. La prueba utiliza ese mismo predicado con resultados reales. Los nuevos DTOs de traducción deben aplicar esta política tanto a la entidad principal como a cada relación, y no enviar el objeto bruto de GraphQL al navegador ni a caché pública. No guardar secretos en ACF como parte de este modelo editorial.

## Conclusión y continuación

No se reprodujo un bypass de permisos atribuible al conector en las comprobaciones descritas y la combinación de versiones probada ejecuta las consultas. La asimetría observada en revisión estática entre translation/translations no produjo fuga de borradores/privados en este laboratorio.

Esto permite mantener el conector como candidato para un piloto controlado, sin certificar su seguridad general. Primero preparar filtros y DTOs públicos por idioma, comprobar la versión real de Polylang instalada y preservar las URLs de las nueve referencias españolas legacy. Después activar coordinadamente y probar el CMS real antes de habilitar idiomas en los CPT o crear traducciones nuevas. No cambiar a un puente propio por defecto sin una razón adicional y pruebas equivalentes.

No cubierto: todas las combinaciones de roles/plugins, medios, previews editoriales, ACF PRO/Options Pages, ataques de carga, auditoría completa de dependencias, todas las rutas de mutación, seguridad de infraestructura ni caché EN/ES en el frontend (todavía no implementada). Compatibilidad funcional no equivale a seguridad certificada.

## Evidencia y reproducción

- [Matriz ejecutada](POLYLANG_LAB_RESULTS.json)
- [Control con conector activo/inactivo](POLYLANG_PASSWORD_CONTROL_RESULTS.json)
- [Laboratorio reproducible](../../tests/integration/wordpress-polylang/README.md)

Ejecutar desde la raíz `sh tests/integration/wordpress-polylang/run.sh`. Requiere Docker y red para instalar paquetes. No forma parte de CI normal ni sustituye pruebas de integración de la aplicación. El directorio .wrangler contiene únicamente el manifiesto/credenciales ficticias del laboratorio, ignorado por Git; se elimina al cerrar la prueba.
