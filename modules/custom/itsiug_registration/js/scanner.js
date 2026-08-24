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

      /*
       * ------------------------------------------------------------
       * BADGE SELF-SCAN
       * ------------------------------------------------------------
       */

      if (badgeQrCode !== '') {

        processQrCode(
          badgeQrCode,
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
    resultElement,
    showScanAgain
  ) {

    if (!resultElement) {
      return;
    }

    resultElement.innerHTML =
      '<p><strong>Processing...</strong></p>';

    try {
      const parsedQrCode = new URL(
        qrCode.trim(),
        window.location.origin
      );
      const urlQrCode = parsedQrCode.searchParams.get('qr');

      if (urlQrCode) {
        qrCode = urlQrCode.trim();
      }
    }
    catch (error) {
      qrCode = qrCode.trim();
    }

    fetch('/session/token')

      .then(function (response) {
        return response.text();
      })

      .then(function (token) {

        const processEndpoint =
          window.location.pathname === '/badge/scanner'
            ? '/badge/scanner/process'
            : '/register/scanner/process';

        return fetch(
          processEndpoint,
          {
            method: 'POST',

            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': token
            },

            body: JSON.stringify({
              qr_code: qrCode
            })
          }
        );

      })

      .then(function (response) {
        return response.json();
      })

      .then(function (data) {

        const presentation = getResultPresentation(data);

        if (data.success) {

          resultElement.innerHTML =
            '<div class="' + presentation.stateClass + '">' +

              '<h2>' +
                Drupal.checkPlain(presentation.heading) +
              '</h2>' +

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
                    'class="button button--primary itsiug-scan-again-button">' +
                    'Scan Another Delegate' +
                    '</button>' +
                    '</p>'
                  : ''
              ) +

            '</div>';

        }
        else {

          resultElement.innerHTML =
            '<div class="' + presentation.stateClass + '">' +

              '<h2>' +
                Drupal.checkPlain(presentation.heading) +
              '</h2>' +

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
                    'class="button itsiug-scan-again-button">' +
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
                    'class="button itsiug-scan-again-button">' +
                  'Scan Another Delegate' +
                  '</button>' +
                  '</p>'
                : ''
            ) +

          '</div>';

      });

  }

  function getResultPresentation(data) {

    const type = (data && data.result_type) ? data.result_type : '';

    if (type === 'scan_recorded') {
      return {
        heading: '\u2713 Scan Successful',
        stateClass: 'itsiug-scan-success'
      };
    }

    if (type === 'already_recorded') {
      return {
        heading: 'Already Recorded',
        stateClass: 'itsiug-scan-info'
      };
    }

    if (type === 'outside_scan_days') {
      return {
        heading: 'Scan Not Available Today',
        stateClass: 'itsiug-scan-warning'
      };
    }

    if (type === 'qr_not_recognised') {
      return {
        heading: 'QR Code Not Found',
        stateClass: 'itsiug-scan-error'
      };
    }

    if (type === 'no_qr_received') {
      return {
        heading: 'No QR Code Received',
        stateClass: 'itsiug-scan-error'
      };
    }

    if (type === 'registration_not_found' || type === 'delegate_not_found') {
      return {
        heading: 'Unable to Process Badge',
        stateClass: 'itsiug-scan-error'
      };
    }

    if (data && data.success) {
      return {
        heading: '\u2713 Scan Successful',
        stateClass: 'itsiug-scan-success'
      };
    }

    return {
      heading: 'Unable to Process Badge',
      stateClass: 'itsiug-scan-warning'
    };
  }

})(Drupal, drupalSettings);