
// Decorative left illustration pointer-follow effect.
document.addEventListener('DOMContentLoaded', () => {
  const feature = document.querySelector('.policy-feature, .forgot-feature');
  const artwork = feature?.querySelector('.feature-art, .forgot-art');
  if (!feature || !artwork || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  feature.addEventListener('pointermove', event => {
    if (event.pointerType === 'touch') return;
    const bounds = feature.getBoundingClientRect();
    const x = ((event.clientX - bounds.left) / bounds.width - .5) * 22;
    const y = ((event.clientY - bounds.top) / bounds.height - .5) * 18;
    artwork.style.setProperty('--move-x', `${x.toFixed(1)}px`);
    artwork.style.setProperty('--move-y', `${y.toFixed(1)}px`);
  });
  feature.addEventListener('pointerleave', () => {
    artwork.style.setProperty('--move-x', '0px');
    artwork.style.setProperty('--move-y', '0px');
  });
});
