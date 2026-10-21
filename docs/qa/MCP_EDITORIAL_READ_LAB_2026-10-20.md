# MCP editorial: primer paquete de lectura —20 octubre2026

Actualización posterior: [transporte HTTP completado](MCP_EDITORIAL_HTTP_2026-10-20.md),36 checks adicionales y91 totales correctos. El runner ahora ejecuta framework+HTTP; los límites sobre transporte pendiente descritos abajo pertenecen a este primer informe histórico. El paquete sigue sin instalarse en CMS compartido.

## Resultado

Plugin independiente de lectura0.1.0 preparado, empaquetado y probado con55 comprobaciones correctas en WordPress Docker aislado. El adaptador oficial descubre, describe y ejecuta sus abilities dentro del framework PHP real. No está instalado en el CMS compartido ni disponible como nueva capacidad del chat real. No se asignó ningún permiso allí.

Paquete: [ZIP0.1.0](../../artifacts/wordpress/alexcuadra-editorial-read-0.1.0.zip), [manifest SHA256](../../artifacts/wordpress/alexcuadra-editorial-read-0.1.0.manifest.json), [código](../../wordpress/plugins/alexcuadra-editorial-read/alexcuadra-editorial-read.php), [contrato incluido](../../wordpress/plugins/alexcuadra-editorial-read/README.md). SHA256 del ZIP: `72f1fa195c04ff1da690d2788e287451d52cc210d69d67eb453c869b8922f302`. Se verificó igualdad entre PHP probado dentro de Docker, fuente local y contenido del ZIP. Solo contiene el plugin y su README; no contiene configuración/credenciales/fixtures del sitio.

## Alcance y permisos

| Ability | Entrada | Resultado |
| --- | --- | --- |
| `alexcuadra/list-public-projects` | locale EN/ES obligatorio; page1–100/per_page1–20; búsqueda opcional hasta100 caracteres | Metadatos públicos, orden ID ascendente, siguiente página o null |
| `alexcuadra/get-public-project` | ID entero positivo y locale exacto | ID, título, slug, locale y traducciones públicas recíprocas |

La ficha no expone cuerpos, ACF, autor, correo, opciones o metadatos privados. Buscar opera solo sobre títulos. Se rechazan propiedades arbitrarias/selección de campos/status, límites inválidos y fallback de idioma. Borradores/privados/protegidos/futuros/sin idioma/tipo distinto/no existentes comparten404 en ficha. Una contradicción en el inventario falla sin devolver datos parciales. El límite de página no se presenta como inventario completo cuando aún hay resultados.

Cada ejecución requiere usuario autenticado, `read` y `alexcuadra_read_public_projects`; permiso/entrada se comprueban otra vez dentro del callback. Activación no modifica usuarios/roles ni siquiera para administradores. La asignación/revocación del permiso en las pruebas pertenece solo al suscriptor sintético, y este no obtiene editar páginas ni gestionar opciones. El rol global subscriber queda intacto.

Las definitions están expuestas solo al MCP Adapter (`meta.mcp.public=true`, public/show_in_rest=false), y la prueba REST confirma su exclusión del listado core. La exposición de una definición no concede autorización para ejecutarla. Categoría/registro/permisos siguen el [API oficial de Abilities](https://developer.wordpress.org/apis/abilities-api/php-reference/); opt-in MCP y anotaciones readonly/destructive/idempotent siguen el [adaptador0.7.1](https://github.com/WordPress/mcp-adapter/blob/v0.7.1/docs/guides/creating-abilities.md).

## Laboratorio y comprobaciones

Proyecto Compose `alexcuadra-editorial-read-lab`, sin puertos HTTP del host. Imágenes base fijadas por digest, WordPress7.1.3/PHP8.5, Polylang3.8.11 y MCP Adapter0.7.1. Montajes únicamente del plugin y fixtures sintéticos; no `.dev.vars`, CMS exportado, medios o credenciales reales. Se inició Docker Desktop porque estaba apagado; al terminar se detuvieron únicamente los contenedores de este laboratorio, sin borrar archivos ni detener otros proyectos.

Runner reproducible:

```sh
npm run test:mcp-read
```

Se ejecutó completo con exit0, incluidas instalación/configuración local y repetición sobre fixtures nuevos. Un fixture sin idioma se corrigió para quitar explícitamente el idioma automático de Polylang al repetir pruebas; el plugin no cambia idiomas ni esa configuración del sitio. [Resultado saneado](MCP_EDITORIAL_READ_LAB_RESULT_2026-10-20.json).

Los55 checks incluyen registro/annotations, no permisos automáticos, denegación anónima/suscriptor/admin sin capacidad, callback directo, paginación completa/única con contenido público, ES exacto/estado vacío, búsqueda solo de títulos, DTO sin cuerpos/meta privados, traducciones públicas recíprocas,404, schema/callback invalid inputs, Unicode, inventario contradictorio, traducción no recíproca, CPT ausente, exclusión REST, descubrimiento/schema/ejecución por meta-tools oficiales y revocación. Hash de posts/postmeta/relaciones antes/después de lecturas confirma que no alteran esos registros editoriales sintéticos. No es una auditoría de todos los posibles observadores/plugins de WordPress.

PHP lint y sintaxis shell correctos; `git diff --check` correcto. WP-CLI emite una deprecación de su librería Colors bajo PHP8.5 y WordPress un notice en la prueba negativa de permiso; no se parchearon ni ocultaron los terceros para declararlos limpios. Las aserciones terminan55/55. No se repitieron build/SSR ni237 pruebas JS anteriores, porque no cambió la app frontend ni sus módulos.

## Estado real y límites

El MCP compartido se inspeccionó solo en lectura: siguen las tres abilities core y `core/get-user-info` filtrado a roles devuelve subscriber. No se leyó el perfil ni se atribuyeron otras capacidades al usuario. No se invocaron ediciones, instalación o gestión de permisos en el CMS compartido.

Falta el transporte HTTP MCP/autenticación y la integración completa cliente→servidor en el laboratorio: se probaron el registro y los meta-tools oficiales mediante PHP/WP_Ability y un request REST interno, no una sesión HTTP MCP. Tampoco se certifica una política de membresía/ACL particular del sitio ni la equivalencia completa con WPGraphQL. Revisar esas integraciones antes de instalar/asignar permisos. Paginación por página no es snapshot transaccional ante ediciones concurrentes; títulos se devuelven como datos no confiables.

Sin CMS/roles reales/staging/producción/contacto/envíos modificados. Staging sigue enbbb91792 y TOC local sin publicar, con aceptación visual pendiente. No se reintentó/eludió el navegador. El429 histórico y la revisión visual siguen abiertos.

## Siguiente incremento

Probar transporte HTTP MCP y permisos en el mismo laboratorio, con autenticación sintética, sin abrir un puerto del host. Después presentar el paquete concreto y la asignación mínima antes de instalar/habilitar acceso en CMS compartido, según [plan](../WORDPRESS_MCP_EDITORIAL_PLAN.md). La conexión del adaptador y la petición de continuar no implican por sí solas autorización para ampliar permisos reales o habilitar escritura. ACF/cuerpos/escritura permanecen fuera de este primer paquete.
