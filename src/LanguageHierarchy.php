<?php

/**
 * @file
 * Contains \Drupal\demo_language\LanguageHierarchy.
 */

namespace Drupal\demo_language;

use Drupal\Core\Language\LanguageManager;
use Drupal\language\Entity\ConfigurableLanguage;

class LanguageHierarchy {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * A static cache of translated language lists.
   *
   * Array of arrays to cache the result of self::getLanguages() keyed by the
   * language the list is translated to (first level) and the flags provided to
   * the method (second level).
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   *
   * @see \Drupal\Core\Language\LanguageManager::getLanguages()
   */
  protected $languages = array();

  /**
   * @var array An array of the available language fallbacks.
   */
  protected $languageFallbackArray;

  /**
   * @var array An array with the language fallbacks tree.
   */
  public $languageTree;

  public function __construct(LanguageManager $language_manager) {
    $this->languageManager = $language_manager;
    $this->languages = $this->languageManager->getLanguages();
    $this->languageFallbackArray = $this->getLanguageFallbacksArray();
    $this->languageTree = $this->getLanguageTree();
  }

  /**
   * @param $langcode
   * @return mixed|null
   */
  public function getLanguageFallback ($langcode) {
    $loaded_language = ConfigurableLanguage::load($langcode);
    return $loaded_language->getThirdPartySetting('language_hierarchy', 'fallback_langcode', '');
  }

  /**
   * @return array
   */
  protected function getLanguageFallbacksArray(){
    $language_fallbacks = array();

    foreach ($this->languages as $language) {
      $langcode = $language->getId();
      $language_fallback = $this->getLanguageFallback($langcode);
      if ($language_fallback) {
        $language_fallbacks[$language_fallback][] = $langcode;
      }
    }

    return $language_fallbacks;
  }

  /**
   * @param $langcode
   * @return array
   */
  protected function getLanguageChildren($langcode){

    if(!isset($this->languageFallbackArray[$langcode])) {
      return;
    }

    $language_children = $this->languageFallbackArray[$langcode];

    foreach ($language_children as $langcode_child) {
      $current_children = $this->getLanguageChildren($langcode_child);
      if (!empty($current_children)) {
        $language_children = array_merge($language_children, $current_children);
      }
    }

    return $language_children;
  }

  /**
   * @return array
   */
  protected function getLanguageTree() {
    $language_tree = array();
    foreach ($this->languages as $language) {
      $langcode = $language->getId();
      $language_tree[$langcode] = $this->getLanguageChildren($langcode);
    }
    return $language_tree;
  }

  /**
   * @param $langcode
   * @return bool
   */
  public function isLeaf($langcode){
    return empty($this->languageTree[$langcode]);
  }
}