<?php

declare(strict_types=1);

namespace Drupal\drupalsky\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\drupalsky\DrupalSky;

/**
 * Returns responses for Drupalsky routes.
 */
final class DrupalskyController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private LoggerChannelInterface $loggerChannelDefault,
    private DrupalSky $service,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('logger.channel.default'),
      $container->get('drupalsky.service'),
    );
  }

	/**
	 * Profile
	 *
	 */
	public function home() {

		$profile = $this->service->getProfile();

		return [
			'#theme'    => 'profile',
			'#profile'  => $profile,
			];
 }


	/**
   * feed
   * Return a render array
   */
  public function feed(){
    $feed = $this->service->getTimeLine();

    return [
      '#theme' => 'feed',
      '#feed'  => $feed,
    ];
  }


  /**
   * thread
   *
   * This looks really bogus
   */
  public function thread($uri){
    $uri ="at%253A%252F%252Fdid%253Aplc%253A2cxgdrgtsmrbqnjkwyplmp43%252Fapp.bsky.feed.post%252F3llfevsz3hk2p";
    $uri = urldecode(urldecode($uri));
    $thread = $this->service->getThread($uri);
    return [
      '#theme' => 'thread',
      '#thread' => $thread
    ];
  }

	/**
   * followers
   * Return a render array
   */
  public function followers(){
  	$followers = $this->service->getFollowers();

  	return [
      '#theme'      => 'followers',
      '#followers'  => $followers,
    ];
  }

	/**
   * following
   * Return a render array
   *
   * Not yet exposed in the SDK
   */
  public function following(){
  	$follows = $this->service->getFollows();

  	return [
      '#theme' => 'followers',
      '#followers'  => $follows,
    ];
  }


	/**
	 * Posts
	 *
	 * Return a render array
   */
  public function posts(){

    $feed = $this->service->getPosts();

    return [
      '#theme' => 'feed',
      '#feed'  => $feed,
    ];
  }


// End of class
}
