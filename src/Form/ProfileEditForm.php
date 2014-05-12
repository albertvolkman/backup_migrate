<?php

/**
 * @file
 * Contains Drupal\backup_migrate\Form\ProfileEditForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\backup_migrate\Form\ProfileForm;

/**
 * Class ProfileEditForm.
 *
 * Provides the edit form for our Profile entity.
 */
class ProfileEditForm extends ProfileForm {

  /**
   * Returns the actions provided by this form.
   *
   * For the edit form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Update Profile');
    return $actions;
  }

}
