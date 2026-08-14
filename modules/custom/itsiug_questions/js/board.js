(function (Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Polls for new questions and appends them to the presenter board.
   */
  Drupal.behaviors.itsiugQuestionBoard = {

    attach: function (context) {

      once('itsiug-board', '#itsiug-board-rows', context).forEach(function (tbody) {

        const settings = drupalSettings.itsiugQuestions || {};
        const emptyElement = document.getElementById('itsiug-board-empty');
        const statusElement = document.getElementById('itsiug-board-status');

        let since = settings.since || 0;
        let failures = 0;

        function setStatus(text) {
          if (statusElement) {
            statusElement.textContent = text;
          }
        }

        function appendRow(row) {
          const tr = document.createElement('tr');
          tr.dataset.questionId = row.id;
          tr.className = 'itsiug-board-row--new';

          [
            ['itsiug-board-number', row.number],
            ['itsiug-board-name', row.name],
            ['itsiug-board-institution', row.institution],
            ['itsiug-board-question', row.question]
          ].forEach(function (cell) {
            const td = document.createElement('td');
            td.className = cell[0];
            td.textContent = cell[1];
            tr.appendChild(td);
          });

          tbody.appendChild(tr);

          window.setTimeout(function () {
            tr.classList.remove('itsiug-board-row--new');
          }, 4000);
        }

        function poll() {
          fetch(settings.dataUrl + '?since=' + encodeURIComponent(since), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
          })
            .then(function (response) {
              return response.json();
            })
            .then(function (data) {
              failures = 0;
              setStatus('');

              if (data.rows && data.rows.length) {
                data.rows.forEach(appendRow);

                if (emptyElement) {
                  emptyElement.hidden = true;
                }
              }

              since = data.since || since;
            })
            .catch(function () {
              failures += 1;

              if (failures > 1) {
                setStatus(Drupal.t('Reconnecting...'));
              }
            });
        }

        window.setInterval(poll, settings.interval || 8000);
      });

    }

  };

})(Drupal, drupalSettings, once);
