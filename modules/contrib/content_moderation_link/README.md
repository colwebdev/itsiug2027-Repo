CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module allows editors to move content to a different workflow state by
accessing a specially crafted URL, for example:
https://mydomain.com/content-moderation-link/process/in_review/node/108,109

Note that the last three parts of the URL are meant to provide:
- the machine name of the target workflow state (e.g. draft or pubclished)
- the machine name of entity type (e.g. node)
- one or more IDs that should be processed

Note that this module currently uses existing permissions, so to use the link a
user must be authenticated (they will be redirected to the login page if not)
and have permission to use the transition needed from the content's current
state to the target state.


INSTALLATION
------------

 * Install this module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.


REQUIREMENTS
------------

This module requires the Drupal core Content Moderation module, and a workflow
with states and transitions defined.


CONFIGURATION
-------------

The settings page provides the following options:
 * Allow multiple IDs: if the URL has more than one ID value (comma separated)
   then process them all, moving them to the target workflow state.
 * Skip errors: If enabled, then when processing multiple IDs the script will
   continue processing other IDs if it's unable to successfully load the entity
   associated with a provided ID.
 * Destination Route: By default this module will redirect the user to the
   site's home page once all IDs have been processed. By providing the name of a
   route here (not a path), the site builder can change where the user will be
   redirected.

MAINTAINERS
-----------

 * Current Maintainer: Martin Anderson-Clutz (mandclu)
  - https://www.drupal.org/u/mandclu
