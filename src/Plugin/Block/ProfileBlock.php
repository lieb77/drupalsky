<?php

namespace Drupal\drupalsky\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\drupalsky\DrupalSky;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a 'Hello' Block.
 */

#[Block(
  id: "dksy_profile_block",
  admin_label: new TranslatableMarkup("Bluesky Profile"),
  category: new TranslatableMarkup("DrupalSky block")
)]
class ProfileBlock extends BlockBase {

/*
	public function __construct(
	 		array $configuration,
      $plugin_id,
      $plugin_definition,
      protected DrupalSky $service) {
	 parent::__construct($configuration, $plugin_id, $plugin_definition);


	}
*/

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
  	$instance->dskyService = $container->get('drupalsky.service');
    return $instance;
  }



  /**
   * {@inheritdoc}
   */
  public function build() {

  	if (!isset($this->dskyService)){
  		$this->dskyService = \Drupal::service('drupalsky.service');
  	}


  	$profile = $this->dskyService->getProfile();

     $render_array = [
      '#theme'    => 'profile',
      '#profile'  => $profile,
    ];

    return $render_array;

  }

}
