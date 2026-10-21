# ACF, Polylang y GraphQL del prototipo — 20 octubre 2026

Actualización posterior: [contrato de aplicación/recuperación definido](../WORDPRESS_MCP_SOURCE_APPLICATION.md), diseño sin código ni abilities nuevas. Runtime0.3 y290 checks de este informe permanecen como última aceptación ejecutada; la siguiente implementación MCP sigue aislada. La [entrega frontend a staging](../STAGING_NEXT_RELEASE.md) es independiente y ya puede prepararse.

## Resultado

`npm run test:mcp-changes-stack` exit0: **290 comprobaciones correctas**,236 existentes (67 framework+44 historial+37 cuotas+86 HTTP+2 guards) y54 de integración ACF/Polylang/WPGraphQL. [Resultado saneado](MCP_EDITORIAL_GRAPHQL_STACK_RESULT_2026-10-20.json), [prueba de integración](../../tests/integration/wordpress-mcp-changes/stack.php).

Editorial Changes sigue en **0.3.0-lab**, sin modificar sus fuentes. Fuente/ZIP Editorial Read0.1.0 y export ACF conservados. No hay instalación, consulta, contenido, permisos o credenciales del CMS compartido alterados; no hace falta Editor. Sin frontend/Workers/contacto/envíos ni navegador. Cambios locales y dev4325 preservados.

## Entorno y campos

Proyecto Docker nuevo `alexcuadra-editorial-graphql-lab`, separado del laboratorio base, sin puertos host. WordPress7.1.3/PHP8.2.34, ACF gratuito6.8.10, WPGraphQL2.24.2, WPGraphQL for ACF3.0.0, Polylang3.9.0, conector wp-graphql-polylang0.7.1 y MCP Adapter0.7.1. Son versiones del inventario local registrado previamente; no se consultó el sitio de nuevo ni se replicó toda su instalación. Imágenes fijadas por digest; plugins por versión/tag, no actualizaciones a latest.

Fixture importa únicamente el grupo Projects del export local del18 de octubre, sin reescribirlo: `group_676d40f7bb646`, `project_tagline`/`field_676dcb2aec8d4` textarea, `githuburl`/`field_676dcd0783231`, `websiteurl`/`field_676dcd1383232`. Nombre GraphQL `projects.projectTagline`. El registro sintético del CPT activa GraphQL con las mismas capacidades de páginas; no es una prueba de la UI de ACF ni un clon de la DB real. Contenido/URLs/usuarios/credenciales son sintéticos.

## Evidencia de integración

- Application Password de suscriptor autentica GraphQL por HTTP Apache; consulta anónima rechazada por la configuración de endpoint restringido del laboratorio.
- ACF `update_field` por field key guarda el valor y la referencia originales. GraphQL devuelve subtítulo/URLs esperados; lectura EN/ES y relaciones de traducción recíprocas funcionan con el conector real. Colecciones con idioma/status/hasPassword explícitos excluyen las fuentes sintéticas no públicas/protegidas.
- Schema inspeccionado con rutas válidas project/projects/node/contentNode/contentNodes y mutaciones createProject/updateProject. Las denegaciones de esas mutaciones no se cuentan como válidas si proceden de un campo/argumento GraphQL desconocido. El suscriptor con scope de preparar propuestas sigue sin poder crear/editar Projects ni cambiar su idioma por esas mutaciones.
- Propuestas EN/ES creadas mediante MCP HTTP, actualización y recuperación del snapshot correctas. Fuente original/ACF privado ausentes del DTO. Fuentes borrador/privada/con contraseña/idioma incorrecto rechazadas por MCP. El CPT de propuestas no se incluye en los tipos traducibles.
- **Privacidad GraphQL:** no hay tipos/rutas/mutaciones de propuestas en el schema inspeccionado. Para dos propuestas propias, los accesos genéricos `contentNode` por ID de DB y `node` por ID global devuelven null tanto al propietario como a otro suscriptor y al administrador sintético. Colecciones genéricas inspeccionadas tampoco las incluyen. Consulta explícita de una colección de propuestas inexistente falla por schema, como se esperaba.
- Crear/actualizar/recuperar propuestas deja idéntica la lectura GraphQL del proyecto fuente. Hash completo de posts/postmeta/relaciones de las seis fuentes coincide antes/después de las operaciones del prototipo.
- El harness cambia deliberadamente un subtítulo usando ACF: invalida la propuesta. Cambiar la referencia de field key invalida la recuperación; restaurar ambos devuelve el hash original. Cambiar la traducción vinculada usando Polylang invalida la propuesta; al restaurar la relación semántica original, se puede consultar otra vez su versión actual. Estas mutaciones de fixture no proceden del prototipo. No se afirma igualdad binaria de las filas de relaciones después de recrearlas.
- Revocar el scope de preparar bloquea MCP y conserva la lectura GraphQL pública autenticada. Limpieza elimina/verifica las Application Passwords generadas (incluida la de administrador sintético) y los scopes de ambos usuarios; solicita cierre de la sesión MCP. No se cambian credenciales reales.

Las236 pruebas existentes también pasan con los plugins de esta combinación, incluidas cuotas/fallos/transacciones/legacy y seis carreras HTTP con procesos independientes. Fuente PHP de propuestas/admisión sin cambios, huellas cotejadas contra el QA anterior; no hubo que ampliar permisos o corregir el prototipo para este stack. El cliente nuevo acepta la notificación MCP202 vacía, coherente con el transporte ya probado.

PHP lint/shell y `git diff --check` correctos. Sin cambio de frontend/paquete de lectura: no se repiten build/SSR ni91 pruebas independientes de lectura. No se ocultaron avisos conocidos de bootstrap/pruebas negativas.

## Ejecución y cierre

```sh
npm run test:mcp-changes-stack
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml \
  -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml \
  -f tests/integration/wordpress-mcp-changes/compose.yaml \
  -f tests/integration/wordpress-mcp-changes/compose.graphql.yaml stop
```

Se detuvo únicamente este laboratorio. Volúmenes tmpfs sintéticos; no hay prueba de backups/pérdida del host ni puertos alternativos para eludir el bloqueo del navegador.

## Límites y siguiente incremento

Verifica interoperabilidad y privacidad del CPT de propuestas en esta combinación. No certifica todos los plugins/hooks/TLS/hosting/cache del sitio compartido ni sustituye la reconciliación de su esquema vigente antes de un futuro apply. Los cuatro casos históricos de ACF en fuentes con contraseña del [laboratorio de seguridad](../wordpress/POLYLANG_LAB_REPORT.md) no se vuelven a ensayar ni se declaran resueltos por esta exclusión de propuestas; son una superficie distinta.

No existe apply/publicación/retirada de fuentes ni creación de Projects traducidos. Tampoco UI/retención/reset/TTL/backup de propuestas o paquete operativo para el CMS real. **Siguiente incremento: contrato local de aplicación controlada y recuperación de fuentes, con autorización distinta de preparar propuestas.** Después se implementará/probará aisladamente antes de presentar cualquier instalación y capacidad concreta del sitio. TOC visual/429 siguen abiertos y contacto desactivado.
