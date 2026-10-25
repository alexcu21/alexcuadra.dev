// Content has no CSS hidden state. WAAPI overlays are removed on finish,
// preference changes and navigation, so hover transforms remain independent.
export function mountPageMotion({ root, preference, animate, inView }) {
  const played = new WeakSet();
  const active = new Map();
  let stopObserving = () => {};
  const reset = () => {
    stopObserving();
    stopObserving = () => {};
    for (const release of [...active.values()]) release();
  };
  const setup = () => {
    reset();
    if (preference.matches) return;
    const targets = [...root.querySelectorAll('[data-motion]')].filter(element => !played.has(element));
    if (!targets.length) return;
    stopObserving = inView(targets, element => {
      if (preference.matches || played.has(element)) return;
      played.add(element);
      const hero = element.dataset.motion === 'hero';
      const delay = Math.min(.18, Math.max(0, Number(element.dataset.motionDelay) || 0));
      const originals = ['opacity', 'transform'].map(name => [name, element.style.getPropertyValue(name), element.style.getPropertyPriority(name)]);
      const restore = () => {
        for (const [name, value, priority] of originals) {
          if (value) element.style.setProperty(name, value, priority);
          else element.style.removeProperty(name);
        }
      };
      try {
        const control = animate(element, {
          // Keep the main heading readable throughout; cards never become hidden.
          opacity: hero ? [1, 1] : [.65, 1],
          transform: ['translateY(12px)', 'translateY(0px)'],
        }, { duration: hero ? .42 : .32, delay, ease: [.22, 1, .36, 1] });
        const release = () => {
          if (!active.has(control)) return;
          active.delete(control);
          control.cancel();
          restore();
        };
        active.set(control, release);
        control.then(release);
      } catch {
        // Unsupported or interrupted animation must not block content or links.
        restore();
      }
    }, { amount: .15 });
  };
  preference.addEventListener('change', setup);
  setup();
  return () => {
    preference.removeEventListener('change', setup);
    reset();
  };
}
