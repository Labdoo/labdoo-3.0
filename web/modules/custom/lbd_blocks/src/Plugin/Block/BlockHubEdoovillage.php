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

    $album_uri = "xxx";
    $code .= "<p><a href='$album_uri'><img src='/themes/custom/bootstrap_labdoo/images/photo-album-icon.png' width='25px'/>&nbsp;" . 
    t("Go to photo album") . "</a></p> ";

    $code .= "<hr/>";

    return [
      '#markup' => $this->t($code),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {

    // Check if the current page is a node page.
    $route_match = \Drupal::routeMatch();
    $node = $route_match->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      // If the current page is a node page, check the node type.
      if ($node->getType() === 'edoovillage' || $node->getType() === 'hub') {
        // Allow access to the block.
        return AccessResult::allowed();
      } else {
        // Deny access to the block.
        return AccessResult::forbidden();
      }
    }

    // If the current page is not a node page, deny access to the block.
    return AccessResult::forbidden();
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
