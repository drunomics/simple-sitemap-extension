<?php

namespace Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorBase;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapGenerator\DynamicSitemapGeneratorInterface;
use Drupal\simple_sitemap_extensions\SitemapIndexTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Generates urls for sitemap variants.
 *
 * This is the URL generator used for the sitemap index.
 *
 * @UrlGenerator(
 *   id = "sitemap_variant",
 *   label = @Translation("Sitemap variant URL generator"),
 *   description = @Translation("Generates URLs for sitemap variants."),
 * )
 */
class SitemapVariantUrlGenerator extends UrlGeneratorBase {

  use SitemapIndexTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Sitemap manager.
   *
   * @var \Drupal\simple_sitemap\SimplesitemapManager
   */
  protected $sitemapManager;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * SitemapVariantUrlGenerator constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param string $plugin_definition
   *   Plugin definition.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   Sitemap generator.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   Logger.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    Logger $logger,
    LanguageManagerInterface $language_manager,
    TimeInterface $time,
    ConfigFactoryInterface $config_factory,
    Connection $database
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $generator,
      $logger
    );

    $this->languageManager = $language_manager;
    $this->sitemapManager = $this->generator->getSitemapManager();
    $this->time = $time;
    $this->configFactory = $config_factory;
    $this->database = $database;
  }

  /**
   * The static create function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorBase|\Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\UrlGenerator\SitemapVariantUrlGenerator|static
   *   Instance of this class.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger'),
      $container->get('language_manager'),
      $container->get('datetime.time'),
      $container->get('config.factory'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSets() {
    $data_sets = [];
    $variants = $this->getNonIndexVariants($this->sitemapManager);

    $config = $this->configFactory->get('simple_sitemap_extensions.sitemap_index.settings');
    $index_config = (array) $config->get($this->sitemapVariant);
    $enabled_variants = $index_config['variants'] ?? [];

    foreach ($variants as $variant_key => $variant_definition) {
      if (!in_array($variant_key, $enabled_variants)) {
        continue;
      }
      $data_sets[] = ['variant' => $variant_key];
    }
    return $data_sets;
  }

  /**
   * Gets the custom base url.
   *
   * @return string
   *   The URL.
   */
  protected function getCustomBaseUrl() {
    $customBaseUrl = $this->settings['base_url'];
    return !empty($customBaseUrl) ? $customBaseUrl : $GLOBALS['base_url'];
  }

  /**
   * Get the number of pages for a given variant.
   *
   * @param string $sitemapVariant
   *   The sitemap variant.
   *
   * @return array
   *   Pages for the variant indexed by delta.
   */
  private function getNumberOfVariantPages($sitemapVariant) {
    $pages = $this->database->select('simple_sitemap', 's')
      ->fields('s', ['delta', 'sitemap_created', 'type'])
      ->condition('s.type', $sitemapVariant)
      ->condition('s.status', 1)
      ->orderBy('delta', 'ASC')
      ->execute()
      ->fetchAllAssoc('delta');

    return (array) $pages;
  }

  /**
   * {@inheritdoc}
   */
  public function generate($data_set) {
    $path_data = $this->processDataSet($data_set);
    return FALSE !== $path_data ? $path_data : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function processDataSet($data_set) {
    $settings = [
      'absolute' => TRUE,
      'base_url' => $this->getCustomBaseUrl(),
      'language' => $this->languageManager->getDefaultLanguage(),
      // Provide additional context for the URL outbound processing.
      'sitemap_index' => $this->sitemapVariant,
    ];

    $pages = $this->getNumberOfVariantPages($data_set['variant']);
    $generator = $this->getGeneratorFromVariant($data_set['variant']);

    if (count($pages) > 1 || $generator instanceof DynamicSitemapGeneratorInterface) {
      $urls = [];
      foreach ($pages as $delta => $page) {
        // Skip index.
        if ($delta == 0) {
          continue;
        }

        if ($generator instanceof DynamicSitemapGeneratorInterface) {
          $url = $generator->getSitemapUrl($delta, $settings);
        }
        else {
          // @todo This duplicates $generator->getSitemapUrl() - needs fix
          // to re-use it while keeping our settings.
          $parameters = [
            'page' => $delta,
            'variant' => $data_set['variant'],
          ];
          $url = Url::fromRoute('simple_sitemap.sitemap_variant', $parameters, $settings);
        }

        $url = [
          'url' => $url,
          'lastmod' => date('c', $this->time->getRequestTime()),
          'langcode' => $this->languageManager->getDefaultLanguage()->getId(),
        ];
        $urls[] = $url;
      }

      return $urls;
    }
    else {
      $url = Url::fromRoute('simple_sitemap.sitemap_variant', ['variant' => $data_set['variant']], $settings);
      return [
        [
          'url' => $url,
          'lastmod' => date('c', $this->time->getRequestTime()),
          'langcode' => $this->languageManager->getDefaultLanguage()->getId(),
        ],
      ];
    }
  }

  /**
   * Helper method to get sitemap generator for variant.
   *
   * @param string $variant
   *   Current sitemap variant.
   *
   * @return \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorBase
   *   Sitemap generator plugin with variant configured.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getGeneratorFromVariant(string $variant) {
    $sitemap_variants = $this->sitemapManager->getSitemapVariants();
    $sitemap_types = $this->sitemapManager->getSitemapTypes();
    $type = $sitemap_variants[$variant]['type'];
    $sitemap_generator_name = $sitemap_types[$type]['sitemapGenerator'];
    $generator = $this->sitemapManager->getSitemapGenerator($sitemap_generator_name);
    $generator->setSitemapVariant($variant);
    return $generator;
  }

}
