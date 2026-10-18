import assert from 'node:assert/strict';
import { readFile, writeFile } from 'node:fs/promises';
import { isPublicContent } from '../../../src/data/content-visibility.js';

const manifest = JSON.parse(await readFile(new URL('../../../.wrangler/polylang-lab.json', import.meta.url), 'utf8'));
const endpoint = 'http://127.0.0.1:8087/graphql';
const authorization = `Basic ${btoa(`${manifest.username}:${manifest.password}`)}`;
const results = [];
async function query(document, variables = {}, authenticated = true) {
  const response = await fetch(endpoint, { method: 'POST', redirect: 'error',
    headers: { 'Content-Type': 'application/json', ...(authenticated ? { Authorization: authorization } : {}) },
    body: JSON.stringify({ query: document, variables }), signal: AbortSignal.timeout(20000) });
  const body = await response.text();
  let parsed;
  try { parsed = JSON.parse(body); } catch { throw new Error(`NON_JSON_RESPONSE_HTTP_${response.status}`); }
  const errors = parsed.errors?.map(item => item.message).join(' ') || '';
  assert.doesNotMatch(errors, /Cannot query field|Unknown argument|Unknown type|Syntax Error|not defined by type/i,
    'GraphQL contract unsupported; authorization assertions would be invalid');
  return parsed;
}
async function check(name, fn) {
  try { await fn(); results.push({ name, status: 'PASS' }); console.log(`PASS ${name}`); }
  catch (error) {
    // Keep fixture secrets and raw response bodies out of the report.
    results.push({ name, status: 'FAIL', reason: error.code || error.name || 'Error' });
    console.log(`FAIL ${name}: ${error.code || error.name || 'Error'}`);
  }
}
const roots = { post: ['post', 'posts', 'Post'], page: ['page', 'pages', 'Page'],
  project: ['project', 'projects', 'Project'], articles: ['article', 'articles', 'Article'] };
const fields = 'id databaseId title content status isRestricted hasPassword language { code } securityFixture { note }';

await check('anonymous language metadata rejected', async () => {
  const result = await query('query { languages { code } }', {}, false);
  assert.ok(result.errors?.length && !result.data?.languages);
  assert.match(result.errors.map(item => item.message).join(' '), /authenticat/i);
});
await check('category translations and language filters resolve consistently', async () => {
  const result = await query(`query ($id: ID!) {
    category(id: $id, idType: DATABASE_ID) { id databaseId language { code }
      translation(language: ES) { databaseId language { code } }
      translations { databaseId language { code } } }
    categories(where: {language: EN}) { nodes { databaseId language { code } } }
  }`, { id: String(manifest.terms.en) });
  assert.ok(!result.errors);
  assert.equal(result.data.category.translation.databaseId, manifest.terms.es);
  assert.equal(result.data.category.translation.language.code, 'ES');
  assert.ok(result.data.category.translations.some(term => term.databaseId === manifest.terms.es));
  assert.ok(result.data.categories.nodes.every(term => term.language.code === 'EN'));
});
await check('subscriber cannot create or change category language', async () => {
  const initial = await query('query ($id: ID!) { category(id:$id,idType:DATABASE_ID) { id name language { code } } }',
    { id: String(manifest.terms.en) });
  const update = await query('mutation ($id: ID!) { updateCategory(input:{ id:$id, name:"UNAUTHORIZED_TERM", language:ES }) { category { databaseId } } }',
    { id: initial.data.category.id });
  const create = await query('mutation { createCategory(input:{ name:"UNAUTHORIZED_TERM", language:ES }) { category { databaseId } } }');
  assert.ok(update.errors?.length && !update.data?.updateCategory?.category);
  assert.ok(create.errors?.length && !create.data?.createCategory?.category);
  const after = await query('query ($id: ID!) { category(id:$id,idType:DATABASE_ID) { name language { code } } }',
    { id: String(manifest.terms.en) });
  assert.equal(after.data.category.language.code, 'EN');
  assert.equal(after.data.category.name, initial.data.category.name);
});

await check('anonymous content rejected', async () => {
  const result = await query('query { posts { nodes { title } } }', {}, false);
  assert.ok(result.errors?.length);
  assert.match(result.errors.map(item => item.message).join(' '), /authenticat/i);
  assert.ok(!result.data?.posts);
});
await check('subscriber identity authenticated', async () => {
  const result = await query('query { viewer { databaseId } languages { code } }');
  assert.equal(result.data?.viewer?.databaseId, manifest.readerId);
  assert.ok(result.data.languages.some(language => language.code === 'EN'));
  assert.ok(result.data.languages.some(language => language.code === 'ES'));
});
for (const [type, [root, connection, graphType]] of Object.entries(roots)) {
  await check(`${type}: collections isolate EN/ES and exclude protected content`, async () => {
    for (const locale of ['EN', 'ES']) {
      const result = await query(`query { ${connection}(first: 100, where: { language: ${locale}, status: PUBLISH, hasPassword: false }) {
        nodes { databaseId language { code } } } }`);
      assert.ok(!result.errors);
      const nodes = result.data[connection].nodes;
      for (const fixture of manifest.fixtures.filter(item => item.type === type)) {
        assert.equal(nodes.some(node => node.databaseId === fixture.en), locale === 'EN');
        assert.equal(nodes.some(node => node.databaseId === fixture.es), locale === 'ES' && fixture.state === 'publish');
      }
      assert.ok(nodes.every(node => node.language?.code === locale));
    }
  });
  for (const fixture of manifest.fixtures.filter(item => item.type === type)) {
    await check(`${type}/${fixture.state}: translation, translations and direct read respect visibility`, async () => {
      const result = await query(`query ($id: ID!) { ${root}(id: $id, idType: DATABASE_ID) {
        ${fields} translation(language: ES) { ${fields} } translations { ${fields} }
      } }`, { id: String(fixture.en) });
      assert.equal(result.data?.[root]?.databaseId, fixture.en, 'public parent must resolve for a valid visibility test');
      if (fixture.state === 'publish') {
        assert.ok(!result.errors);
        assert.equal(result.data[root].translation.databaseId, fixture.es);
        assert.match(result.data[root].translation.content, new RegExp(fixture.marker));
        assert.equal(result.data[root].translation.securityFixture.note, `${fixture.marker}_ACF`);
        assert.ok(result.data[root].translations.some(item => item.databaseId === fixture.es));
      } else {
        assert.ok(!JSON.stringify(result).includes(fixture.marker), 'non-public content or ACF field leaked');
        const direct = await query(`query ($id: ID!) { ${root}(id: $id, idType: DATABASE_ID) { ${fields} } }`, { id: String(fixture.es) });
        assert.ok(!JSON.stringify(direct).includes(fixture.marker), 'direct non-public content or ACF field leaked');
      }
    });
  }
  await check(`${type}: portfolio guard rejects live password-protected result`, async () => {
    const fixture = manifest.fixtures.find(item => item.type === type && item.state === 'password');
    const result = await query(`query ($id: ID!) { ${root}(id: $id, idType: DATABASE_ID) { ${fields} } }`,
      { id: String(fixture.es) });
    assert.ok(!result.errors);
    assert.equal(result.data[root].hasPassword, true);
    assert.equal(isPublicContent(result.data[root]), false);
  });
  await check(`${type}: subscriber cannot create or change language through mutations`, async () => {
    const fixture = manifest.fixtures.find(item => item.type === type && item.state === 'publish');
    const initial = await query(`query ($id: ID!) { ${root}(id: $id, idType: DATABASE_ID) { id title language { code } } }`, { id: String(fixture.en) });
    const gid = initial.data[root].id;
    const update = await query(`mutation ($id: ID!) { update${graphType}(input: {id: $id, language: ES, title: "UNAUTHORIZED_LAB_CHANGE"}) {
      ${root} { databaseId } } }`, { id: gid });
    assert.ok(update.errors?.length && !update.data?.[`update${graphType}`]?.[root]);
    const create = await query(`mutation { create${graphType}(input: {title: "UNAUTHORIZED_LAB_CREATION", status: PUBLISH, language: ES}) {
      ${root} { databaseId } } }`);
    assert.ok(create.errors?.length && !create.data?.[`create${graphType}`]?.[root]);
    const after = await query(`query ($id: ID!) { ${root}(id: $id, idType: DATABASE_ID) { title language { code } } }`, { id: String(fixture.en) });
    assert.equal(after.data[root].title, initial.data[root].title);
    assert.equal(after.data[root].language.code, 'EN');
  });
}
const report = { testedAt: new Date().toISOString(), versions: manifest.versions,
  scope: 'Isolated synthetic fixtures. Not a complete security audit; no production data or credentials used.',
  total: results.length, passed: results.filter(item => item.status === 'PASS').length, results };
await writeFile(new URL('../../../docs/wordpress/POLYLANG_LAB_RESULTS.json', import.meta.url), JSON.stringify(report, null, 2) + '\n');
console.log(`${report.passed}/${report.total} checks passed`);
if (report.passed !== report.total) process.exitCode = 1;
