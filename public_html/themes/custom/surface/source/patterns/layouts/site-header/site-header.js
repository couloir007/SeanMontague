document.addEventListener('DOMContentLoaded', () => {

  const siteBranding = document.querySelector('.site-header .site-branding');

  const sitePrimary = document.querySelector('.site-primary__content');

  // Listen for the window scroll event
  window.addEventListener('scroll', () => {
    if (window.scrollY > 0) {
      // Add the class that triggers the fade/slide-out transition
      siteBranding.classList.add('primary-hidden');
      sitePrimary.classList.add('primary-hidden');
    } else {
      // Remove the class, fading/sliding back in
      siteBranding.classList.remove('primary-hidden');
      sitePrimary.classList.remove('primary-hidden');
    }
  });
});
