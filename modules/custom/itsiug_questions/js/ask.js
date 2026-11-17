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

  /**
   * Speech-to-text input for the delegate question form.
   */
  Drupal.behaviors.itsiugAskVoiceInput = {

    attach: function (context) {

      once('itsiug-ask-voice-input', '#itsiug-ask-record-question', context)
        .forEach(function (recordButton) {

          const questionField = document.getElementById('itsiug-ask-question');
          const statusElement = document.getElementById('itsiug-ask-voice-status');
          const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

          if (!questionField || !statusElement) {
            return;
          }

          function setStatus(text, isError) {
            statusElement.textContent = text;
            statusElement.classList.toggle('itsiug-ask-voice-status--error', Boolean(isError));
          }

          if (!SpeechRecognition) {
            recordButton.disabled = true;
            setStatus(Drupal.t('Voice input is unavailable. Please type your question.'), true);
            return;
          }

          const recognition = new SpeechRecognition();
          let recording = false;
          let recognitionError = false;
          let receivedTranscript = false;
          let limitReached = false;
          recognition.continuous = true;
          recognition.interimResults = false;
          recognition.lang = document.documentElement.lang || 'en-US';

          recordButton.addEventListener('click', function () {
            if (recording) {
              recordButton.disabled = true;
              recognition.stop();
              return;
            }

            recognitionError = false;
            receivedTranscript = false;
            limitReached = false;
            try {
              recognition.start();
              recording = true;
              recordButton.textContent = Drupal.t('Stop recording');
              setStatus(Drupal.t('Listening... Select Stop recording when you are finished.'), false);
            }
            catch (error) {
              const message = error.name === 'NotAllowedError' || error.name === 'SecurityError'
                ? Drupal.t('Microphone access was denied. Allow access or type your question.')
                : Drupal.t('Recording could not be started. Try again or type your question.');
              setStatus(message, true);
            }
          });

          recognition.addEventListener('result', function (event) {
            const transcript = Array.from(event.results)
              .slice(event.resultIndex)
              .filter(function (result) {
                return result.isFinal;
              })
              .map(function (result) {
                return result[0].transcript.trim();
              })
              .filter(Boolean)
              .join(' ');

            if (!transcript) {
              return;
            }

            const remaining = questionField.maxLength - questionField.value.length;
            if (remaining <= 0) {
              limitReached = true;
              recordButton.disabled = true;
              recognition.stop();
              return;
            }

            const separator = questionField.value && !/\s$/.test(questionField.value) ? ' ' : '';
            const addition = separator + transcript;
            questionField.value += addition.slice(0, remaining);
            receivedTranscript = true;
            questionField.dispatchEvent(new Event('input', { bubbles: true }));

            if (addition.length > remaining) {
              limitReached = true;
              recordButton.disabled = true;
              recognition.stop();
            }
          });

          recognition.addEventListener('error', function (event) {
            recording = false;
            recognitionError = true;
            recordButton.disabled = false;
            recordButton.textContent = Drupal.t('Record your question');

            const message = event.error === 'not-allowed' || event.error === 'service-not-allowed'
              ? Drupal.t('Microphone access was denied. Allow access or type your question.')
              : event.error === 'no-speech'
                ? Drupal.t('No speech was detected. Try recording again or type your question.')
                : Drupal.t('Voice input failed. Try again or type your question.');
            setStatus(message, true);
          });

          recognition.addEventListener('end', function () {
            recording = false;
            recordButton.disabled = false;
            recordButton.textContent = Drupal.t('Record your question');

            if (!recognitionError) {
              if (limitReached) {
                setStatus(Drupal.t('The question reached its character limit. Review it before submitting.'), true);
              }
              else if (receivedTranscript) {
                setStatus(Drupal.t('Recording finished. Review or edit your question before submitting.'), false);
              }
              else {
                setStatus(
                  questionField.value
                    ? Drupal.t('No speech was added. Your existing text is unchanged.')
                    : Drupal.t('No speech was detected. Try recording again or type your question.'),
                  !questionField.value
                );
              }
            }
          });

        });

    }

  };

})(Drupal, once);
