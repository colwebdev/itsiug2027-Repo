/* jslint white:true, multivar, this, browser:true */

/**
 * @file Utility functions for image handling in DXPR Builder Drupal integration.
 */

(function ($, Drupal, drupalSettings, window) {
  "use strict";

  /**
   * Hide the resize image controls
   *
   * @param {jQuery} input The input for which the resize image controls should be hidden
   */
  function hideImageStyleControls(input) {
    input.siblings("label:first, .chosen-container:first").hide();
  }

  /**
   * Create an array of image URLs from the image input
   *
   * @var {jQuery} imageInput The image input from which the URLs should be extracted
   * @var {string} delimiter The delimiter used between filenames stored in the input
   *
   * @return {array} An array of image names extracted from the image input
   */
  function getUrlsFromInput(input, delimiter) {
    if (delimiter) {
      return input
        .val()
        .split(delimiter)
        .filter((el) => Boolean(el.length));
    }

    return [input.val().trim()];
  }

  /**
   * Show the resize image controls
   *
   * @param {jQuery} input The input for which the resize image controls should be hidden
   */
  function showImageStyleControls(input) {
    input.siblings("label:first, .chosen-container:first").show();
  }

  /**
   *
   * @param {string} url The URL from which the file name should be extracted
   * @return {string} The name of the file
   */
  function getFileNameFromUrl(url) {
    const parts = url.split("/");

    return parts[parts.length - 1];
  }

  /**
   *
   * @param {string} url The URL to be altered
   * @param {string} imageStyle The image style that should be applied to that URL. If this is equal to
   *   'original', The original image URL will be returned, instead of a URL
   *   with an image style path
   *
   * @returns {string} The image style URL for the original image
   */
  function getImageStyleUrl(url, imageStyle) {
    const filesUrl = drupalSettings.dxprBuilder.publicFilesFolder;

    // First check if we're dealing with a local image in public files storage
    if (url.indexOf(filesUrl) !== -1 && url.indexOf("svg") === -1) {
      // Check if we're dealing with a non-image style URL
      const isPrivate = url.indexOf("/system/files/") !== -1;
      if (isPrivate) {
        if (url.indexOf("/private/") === -1) {
          // Insert this private image style into the URL
          return url.replace(
            filesUrl,
            `${filesUrl}styles/${imageStyle}/private/`,
          );
        }
        // If the image style is 'original', then return non-image style URL
        if (imageStyle === "original") {
          return url.replace(/ styles\/[^/]+\/private\/ /, "");
        }
        // Otherwise swap out the current image style with the new one.
        return url.replace(/ \/styles\/[^/]+ /, `/styles/${imageStyle}`);
      }
      // Public file case.
      if (url.indexOf("/public/") === -1) {
        // Insert private image style into the URL
        return url.replace(filesUrl, `${filesUrl}styles/${imageStyle}/public/`);
      }
      // If the image style is 'original', then return non-image style URL
      if (imageStyle === "original") {
        return url.replace(/ styles\/[^/]+\/public\/ /, "");
      }
      // Otherwise swap out the current image style with the new one.
      return url.replace(/ \/styles\/[^/]+ /, `/styles/${imageStyle}`);
    }
    return url;
  }

  /**
   *
   * @param {jQuery} imageList
   * @param {string} delimiter
   */
  function sortFilenames(imageList, delimiter) {
    const imageInput = imageList.siblings(".form-control:first");
    const urls = getUrlsFromInput(imageInput, delimiter);

    // Create a simple map that allows duplicate keys
    const urlFilenameMap = [...urls].map((url) => {
      const urlSeparated = url.split("/");
      const name = urlSeparated[urlSeparated.length - 1];

      return [name, url];
    });

    const fileNames = [];
    imageList.children("li").each(function () {
      const filename =
        this.querySelector(":first-child").getAttribute("data-filename");
      if (filename && filename.length) {
        fileNames.push(filename);
      }
    });

    // Count the frequency of each filename in the array
    const filenameCounts = fileNames.reduce((countMap, filename) => {
      // Initialize the count to 0 if not present, then increment
      countMap[filename] = (countMap[filename] || 0) + 1;

      return countMap;
    }, {});

    // Track the filtered URLs based on filename counts
    const filteredUrls = [];

    // Iterate over each [filename, url] pair in the urlFilenameMap
    urlFilenameMap.forEach(([filename, url]) => {
      // Check if the filename count is positive
      if (filenameCounts[filename] > 0) {
        filteredUrls.push(url); // Add URL to the result list

        filenameCounts[filename]--; // Decrements the filename count
      }
    });

    // Sort the filtered URLs based on the order of fileNames
    const sortedUrls = fileNames.map((fileName) => {
      const index = filteredUrls.findIndex((url) => url.endsWith(fileName));
      return filteredUrls.splice(index, 1)[0];
    });

    imageInput.val(sortedUrls.join(delimiter));
  }

  /**
   * Click handler for the remove button on thumbnails
   */
  function thumbnailCloseButtonClickHandler(e) {
    e.preventDefault();

    const closeButton = $(this);
    const thumbnailContainer = closeButton.parent().parent();

    const imageList = thumbnailContainer.parent();
    const selectElement = thumbnailContainer.parent().siblings("select:first");

    // Unset the currently selected image style
    selectElement.find("option[selected='selected']").removeAttr("selected");

    // Set the new image style
    selectElement
      .find("option[value='original']")
      .attr("selected", "selected")
      .trigger("chosen:updated");

    // Remove the thumbnail
    thumbnailContainer.remove();

    if (!imageList.children("li:first").length) {
      hideImageStyleControls(imageList.siblings(".form-control:first"));
    }

    sortFilenames(imageList, ",");

    liveEditingManager.update();
  }

  /**
   *
   * @param {string} fileUrl
   * @param {jQuery} input
   * @param {string} delimiter
   * @param {string} fileLocation
   */
  function insertImageThumbnail(fileUrl, input, delimiter, fileLocation) {
    // Create a container for the thumbnail
    const imageContainer = $("<div/>", {
      class: "image-preview",
      "data-filename": getFileNameFromUrl(fileUrl),
    });

    // Create the image element
    const image = fileLocation
      ? $("<img/>", { src: fileLocation })
      : $("<img/>", { src: getImageStyleUrl(fileUrl, "thumbnail") });

    // Create the remove button
    const closeButton = $("<a/>", {
      class: "glyphicon glyphicon-remove",
      href: "#",
    }).click(thumbnailCloseButtonClickHandler);

    // Add the image and close button to the container
    imageContainer.append(image).append(closeButton);

    // Retrieve list of images
    let imageList = input.siblings(".preview:first");

    // If the list doesn't exist, it needs to be created
    if (!imageList.length) {
      imageList = $("<ul/>", { class: "preview ui-sortable" }).insertBefore(
        input.siblings(".chosen-container-single"),
      );
      window.Sortable.create(imageList[0], {
        forceFallback: true,
        onEnd: () => {
          sortFilenames($(imageList), delimiter);
          liveEditingManager.update();
        },
      });
    }

    // If multiple images are not allowed, any existing thumbnails are first removed.
    if (!delimiter) {
      imageList.empty();
    }

    // Insert the container into the list
    $("<li/>", { class: "added" }).append(imageContainer).appendTo(imageList);

    showImageStyleControls(input);
  }

  /**
   * Get image style url with itok.
   *
   * @param {string} imageStyle
   * @param {string} entityTypeId
   * @param {string} entityId
   * @param {callback} callback
   */
  function dxpr_builder_get_image_style_url(
    imageStyle,
    entityTypeId,
    entityId,
    callback,
  ) {
    fetch(drupalSettings.dxprBuilder.dxprCsrfUrl, {
      method: "GET",
      headers: {
        Accept: "application/json",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        fetch(data, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            action: "dxpr_builder_get_image_style_url",
            imageStyle,
            entityTypeId,
            entityId,
          }),
        })
          .then((response) => response.text())
          .then((res) => {
            if (typeof callback === "function") {
              callback(res);
            }
            liveEditingManager.update();
          })
          .catch(() => {
            callback("");
          });
      });
  }

  /**
   *
   * @param {boolean} updateThumb
   * @param {string} imageStyle
   * @param {string} fileUrl file location url
   * @param {string} fileId image file id
   * @param {any} input The input used as a reference for inserting the select element into the DOM.
   * @param {string} delimiter The delimiter used between URLs in the input.
   * @param {string} newImages The array with images for elements with multiple images.
   * @param {string} fileLocation The image new location url.
   */
  function dxpr_builder_insert_image(
    updateThumb,
    imageStyle,
    fileUrl,
    fileId,
    input,
    delimiter,
    newImages,
    fileLocation,
  ) {
    if (!fileLocation) {
      fileLocation = fileUrl;
    }

    if (updateThumb) {
      insertImageThumbnail(fileUrl, input, delimiter, fileLocation);
    } else if (delimiter) {
      newImages.push(fileLocation);
      // Insert the new image URLs into the image field
      input.val(newImages.join(delimiter));
    } else {
      input.val(fileLocation);
    }
  }

  /**
   * Helper function to load images.
   *
   * @param {boolean} updateThumb
   * @param {string} imageStyle
   * @param {string} fileUrl
   * @param {string} input
   * @param {string} delimiter
   * @param {array} newImages
   */
  function dxpr_builder_get_images(
    updateThumb,
    imageStyle,
    fileUrl,
    input,
    delimiter,
    newImages,
  ) {
    const isProtectedFiles = fileUrl.indexOf("/system/files/") !== -1;
    const isPublicFiles = fileUrl.indexOf("/sites/default/files/") !== -1;
    // We check if GET parameter "acquiaDamAsset" exists in the url.
    const isAcquiaAsset = fileUrl.indexOf("acquiaDamAsset=1") !== -1;

    // Check if it's an image stored in files.
    if (isPublicFiles || isProtectedFiles) {
      let fileId = "";
      const idPosition = fileUrl.indexOf("fid=");
      if (idPosition > -1) {
        fileId = fileUrl.substr(idPosition + 4);
      }

      if (fileId.length > 0) {
        dxpr_builder_get_image_style_url(
          imageStyle,
          "file",
          fileId,
          (fileLocation) => {
            dxpr_builder_insert_image(
              updateThumb,
              imageStyle,
              fileUrl,
              fileId,
              input,
              delimiter,
              newImages,
              fileLocation,
            );
          },
        );
      } else {
        const fileLocation = getImageStyleUrl(fileUrl, imageStyle);
        dxpr_builder_insert_image(
          updateThumb,
          imageStyle,
          fileUrl,
          fileId,
          input,
          delimiter,
          newImages,
          fileLocation,
        );
      }
    } else if (isAcquiaAsset) {
      // Url is always an absolute path.
      const urlObject = new URL(fileUrl);
      // Get media entity id from the url.
      const mediaId = urlObject.searchParams.get("mid");

      if (!mediaId) {
        // We can't do anything as all data about media asset image styles exist in media entity, it couldn't be
        // generated.
        return;
      }
      // Get a chosen image with appropriate style and insert it as a thumbnails to the builder element.
      // For Acquia assets, we shouldn't create a new images as it's not possible.
      dxpr_builder_get_image_style_url(
        imageStyle,
        "media",
        mediaId,
        (fileLocation) => {
          dxpr_builder_insert_image(
            updateThumb,
            imageStyle,
            fileUrl,
            null,
            input,
            delimiter,
            newImages,
            fileLocation,
          );
        },
      );
    } else {
      dxpr_builder_insert_image(
        updateThumb,
        imageStyle,
        fileUrl,
        null,
        input,
        delimiter,
        newImages,
        null,
      );
    }
  }

  /**
   * When an image is being edited, a URL will exist in the input. This
   * function creates a thumbnail from that URL.
   *
   * @param {jQuery} input The input from which the URL will be retrieved
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  function createThumbailFromDefault(input, delimiter) {
    let currentImages;
    // If a value exists, thumbnails need to be created
    if (input.val().length) {
      // Get the list of images that exist in the input
      currentImages = getUrlsFromInput(input, delimiter);

      // Loop through the images creating thumbnails for each image
      currentImages.forEach((fileUrl) => {
        dxpr_builder_get_images(true, "thumbnail", fileUrl, input, delimiter);
      });

      // Show the image controls, since there has been an image inserted
      showImageStyleControls(input);
    }
  }

  // Expose functions needed by other files
  window.dxprBuilder = window.dxprBuilder || {};
  window.dxprBuilder.hideImageStyleControls = hideImageStyleControls;
  window.dxprBuilder.getUrlsFromInput = getUrlsFromInput;
  window.dxprBuilder.showImageStyleControls = showImageStyleControls;
  window.dxprBuilder.getFileNameFromUrl = getFileNameFromUrl;
  window.dxprBuilder.getImageStyleUrl = getImageStyleUrl;
  window.dxprBuilder.sortFilenames = sortFilenames;
  window.dxprBuilder.thumbnailCloseButtonClickHandler =
    thumbnailCloseButtonClickHandler; // Check if needed externally
  window.dxprBuilder.insertImageThumbnail = insertImageThumbnail;
  window.dxprBuilder.dxpr_builder_get_image_style_url =
    dxpr_builder_get_image_style_url;
  window.dxprBuilder.dxpr_builder_insert_image = dxpr_builder_insert_image;
  window.dxprBuilder.dxpr_builder_get_images = dxpr_builder_get_images;
  window.dxprBuilder.createThumbailFromDefault = createThumbailFromDefault;
})(jQuery, Drupal, drupalSettings, window);
