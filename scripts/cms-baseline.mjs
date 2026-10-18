import { readFile, writeFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import dotenv from 'dotenv';
import { request } from 'graphql-request';
import { cmsAuthHeaders } from '../src/data/cms-auth.js';

const target = new URL('../docs/wordpress/content-baseline.json', import.meta.url);
async function main() {
  const headers = cmsAuthHeaders(dotenv.parse(await readFile(new URL('../.dev.vars', import.meta.url))));
  if (!headers.has('authorization')) throw new Error('AUTH_REQUIRED');
  const data = await request({ url: 'https://content.alexcuadra.dev/graphql', requestHeaders: headers,
    signal: AbortSignal.timeout(20000), document: `query MigrationBaseline {
      projects(first: 100, where: {status: PUBLISH, hasPassword: false}) {
        pageInfo { hasNextPage }
        nodes { databaseId slug status title content excerpt date modified uri
          featuredImage { node { databaseId sourceUrl } }
          categories { nodes { databaseId slug } } tags { nodes { databaseId slug } }
          projects { projectTagline githuburl websiteurl }
        }
      }
      articles(first: 100, where: {status: PUBLISH, hasPassword: false}) {
        pageInfo { hasNextPage }
        nodes { databaseId slug status title content excerpt date modified uri
          featuredImage { node { databaseId sourceUrl } }
          categories { nodes { databaseId slug } } tags { nodes { databaseId slug } }
          articlesFields { quote }
        }
      }
    }` });
  const records = {};
  for (const kind of ['projects', 'articles']) {
    if (!Array.isArray(data[kind]?.nodes) || data[kind].pageInfo.hasNextPage) throw new Error('INCOMPLETE_BASELINE');
    records[kind] = data[kind].nodes.map(node => ({ id: node.databaseId, slug: node.slug,
      hash: createHash('sha256').update(JSON.stringify(node)).digest('hex') })).sort((a, b) => a.id - b.id);
  }
  if (process.argv.includes('--verify')) {
    const baseline = JSON.parse(await readFile(target, 'utf8'));
    const equal = JSON.stringify(baseline.records) === JSON.stringify(records);
    console.log(equal ? 'PASS: published IDs, slugs and content hashes match baseline.'
      : 'DIFFERENCE: published IDs, slugs or content hashes changed. Inspect before continuing.');
    if (!equal) process.exitCode = 1;
    return;
  }
  await writeFile(target, JSON.stringify({ capturedAt: new Date().toISOString(),
    scope: 'Published, non-password-protected content visible to the service account; not a database backup.', records }, null, 2) + '\n', { flag: 'wx' });
  console.log(`Baseline saved: ${records.projects.length} projects, ${records.articles.length} articles. No editorial bodies stored.`);
}
main().catch(error => {
  console.error(JSON.stringify({ event: 'cms_baseline_failed', httpStatus: error?.response?.status,
    code: error?.code === 'EEXIST' ? 'BASELINE_ALREADY_EXISTS' : ['AUTH_REQUIRED', 'INCOMPLETE_BASELINE'].includes(error?.message)
      ? error.message : 'BASELINE_UNAVAILABLE' }));
  process.exitCode = 1;
});
