<?php

namespace Drupal\simple_sitemap_extensions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Form\FormHelper;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SimplesitemapManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for Sitemap index settings.
 */
class SitemapIndexForm extends ConfigFormBase {

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
    $form['sitemap_index'] = [
      '#title' => $this->t('Sitemap index'),
      '#type' => 'fieldset',
      '#markup' => '<div class="description">' . $this->t('Enable or disable sitemap variants on the index.') . '</div>',
    ];

    $variants = $this->manager->getSitemapVariants();
    $variants = array_filter($variants, function ($variant) {
      return $variant['type'] != 'sitemap_index';
    });

    $config = $this->getEditableConfig();
    $enabled_variants = (array) $config->get('variants');

    foreach ($variants as $variant_key => $variant) {
      $form['sitemap_index']['variant_' . $variant_key] = [
        '#type' => 'checkbox',
        '#title' => $variant['label'],
        '#default_value' => in_array($variant_key, $enabled_variants),
      ];
    }

    $this->formHelper->displayRegenerateNow($form);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $enabled_variants = $this->getEnabledVariants($form_state);
    if (empty($enabled_variants)) {
      $form_state->setErrorByName('', $this->t("Enable at least one variant for the sitemap index."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $enabled_variants = $this->getEnabledVariants($form_state);
    $config = $this->getEditableConfig();
    $config->set('variants', $enabled_variants);
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
   *   The enabled variants.
   */
  protected function getEnabledVariants(FormStateInterface $form_state) {
    $enabled_variants = [];
    foreach ($form_state->getValues() as $key => $value) {
      if (preg_match('/^variant_(.*)$/', $key, $m) && $value) {
        $enabled_variants[] = $m[1];
      }
    }
    return $enabled_variants;
  }

}
