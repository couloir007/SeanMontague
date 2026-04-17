/* jshint esversion: 6 */
document.addEventListener('DOMContentLoaded', () => {
  const header = document.getElementById('header');

  if (header) {
    // Listen for the window scroll event
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 60);
    });
  }
});
