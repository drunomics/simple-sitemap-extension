<?php

namespace Drupal\simple_sitemap_extensions\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\State\StateInterface;
use Drupal\simple_sitemap\Controller\SimplesitemapController;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap_extensions\Plugin\simple_sitemap\SitemapGenerator\DynamicSitemapGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Extension of a SimplesitemapController.
 */
class DynamicSimplesitemapController extends SimplesitemapController {

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * MonthlySitemapController constructor.
   *
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   Simple sitemap generator service.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config factory service.
   */
  public function __construct(Simplesitemap $generator, StateInterface $state, ConfigFactory $configFactory) {
    parent::__construct($generator, $configFactory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('state'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSitemap(Request $request, $variant = NULL, $chunk = NULL) {
    // Convert dynamic parameter into delta.
    if (isset($variant)) {
      $sitemap_generator = $this->getGeneratorFromVariant($variant);
      if ($sitemap_generator instanceof DynamicSitemapGeneratorInterface && $chunk) {
        // Parameter was set by PathProcessorSitemapVariantIn.
        $delta = $sitemap_generator->getCurrentDeltaFromMapping($chunk) ?: $chunk;
        $output = $this->generator->setVariants($variant)->getSitemap($delta);
        if (!$output) {
          throw new NotFoundHttpException();
        }

        return new Response($output, Response::HTTP_OK, [
          'Content-type' => 'application/xml; charset=utf-8',
          'X-Robots-Tag' => 'noindex, follow',
        ]);
      }
    }
    return parent::getSitemap($request, $variant);
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
    $sitemap_manager = $this->generator->getSitemapManager();
    $sitemap_variants = $sitemap_manager->getSitemapVariants();
    $sitemap_types = $sitemap_manager->getSitemapTypes();
    $type = $sitemap_variants[$variant]['type'];
    $sitemap_generator_name = $sitemap_types[$type]['sitemapGenerator'];
    $generator = $sitemap_manager->getSitemapGenerator($sitemap_generator_name);
    $generator->setSitemapVariant($variant);
    return $generator;
  }

}
