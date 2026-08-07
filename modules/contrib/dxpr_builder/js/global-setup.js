/* jslint white:true, multivar, this, browser:true */

/**
 * @file Handles global setup and initial configuration for DXPR Builder Drupal integration.
 */

(function ($, Drupal, drupalSettings, window) {
  "use strict";

  window.onload = function () {
    let bootstrapVersion = false;
    // Safely check nested objects using optional chaining
    const bs3bs4 = window.jQuery?.fn?.popover?.Constructor?.VERSION;
    const bs5 = window.bootstrap?.Popover?.VERSION;
    if (bs3bs4) {
      bootstrapVersion = bs3bs4.charAt(0);
    } else if (bs5) {
      bootstrapVersion = bs5.charAt(0);
    }
    if (!bootstrapVersion) {
      const messages = new Drupal.Message();
      const message = Drupal.t(
        "The DXPR Builder depends on Bootstrap framework to work. " +
          "Please enable Bootstrap in " +
          "the <a href='@dxpr_builder_settings'>DXPR Builder settings form</a>.",
        {
          "@dxpr_builder_settings": Drupal.url(
            "admin/dxpr_studio/dxpr_builder/settings",
          ),
        },
      );
      messages.add(message, { type: "error" });
    }
  };

  window.dxprBuilder = window.dxprBuilder || {};
  // Set elements that DXPR Builder will globally recognize
  window.dxprBuilder.dxpr_editable = [
    "h1",
    "h2",
    "h3",
    "h4",
    "h5",
    "h6",
    "img:not(.not-editable)",
    "a:not(.not-editable)",
    "i:not(.not-editable)",
  ];
  window.dxprBuilder.dxpr_styleable = [];
  window.dxprBuilder.dxpr_textareas = [];
  window.dxprBuilder.dxpr_formats = [];
})(jQuery, Drupal, drupalSettings, window);
