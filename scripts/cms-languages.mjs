import { readFile, writeFile } from 'node:fs/promises';
import dotenv from 'dotenv';
import { request } from 'graphql-request';
import { cmsAuthHeaders } from '../src/data/cms-auth.js';

async function main() {
  const headers = cmsAuthHeaders(dotenv.parse(await readFile(new URL('../.dev.vars', import.meta.url))));
  if (!headers.has('authorization')) throw new Error('AUTH_REQUIRED');
  const run = document => request({ url: 'https://content.alexcuadra.dev/graphql', document,
    requestHeaders: headers, signal: AbortSignal.timeout(15000) });
  const types = ['Page', 'Project', 'Article', 'Post', 'Category'];
  const connections = ['Page', 'Project', 'Article', 'Post', 'Category'];
  const document = `query LanguageSchemaCheck {
    root: __type(name: "RootQuery") { fields { name } }
    ${types.map(type => `${type}: __type(name: "${type}") { fields { name } }`).join('\n')}
    ${connections.map(type => `${type}Where: __type(name: "RootQueryTo${type}ConnectionWhereArgs") { inputFields { name } }`).join('\n')}
  }`;
  const schema = await run(document);
  const rootFields = schema.root?.fields?.map(field => field.name) || [];
  const metadata = Object.fromEntries(types.map(type => [type, {
    language: schema[type]?.fields?.some(field => field.name === 'language') || false,
    translations: schema[type]?.fields?.some(field => field.name === 'translations') || false,
    languageFilter: schema[`${type}Where`]?.inputFields?.some(field => field.name === 'language') || false,
  }]));
  const rootReady = ['languages', 'defaultLanguage'].every(name => rootFields.includes(name));
  let languages;
  if (rootReady) languages = await run('query ConfiguredLanguages { languages { code name } defaultLanguage { code name } }');
  const ready = rootReady && Object.values(metadata).every(item => Object.values(item).every(Boolean))
    && languages?.defaultLanguage?.code?.toLowerCase() === 'en'
    && ['en', 'es'].every(code => languages?.languages?.some(language => language.code.toLowerCase() === code));
  const result = { checkedAt: new Date().toISOString(), rootReady, metadata, languages: languages || null,
    schemaReady: ready, note: 'Schema checks only; translation/ACF isolation, auth and routing still require a paired-content test.' };
  await writeFile(new URL('../docs/wordpress/LANGUAGE_SCHEMA_STATUS.md', import.meta.url),
    '# Estado del esquema de idiomas\n\nComprobación autenticada de solo lectura; sin contenidos ni credenciales.\n\n```json\n'
    + JSON.stringify(result, null, 2) + '\n```\n');
  console.log(JSON.stringify(result, null, 2));
  if (process.argv.includes('--require-ready') && !ready) process.exitCode = 1;
}
main().catch(error => {
  console.error(JSON.stringify({ event: 'language_check_failed', httpStatus: error?.response?.status,
    code: error?.message === 'AUTH_REQUIRED' ? 'AUTH_REQUIRED' : 'LANGUAGE_CHECK_UNAVAILABLE' }));
  process.exitCode = 1;
});
