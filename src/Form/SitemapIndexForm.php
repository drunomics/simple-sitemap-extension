<?php

namespace Drupal\simple_sitemap_extensions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Form\FormHelper;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SimplesitemapManager;
use Drupal\simple_sitemap_extensions\SitemapIndexTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for Sitemap index settings.
 */
class SitemapIndexForm extends ConfigFormBase {

  use SitemapIndexTrait;

  /**
   * Sitemap generator.
   *
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * Sitemap manager.
   *
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $manager;

  /**
   * Form helper.
   *
   * @var \Drupal\simple_sitemap\Form\FormHelper
   */
  protected $formHelper;

  /**
   * SitemapIndexForm constructor.
   *
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   Sitemap generator.
   * @param \Drupal\simple_sitemap\SimplesitemapManager $manager
   *   Sitemap manager.
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   *   Form helper.
   */
  public function __construct(
    Simplesitemap $generator,
    SimplesitemapManager $manager,
    FormHelper $form_helper
  ) {
    $this->generator = $generator;
    $this->manager = $manager;
    $this->formHelper = $form_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.manager'),
      $container->get('simple_sitemap.form_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_sitemap_extensions.sitemap_index.settings'];
  }

  /**
   * Gets the configuration for sitemap index.
   *
   * @return \Drupal\Core\Config\Config
   *   The config.
   */
  protected function getEditableConfig() {
    return $this->config('simple_sitemap_extensions.sitemap_index.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_sitemap_extensions_sitemap_index';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $variants = $this->getNonIndexVariants($this->manager);
    $sitemapindexes = $this->getIndexVariants($this->manager);

    $config = $this->getEditableConfig();
    foreach ($sitemapindexes as $index_key => $sitemapindex) {
      $index_config = (array) $config->get($index_key);
      $enabled_variants = $index_config['variants'] ?? [];

      $form[$index_key] = [
        '#type' => 'details',
        '#title' => $sitemapindex['label'],
        '#markup' => '<div class="description">' . $this->t('Enable variants on the index.') . '</div>',
        '#open' => TRUE,
      ];

      foreach ($variants as $variant_key => $variant) {
        $form[$index_key][$index_key . '_INDEXVARIANT_' . $variant_key] = [
          '#type' => 'checkbox',
          '#title' => $variant['label'],
          '#default_value' => in_array($variant_key, $enabled_variants),
        ];
      }
    }

    $this->formHelper->displayRegenerateNow($form);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $enabled_variants = $this->getEnabledVariants($form_state);
    $config = $this->getEditableConfig();
    foreach ($enabled_variants as $sitemap_index => $variants) {
      $index_config = (array) $config->get($sitemap_index);
      $index_config['variants'] = $variants;
      $config->set($sitemap_index, $index_config);
    }

    $config->save();

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $this->generator->setVariants(TRUE)
        ->rebuildQueue()
        ->generateSitemap();
    }
  }

  /**
   * Gets enabled variants from the form submission.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate.
   *
   * @return string[]
   *   The enabled variants (values) per sitemap index (key).
   */
  protected function getEnabledVariants(FormStateInterface $form_state) {
    $enabled_variants = [];
    foreach ($form_state->getValues() as $key => $value) {
      if (preg_match('/^(.*)_INDEXVARIANT_(.*)$/', $key, $m)) {
        $sitemap_index = $m[1];
        $variant = $m[2];
        if (empty($enabled_variants[$sitemap_index])) {
          $enabled_variants[$sitemap_index] = [];
        }
        if ($value) {
          $enabled_variants[$sitemap_index][] = $variant;
        }
      }
    }
    return $enabled_variants;
  }

}
