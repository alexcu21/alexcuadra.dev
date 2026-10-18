import { test } from 'node:test';
import assert from 'node:assert/strict';
import { isWordPressRestProbe } from '../src/data/request-policy.js';

test('REST probes are rejected regardless of path or encoded parameter name', () => {
  for (const path of ['/?rest_route=/wp/v2/users', '/about/?rest_route=', '/?rest%5Froute=%2Fwp%2Fv2']) {
    assert.equal(isWordPressRestProbe(new URL(path, 'https://example.com')), true);
  }
  for (const path of ['/', '/articles/?utm_source=search', '/work/?page=2']) {
    assert.equal(isWordPressRestProbe(new URL(path, 'https://example.com')), false);
  }
});
