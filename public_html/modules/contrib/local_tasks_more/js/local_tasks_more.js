/**
 * @file
 * Local Tasks More behaviors.
 */

((Drupal, once) => {
  // Determine if local storage exists and is enabled.
  // This approach is copied from Modernizr.
  // @see https://github.com/Modernizr/Modernizr/blob/c56fb8b09515f629806ca44742932902ac145302/modernizr.js#L696-731
  let hasLocalStorage;
  try {
    localStorage.setItem('has_local_storage', '');
    localStorage.removeItem('has_local_storage');
    hasLocalStorage = true;
  } catch (e) {
    hasLocalStorage = false;
  }

  const lessIcon =
    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path fill="#000000" d="M7.951 7.645c-.193.196-.193.516 0 .71l3.258 3.29c.193.193.191.519-.002.709l-1.371 1.371c-.193.192-.512.191-.707 0l-5.335-5.371c-.194-.194-.194-.514 0-.708l5.335-5.369c.195-.195.514-.195.707-.001l1.371 1.371c.193.194.195.513.002.709l-3.258 3.289z"/></svg>';
  const lessTitle = Drupal.t('Show less');

  const moreIcon =
    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path fill="#000000" d="M8.053 8.355c.193-.195.193-.517 0-.711l-3.26-3.289c-.193-.195-.192-.514.002-.709l1.371-1.371c.194-.194.512-.193.706.001l5.335 5.369c.195.195.195.515 0 .708l-5.335 5.37c-.194.192-.512.193-.706.002l-1.371-1.371c-.194-.195-.195-.514-.002-.709l3.26-3.29z"/></svg>';
  const moreTitle = Drupal.t('Show more');

  Drupal.behaviors.localTasksMore = {
    attach(context) {
      once(
        'local-tasks-more-toggle',
        '.local-tasks-more-toggle',
        context,
      ).forEach((element) => {
        const type = element.getAttribute('data-local-tasks-more');
        const key = `local_tasks_more_${type}`;
        let visible = Boolean(
          (hasLocalStorage && localStorage.getItem(key)) || '',
        );

        /**
         * Set the show more/less state of local tasks.
         */
        function setLocalTasks() {
          // Store the toggle state of the local tasks.
          if (hasLocalStorage) {
            localStorage.setItem(key, visible ? '1' : '');
          }

          // Toggle tabs.
          element.parentElement
            .querySelectorAll('.local-tasks-more-tab')
            .forEach((tab) => {
              tab.style.display = visible ? 'block' : 'none';
            });

          // Toggle the 'show/less' more link icon, title, and aria-expanded.
          const a = element.querySelector('a');
          a.innerHTML = visible ? lessIcon : moreIcon;
          a.setAttribute('title', visible ? lessTitle : moreTitle);
          a.setAttribute('aria-expanded', visible ? 'true' : 'false');
        }

        // Initialize local tasks.
        setLocalTasks();

        // Add event handler to toggle local tasks.
        element.addEventListener('click', (event) => {
          visible = !visible;
          setLocalTasks();
          event.preventDefault();
        });
      });
    },
  };
})(Drupal, once);
