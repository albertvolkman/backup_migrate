<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\Controller\ProfileListBuilder.
 */

namespace Drupal\backup_migrate\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Profile.
 */
class ProfileListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['filename'] = $this->t('Filename');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['filename'] = $entity->filename;
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }
}
