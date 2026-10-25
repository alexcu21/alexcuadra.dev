// CSS can blur an element before matchMedia fires when its region is hidden.
// Remember keyboard focus, but discard it when the user clicks elsewhere.
export function mountResponsiveFocus({ root, media, narrow, wide, update = (_desktop) => {} }) {
  let remembered = root.activeElement;
  const remember = event => { remembered = event.target; };
  const clear = () => { remembered = null; };
  const pointer = event => {
    const active = root.activeElement;
    remembered = active !== root.body && (active === event.target || active?.contains?.(event.target)) ? active : null;
  };
  const change = event => {
    const outgoing = event.matches ? narrow : wide;
    const incoming = event.matches ? wide : narrow;
    const active = root.activeElement;
    const focused = active === root.body ? remembered : active;
    const move = outgoing.region.contains(focused);
    update(event.matches);
    if (move) incoming.target.focus();
  };
  update(media.matches);
  root.addEventListener('focusin', remember);
  root.addEventListener('pointerdown', pointer);
  root.defaultView?.addEventListener('blur', clear);
  media.addEventListener('change', change);
  return () => {
    root.removeEventListener('focusin', remember);
    root.removeEventListener('pointerdown', pointer);
    root.defaultView?.removeEventListener('blur', clear);
    media.removeEventListener('change', change);
  };
}
