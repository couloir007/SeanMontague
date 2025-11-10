'use strict';

((Drupal) => {
  Drupal.theme.checkbox = function () {
    return '<input type="checkbox" class="form-checkbox form-boolean form-boolean--type-checkbox"/>';
  };
})(Drupal);
