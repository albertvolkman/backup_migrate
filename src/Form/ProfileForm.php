<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Form\ProfileForm.
 */

namespace Drupal\backup_migrate\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProfileForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Construct the ProfileForm.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *  An entity query factory for the profile entity type.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * Creates an instance of \Drupal\backup_migrate\Form\ProfileForm.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    $profile = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Profile Name'),
      '#maxlength' => 255,
      '#default_value' => $profile->label(),
      '#description' => $this->t("Label for the Profile."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $profile->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
      '#disabled' => !$profile->isNew(),
    );
    $form['file'] = array(
      "#type" => "details",
      "#title" => t("Backup File"),
      "#collapsed" => FALSE,
      "#tree" => FALSE,
    );
    $form['file']['filename'] = array(
      "#type" => "textfield",
      "#title" => t("Backup file name"),
      "#default_value" => $profile->filename,
      '#description' => t('You can use tokens in the file name.'),
    );
    $form['file']['token_help'] = array(
      '#title' => t('Replacement patterns'),
      '#type' => 'details',
      '#collapsed' => TRUE,
    );
    $form['file']['token_help']['help'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('current-date', 'site'),
      '#global_types' => FALSE,
    );
    $form['file']['append_timestamp'] = array(
      "#type" => "checkbox",
      "#title" => t("Append a timestamp."),
      "#default_value" => $profile->append_timestamp,
    );
    $form['file']['timestamp_format'] = array(
      "#type" => "textfield",
      "#title" => t("Timestamp format"),
      "#default_value" => $profile->timestamp_format,
      "#description" => t(
        'Should be a PHP <a href="!url">date()</a> format string.',
        array('!url' => 'http://www.php.net/date')
      ),
    );

    // Add the advanced fieldset, hidden initially.
    $form['advanced'] = array(
      '#type' => 'hidden',
      '#title' => t('Advanced Options'),
      '#collapsed' => TRUE,
      '#weight' => 10,
    );

    //   $form = array_merge_recursive($form, backup_migrate_filters_settings_form($profile->filters, 'backup'));

    /*
    $form['#attached']['js'][] = array(
      drupal_get_path('module', 'backup_migrate') . '/backup_migrate.js' => array(
        'type' => 'file',
        'scope' => 'footer',
      )
    );
    $form['#attached']['js'][] = array(
      'data' => array('backup_migrate' => array('checkboxLinkText' => t('View as checkboxes'))),
      'type' => 'setting',
    );
    $form['#attached']['css'][] = drupal_get_path('module', 'backup_migrate') . '/backup_migrate.css';
    */

//    $form['#validate'][] = '_backup_migrate_ui_backup_settings_form_validate';
//    $form['#submit'][] = '_backup_migrate_ui_backup_settings_form_submit';

    return $form;
  }

  /**
   * Checks for an existing profile.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param array $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, array $form_state) {
    // Use the query factory to build a new profile entity query.
    $query = $this->entityQueryFactory->get('backup_migrate_profile');

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $profile = $this->entity;
    $status = $profile->save();
    if ($status) {
      drupal_set_message(
        $this->t(
          'Saved the %label Profile.',
          array(
            '%label' => $profile->label(),
          )
        )
      );
    }
    else {
      drupal_set_message(
        $this->t(
          'The %label Profile was not saved.',
          array(
            '%label' => $profile->label(),
          )
        )
      );
    }

    $form_state['redirect_route']['route_name'] = 'backup_migrate_profile.list';
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $destination = drupal_get_destination();
      $request->query->remove('destination');
    }
    $form_state['redirect'] = array(
      'admin/config/system/example/' . $this->entity->id() . '/delete',
      array('query' => $destination)
    );
  }
}
