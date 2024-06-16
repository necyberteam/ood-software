<?php

namespace Drupal\ood\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller for Match.
 */
class softwareController extends ControllerBase {

  /**
   * Check user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Perform redirect.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructs request stuff.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Used to get current active user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   Kill switch.
   */
  public function __construct(AccountProxyInterface $current_user,
                              KillSwitch $kill_switch,
                              RedirectDestinationInterface $redirect_destination
  ) {
    $this->currentUser = $current_user;
    $this->redirectDestination = $redirect_destination;
    $this->killSwitch = $kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_user'),
      $container->get('page_cache_kill_switch'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Show proper block depending on filter..
   */
  public function software() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $plugin_filter = $block_manager->createInstance('views_exposed_filter_block:software-block_2', array());
    $filter = $plugin_filter->build();

    $plugin_block_1 = $block_manager->createInstance('views_block:software-block_1', array());
    $block_1 = $plugin_block_1->build();

    $plugin_block_2 = $block_manager->createInstance('views_block:software-block_2', array());
    $block_2 = $plugin_block_2->build();

    // Render $filter.
    $filter_render = \Drupal::service('renderer')->renderRoot($filter);

    // Get url parameters.
    $url = \Drupal::request()->query->all();

    $software_content = $block_1;

    $pop_url = Url::fromUri('internal:/software');
    $pop_link = Link::fromTextAndUrl($this->t('Popularity'), $pop_url);
    $pop_link = $pop_link->toRenderable();

    $alpha_options = ['query' => ['title' => '', 'sort_by' => 'title', 'sort_order' => 'ASC']];
    $alpha_url = Url::fromUri('internal:/software', $alpha_options);
    $alpha_link = Link::fromTextAndUrl($this->t('Alphabetical'), $alpha_url);
    $alpha_link = $alpha_link->toRenderable();

    if (isset($url['sort_order'])) {
      $software_content = $block_2;
      $alpha_link['#attributes'] = ['class' => ['font-bold', 'no-underline']];
    } else {
      $pop_link['#attributes'] = ['class' => ['font-bold', 'no-underline']];
    }

    $software_content_render = \Drupal::service('renderer')->renderRoot($software_content);

    $software['string'] = [
      '#type' => 'inline_template',
      '#template' => '
          <h1 class="font-bold text-2xl mb-4">{{ title }}</h1>
          <div class="mb-4">
            <p>{{ intro_text }}</p>
          </div>
          <div class="flex justify-between">
            {{ filter | raw }}
            <div>
              <dl class="flex mt-2">
                <dt class="mr-4 font-bold uppercase">{{ sort_label }}:</dt>
                <dd class="mr-4">{{ popularity }}</dd>
                <dd class="mr-4">{{ alpha }}</dd>
              </dl>
            </div>
          </div>
          {{ software | raw }}
        ',
      '#context' => [
        'title' => $this->t('Software available through OnDemand'),
        'intro_text' => $this->t('OnDemand makes it easy to access your favorite software for data visualization, simulations, modeling, and more. Find where our most popular apps are enabled.'),
        'filter' => $filter_render,
        'software' => $software_content_render,
        'sort_label' => $this->t('Sort by'),
        'popularity' => $pop_link,
        'alpha' => $alpha_link,
      ],
    ];

    return $software;

  }

}
