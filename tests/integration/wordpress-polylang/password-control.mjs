import { readFile, writeFile } from 'node:fs/promises';
const m = JSON.parse(await readFile(new URL('../../../.wrangler/polylang-lab.json', import.meta.url), 'utf8'));
const reportPath = new URL('../../../docs/wordpress/POLYLANG_PASSWORD_CONTROL_RESULTS.json', import.meta.url);
const headers = { 'Content-Type': 'application/json', Authorization: `Basic ${btoa(`${m.username}:${m.password}`)}` };
async function query(query, variables = {}) {
  const response = await fetch('http://127.0.0.1:8087/graphql', { method: 'POST', headers,
    body: JSON.stringify({ query, variables }), signal: AbortSignal.timeout(15000) });
  return response.json();
}
const schema = await query('query { __type(name: "RootQuery") { fields { name } } }');
const connectorActive = schema.data?.__type?.fields?.some(field => field.name === 'languages') || false;
const checks = [];
for (const f of m.fixtures.filter(item => item.state === 'password')) {
  const root = { post: 'post', page: 'page', project: 'project', articles: 'article' }[f.type];
  const result = await query(`query ($id: ID!) { ${root}(id:$id,idType:DATABASE_ID) {
    hasPassword content securityFixture { note } } }`, { id: String(f.es) });
  if (result.errors?.length) throw new Error('Control query must execute without errors');
  const node = result.data?.[root];
  checks.push({ type: f.type, nodePresent: Boolean(node), passwordProtected: node?.hasPassword,
    bodyMarkerVisible: node?.content?.includes(f.marker) || false,
    acfMarkerVisible: node?.securityFixture?.note?.includes(f.marker) || false });
}
let previous = {};
try { previous = JSON.parse(await readFile(reportPath, 'utf8')); } catch {}
previous[connectorActive ? 'connectorActive' : 'connectorInactive'] = { testedAt: new Date().toISOString(), checks };
await writeFile(reportPath, JSON.stringify(previous, null, 2) + '\n');
console.log(JSON.stringify({ connectorActive, checks }, null, 2));
