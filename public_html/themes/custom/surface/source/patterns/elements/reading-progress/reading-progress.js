/* jshint esversion: 6 */
(function () {
  const bar = document.getElementById('reading-progress');
  if (!bar) return;
  window.addEventListener('scroll', () => {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
    bar.style.width = pct + '%';
    bar.setAttribute('aria-valuenow', Math.round(pct));
  });
})();
