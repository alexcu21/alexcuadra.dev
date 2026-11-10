type Turnstile = {
  render(element: HTMLElement, options: Record<string, unknown>): string;
  reset(id: string): void;
};
declare global { interface Window { turnstile?: Turnstile; } }
let challengeLoader: Promise<Turnstile> | undefined;
function loadChallenge(): Promise<Turnstile> {
  if (window.turnstile) return Promise.resolve(window.turnstile);
  return challengeLoader ??= new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
    script.async = true;
    script.onload = () => window.turnstile ? resolve(window.turnstile) : reject(new Error('CHALLENGE'));
    script.onerror = () => reject(new Error('CHALLENGE'));
    document.head.append(script);
  });
}
export async function initializeContactForm(form: HTMLFormElement) {
  form.addEventListener('submit', event => event.preventDefault());
  const button = form.querySelector<HTMLButtonElement>('button')!;
  const status = form.querySelector<HTMLElement>('[role="status"]')!;
  const challenge = form.querySelector<HTMLElement>('[data-contact-challenge]')!;
  let token = '', busy = false, uncertain = false, hasResult = false;
  let api: Turnstile, widget: string;
  const announce = (key: string, focus = false) => { status.textContent = form.dataset[key] || ''; if (focus) status.focus(); };
  try {
    api = await loadChallenge();
    widget = api.render(challenge, { sitekey: form.dataset.sitekey, action: 'contact', language: form.dataset.locale, size: 'flexible',
      callback: (value: string) => { token = value; button.disabled = busy || uncertain; if (!busy && !hasResult) status.textContent = ''; },
      'expired-callback': () => { token = ''; button.disabled = true; if (!busy && !hasResult) announce('challenge'); },
      'error-callback': () => { token = ''; button.disabled = true; if (!busy && !hasResult) announce('challenge'); },
    });
  } catch { announce('unavailable'); return; }
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy || uncertain) return;
    if (!form.reportValidity()) { announce('invalid'); return; }
    if (!token) { announce('challenge', true); return; }
    busy = true; button.disabled = true; announce('sending');
    const data = new FormData(form);
    const payload = { name: data.get('name'), email: data.get('email'), message: data.get('message'),
      locale: form.dataset.locale, turnstileToken: token };
    let outcome = 'unknown';
    try {
      const response = await fetch('/api/contact', { method: 'POST', credentials: 'omit', redirect: 'error',
        headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload), signal: AbortSignal.timeout(25000) });
      const result = await response.json();
      if (response.status === 202 && result.code === 'CONTACT_ACCEPTED') { outcome = 'success'; form.reset(); }
      else if (result.code === 'CONTACT_DELIVERY_UNKNOWN') outcome = 'unknown';
      else if (response.status === 429) outcome = 'limited';
      else if (response.status === 403) outcome = 'challenge';
      else if (response.status === 400 || response.status === 413) outcome = 'invalid';
      else outcome = 'unavailable';
    } catch { /* The provider may have accepted the message; never retry automatically. */ }
    finally {
      uncertain = outcome === 'unknown'; hasResult = true; busy = false; token = ''; button.disabled = true;
      announce(outcome, true);
      if (!uncertain) api.reset(widget);
    }
  });
}
