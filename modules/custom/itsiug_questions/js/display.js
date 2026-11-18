(function (Drupal, once) {

  'use strict';

  Drupal.behaviors.itsiugDisplayPicker = {

    attach: function (context) {

      once('itsiug-display-picker', 'select[data-itsiug-session-picker]', context)
        .forEach(function (select) {
          select.addEventListener('change', function () {
            if (select.value) {
              window.location.assign(select.value);
            }
          });
        });

    }

  };

})(Drupal, once);
