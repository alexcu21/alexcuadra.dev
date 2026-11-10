import test from 'node:test';
import assert from 'node:assert/strict';
import { contactCopy, contactTextKeys } from '../src/data/contact-content.js';
const copy = () => ({ ...Object.fromEntries(contactTextKeys.map(key => [key, 'CMS text'])), privacy_url: 'https://alexcuadra.dev/privacy/' });
test('contact copy requires all CMS fields without inventing defaults or exposing unknown fields', () => {
  const input = { ...copy(), recipient: 'private@example.com' };
  assert.equal(contactCopy(input).recipient, undefined);
  for (const key of contactTextKeys) assert.equal(contactCopy({ ...input, [key]: ' ' }), null);
  assert.equal(contactCopy(null), null);
});
test('contact privacy link rejects script URLs, credentials and insecure protocols', () => {
  for (const url of ['javascript:alert(1)', 'http://example.com', 'https://user:pass@example.com', '/privacy']) {
    assert.equal(contactCopy({ ...copy(), privacy_url: url }), null);
  }
  assert.equal(contactCopy(copy()).privacy_url, 'https://alexcuadra.dev/privacy/');
});
