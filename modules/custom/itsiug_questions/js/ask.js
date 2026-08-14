(function (Drupal, once) {

  'use strict';

  /**
   * Badge scanning for the delegate question form.
   */
  Drupal.behaviors.itsiugAskScanner = {

    attach: function (context) {

      once('itsiug-ask-scanner', '#itsiug-ask-scan-start', context)
        .forEach(function (startButton) {

          const readerElement = document.getElementById('itsiug-ask-qr-reader');
          const identityElement = document.getElementById('itsiug-ask-identity');
          const qrField = document.getElementById('itsiug-ask-qr-code');

          if (!readerElement || !identityElement || !qrField) {
            return;
          }

          let scanner = null;

          function setMessage(text, stateClass) {
            identityElement.className = 'itsiug-ask-identity ' + (stateClass || '');
            identityElement.textContent = text;
          }

          function stopScanner() {
            if (!scanner) {
              return Promise.resolve();
            }

            return scanner.stop().catch(function () {});
          }

          // iOS Safari only grants camera access from a user gesture.
          startButton.addEventListener('click', function () {

            if (typeof Html5Qrcode === 'undefined') {
              setMessage(
                Drupal.t('The scanner could not load. Please type your QR Code ID instead.'),
                'itsiug-ask-identity--error'
              );
              return;
            }

            startButton.disabled = true;
            readerElement.classList.add('is-active');
            setMessage(Drupal.t('Starting camera...'), '');

            scanner = new Html5Qrcode('itsiug-ask-qr-reader');

            scanner.start(
              { facingMode: 'environment' },
              { fps: 10, qrbox: { width: 250, height: 250 } },
              function (decodedText) {
                stopScanner().then(function () {
                  readerElement.classList.remove('is-active');
                  startButton.disabled = false;
                  startButton.textContent = Drupal.t('Not you? Scan again');
                  resolveBadge(decodedText);
                });
              },
              function () {
                // Fires continuously while the camera searches; nothing to do.
              }
            ).catch(function () {
              startButton.disabled = false;
              readerElement.classList.remove('is-active');
              setMessage(
                Drupal.t('Camera unavailable. Please allow camera access or type your QR Code ID.'),
                'itsiug-ask-identity--error'
              );
            });

          });

          function resolveBadge(decodedText) {

            setMessage(Drupal.t('Checking your badge...'), '');

            fetch('/session/token')
              .then(function (response) {
                return response.text();
              })
              .then(function (token) {
                return fetch('/ask/lookup', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': token
                  },
                  body: JSON.stringify({ qr_code: decodedText })
                });
              })
              .then(function (response) {
                return response.json();
              })
              .then(function (data) {

                if (!data.success) {
                  setMessage(
                    data.message || Drupal.t('That badge was not recognised.'),
                    'itsiug-ask-identity--error'
                  );
                  return;
                }

                qrField.value = data.qr_code;

                const institution = data.institution
                  ? ' · ' + data.institution
                  : '';

                setMessage(data.delegate + institution, 'itsiug-ask-identity--found');
              })
              .catch(function () {
                setMessage(
                  Drupal.t('Could not check your badge. Please type your QR Code ID instead.'),
                  'itsiug-ask-identity--error'
                );
              });
          }

        });

    }

  };

})(Drupal, once);
