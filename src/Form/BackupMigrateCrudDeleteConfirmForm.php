<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Form\BackupMigrateCrudDeleteConfirmForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Form\ConfirmFormBase;

/**
 * Defines a confirmation form for deleting an item.
 */
class BackupMigrateCrudDeleteConfirmForm extends ConfirmFormBase {

  /**
   * The item to delete.
   *
   * @var object
   */
  protected $item;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'backup_migrate_crud_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuestion() {
    return t('Are you sure?');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelRoute() {
    return array(
      'route_name' => 'backup_migrate.' . $this->item->type_name . '_delete',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDescription() {
    return $this->item->delete_confirm_message();
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelText() {
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::buildForm().
   *
   * @param int $id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, array &$form_state, $item = NULL) {
    $this->item = $item;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if ($form_state['values']['confirm']) {
      $item = $form_state['values']['item'];
      $this->item->delete();
    }
    $form_state['redirect'] = BACKUP_MIGRATE_MENU_PATH . "/". $this->item->type_name;
  }
}
