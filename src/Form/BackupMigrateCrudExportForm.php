<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Form\BackupMigrateCrudExportForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Form\FormBase;

/**
 * Controller for export form.
 */
class BackupMigrateCrudExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'backup_migrate_crud_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $export) {
    $form['export'] = array(
      '#title' => t('Exported content'),
      '#type' => 'textarea',
      '#rows' => min(30, count(explode("\n", $export))),
      '#value' => $export,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

}
