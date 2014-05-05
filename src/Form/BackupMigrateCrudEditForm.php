<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Form\BackupMigrateCrudEditForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Form\FormBase;

/**
 * Controller for export form.
 */
class BackupMigrateCrudEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'backup_migrate_crud_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $item = $form_state['build_info']['args'][0];
    $form = $item->edit_form();
    $form['item'] = array(
      '#type' => 'value',
      '#value' => $item,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $item = $form_state['values']['item'];
    $item->edit_form_validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $item = $form_state['values']['item'];
    $item->edit_form_submit($form, $form_state);
    if (empty($form_state['redirect'])) {
      $form_state['redirect'] = BACKUP_MIGRATE_MENU_PATH . '/'. $item->type_name;
    }
  }

}
