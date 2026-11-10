import { test } from 'node:test';
import assert from 'node:assert/strict';
import { contactVersionReadiness, latestActiveVersions } from '../src/data/contact-readiness.js';

const expected = { CONTACT_FORM_ENABLED: 'false', CONTACT_ORIGIN: 'https://staging.example.test', CONTACT_FROM_EMAIL: 'contact@forms.alexcuadra.dev', TURNSTILE_SITE_KEY: 'public-key' };
const fixture = () => ({ metadata: { author_email: 'private-author@example.test' }, resources: { bindings: [
  { name: 'CONTACT_EMAIL', type: 'send_email', allowed_sender_addresses: [expected.CONTACT_FROM_EMAIL], destination_address: 'private-destination@example.test' },
  { name: 'CONTACT_RATE_LIMITER', type: 'ratelimit' },
  { name: 'CONTACT_COORDINATOR', type: 'durable_object_namespace', class_name: 'ContactCoordinator' },
  ...['CONTACT_TO_EMAIL', 'TURNSTILE_SECRET_KEY', 'CONTACT_DEDUP_SECRET'].map(name => ({ name, type: 'secret_text', text: 'PRIVATE_SECRET' })),
  ...Object.entries(expected).map(([name, text]) => ({ name, type: 'plain_text', text })),
] } });

test('readiness projects only booleans and never returns metadata, addresses or secret values', () => {
  const result = contactVersionReadiness(fixture(), expected);
  assert.equal(result.configurationReady, true);
  assert.doesNotMatch(JSON.stringify(result), /PRIVATE_SECRET|private-author|private-destination|public-key|forms\.alexcuadra/);
});
test('missing secrets, wrong binding types, foreign namespace and enabled form fail readiness', () => {
  for (const mutate of [
    bindings => bindings.splice(bindings.findIndex(item => item.name === 'CONTACT_DEDUP_SECRET'), 1),
    bindings => { bindings[0].type = 'plain_text'; },
    bindings => { bindings[2].script_name = 'another-worker'; },
    bindings => { bindings.find(item => item.name === 'CONTACT_FORM_ENABLED').text = 'true'; },
    bindings => { bindings[0].allowed_sender_addresses = ['other@example.test']; },
  ]) {
    const value = fixture(); mutate(value.resources.bindings);
    assert.equal(contactVersionReadiness(value, expected).configurationReady, false);
  }
  assert.throws(() => contactVersionReadiness({}, expected), /METADATA_UNAVAILABLE/);
});
test('audit inspects every active version of the newest deployment without modifying input', () => {
  const id = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
  const deployments = [{ created_on: '2026-10-17', versions: [{ version_id: id, percentage: 100 }] },
    { created_on: '2026-10-18', versions: [{ version_id: id, percentage: 60 }, { version_id: 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', percentage: 40 }] }];
  assert.equal(latestActiveVersions(deployments).length, 2);
  assert.equal(deployments[0].created_on, '2026-10-17');
  assert.throws(() => latestActiveVersions([{ created_on: '2026-10-18', versions: [{ version_id: id, percentage: 30 }] }]), /UNAVAILABLE/);
  assert.throws(() => latestActiveVersions([]), /UNAVAILABLE/);
});
