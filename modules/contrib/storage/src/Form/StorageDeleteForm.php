<?php

namespace Drupal\storage\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting Storage entities.
 *
 * @ingroup storage
 */
class StorageDeleteForm extends ContentEntityDeleteForm {

   /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {

    return $this->t('The %bundleLabel %label has been deleted.', [
      '%label' => $this->entity->label(),
      '%bundleLabel' => $this->entity->type->entity->label(),
    ]);

  }

}
