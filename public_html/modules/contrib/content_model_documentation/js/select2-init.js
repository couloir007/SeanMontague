(function ($, Drupal, once) {
  Drupal.behaviors.chosen = {
    attach: function (context, settings) {
      once('select2', '#edit-documented-entity', context).forEach(function (element) {
        $(element).select2({no_results_text: "Sorry, no match found!"});
      });
    }
  };
})(jQuery, Drupal, once);