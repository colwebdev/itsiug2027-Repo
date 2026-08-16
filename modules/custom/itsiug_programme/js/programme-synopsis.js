/**
 * @file
 * Synopsis modal for programme cards.
 */

(function (Drupal, once) {
  'use strict';

  var dialog = null;
  var titleEl = null;
  var bodyEl = null;
  var lastTrigger = null;

  function buildDialog() {
    if (dialog) {
      return;
    }
    dialog = document.createElement('div');
    dialog.className = 'programme-synopsis-modal';
    dialog.setAttribute('hidden', 'hidden');
    dialog.innerHTML =
      '<div class="programme-synopsis-modal__backdrop" data-programme-synopsis-close></div>' +
      '<div class="programme-synopsis-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="programme-synopsis-title">' +
      '<button type="button" class="programme-synopsis-modal__close" data-programme-synopsis-close aria-label="' +
      Drupal.t('Close') +
      '">&times;</button>' +
      '<h2 class="programme-synopsis-modal__title" id="programme-synopsis-title"></h2>' +
      '<div class="programme-synopsis-modal__body"></div>' +
      '</div>';
    document.body.appendChild(dialog);
    titleEl = dialog.querySelector('.programme-synopsis-modal__title');
    bodyEl = dialog.querySelector('.programme-synopsis-modal__body');

    dialog.addEventListener('click', function (event) {
      if (event.target.hasAttribute('data-programme-synopsis-close')) {
        closeDialog();
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !dialog.hasAttribute('hidden')) {
        closeDialog();
      }
    });
  }

  function closeDialog() {
    dialog.setAttribute('hidden', 'hidden');
    document.body.classList.remove('programme-synopsis-open');
    if (lastTrigger) {
      lastTrigger.focus();
      lastTrigger = null;
    }
  }

  function openDialog(trigger) {
    var source = trigger.parentNode.querySelector('.programme-synopsis-source');
    if (!source) {
      return;
    }
    lastTrigger = trigger;
    titleEl.textContent = source.querySelector('.programme-synopsis-source__title').textContent;
    bodyEl.innerHTML = source.querySelector('.programme-synopsis-source__body').innerHTML;
    dialog.removeAttribute('hidden');
    document.body.classList.add('programme-synopsis-open');
    dialog.querySelector('.programme-synopsis-modal__close').focus();
  }

  Drupal.behaviors.itsiugProgrammeSynopsis = {
    attach: function (context) {
      var triggers = once('programme-synopsis', '[data-programme-synopsis-open]', context);
      if (!triggers.length) {
        return;
      }
      buildDialog();
      triggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
          openDialog(trigger);
        });
      });
    }
  };
})(Drupal, once);
