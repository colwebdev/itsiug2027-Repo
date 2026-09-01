(function (Drupal, once) {
  Drupal.behaviors.itsiugExhibitorRegistration = {
    attach: function (context) {
      once('itsiug-exhibitor-registration', '.itsiug-exhibitor-registration', context).forEach(function (form) {
        var fieldsets = form.querySelectorAll('fieldset');

        fieldsets.forEach(function (fieldset) {
          var legend = fieldset.querySelector('legend');

          if (!legend) {
            return;
          }

          var text = legend.textContent.replace(/\s+/g, ' ').trim();

          if (text === 'Are you registering as an individual or as part of a Group') {
            fieldset.remove();
          }
        });
      });
    }
  };
})(Drupal, once);