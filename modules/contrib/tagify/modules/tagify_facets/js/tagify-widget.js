/**
 * @file
 * Init tagify widget.
 */

// eslint-disable-next-line func-names
(function ($, Drupal, once) {
  // cspell:ignore whitelist
  Drupal.facets = Drupal.facets || {};

  // eslint-disable-next-line func-names
  Drupal.facets.initTagify = function (context, settings) {
    const widgets = once('tagify-widget', '.js-facets-tagify');

    widgets.forEach((widget) => {
      const widgetLinks = Array.from(
        widget.querySelectorAll('.facet-item > a'),
      );
      const whitelist = [];
      const selected = [];

      widgetLinks.forEach((link) => {
        const href = link.getAttribute('href');
        const valueEl = link.querySelector('.facet-item__value');
        const value = valueEl ? valueEl.textContent.trim() : '';
        const countEl = link.querySelector('.facet-item__count');
        const count = countEl ? countEl.textContent.trim() : null;

        // Create whitelist for Tagify suggestions with values coming from
        // links.
        whitelist.push({
          value: href,
          text: count ? `${value} ${count}` : value,
        });

        // If link is active, add to the input (which will be used on Tagify).
        if (link.classList.contains('is-active')) {
          selected.push({
            value: href,
            text: value,
            count,
          });
        }
      });

      // Create the input element Tagify attaches to and insert it before the
      // widget. The lookup is scoped to this widget so multiple facet blocks
      // on a page each get their own input instead of sharing the first one.
      const input = document.createElement('input');
      input.setAttribute('class', 'tagify-input');
      input.value = JSON.stringify(selected);
      widget.before(input);

      /**
       * Generates HTML markup for a tag based on the provided tagData.
       * @param {Object} tagData - Data for the tag, including value, entity_id, class, etc.
       * @return {string} - HTML markup for the generated tag.
       */
      function tagTemplate(tagData) {
        const text = tagData.text ? Drupal.checkPlain(tagData.text) : '';
        const value = tagData.value ? Drupal.checkPlain(tagData.value) : '';
        const entityId = tagData.entity_id
          ? Drupal.checkPlain(tagData.entity_id)
          : '';
        const count = tagData.count ? Drupal.checkPlain(tagData.count) : '';

        const entityIdDiv =
          parseInt(input.dataset.showEntityId, 10) && tagData.entity_id
            ? `<div id="tagify__tag-items" class="tagify__tag_with-entity-id"><div class='tagify__tag__entity-id-wrap'><span class='tagify__tag-entity-id'>${entityId}</span></div><span class='tagify__tag-text'>${value}</span></div>`
            : `<div id="tagify__tag-items"><span class='tagify__tag-facets-text'>${text}</span>
                  ${count ? `<span class="tagify__tag-facets-count"> ${count}</span>` : ''}
               </div>`;

        return `<tag title="${text}"
          contenteditable='false'
          spellcheck='false'
          tabIndex="0"
          class="tagify__tag ${tagData.class ? tagData.class : ''}"
          ${this.getAttributes(tagData)}>
            <x id="tagify__tag-remove-button" class='tagify__tag__removeBtn' role='button' aria-label='remove tag'></x>
            ${entityIdDiv}
        </tag>`;
      }

      /**
       * Generates the HTML template for a suggestion item in the Tagify dropdown based on the provided tagData.
       * @param {Object} tagData - The data representing the suggestion item.
       * @return {string} - The HTML template for the suggestion item.
       */
      function suggestionItemTemplate(tagData) {
        return `<div ${this.getAttributes(
          tagData,
        )} class='tagify__dropdown__item ${
          tagData.class ? tagData.class : ''
        }' tabindex="0" role="option">${Drupal.tagify.highlightMatchingLetters(
          tagData.text,
          this.state.inputText,
        )}</div>`;
      }

      // eslint-disable-next-line no-undef
      const tagify = new Tagify(input, {
        dropdown: {
          enabled: 0,
          highlightFirst: true,
          searchKeys: ['text'],
          fuzzySearch: !!parseInt(
            settings.tagify.tagify_facets_widget.match_operator,
            10,
          ),
          maxItems:
            settings.tagify.tagify_facets_widget.max_items === '0'
              ? Infinity
              : settings.tagify.tagify_facets_widget.max_items,
        },
        templates: {
          tag: tagTemplate,
          dropdownItem: suggestionItemTemplate,
          dropdownFooter() {
            return '';
          },
        },
        whitelist,
        enforceWhitelist: true,
        editTags: false,
        placeholder: settings.tagify.tagify_facets_widget.placeholder,
      });

      // Listens to add tag event and updates facets values accordingly. The
      // 'facets_filter' event is a jQuery event consumed by the contrib Facets
      // module, so it must be triggered through jQuery.
      tagify.on('add', (e) => {
        const value = e.detail?.data?.value;
        if (!value) {
          return;
        }
        e.preventDefault();
        if (widgetLinks.some((link) => link.getAttribute('href') === value)) {
          $(widget).trigger('facets_filter', [value]);
        }
      });

      // Listens to remove tag event and updates facets values accordingly.
      tagify.on('remove', (e) => {
        const value = e.detail?.data?.value;
        if (!value) {
          return;
        }
        e.preventDefault();
        $(widget).trigger('facets_filter', [value]);
      });
    });
  };

  /**
   * Behavior to register tagify widget to be used for facets.
   */
  Drupal.behaviors.facetsTagifyWidget = {
    attach(context, settings) {
      Drupal.facets.initTagify(context, settings);
    },
  };
})(jQuery, Drupal, once);
