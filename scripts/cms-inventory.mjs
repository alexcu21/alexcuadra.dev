import { readFile, writeFile } from 'node:fs/promises';
import dotenv from 'dotenv';
import { request } from 'graphql-request';
import { cmsAuthHeaders } from '../src/data/cms-auth.js';

// Read-only schema inventory. Never print credentials, raw errors or editorial bodies.
const endpoint = 'https://content.alexcuadra.dev/graphql';
async function main() {
  const config = dotenv.parse(await readFile(new URL('../.dev.vars', import.meta.url)));
  const headers = cmsAuthHeaders(config);
  if (!headers.has('authorization')) throw new Error('AUTH_REQUIRED');
  const query = document => request({ url: endpoint, document, requestHeaders: headers,
    signal: AbortSignal.timeout(15000) });
  const schema = await query(`query InventorySchema {
    contentType: __type(name: "ContentType") { fields { name args { name } type { kind name ofType { kind name } } } }
    root: __type(name: "RootQuery") { fields { name type { kind name ofType { kind name } } } }
  }`);
  if (!schema.contentType || !schema.root) throw new Error('SCHEMA_UNAVAILABLE');
  const allowed = ['name', 'label', 'graphqlSingleName', 'graphqlPluralName', 'isPublic',
    'isHierarchical', 'isPubliclyQueryable', 'showInRest', 'showUi', 'hasArchive', 'restBase'];
  const fields = schema.contentType.fields.filter(field => allowed.includes(field.name)
    && field.args.length === 0 && ['SCALAR', 'ENUM'].includes(field.type.kind === 'NON_NULL'
      ? field.type.ofType.kind : field.type.kind)).map(field => field.name);
  if (!fields.includes('name')) throw new Error('CONTENT_TYPE_NAME_UNAVAILABLE');
  const result = await query(`query InventoryContentTypes { contentTypes(first: 100) {
    nodes { ${fields.join(' ')} } pageInfo { hasNextPage } } }`);
  const roots = schema.root.fields.filter(field => ['projects', 'articles', 'pages', 'categories'].includes(field.name));
  const output = { generatedAt: new Date().toISOString(), endpoint,
    contentTypes: result.contentTypes.nodes, contentTypesTruncated: result.contentTypes.pageInfo.hasNextPage,
    frontendRootFields: roots };
  const report = '# Inventario de esquema WordPress\n\n'
    + 'Lectura autenticada y sin modificaciones. No incluye contenido editorial ni credenciales. '
    + 'Los nombres internos proceden del esquema activo; no identifican por sí solos al plugin que los registra.\n\n'
    + '```json\n' + JSON.stringify(output, null, 2) + '\n```\n\n'
    + 'Pendiente inspeccionar el plugin custom, grupos ACF exportados y configuración de registro para decidir '
    + 'si basta conservar las claves post_type al pasar el registro a ACF. No desactivar ni desinstalar el plugin.\n';
  await writeFile(new URL('../docs/CMS_SCHEMA_INVENTORY.md', import.meta.url), report);
  console.log(`Inventoried ${output.contentTypes.length} content types; report: docs/CMS_SCHEMA_INVENTORY.md`);
}
main().catch(error => {
  console.error(JSON.stringify({ event: 'cms_inventory_failed', httpStatus: error?.response?.status,
    code: ['AUTH_REQUIRED', 'SCHEMA_UNAVAILABLE', 'CONTENT_TYPE_NAME_UNAVAILABLE'].includes(error?.message)
      ? error.message : 'INVENTORY_UNAVAILABLE' }));
  process.exitCode = 1;
});
