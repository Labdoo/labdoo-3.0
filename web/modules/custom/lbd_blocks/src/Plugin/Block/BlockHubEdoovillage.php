<?php

namespace Drupal\lbd_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "lbd_block_hub_edoovillage",
 *   admin_label = @Translation("Block: Hub & Edoovillage"),
 * )
 */
class BlockHubEdoovillage extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $object_string = "Edoovillage";
    $code = "";
    $code .= "<p><strong><font color=#009900 size=2px>";
    $replacements['@object_string'] = "Actions available for this $object_string:";
    $code .= $this->t("@object_string", $replacements);
    $code .= "</font></strong></p>";

    // Disable the block title.
//    $build['#title_display'] = 'hidden';

    $album_uri = "xxx";
    $code .= "<p><a href='$album_uri'><img src='/themes/custom/bootstrap_labdoo/images/photo-album-icon.png' width='25px'/>&nbsp;" . 
    t("Go to photo album") . "</a></p> ";

    $code .= "<hr/>";

    return [
      '#markup' => $this->t($code),
//      '#title_display' => 'hidden',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // $config = $this->getConfiguration();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['my_block_settings'] = $form_state->getValue('my_block_settings');
  }

}
