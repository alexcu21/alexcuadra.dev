// Binding only; no public proxy, remote endpoint or caller-supplied credentials.
export async function coordinateCmsOrigin(binding, key, seconds) {
  if (!binding || !/^[a-f0-9]{64}$/.test(key)) throw new Error('CMS_COORDINATOR_UNAVAILABLE');
  const stub = binding.get(binding.idFromName('cms-origin-v1'));
  const response = await stub.fetch(`https://cms-origin.internal/${seconds === undefined ? 'check' : 'report'}`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(seconds === undefined ? { key } : { key, seconds }),
    signal: AbortSignal.timeout(2000),
  });
  if (response.status !== 200) throw new Error('CMS_COORDINATOR_UNAVAILABLE');
  const body = await response.text();
  if (body.length > 128) throw new Error('CMS_COORDINATOR_UNAVAILABLE');
  const value = JSON.parse(body);
  if (!value || Object.keys(value).join(',') !== 'retryAfter'
    || !Number.isSafeInteger(value.retryAfter) || value.retryAfter < 0) throw new Error('CMS_COORDINATOR_UNAVAILABLE');
  return value.retryAfter;
}
