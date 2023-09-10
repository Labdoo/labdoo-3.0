<?php

namespace Drupal\lbd_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;


/**
 * Checks if user has (specific) edit rights to an edoovillage or hub without such
 * user being a (general) edoovillage or hub manager
 *
 * @param node $node The edoovillage or hub node (must be one of the two)
 * @param user $user The user upon which edit access is to be checked 
 *
 * @return True if user has edit rights, False otherwise
 *
 */
function labdoo_lib_edoo_hub_edit_access($node, $user) {
  // TODO: port this function to Drupal 9
  return FALSE;

  // Assumes node is always either an edoovillage or a hub
  if($node->type == 'edoovillage') {
    $field_additional_editors = 'field_edoo_additional_editors';
    $field_managers = 'field_project_manager_s_';
  }
  else {  // It's a hub
    $field_additional_editors = 'field_hub_additional_editors';
    $field_managers = 'field_hub_manager_s_';
  }
  $editorIds = labdoo_lib_get_field_all($node, $field_additional_editors, 'node', 'target_id'); 
  if(in_array($user->uid, $editorIds)) 
    return TRUE;
  $managerIds = labdoo_lib_get_field_all($node, $field_managers, 'node', 'target_id'); 
  if(in_array($user->uid, $managerIds)) 
    return TRUE;
  return FALSE;
}


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
    $user = User::load(\Drupal::currentUser()->id());
    // TOASK: is this the right way to load the id?
    // Load the current node
    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = $node->id();
    $node_obj = Node::load($nid);
    // Get the current user's role
    $roles = \Drupal::currentUser()->getRoles();

    \Drupal::logger('labdoo_lib')->notice("1- " . implode($roles));
    \Drupal::logger('labdoo_lib')->notice("2- " . $nid);
    \Drupal::logger('labdoo_lib')->notice("3- " . $user->get('uid')->value);
    if(in_array('superhub manager', $roles) || 
      in_array('edoovillage manager', $roles) || 
      in_array('hub manager', $roles) || 
      in_array('wiki manager', $roles) || 
      in_array('administrator', $roles) ||
      labdoo_lib_edoo_hub_edit_access($node_obj , $user)) {


    }
    // if ($user) {
    //   $role_ids = $user->getRoles();
    // }
    $object_string = "Edoovillage";
    $code = "";
    $code .= "<p><strong><font color=#009900 size=2px>";
    $replacements['@object_string'] = "Actions available for this $object_string:";
    $code .= $this->t("@object_string", $replacements);
    $code .= "</font></strong></p>";

    $album_uri = "xxx";
    $code .= "<p><a href='$album_uri'><img src='/themes/custom/bootstrap_labdoo/images/photo-album-icon.png' width='25px'/>&nbsp;" . 
    t("Go to photo album") . "</a></p> ";

    $story_uri = "xxx";
    $code .= "<p><a href='$story_uri'>
    <img src='/themes/custom/bootstrap_labdoo/images/pencil-icon.png' width='25px'/>&nbsp;" . 
    t("Write a story about this $object_string") . "</a></p>";

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


// Todo: 
// (1) Story content type
// (2) Album content type
// (3) Link story and album content type with edoovillage/hub