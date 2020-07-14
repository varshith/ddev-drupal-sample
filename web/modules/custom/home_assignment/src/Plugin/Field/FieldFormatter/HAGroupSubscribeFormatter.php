<?php

namespace Drupal\home_assignment\Plugin\Field\FieldFormatter;

use Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Plugin implementation for the OG subscribe formatter.
 *
 * @FieldFormatter(
 *   id = "home_assignment_group_subscribe",
 *   label = @Translation("Home Assignment Group subscribe"),
 *   description = @Translation("Display OG Group subscribe and un-subscribe links."),
 *   field_types = {
 *     "og_group"
 *   }
 * )
 */
class HAGroupSubscribeFormatter extends GroupSubscribeFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Do not continue if element is not a subscription link.
    if ($elements[0]['#type'] != 'link') {
      return $elements;
    }

    // We want to update link only for 'authenticated' users.
    $user = $this->entityTypeManager->load(($this->currentUser->id()));
    if ($user instanceof User && !$user->hasRole('authenticated')) {
      return $elements;
    }

    $group = $items->getEntity();
    $settings = $this->getSettings();
    $tokens = [
      '%user' => $user->getDisplayName(),
      '%group' => $group->label(),
    ];

    // Update subscription links as configured in field formatter settings.
    if (($access = $this->ogAccess->userAccess($group, 'subscribe without approval', $user))
      && $access->isAllowed()) {
      $elements[0]['#title'] = self::replaceTokens($settings['sub_message'], $tokens);
    }
    elseif (($access = $this->ogAccess->userAccess($group, 'subscribe', $user))
      && $access->isAllowed()) {
      $elements[0]['#title'] = self::replaceTokens($settings['sub_message_approval'], $tokens);
    }

    return $elements;
  }

  /**
   * A simple token replacement.
   *
   * @param string $str
   *   The subject string.
   * @param array $tokens
   *   An array of tokens and their values.
   *
   * @return string|string[]
   *   The subject string with tokens replaced (if applicable).
   */
  public static function replaceTokens($str, array $tokens) {
    foreach ($tokens as $token => $value) {
      if (strpos($str, $token) === FALSE) {
        continue;
      }
      $str = str_replace($token, $value, $str);
    }

    return $str;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'sub_message' => t('Hi %user, click here if you would like to subscribe to this group called %group'),
      'sub_message_approval' => t('Hi %user, click here if you would like to subscribe to this group called %group'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $defaults = self::defaultSettings();
    $default_value = $defaults['sub_message'];
    if (isset($this->settings['sub_message'])) {
      $default_value = $this->settings['sub_message'];
    }
    $form['sub_message'] = [
      '#title' => $this->t('OG Subscribe Message'),
      '#type' => 'textfield',
      '#description' => $this->t('Use %user token for user name and %group for group name'),
      '#default_value' => $default_value,
    ];

    $default_value = $defaults['sub_message_approval'];
    if (isset($this->settings['sub_message_approval'])) {
      $default_value = $this->settings['sub_message_approval'];
    }
    $form['sub_message_approval'] = [
      '#title' => $this->t('OG Subscribe Message (approval required)'),
      '#type' => 'textfield',
      '#description' => $this->t('Use %user token for user name and %group for group name'),
      '#default_value' => $default_value,
    ];

    return $form;
  }

}
