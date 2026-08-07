# Tagify

Tagify provide a widget to transform entity reference fields into a more
user-friendly tags component, in an easy, customizable way, with great
performance and small code footprint.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/tagify).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/search/tagify).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

By default, the module uses CDNs to give flexibility to the installation,
but you can also download and serve the scripts locally. This is required for
environments that block third-party requests (a strict Content Security
Policy, offline deployments, or company policy), where the CDN would simply
fail to load.

The scripts are discovered automatically by the module once present. You can
confirm which source is active on the status report at
`/admin/reports/status` (look for the "Tagify library" entry: it reads
"Served locally" or "Loaded from CDN"), or under Network in your browser.

Use one of the options below, for locally served script files.

### Option 1 - Download required libraries (optional installation)

1. Download the required libraries.
   - https://github.com/yairEO/tagify/releases/tag/v4.37.1
1. Extract the libraries into their corresponding folders.
   - libraries/tagify
1. Enable the Tagify module.


### Option 2 - Composer (optional installation)

1. Copy the following into your project's composer.json file.
    ```json
    "repositories": [
        {
          "type": "package",
          "package": {
            "name": "yaireo/tagify",
            "version": "4.37.1",
            "type": "drupal-library",
            "dist": {
              "url": "https://github.com/yaireo/tagify/archive/refs/tags/v4.37.1.zip",
              "type": "zip"
            }
          }
        }
    ]
    ```
1. Ensure you have following mapping inside your composer.json.
    ```json
    "extra": {
      "installer-paths": {
        "web/libraries/{$name}": ["type:drupal-library"]
      }
    }
    ```
1. Run following command to download required library.
    ```php
    composer require yaireo/tagify
    ```
1. Enable the Tagify module.

## Theme styling

Tagify ships dedicated styling for the Gin and Claro admin themes. Gin ships in
Drupal core as the `default_admin` theme. Styling is applied automatically when
the active theme is `default_admin`/`claro`, or a sub-theme of either (one that
declares `base theme: default_admin` / `base theme: claro`).

## Code Quality (JS)

You have the capability to execute ESLint and Prettier on this module. Simply
follow these steps:

- Install the needed packages

```bash
yarn install
```

- Lint JS files to find errors

```bash
yarn lint:js
```

- Run Prettier script to fix errors

```bash
yarn prettier
```

## Configuration

Set the reference field Widget to use Tagify, under the desired content type
form display settings. For example for Article, this is under
*/admin/structure/types/manage/article/form-display*.

Under Tagify Widget settings, you can set Autocomplete matching to be
"Starts with" or "Contains", the number of results, set the Suggestions
dropdown method to be on click or when 1 character is typed, as well as
defining a placeholder.

## Maintainers

- David Galeano - [gxleano](https://www.drupal.org/u/gxleano)
