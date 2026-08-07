/* jslint white:true, multivar, this, browser:true */

/**
 * @file Handles the image selection UI component for DXPR Builder Drupal integration.
 */

(function ($, Drupal, drupalSettings, window) {
  "use strict";

  // Dependencies from other files (ensure they are loaded before this script)
  const getUrlsFromInput =
    window.dxprBuilder && window.dxprBuilder.getUrlsFromInput;
  const dxpr_builder_get_images =
    window.dxprBuilder && window.dxprBuilder.dxpr_builder_get_images;
  const hideImageStyleControls =
    window.dxprBuilder && window.dxprBuilder.hideImageStyleControls;
  const createThumbailFromDefault =
    window.dxprBuilder && window.dxprBuilder.createThumbailFromDefault;
  const createEntityBrowserButton =
    window.dxprBuilder && window.dxprBuilder.createEntityBrowserButton;
  const createFileUploadElement =
    window.dxprBuilder && window.dxprBuilder.createFileUploadElement;
  const createFileUploadButton =
    window.dxprBuilder && window.dxprBuilder.createFileUploadButton;

  /**
   * Change handler for the image style select element
   *
   * @param {jQuery} selectElement The select element for image styles
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  function imageStyleChangeHandler(selectElement, delimiter) {
    if (!getUrlsFromInput || !dxpr_builder_get_images) {
      console.error(
        "DXPR Builder: Missing dependencies for image style change.",
      );
      return;
    }
    // Find the selected option and act on it
    const imageStyle = selectElement.val();

    // Get the image input containing the URL of the image
    const imageInput = selectElement.siblings(".form-control:first");
    // If a delimiter has been provided, it means multiple images are allowed,
    // so each image needs the image style applied
    if (delimiter) {
      // Create an array of the currently entered images
      const currentImages = getUrlsFromInput(imageInput, delimiter);

      // Create an array to hold the images with the new image style URLs
      const newImages = [];
      // Loop through each of the current images, creating an array with the new image URLs
      currentImages.forEach((fileUrl) => {
        dxpr_builder_get_images(
          false,
          imageStyle,
          fileUrl,
          imageInput,
          delimiter,
          newImages,
        );
      });
    } else {
      const fileUrl = imageInput.val();
      dxpr_builder_get_images(false, imageStyle, fileUrl, imageInput);
    }
  }

  /**
   * Create the select element users will use to select an image style
   *
   * @param {jQuery} input The input used as a reference for inserting the select element into the DOM
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  function createImageStyleInput(input, delimiter) {
    if (!hideImageStyleControls) {
      console.error(
        "DXPR Builder: Missing dependencies for image style input creation.",
      );
      return;
    }
    // TODO: is this variable ever used?
    let label;

    // Create the select element used for selecting an image style
    const imageStyleSelect = $(
      '<select class="dxpr-builder-image-styles"/>',
    ).change(function () {
      imageStyleChangeHandler($(this), delimiter);
    });

    // Add an <option> tag for each image style to the image style select element
    Object.keys(drupalSettings.dxprBuilder.imageStyles).forEach((key) => {
      const option = document.createElement("option");
      option.value = key;
      option.textContent = drupalSettings.dxprBuilder.imageStyles[key];
      imageStyleSelect.append(option);
    });

    const inputValue = input.val();

    // Set a default value for the image styles select list.
    if (inputValue) {
      let imageStyle = null;
      // When editing an existing image, the image input will contain a URL.
      // This URL is parsed to see if it has an image style applied to it.
      let matches = inputValue.match(/styles\/([^/]+)\/(public|private)/);
      if (matches && matches[1]) {
        [, imageStyle] = matches;
      }

      // If we still don't have image style, try to find it as the "imageStyle"
      // query parameter in the URL.
      if (!imageStyle) {
        matches = inputValue.match(/imageStyle=([^&,]*)/);
        if (matches && matches[1]) {
          [, imageStyle] = matches;
        }
      }

      if (imageStyle) {
        // If the URL has an image style applied to it, that image style is set as the current selection.
        imageStyleSelect
          .find(`option[value='${imageStyle}']`)
          .attr("selected", "selected");
      }
    }

    // Append the newly created elements to the page
    input.before(imageStyleSelect).prepend(label);

    // Use jQuery.chosen() to make a cleaner select element for the image styles.
    if ($.fn.chosen) {
      imageStyleSelect.chosen({
        search_contains: true,
        allow_single_deselect: true,
      });
    } else {
      // Chosen plugin not found, but this is a non-critical UI enhancement
    }

    hideImageStyleControls(input);
  }

  /**
   * This function is used to launch the code in this script, and is
   * called by external scripts.
   *
   * @param {HTMLElement} input The input into which URLs should be inserted. The URLs will then
   *   become images in the DOM when the dialog is saved
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  function backend_images_select(input, delimiter) {
    if (
      !createThumbailFromDefault ||
      !createEntityBrowserButton ||
      !createFileUploadElement ||
      !createFileUploadButton
    ) {
      console.error(
        "DXPR Builder: Missing dependencies for backend_images_select.",
      );
      return;
    }
    const inputElement = $(input);
    inputElement
      .css("display", "block")
      .wrap($("<div/>", { class: "ac-select-image" }))
      .wrap($("<div/>", { class: "ac-select-image__content-container" }));

    if (
      drupalSettings.dxprBuilder.mediaBrowser &&
      drupalSettings.dxprBuilder.mediaBrowser.length > 0
    ) {
      createEntityBrowserButton(inputElement);
    } else {
      createFileUploadElement(inputElement, delimiter, "image");
      createFileUploadButton(inputElement, "image");
    }
    createImageStyleInput(inputElement, delimiter);
    createThumbailFromDefault(inputElement, delimiter);

    inputElement.change({ input: inputElement, delimiter }, (event) => {
      inputElement.siblings(".preview:first").empty();
      createThumbailFromDefault(inputElement, delimiter);
    });
  }

  // Expose the public API function
  window.dxprBuilder = window.dxprBuilder || {};
  window.dxprBuilder.backend_images_select = backend_images_select;

  // Expose internal functions needed by other files (if any)
  // Example: window.dxprBuilder.imageStyleChangeHandler = imageStyleChangeHandler;
  // Example: window.dxprBuilder.createImageStyleInput = createImageStyleInput;
})(jQuery, Drupal, drupalSettings, window);
