# Event Platform

The Event Platform is actually a set of modules, each of which provides
functionality designed to satisfy the needs of anyone creating a site for
a Drupal Camp or similar event.

Event Platform Sessions creates a robust system for user-suggested sessions,
including an approval workflow, and the ability to automatically notify a user
when their suggestion is accepted or rejected. Accepted sessions are displayed
in a list, and there's also the ability to associate sessions with rooms, tracks,
time slots, and more.

Event Platform Sponsors allows your site to display sponsors, sorted by tier of
sponsorship (bronze, silver, gold, etc).

For a full description of the module, visit the
[project page](https://www.drupal.org/project/event_platform).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/event_platform).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires the Add Content By Bundle, Auto Entity Label, Config Pages,
Field Group, Field Permissions, Hide Revisions Field, Smart Date, and ECA
modules.


## Installation

- Install the one or more of the modules as you would normally install a
  contributed Drupal module. Visit `https://www.drupal.org/node/1897420` for
  further information. We strongly recommend using composer to ensure all
  dependencies will be handled automatically.
- If your site is using the Olivero theme, installing the
  event_platform_olivero will install the other modules, and place all
  available blocks in their intended regions. If using a custom theme, install
  the main event_platform to install all submodules except
  event_platform_olivero. You will then need to place the available blocks
  yourself.
- This will import configuration. Once installed, the module doesn't really
  provide any additional functionality, so it can be uninstalled. Note that
  you won't be able to install it again on the same site, unless you delete the
  bundles that were installed originally. The one exception is the Scheduler
  submodule, which provides a custom screen (and related setting form) for
  scheduling sessions into rooms and time slots.


## Configuration

If not using event_platform_olivero but still using event_platform_details,
place the Home Hero block into a region where it will be above the main page
content, configured only to appear on the <front> page. The Heading CTA block
should be placed in one of the header regions. The Copyright block should be
placed in one of the footer regions.


## Maintainers

[//]: # cSpell:disable
[//]: # Do not add maintainers to cspell-project-words file
- Martin Anderson-Clutz - [mandclu](https://www.drupal.org/u/mandclu)
- Bernardo Martinez - [bernardm28](https://www.drupal.org/u/bernardm28)
- Kaleem Clarkson - [kclarkson](https://www.drupal.org/u/kclarkson)
- Mike Herchel - [mherchel](https://www.drupal.org/u/mherchel)
