(function (Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.itsiugScanner = {

    attach: function (context) {

      const scannerElement =
        context.querySelector('#itsiug-qr-reader');

      const badgeMessageElement =
        context.querySelector('#itsiug-badge-message');

      const resultElement =
        context.querySelector('#itsiug-scan-result');

      const settings =
        drupalSettings.itsiugRegistration || {};

      const badgeQrCode =
        settings.qrCode || '';

      const testDate =
        settings.testDate || '';

      /*
       * ------------------------------------------------------------
       * BADGE SELF-SCAN
       * ------------------------------------------------------------
       */

      if (badgeQrCode !== '') {

        processQrCode(
          badgeQrCode,
          testDate,
          badgeMessageElement || resultElement,
          false
        );

        return;
      }

      /*
       * ------------------------------------------------------------
       * STAFF CAMERA SCANNER
       * ------------------------------------------------------------
       */

      if (!scannerElement || !resultElement) {
        return;
      }

      if (scannerElement.dataset.initialized === 'true') {
        return;
      }

      scannerElement.dataset.initialized = 'true';

      /*
       * Keep the current scanner instance here so it can
       * be stopped and restarted.
       */
      let scanner = null;

      /*
       * ------------------------------------------------------------
       * START CAMERA
       * ------------------------------------------------------------
       */

      function startCamera() {

        resultElement.innerHTML =
          '<p>Starting camera...</p>';

        scanner = new Html5Qrcode(
          'itsiug-qr-reader'
        );

        scanner.start(
          {
            facingMode: 'environment'
          },
          {
            fps: 10,
            qrbox: {
              width: 250,
              height: 250
            }
          },
          function (decodedText) {

            scanner.stop()
              .catch(function () {});

            resultElement.innerHTML =
              '<p><strong>QR Code detected.</strong></p>' +
              '<p>Processing...</p>';

            processQrCode(
              decodedText,
              new URLSearchParams(
                window.location.search
              ).get('test_date'),
              resultElement,
              true
            );

          },
          function () {
            // Normal while camera is searching.
          }
        )

        .catch(function (error) {

          console.error(error);

          resultElement.innerHTML =
            '<h2>Camera unavailable</h2>' +
            '<p>Please allow camera access in your browser.</p>';

        });

      }

      /*
       * ------------------------------------------------------------
       * SCAN ANOTHER BUTTON
       * ------------------------------------------------------------
       *
       * Event delegation means this works even though the
       * button is inserted into the page later.
       */

      document.addEventListener(
        'click',
        function (event) {

          const button =
            event.target.closest(
              '#itsiug-scan-again'
            );

          if (!button) {
            return;
          }

          event.preventDefault();

          button.disabled = true;

          startCamera();

        }
      );

      /*
       * Start the first scan.
       */
      if (typeof Html5Qrcode === 'undefined') {

        resultElement.innerHTML =
          '<p>QR scanner library could not be loaded.</p>';

        return;
      }

      startCamera();

    }

  };

  /*
   * --------------------------------------------------------------
   * PROCESS QR CODE
   * --------------------------------------------------------------
   */

  function processQrCode(
    qrCode,
    testDate,
    resultElement,
    showScanAgain
  ) {

    if (!resultElement) {
      return;
    }

    resultElement.innerHTML =
      '<p><strong>Processing...</strong></p>';

    fetch('/session/token')

      .then(function (response) {
        return response.text();
      })

      .then(function (token) {

        return fetch(
          '/register/scanner/process',
          {
            method: 'POST',

            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': token
            },

            body: JSON.stringify({
              qr_code: qrCode,
              test_date: testDate || null
            })
          }
        );

      })

      .then(function (response) {
        return response.json();
      })

      .then(function (data) {

        if (data.success) {

          resultElement.innerHTML =
            '<div class="itsiug-scan-success">' +

              '<h2>✓ Scan Successful</h2>' +

              '<p><strong>' +
                Drupal.checkPlain(
                  data.delegate || ''
                ) +
              '</strong></p>' +

              '<p>' +
                Drupal.checkPlain(
                  data.institution || ''
                ) +
              '</p>' +

              '<p>' +
                Drupal.checkPlain(
                  data.message || ''
                ) +
              '</p>' +

              (
                showScanAgain
                  ? '<p>' +
                    '<button type="button" ' +
                    'id="itsiug-scan-again" ' +
                    'class="button button--primary">' +
                    'Scan Another Delegate' +
                    '</button>' +
                    '</p>'
                  : ''
              ) +

            '</div>';

        }
        else {

          resultElement.innerHTML =
            '<div class="itsiug-scan-error">' +

              '<h2>Unable to process badge</h2>' +

              '<p>' +
                Drupal.checkPlain(
                  data.message || ''
                ) +
              '</p>' +

              (
                showScanAgain
                  ? '<p>' +
                    '<button type="button" ' +
                    'id="itsiug-scan-again" ' +
                    'class="button">' +
                    'Scan Another Delegate' +
                    '</button>' +
                    '</p>'
                  : ''
              ) +

            '</div>';

        }

      })

      .catch(function (error) {

        console.error(error);

        resultElement.innerHTML =
          '<div class="itsiug-scan-error">' +

            '<h2>Scanner Error</h2>' +

            '<p>Unable to process the QR code.</p>' +

            (
              showScanAgain
                ? '<p>' +
                  '<button type="button" ' +
                  'id="itsiug-scan-again" ' +
                  'class="button">' +
                  'Scan Another Delegate' +
                  '</button>' +
                  '</p>'
                : ''
            ) +

          '</div>';

      });

  }

})(Drupal, drupalSettings);