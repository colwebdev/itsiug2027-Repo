/**
 * @file
 * Shared client-side helpers reused by the Tagify widgets and sub-modules.
 */

// eslint-disable-next-line func-names
(function (Drupal) {
  // cspell:ignore whitelist

  /**
   * Namespace for shared Tagify helpers.
   */
  Drupal.tagify = Drupal.tagify || {};

  /**
   * Counts the tags currently rendered inside a widget container.
   *
   * @param {string} identifier
   *   The widget container CSS class (without the leading dot).
   *
   * @return {number}
   *   The number of selected tags, or 0 when the container is absent.
   */
  Drupal.tagify.countSelectedTags = (identifier) => {
    const tagsElement = identifier
      ? document.querySelector(`.${identifier}`)
      : null;
    return tagsElement
      ? tagsElement.querySelectorAll('.tagify__tag').length
      : 0;
  };

  /**
   * Checks whether the configured tag limit has been reached.
   *
   * @param {string} identifier
   *   The widget container CSS class (without the leading dot).
   * @param {number} cardinality
   *   The field cardinality; 0 or a negative value means unlimited.
   *
   * @return {boolean}
   *   TRUE when the tag limit has been reached.
   */
  Drupal.tagify.isTagLimitReached = (identifier, cardinality) =>
    cardinality > 0 &&
    Drupal.tagify.countSelectedTags(identifier) >= cardinality;

  /**
   * Appends a hidden loading-text element to the widget container.
   *
   * @param {string} identifier
   *   The widget container CSS class (without the leading dot).
   */
  Drupal.tagify.createLoadingTextMarkup = (identifier) => {
    const tagsElement = document.querySelector(`.${identifier}`);
    if (!tagsElement) {
      return;
    }
    const loadingText = document.createElement('div');
    loadingText.className = 'tagify--loading-text hidden';
    loadingText.textContent = Drupal.t('Loading...');
    tagsElement.appendChild(loadingText);
  };

  /**
   * Removes the loading-text element from the widget container.
   *
   * @param {string} identifier
   *   The widget container CSS class (without the leading dot).
   */
  Drupal.tagify.removeLoadingTextMarkup = (identifier) => {
    const tagsElement = document.querySelector(`.${identifier}`);
    if (tagsElement) {
      const loadingText = tagsElement.querySelector('.tagify--loading-text');
      if (loadingText) {
        loadingText.remove();
      }
    }
  };

  /**
   * Highlights occurrences of a search term within a string.
   *
   * Matching runs on the raw input and each resulting segment is escaped
   * individually via Drupal.checkPlain(), so the returned markup is safe to
   * inject into the dropdown and the <strong> wrapping never corrupts an HTML
   * entity.
   *
   * @param {string} inputTerm
   *   The string to highlight within.
   * @param {string} searchTerm
   *   The term to highlight.
   *
   * @return {string}
   *   The escaped input string with matches wrapped in <strong> tags.
   */
  Drupal.tagify.highlightMatchingLetters = (inputTerm, searchTerm) => {
    inputTerm = inputTerm == null ? '' : inputTerm.toString();
    searchTerm = searchTerm == null ? '' : searchTerm.toString();
    // Escape special characters in the search term.
    const escapedSearchTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    // Without a search term there is nothing to highlight: escape and return.
    if (!escapedSearchTerm) {
      return Drupal.checkPlain(inputTerm);
    }
    // Highlight on the RAW term, then escape each piece individually, so the
    // <strong> wrapping never lands inside an HTML entity produced by escaping
    // (e.g. the "a" in "&amp;"), which would corrupt it. String.split with a
    // capturing group yields matches at odd indices.
    const regex = new RegExp(`(${escapedSearchTerm})`, 'gi');
    return inputTerm
      .split(regex)
      .map((segment, index) =>
        index % 2 === 1
          ? `<strong>${Drupal.checkPlain(segment)}</strong>`
          : Drupal.checkPlain(segment),
      )
      .join('');
  };
})(Drupal);
