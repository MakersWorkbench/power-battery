<?php

namespace Drupal\tmwb_power_battery\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Embeds the Power and Battery Tools from a placeholder in content.
 *
 * Editors add a plain placeholder where the tool should appear:
 *
 * @code
 * <div class="tmwb-power-battery"></div>
 * @endcode
 *
 * Optional attribute: data-heading="h2" (heading level of the tool's
 * title, default h2).
 *
 * This filter runs at render time. It swaps the placeholder for the tool's
 * mount point plus a short server-rendered description (the tool itself is
 * built by JavaScript, so crawlers need real text), attaches the library
 * that loads the tool, and optionally adds WebApplication structured data
 * (JSON-LD) to the page head. The tool mounts on a fixed element id, so a
 * page can hold only one instance.
 *
 * @Filter(
 *   id = "tmwb_power_battery_embed",
 *   title = @Translation("TMWB Power and Battery Tools Embed"),
 *   description = @Translation("Replaces a div with the class tmwb-power-battery with the Power and Battery Tools tool."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "show_description" = TRUE,
 *     "structured_data" = TRUE,
 *   },
 *   weight = 10
 * )
 */
class PowerBatteryEmbedFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack, used to build absolute URLs for the structured data.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $pathResolver;

  /**
   * Constructs the filter.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $path_resolver
   *   The extension path resolver.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, RequestStack $request_stack, ExtensionPathResolver $path_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->pathResolver = $path_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('extension.path.resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['show_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show a description under the tool'),
      '#default_value' => $this->settings['show_description'],
      '#description' => $this->t('A short, server-rendered list of what the tool can decode. Helps search engines and visitors without JavaScript.'),
    ];
    $form['structured_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add structured data (JSON-LD)'),
      '#default_value' => $this->settings['structured_data'],
      '#description' => $this->t('Adds Schema.org WebApplication data to the page head so search engines understand what the tool is.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'tmwb-power-battery') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " tmwb-power-battery ")]');

    if ($nodes->length === 0) {
      return $result;
    }

    // The tool mounts on a fixed element id, so only the first placeholder
    // on a page becomes the tool. Any further ones are removed instead of
    // producing duplicate ids.
    $replaced = FALSE;
    foreach ($nodes as $node) {
      if ($replaced) {
        $node->parentNode->removeChild($node);
        continue;
      }

      $heading = $node->getAttribute('data-heading');
      $render = [
        '#theme' => 'tmwb_power_battery',
        '#heading' => preg_match('/^h[1-6]$/', $heading) ? $heading : 'h2',
        '#features' => static::features(),
        '#show_description' => !empty($this->settings['show_description']),
      ];
      $embed_html = (string) $this->renderer->renderInIsolation($render);

      // Parse the rendered markup into its own DOM, then import its nodes
      // in place of the placeholder.
      $embed_dom = Html::load($embed_html);
      $embed_body = $embed_dom->getElementsByTagName('body')->item(0);
      if ($embed_body) {
        foreach ($embed_body->childNodes as $child) {
          $node->parentNode->insertBefore($dom->importNode($child, TRUE), $node);
        }
      }
      $node->parentNode->removeChild($node);
      $replaced = TRUE;
    }

    $result->setProcessedText(Html::serialize($dom));
    $attachments = ['library' => ['tmwb_power_battery/tools']];
    if (!empty($this->settings['structured_data'])) {
      $attachments['html_head'] = [[$this->structuredData(), 'tmwb_power_battery_jsonld']];
      // The structured data holds the page's own URL.
      $result->addCacheContexts(['url.path']);
    }
    $result->addAttachments($attachments);

    return $result;
  }

  /**
   * The parts the tool can decode, as label and description pairs.
   *
   * Used both for the visible description under the tool and for the
   * structured data, so crawlers and visitors see the same list.
   *
   * @return array
   *   Array of [label, description] pairs.
   */
  public static function features(): array {
    return [
      ['Linear regulator heat', 'Power lost as heat, junction temperature, and the heatsink a 7805, LM317, or LDO needs.'],
      ['Capacitor discharge and bleeder', 'How long a resistor takes to discharge a capacitor, or the bleeder resistor for a set time, with the stored energy.'],
      ['Battery pack designer', 'Voltage, capacity, and energy of a series and parallel pack (3S2P and similar) for Li-ion, LiFePO4, NiMH, alkaline, and lead-acid cells.'],
      ['Battery life with a regulator', 'Run time from active and sleep currents and times, counting a linear or switching regulator.'],
      ['Rectifier filter capacitor', 'The smoothing capacitor for a power supply, or the ripple a given capacitor leaves.'],
    ];
  }

  /**
   * Builds the WebApplication JSON-LD script for the page head.
   *
   * @return array
   *   A render array for an html_head script element.
   */
  protected function structuredData(): array {
    $request = $this->requestStack->getCurrentRequest();
    $origin = $request ? $request->getSchemeAndHttpHost() : '';
    $page_url = $request ? $origin . $request->getBaseUrl() . $request->getPathInfo() : '';
    $screenshot = $origin . ($request ? $request->getBasePath() : '') . '/' . $this->pathResolver->getPath('module', 'tmwb_power_battery') . '/assets/images/power-overview.png';

    $publisher = [
      '@type' => 'Organization',
      'name' => 'TheMakersWorkbench.com',
      'url' => 'https://www.themakersworkbench.com',
    ];

    $data = [
      '@context' => 'https://schema.org',
      '@type' => 'WebApplication',
      'name' => 'Power and Battery Tools',
      'url' => $page_url,
      'description' => 'A free set of browser-based power tools for makers: linear regulator heat and heatsink checks, capacitor discharge and bleeder resistors, battery pack design, battery life with a regulator, and rectifier filter capacitors.',
      'applicationCategory' => 'UtilitiesApplication',
      'applicationSubCategory' => 'Power calculators',
      'operatingSystem' => 'Any (web browser)',
      'browserRequirements' => 'Requires JavaScript',
      'inLanguage' => 'en',
      'isAccessibleForFree' => TRUE,
      'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
      'license' => 'https://www.apache.org/licenses/LICENSE-2.0',
      'author' => $publisher,
      'publisher' => $publisher,
      'screenshot' => ['@type' => 'ImageObject', 'url' => $screenshot, 'caption' => 'The Power and Battery Tools showing the regulator heat check'],
      'featureList' => array_map(function ($f) {
        return $f[0] . ': ' . $f[1];
      }, static::features()),
    ];

    return [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => ['type' => 'application/ld+json'],
      '#value' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('A div with the class tmwb-power-battery is replaced by the Power and Battery Tools tool.');
  }

}
