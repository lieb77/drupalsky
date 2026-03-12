<?php

declare(strict_types=1);

namespace Drupal\drupalsky\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\drupalsky\BlueskyContentService;

/**
 * Returns responses for Drupalsky routes.
 */
final class DrupalskyController extends ControllerBase {

	/**
	* The controller constructor.
	*/
	public function __construct(
		private BlueskyContentService $service,
	){} 

	/**
	* {@inheritdoc}
	*/
	public static function create(ContainerInterface $container): self {
		return new self(
			$container->get('drupalsky.bskyservice'),
		);
	}

  /**
   * Profile.
   */
  public function home() {

    // Get the Profile data.
    $profile = $this->service->getProfile();

    // Add current user id to the Profile
    // We needs this to build the links in the template.
    $profile['uid'] = $this->currentUser()->id();

    return [
      '#type' => 'component',
      '#component' => 'drupalsky:bskyprofile',
      '#props' => $profile,
    ];
  }

  /**
   * Feed
   * Return a render array
   */
  public function feed() {
    $feed['feed'] = $this->service->getTimeLine();
    $feed['uid'] = $this->currentUser()->id();

    return [
      '#type' => 'component',
      '#component' => 'drupalsky:bskyfeed',
      '#props' => $feed,
    ];
  }

  /**
   * Thread.
   */
  public function thread($uri) {
    $thread = $this->service->getThread($uri);

    $feed['uid'] = $this->currentUser()->id();
    $feed['post'] = $thread['post'];
    $feed['feed'] = $thread['replies'];

    return [
      '#type' => 'component',
      '#component' => 'drupalsky:bskyfeed',
      '#props' => $feed,
    ];
  }

  /**
   * Followers
   * Return a render array
   */
  public function followers() {
    $followers['followers'] = $this->service->getFollowers();

    return [
      '#type' => 'component',
      '#component' => 'drupalsky:bskyfollowers',
      '#props' => $followers,
    ];
  }

  /**
   * Following
   * Return a render array
   *
   * Not yet exposed in the SDK.
   */
  public function following() {
    $follows['follows'] = $this->service->getFollows();

    return [
      '#type' => 'component',
      '#component' => 'drupalsky:bskyfollows',
      '#props' => $follows,
    ];
  }

  /**
   * Posts.
   *
   * Return a render array.
   */
  public function posts() {
    $feed['feed'] = $this->service->getPosts();
    $feed['uid'] = $this->currentUser()->id();

    return [
      '#type' => 'component',
      '#component' => 'drupalsky:bskyfeed',
      '#props' => $feed,
    ];
  }
  
  /**
   * Rides.
   *
   * Return a render array.
   */
  public function rides() {
    $rides  = $this->service->getRides();
	
    return [
      '#type' => 'component',
      '#component' => 'drupalsky:bskyrides',
      '#props' => ['rides' => $rides],
    ];
  }
  
  

  /**
   * Logout.
   */
  public function logout() {
    $this->service->logout();

    return [
      '#type' => 'item',
      '#markup' => $this->t("Your Bluesky session has been cleared"),
    ];
  }

  // End of class.
}
