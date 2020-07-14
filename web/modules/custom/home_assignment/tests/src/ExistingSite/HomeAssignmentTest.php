<?php

namespace Drupal\Tests\home_assignment\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Behat\Mink\Exception\ExpectationException;
use Drupal\home_assignment\Plugin\Field\FieldFormatter\HAGroupSubscribeFormatter;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A model test case using traits from Drupal Test Traits.
 */
class HomeAssignmentTest extends ExistingSiteBase {
  use StringTranslationTrait;

  /**
   * An example test method; note that Drupal API's and Mink are available.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testGroupSubscriptionMessage() {
    // Get view modes for bundle.
    $view_modes = \Drupal::service('entity_display.repository')
      ->getViewModeOptionsByBundle(
        'node', 'ha_group'
      );
    $view_mode = 'default';
    if (in_array('full', $view_modes)) {
      $view_mode = 'full';
    }

    // Check if the view mode is using 'home_assignment_group_subscribe' plugin.
    $config = \Drupal::service('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.ha_group.' . $view_mode)
      ->getRenderer('og_group');
    $plugin_id = $config->getPluginId();
    if ($plugin_id != 'home_assignment_group_subscribe') {
      $this->assertEqual(
        TRUE,
        TRUE,
        $this->t('Home Assignment custom field formatter not in use for full/default view mode.')
      );
      return;
    }

    // Create test user.
    $user = $this->createUser([], 'tester', TRUE);

    // Create test group.
    $node = $this->createNode([
      'title' => 'Test Group',
      'type' => 'ha_group',
      'uid' => 1,
    ]);
    $node->setPublished()->save();

    // Login as test user.
    $this->drupalLogin($user);

    // Get expected values.
    $settings = $config->getSettings();
    $tokens = [
      '%user' => $user->getDisplayName(),
      '%group' => $node->label(),
    ];
    $sub_message = HAGroupSubscribeFormatter::replaceTokens($settings['sub_message'], $tokens);
    $sub_message_approval = HAGroupSubscribeFormatter::replaceTokens($settings['sub_message_approval'], $tokens);

    // Visit our test group page.
    $this->drupalGet($node->toUrl());

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('/group/node/' . $node->id() . '/subscribe');
    // Either $sub_message or $sub_message_approval label should be present.
    try {
      $this->assertSession()->linkExists($sub_message);
    }
    catch (ExpectationException $e) {
      // Try $sub_message_approval.
      $this->assertSession()->linkExists($sub_message_approval);
    }
  }

}
