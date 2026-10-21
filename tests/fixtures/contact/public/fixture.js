let callbacks;
      window.turnstile = { render(element, options) { callbacks = options; element.textContent = 'Mock Turnstile (no external requests)'; return 'fixture'; }, reset() {} };
      document.querySelector('#verify').onclick = () => callbacks.callback('fixture-token');
      document.querySelector('#expire').onclick = () => callbacks['expired-callback']();
      document.querySelector('#challenge-error').onclick = () => callbacks['error-callback']();
      let attempts = 0;
      window.fetch = async (url) => {
        if (url !== '/api/contact') throw new Error('Fixture blocks external fetch');
        document.querySelector('#attempts').textContent = `${++attempts} local attempts`;
        const outcome = document.querySelector('#outcome').value;
        if (outcome === 'network') throw new Error('Simulated network error');
        const states = { accepted: [202, 'CONTACT_ACCEPTED'], limited: [429, 'CONTACT_RATE_LIMITED'], unknown: [503, 'CONTACT_DELIVERY_UNKNOWN'], unavailable: [503, 'CONTACT_CONFIG'] };
        const [status, code] = states[outcome];
        return Response.json({ code }, { status });
      };
