# Propuestas editoriales acotadas en laboratorio —20 octubre2026

Actualización posterior: [transporte HTTP y concurrencia independiente completados](MCP_EDITORIAL_CHANGES_HTTP_2026-10-20.md),48 checks adicionales y117 en la pasada combinada. El prototipo PHP sigue intacto. Este informe conserva el alcance histórico de67 framework+2 guards; sus pendientes HTTP quedaron superados por esa nueva evidencia.

## Resultado

Preparados [contrato](../WORDPRESS_MCP_PROJECT_CHANGES.md) y [prototipo0.1.0-lab](../../wordpress/plugins/alexcuadra-editorial-changes/alexcuadra-editorial-changes.php). **67 comprobaciones WordPress correctas y2 escenarios de guard correctos**. [Resultado saneado](MCP_EDITORIAL_CHANGES_LAB_RESULT_2026-10-20.json).

La prueba demuestra que un suscriptor con permisos específicos puede crear/revisar/actualizar propuestas privadas propias, sin `edit_pages`, `publish_pages` ni `manage_options`. Editor/Administrador por sí solos no autorizan estas abilities; un Editor con capacidades específicas tampoco puede acceder a la propuesta ajena. **Alex no necesita crear otro usuario Editor para este incremento.** No se crearon usuarios ni se ampliaron permisos en el CMS real.

## Entorno y alcance probado

Proyecto Docker independiente `alexcuadra-editorial-change-lab`, sin puertos del host, reutilizando imágenes oficiales fijadas por digest del perfil WordPress7.1.3/PHP8.2.34/Polylang3.9.0/Adapter0.7.1. Solo código y fixtures sintéticos; sin export/configuración/credenciales reales, sin SSH al sitio ni navegador. El guard del prototipo registra cero hooks en entorno production u origen distinto del laboratorio; no se creó ZIP instalable para el sitio.

Los67 checks verifican tres abilities MCP-only; falta de permisos/anónimo; read scope insuficiente; prepare scope sin read insuficiente; rol de preparador subscriber sin edición global; creación privada con dueño/tipo/estado fijos; lectura y actualización propias; versión nueva y rechazos de token viejo; preservación de campos no parcheados; denegación de cambios de autor/estado/ID/idioma/meta/URLs; tipos/límites/UTF-8/controles; origen publicado/sin contraseña/tipo/idioma correcto; aislamiento de usuarios; ES; HTML normalizado; CPT sin rutas REST y exclusión de abilities en descubrimiento REST; registro/descubrimiento/creación/lectura/actualización por meta-tools oficiales desde PHP.

Concurrencia: un filtro exclusivo del harness intercala una segunda llamada autorizada entre la lectura y el UPDATE de la primera. Ambas ejecutan el código y SQL reales: gana la segunda, el compare-and-swap de la primera afecta cero filas y devuelve409; se comprueba que su propuesta no pisa la ganadora. **No equivale a dos solicitudes HTTP simultáneas.** El filtro de prueba se retira después y no forma parte del plugin.

Hash de posts/postmeta/relaciones de seis fuentes sintéticas coincide antes/después de todas las operaciones de propuesta. Al final se modifica explícitamente una fuente solo como fixture: su cambio produce409, no se sobrescribe y retirarla produce404. Esas dos mutaciones de fixture no proceden del prototipo. Revocar scope bloquea propuestas existentes/callback directo. Limpieza verifica retirada de capacidades de todos los usuarios sintéticos.

## Ejecución y límites

```sh
npm run test:mcp-changes
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml \
  -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml \
  -f tests/integration/wordpress-mcp-changes/compose.yaml stop
```

Runner final exit0, PHP lint/shell y `git diff --check` correctos. Inicialmente el harness esperaba status HTTP en el error de permisos y se ajustó al código oficial `ability_invalid_permissions`; también se añadió inicialización REST antes de probar los meta-tools. No se cambió WordPress/adaptador para forzar resultados. WP-CLI muestra avisos de plugins ya activos y WordPress un notice de permiso negativo; bootstrap inicial emitió warning HTTP_HOST antes de instalar el sitio sintético. No se presentan esos logs como libres de avisos.

`httpTransportVerified=false`: faltan sesión HTTP MCP, concurrencia entre procesos independientes, y validación con el plugin WPGraphQL real (solo se prueba la configuración show_in_graphql=false). No se prueba ni existe apply/publicación/creación de traducciones. Historial durable, idempotencia de creación/cuotas y revisión de compatibilidad de futuras operaciones sobre ACF quedan fuera de este prototipo. La cadena de recuperación de fuentes no se altera porque las propuestas nunca se aplican.

Sin nueva instalación/consultas/cambios en CMS real, roles/capacidades/credenciales reales, Workers/contacto/envíos. Editorial Read0.1.0 instalado sigue intacto; paquete/fuente verificados por hashes. No se repitieron sus91 checks ni frontend/build al no cambiarlos. Lab de propuestas detenido al terminar; Docker Desktop y otros proyectos sin detener globalmente. Dev4325/árbol local preservados, navegador no usado ni eludido. TOC visual/429 siguen abiertos.
