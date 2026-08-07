(function (Drupal, once) {
  Drupal.behaviors.eventPlatformScheduler = {
    attach(context) {
      const unassign = document.querySelector('.scheduler--unassign');
      const filters = document.querySelectorAll('#scheduler_filters select');
      const filterValues = [];

      function arrayEmpty(array) {
        let isEmpty = true;
        // We don't use the index value, but wanted approach to be consistent.
        Object.entries(array).forEach(([index, value]) => {
          if (value) {
            isEmpty = false;
          }
        });
        return isEmpty;
      }

      function checkFiltersMatch(session) {
        let isMatch = true;
        Object.entries(filterValues).forEach(([index, value]) => {
          if (!value) {
            return;
          }
          if (session.dataset[index] !== value) {
            isMatch = false;
          }
        });
        return isMatch;
      }

      // Basic Drag Functions
      function dragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
      }

      function dragEnter(e) {
        e.preventDefault();
        this.classList.add('dragover');
      }

      function dragLeave() {
        this.classList.remove('dragover');
      }

      function dragEnd() {
        this.classList.remove('dragging');
        unassign.classList.add('hidden');
        // eslint-disable-next-line
        removeConflicts();
      }

      function dragDrop(e) {
        // Get the id of the target and add the moved element to the target's DOM
        const data = e.dataTransfer.getData('text/plain');
        const node = document.getElementById(data);
        // Replace classes.
        this.classList.remove('dragover');
        this.classList.remove('empty');
        this.classList.add('filled');
        // eslint-disable-next-line
        removeDragTarget(this);
        // Assemble the data necessary to update the node.
        const nid = node.dataset.nid;
        const room = this.dataset.room;
        const timeslot = this.dataset.timeslot;
        // Only update if the new data is different.
        if (
          nid &&
          room &&
          timeslot &&
          (room !== node.dataset.room || timeslot !== node.dataset.timeslot)
        ) {
          e.target.appendChild(node);
          // console.log("Target: " + nid + "-" + room + "-" + timeslot);
          const path = `/admin/event-details/scheduler/assign/${nid}/${room}/${
            timeslot
          }`;
          fetch(path).then((response) => response.text());
          // .then(text => console.log(text));
          // @todo show feedback to user.
          // Add data attributes to the session.
          node.dataset.room = room;
          node.dataset.timeslot = timeslot;
          // Check if the session should be highlighted as a filters match.
          if (!arrayEmpty(filterValues) && checkFiltersMatch(node)) {
            node.classList.add('scheduler--filters-match');
          }
        }
      }

      function makeDragTarget(element) {
        element.addEventListener('dragover', dragOver);
        element.addEventListener('dragenter', dragEnter);
        element.addEventListener('dragleave', dragLeave);
        element.addEventListener('drop', dragDrop);
      }

      function removeDragTarget(element) {
        element.removeEventListener('dragover', dragOver);
        element.removeEventListener('dragenter', dragEnter);
        element.removeEventListener('dragleave', dragLeave);
        element.removeEventListener('drop', dragDrop);
      }

      // Advanced Drag Functions
      function dragDropUnassign(e) {
        // Get the id of the target and return the element to the list.
        const data = e.dataTransfer.getData('text/plain');
        const node = document.getElementById(data);
        e.target.parentElement.appendChild(node);
        const nid = node.dataset.nid;
        if (nid) {
          const path = `/admin/event-details/scheduler/unassign/${nid}`;
          fetch(path).then((response) => response.text());
          // .then(text => console.log(text));
          // @todo show feedback to user.
        }
        // Reset data attributes on the session.
        node.dataset.room = '';
        node.dataset.timeslot = '';
        node.classList.remove('scheduler--filters-match');
      }

      function createConflict(timeslot, user) {
        const cells = document.querySelectorAll(
          `td[data-timeslot="${timeslot}"]`,
        );
        let session;
        cells.forEach((cell) => {
          cell.classList.add('conflict');
          removeDragTarget(cell);
          const session = cell.querySelector(`.session[data-user="${user}"]`);
          if (session) {
            session.classList.add('theconflict');
          }
        });
      }

      function checkForConflicts(element) {
        const user = element.dataset.user;
        const timeslot = element.dataset.timeslot;
        const conflicts = document.querySelectorAll(
          `.session[data-user="${user}"]`,
        );
        if (conflicts) {
          conflicts.forEach((conflict) => {
            if (
              !conflict.dataset.timeslot ||
              conflict.dataset.timeslot === timeslot
            ) {
              return;
            }
            createConflict(conflict.dataset.timeslot, user);
          });
        }
      }

      function removeConflicts() {
        const cells = document.querySelectorAll('td.conflict');
        cells.forEach((cell) => {
          cell.classList.remove('conflict');
          if (!cell.classList.contains('filled')) {
            makeDragTarget(cell);
          }
        });
        const sessions = document.querySelectorAll('.session.theconflict');
        sessions.forEach((session) => {
          session.classList.remove('theconflict');
        });
      }

      function dragStart(e) {
        this.classList.add('dragging');
        e.dataTransfer.setData('text/plain', e.target.id);
        const slot = this.closest('.filled');
        if (slot) {
          makeDragTarget(slot);
          slot.classList.remove('filled');
        }
        if (this.dataset.room || this.dataset.timeslot) {
          unassign.classList.remove('hidden');
        }
        checkForConflicts(this);
      }

      function applyFilters() {
        // Get all filter values.
        filters.forEach((element) => {
          filterValues[element.name] = element.value;
        });
        // Get all unassigned sessions.
        const sessions = document.querySelectorAll(
          '#scheduler--sessions .session',
        );
        // Make all sessions visible.
        sessions.forEach((session) => {
          session.style.display = 'block';
          // Hide any sessions that don't match filter criteria.
          if (!checkFiltersMatch(session)) {
            session.style.display = 'none';
          }
        });
        // Selectively add a class to highlight filter matches.
        const filtersEmpty = arrayEmpty(filterValues);
        const scheduledMatches = document.querySelectorAll(
          '#scheduler-table .session',
        );
        scheduledMatches.forEach((session) => {
          // Reset all existing classes to start.
          session.classList.remove('scheduler--filters-match');
          if (!filtersEmpty && checkFiltersMatch(session)) {
            session.classList.add('scheduler--filters-match');
          }
        });
      }

      // Activate empty slots as drag targets.
      once(
        'eventPlatformScheduler',
        '#scheduler-table .empty',
        context,
      ).forEach(function (element) {
        makeDragTarget(element);
      });

      // Activate filters.
      once(
        'eventPlatformScheduler',
        '#scheduler_filters select',
        context,
      ).forEach(function (element) {
        element.addEventListener('change', applyFilters);
      });

      // Activate unassign slots as a drag target.
      once(
        'eventPlatformScheduler',
        '#scheduler--sessions .scheduler--unassign',
        context,
      ).forEach(function (element) {
        element.addEventListener('dragover', dragOver);
        element.addEventListener('dragenter', dragEnter);
        element.addEventListener('dragleave', dragLeave);
        element.addEventListener('drop', dragDropUnassign);
      });

      // Activate sessions as draggable.
      once('eventPlatformScheduler', '.session', context).forEach(
        function (element) {
          element.addEventListener('dragstart', dragStart);
          element.addEventListener('dragend', dragEnd);
          if (element.dataset.room && element.dataset.timeslot) {
            // If a cell matches the sessions data, move it there.
            const cell = document.querySelector(
              `td[data-room="${element.dataset.room}"][data-timeslot="${
                element.dataset.timeslot
              }"]`,
            );
            if (cell) {
              cell.appendChild(element);
              cell.classList.remove('empty');
              cell.classList.add('filled');
              removeDragTarget(cell);
            } else {
              // Invalid room or timeslot, so reset values.
              element.dataset.room = '';
              element.dataset.timeslot = '';
            }
          }
        },
      );
    },
  };
})(Drupal, once);
