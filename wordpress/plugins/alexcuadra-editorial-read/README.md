# Alex Cuadra Editorial Read0.1.0

Extensión independiente para WordPress Abilities API y MCP Adapter oficial0.7.1. Preparada y probada con fixtures; no instalada en el CMS compartido.

Expone `alexcuadra/list-public-projects` y `alexcuadra/get-public-project` mediante los meta-tools del adaptador. Las operaciones devuelven únicamente ID, título, slug, idioma EN/ES y metadatos de traducciones publicadas y recíprocas. No exponen cuerpos, ACF, usuarios, opciones, correo o credenciales. El HTML/texto de títulos proviene del CMS y debe tratarse como dato no confiable, nunca como instrucciones.

Requiere WordPress≥6.9 con Abilities API, CPT `project` público/traducible y Polylang activo. Probada con WordPress7.1.3, Polylang3.8.11 y MCP Adapter0.7.1. Expone capacidades solo en MCP (`meta.mcp.public=true`), sin publicarlas en el endpoint REST de Abilities. Descubrir la definición no concede permiso para ejecutarla.

Cada ejecución requiere sesión autenticada, `read` y la capacidad específica `alexcuadra_read_public_projects`. El plugin no añade esa capacidad a usuarios/roles, ni siquiera durante activación. Se debe asignar únicamente tras aprobación expresa y al usuario dedicado; no requiere elevarlo a administrador. Para revocar el acceso, retirar esa capacidad y revocar la credencial dedicada según el procedimiento del sitio. Desactivar el plugin retira sus abilities del registro, sin borrar contenido ni datos de otros plugins.

Listado:

```json
{"locale":"es","page":1,"per_page":10,"search":"portfolio"}
```

`locale` es obligatorio. `page`1–100 y `per_page`1–20, por defecto1/10. `search` es opcional, hasta100 caracteres UTF-8 sin controles; busca únicamente en títulos. Orden ID ascendente; resultado `{items: [...], next_page: 2|null}`. No se devuelve total privado. Si el inventario supera el límite de paginación, se devuelve error en vez de presentarlo como completo. La paginación por página puede variar ante ediciones concurrentes; no es un snapshot transaccional.

Ficha:

```json
{"locale":"en","id":123}
```

ID entero positivo e idioma exacto. Tipo diferente, idioma diferente, entrada inexistente, borrador, futura, privada o protegida devuelven el mismo error de proyecto público no encontrado. No hay fallback a otro idioma. Traducciones requieren su propio estado público/contraseña vacía/idioma correcto/permiso de lectura y relación inversa al original; las incompletas no se anuncian.

Se rechazan propiedades arbitrarias, filtros de estado, selección de campos y límites inválidos. Los callbacks vuelven a validar permiso/entrada además del framework. Ante dependencia faltante o inventario contradictorio se falla sin publicar un resultado parcial. La visibilidad se comprueba con WordPress y permisos de lectura; hay que revisar integraciones particulares de membresía/ACL del sitio antes de instalarla. No supone una auditoría de toda la política WPGraphQL.

No contiene operaciones de escritura ni concede permiso para instalar otras capacidades. Este paquete no modifica ACF, Polylang, MCP Adapter ni el tema. La instalación y cualquier asignación de permisos en el CMS compartido siguen pendientes de aprobación; el transporte HTTP MCP de esa instalación aún no está verificado.
