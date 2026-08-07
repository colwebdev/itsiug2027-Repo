/**
 * @file
 * Default JavaScript file for Modal Page using Dialog API.
 */

(function ($, cookies, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.modalPageDialogApi = {
    attach: function (context, settings) {

      // Get Modals to Show.
      var modals = $('.js-modal-page-show', context);

      // Verify if there is Modal.
      if (!modals.length) {
        return false;
      }

      var rightButtonCallback = function() {
        var modal = $(this);
        var id_modal = $('#modal_id', modal).val();
        var checkbox_please_do_not_show_again = $('#dnsa-' + id_modal);
        var modalSettings = drupalSettings.modal_page.modals[id_modal] ?? {};

        if (checkbox_please_do_not_show_again.is(':checked')) {
          var cookieTime = modalSettings['#cookie_expiration'] ?? 10000;
          var cookieSettings = { path: '/' };
          // If it's 0, don't add expire date. Expire at end of session.
          if (cookieTime > 0) {
            cookieSettings.expires = parseInt(cookieTime);
          }
          cookies.set('please_do_not_show_again_modal_id_' + id_modal, true, cookieSettings);
        }

        $(this).dialog('close');

        // URL to send data.
        var urlModalSubmit = "/modal/ajax/hook-modal-submit";

        var dontShowAgainOption = checkbox_please_do_not_show_again.is(':checked');

        var modalState = new Object();
        modalState.dont_show_again_option = dontShowAgainOption;

        // Params to be sent.
        var params = new Object();

        // Send Modal ID.
        params.id = id_modal;

        // Send Modal State.
        params.modal_state = modalState;

        $.post(urlModalSubmit, params, function (result) {

          // Check the 'success' flag in the JSON response.
          if (result.success) {

            var redirect = modalSettings['#enable_redirect_link'] ?? false;

            if (redirect && modalSettings['#redirect_link']) {
              window.location.replace(modalSettings['#redirect_link']);
            }
          }
          else {
            console.error('Modal Ajax submit error:', result.message);
          }
        }, "json");
      };

      // Foreach in all Modals.
      $(modals).each(function () {

        // Get default variables.
        var modal = $(this);

        var id_modal = $('#modal_id', modal).val();
        var modalSettings = drupalSettings.modal_page.modals[id_modal] ?? {};
        var show_once = modalSettings['#modal_page_show_once'] ?? false;

        // Remove the cookie if show only once option is disabled.
        if (!show_once) {
          cookies.remove('hide_modal_id_' + id_modal);
        }

        // Get cookies for do not show again option and show only once.
        var hide_modal_cookie = cookies.get('hide_modal_id_' + id_modal) || cookies.get('please_do_not_show_again_modal_id_' + id_modal);

        // Verify don't show again and show only once options.
        if (hide_modal_cookie) {
          return;
        }

        // Verify auto-open.
        var auto_open = modalSettings['#auto_open'] ?? true;

        // handle header
        var enable_modal_header = modalSettings['#enable_modal_header'] ?? true;
        var modal_title = modalSettings['#title'] ?? '';
        var display_title = modalSettings['#display_title'] ?? true;
        var display_button_x_close = modalSettings['#display_button_x_close'] ?? true;

        if (!enable_modal_header) {
          display_title = false;
          display_button_x_close = false;
        }

        var dialogClass = [];

        // Hide close button X
        if (!display_button_x_close) {
          dialogClass.push('no-close');
        }

        // Hide full header.
        if (!enable_modal_header) {
          dialogClass.push('no-header');
        }

        // Handle footer.
        var footerButtons = [];
        var enable_left_button = modalSettings['#enable_left_button'] ?? false;
        var enable_right_button = modalSettings['#enable_right_button'] ?? false;

        if (enable_left_button) {
          footerButtons.push({
            text: modalSettings['#left_label_button'] ?? 'Dismiss',
            click: function() {
              $(this).dialog('close');
            },
            class: modalSettings['#left_button_class'] ?? ''
          })
        }

        if (enable_right_button) {
          footerButtons.push({
            text: modalSettings['#button'] ?? 'Ok',
            click: rightButtonCallback,
            class: modalSettings['#ok_button_class'] ?? ''
          })
        }

        var classes = {};
        if (modalSettings['#modal_header_classes']) {
          classes['ui-dialog-titlebar'] = modalSettings['#modal_header_classes'];
        }

        if (modalSettings['#modal_footer_classes']) {
          classes['ui-dialog-buttonpane'] = modalSettings['#modal_footer_classes'];
        }

        classes['ui-dialog-buttonpane'] = classes['ui-dialog-buttonpane'] ? classes['ui-dialog-buttonpane'] + ' modal-page-ui-dialog-buttonset' : 'modal-page-ui-dialog-buttonset';


        if (modalSettings['#top_right_button_class']) {
          classes['ui-dialog-titlebar-close'] = modalSettings['#top_right_button_class'];
        }

        var modalOpions = {
          title: display_title ? modal_title : null,
          dialogClass: dialogClass.join(' '),
          classes: classes,
          width: 'auto',
          closeOnEscape: (modalSettings['#close_modal_esc_key'] ?? "true") === "true",
          buttons: footerButtons,
          maxHeight: 1500 // Setting this to maximum value as it getting set to 0 in gin theme.
        };
        var dailogModal = Drupal.dialog(modal, modalOpions);

        // Auto hide modal
        modal.on('dialogopen', function () {

          // Move do not show checkbox to fotter pane
          $('#modal-dont-show-again-container').appendTo($('.ui-dialog-buttonpane'))

          $(this).find(".js-modal-page-ok-buttom").first().focus();

          var auto_hide = modalSettings['#modal_page_auto_hide'] ?? false;
          var auto_hide_delay = modalSettings['#modal_page_auto_hide_delay'] ?? 0;
          if (auto_hide) {
            setTimeout(function () {
              dailogModal.close();
            }, auto_hide_delay * 1000);
          }
        });

        modal.on('dialogclose', function () {
          if (show_once) {
            cookies.set('hide_modal_id_' + id_modal, true, { expires: 365 * 20, path: '/' });
          }
        });

        // Open Modal Page clicking on "open-modal-page" class.
        $('.open-modal-page', modal).on('click', function (e) {
          e.preventDefault();
          dailogModal.showModal();
        });

        // Open Modal Page clicking on user custom element.
        const open_modal_on_element_click = modalSettings['#open_modal_on_element_click'] ?? "";
        if (open_modal_on_element_click !== "") {
          $(open_modal_on_element_click).on('click', function (e) {
            e.preventDefault();
            dailogModal.showModal();
          });
        }

        // Open Modal on Auto Open.
        if (auto_open == true) {

          // Verify if the modal should be trigged by height instead of time
          var enable_show_on_height = modalSettings['#enable_show_on_height'] ?? false;
          if (enable_show_on_height) {
            var offsetHeight = modalSettings['#height_offset'] ?? 0;

            // Namespace guarantee we are only working with events of one modal, even on the DOM document.
            var namespace = '.' + modalSettings['#id_desc'] ?? 'modal';

            // If page has no scrollbar show the modal.
            if ($(document).height() == $(window).height()) {
              dailogModal.showModal();
              $(document).off(namespace);
              return;
            }

            // Check if is using pixels or percentage, if using percentage
            // remove the % symbol, if using pixels convert to percentage.
            if (!$.isNumeric(offsetHeight)) {
              offsetHeight = offsetHeight.slice(0, -1);
            } else {
              offsetHeight = (offsetHeight / $(document).height()) * 100;
            }
            $(document).on('scroll' + namespace, function () {
              // Account for the offset touch option
              // 0 to start, 0.5 to center, 1 to end.
              var offsetTouch = modalSettings['#height_offset_touch'] ?? '';

              // Getting the position of the scroll on the page according to
              // the page total size and window size.
              var positionY = Math.round(
                ($(window).scrollTop() / ($(document).height() - $(window)
                  .height())) * 100
              );

              var windowPercentage = Math.round(($(window).height() / $(document).height()) * 100);

              // Adjusting the scroll position by the offsetTouch.
              positionY = positionY + (offsetTouch * windowPercentage);

              if (positionY >= offsetHeight) {
                dailogModal.showModal();
                $(document).off(namespace);
              }
            });
          }
          else {
            // Verify if there is a delay to show Modal.
            var delay = modalSettings['#delay_display'] * 1000;
            setTimeout(function () {
              dailogModal.showModal();
            }, delay);
          }
        }

      });
    }
  };
})(jQuery, window.Cookies, Drupal, drupalSettings);
