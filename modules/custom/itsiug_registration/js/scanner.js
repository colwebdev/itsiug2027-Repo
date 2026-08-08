(function (Drupal) {

  'use strict';

  Drupal.behaviors.itsiugScanner = {

    attach: function (context) {

      const scannerElement =
        context.querySelector('#itsiug-qr-reader');

      if (!scannerElement) {
        return;
      }

      // Prevent the scanner from being initialized more than once.
      if (scannerElement.dataset.initialized === 'true') {
        return;
      }

      scannerElement.dataset.initialized = 'true';

      const resultElement =
        context.querySelector('#itsiug-scan-result');

      if (typeof Html5Qrcode === 'undefined') {

        if (resultElement) {
          resultElement.innerHTML =
            '<p>QR scanner library could not be loaded.</p>';
        }

        return;
      }

      const html5QrCode =
        new Html5Qrcode('itsiug-qr-reader');

      function onScanSuccess(decodedText) {

        // Stop the camera after the first successful scan.
        html5QrCode.stop()
          .catch(function () {
            // Camera may already have stopped.
          });

        if (resultElement) {
          resultElement.innerHTML =
            '<p><strong>QR Code detected.</strong></p>' +
            '<p>Processing...</p>';
        }

        /*
         * Obtain a Drupal CSRF token.
         *
         * Drupal provides /session/token for this purpose.
         */
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
      qr_code: decodedText,
      test_date: new URLSearchParams(window.location.search).get('test_date')
    })
  }
);

          })

          .then(function (response) {
            return response.json();
          })

          .then(function (data) {

            if (!resultElement) {
              return;
            }

            if (data.success) {

              resultElement.innerHTML =
                '<div class="itsiug-scan-success">' +
                  '<h2>✓ Scan Successful</h2>' +
                  '<p><strong>' +
                    Drupal.checkPlain(data.delegate) +
                  '</strong></p>' +
                  '<p>' +
                    Drupal.checkPlain(data.institution) +
                  '</p>' +
                  '<p>' +
                    Drupal.checkPlain(data.message) +
                  '</p>' +
                '</div>';

            }
            else {

              resultElement.innerHTML =
                '<div class="itsiug-scan-error">' +
                  '<h2>Scan Not Processed</h2>' +
                  '<p>' +
                    Drupal.checkPlain(data.message) +
                  '</p>' +
                '</div>';

            }

          })

          .catch(function (error) {

            console.error(error);

            if (resultElement) {
              resultElement.innerHTML =
                '<div class="itsiug-scan-error">' +
                  '<h2>Scanner Error</h2>' +
                  '<p>Unable to process the QR code.</p>' +
                '</div>';
            }

          });
      }

      function onScanFailure() {
        // Normal while the camera is searching.
      }

      // Prefer the rear-facing camera on mobile devices.
      html5QrCode.start(
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
        onScanSuccess,
        onScanFailure
      )

      .catch(function () {

        if (resultElement) {
          resultElement.innerHTML =
            '<h2>Camera unavailable</h2>' +
            '<p>Please allow camera access in your browser.</p>';
        }

      });

    }

  };

})(Drupal);