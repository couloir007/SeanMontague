(function ($, Drupal) {
  Drupal.behaviors.searchApiOpenSolrPassword = {
    attach(context, settings) {
      const defaultValue = settings.opensolr_key ? settings.opensolr_key : '';
      const $passwordInput = $(once('pass-shown', '.pass-show', context));
      $passwordInput.each(function setDefaultValue() {
        this.value = defaultValue;
      });
      if ($passwordInput.length > 0) {
        $passwordInput.after(
          `<span class="opensolr-toggle-password">${Drupal.t('Show')}</span>`,
        );
        $('.opensolr-toggle-password', context).on(
          'click',
          function togglePassword() {
            this.textContent =
              this.textContent === Drupal.t('Show') ? 'Hide' : 'Show';
            $(this)
              .prev()
              .attr('type', function resolveType(index, attr) {
                return attr === 'password' ? 'text' : 'password';
              });
          },
        );
      }
    },
  };
})(jQuery, Drupal);
