<?php

declare(strict_types=1);

namespace Drupal\drupalsky;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\ClientInterface;

use Drupal\drupalsky\EndPoints;


/**
 *
 */
 class DrupalSky{

  /**
   * @var \Drupal\Core\Config\ImmutableConfig Module configuration.
   */
  protected $settings;      // Drupal\Core\Config\ConfigFactoryInterface
  protected $logger;        // Drupal\Core\Logger\LoggerChannelFactoryInterface
  protected $key;           // Drupal\key\KeyRepositoryInterface
  protected $httpClient;    // GuzzleHttp\ClientInterface
  protected $endpoints;     // Drupal\drupalsky\EndPoints
  protected $handle;        // Bluesky identifier
  protected $session;       // session data
  protected $baseUrl = "https://bsky.social";

  public function __construct(
    LoggerChannelFactoryInterface $loggerFactory,
    ConfigFactoryInterface $configFactory,
    KeyRepositoryInterface $keyRepository,
    ClientInterface $http_client,
    EndPoints $endpoints)
  {
    $this->logger       = $loggerFactory->get('drupalsky');
    $this->endpoints    = $endpoints;
    $this->httpClient   = $http_client;
    $this->settings     = $configFactory->get('drupalsky.settings');
    $this->handle       = $this->settings->get('handle');
    $app_key_name       = $this->settings->get('app_key');
    $this->key          = $keyRepository->getKey($app_key_name)->getKeyValue();
    $this->session      = $this->createSession($this->handle, $this->key);
  }

  /**
   * Create authenicated sessionm
   *
   * Returns session data array
   *        did => string
   *        didDoc => array
   *        handle => string
   *        email => string
   *        emailConfirmed
   *        emailAuthFactor
   *        accessJwt => string
   *        refreshJwt => string
   *        active => boolean true
   */
  private function createSession($user, $pass) {

    $request = $this->httpClient->post($this->baseUrl . $this->endpoints->createSession(),
      [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept'       => 'application/json'
        ],
        'body'  => json_encode(['identifier' => $user, 'password'   => $pass]),
      ]);

    if ($request->getStatusCode() == 200) {
      $this->logger->notice("Session opened");
      return json_decode($request->getBody()->getContents());
    }
    $this->logger->error("Create session got " . $request->getStatusCode());
    return FALSE;
  }

  /**
   * Make authenticated call
   *
   * @param $endpoint
   * @param $query
   */
  private function makeAuthCall($endpoint, $params) {
    $request = $this->httpClient->get($this->baseUrl . $endpoint,
      [
        'headers' => [
          'Content-Type'  => 'application/json',
          'Accept'        => 'application/json',
          'Authorization' => "Bearer " . $this->session->accessJwt
        ],
        'query' => $params,
      ]);

    if ($request->getStatusCode() == 200) {
      return json_decode($request->getBody()->getContents());
    }
    $this->logger->error("Auth call to " . $endpoint . " got " . $request->getStatusCode());
    return FALSE;
  }


  /**
   * getProfile
   */
  public function getProfile(){
    $profile  = [];
    $endpoint = $this->endpoints->getProfile();
    $query    = ['actor' => $this->handle];

    if ($profile = $this->makeAuthCall($endpoint, $query)) {
      $profile = array_merge(
        $this->parseProfile($profile),
        [
          'banner'    => $profile->banner,
          'followers' => $profile->followersCount,
          'follows'   => $profile->followsCount,
          'posts'     => $profile->postsCount,
        ]);
    }
    return $profile;
  }

 /**
   * Parse profile
   *
   * Get's called from multiple places
   */
  private function parseProfile($profile) {

    return [
      'displayName' => !empty($profile->displayName) ? $profile->displayName : "",
      'handle'      => $profile->handle,
      'avatar'      => !empty($profile->avatar) ? $profile->avatar : null,
      'description' => !empty($profile->description) ? $profile->description : "",
    ];
  }



  /**
   * getTimeline
   */
  public function getTimeline(){

    $feed = [];
    $endpoint = $this->endpoints->getTimeline();
    $query    = ['actor' => $this->handle];

    if ($response = $this->makeAuthCall($endpoint, $query)) {
      foreach ($response->feed as $item) {
        $feed[] = $this->parsePost($item->post);
      }
    }
    return $feed;
  }

  /**
   * getFeed
   *
   * This needs a feed uri
   */
  public function getFeed($uri){

    $feed = [];
    $endpoint = $this->endpoints->getfeed();
    $query    = ['feed' => $uri];

    if ($response = $this->makeAuthCall($endpoint, $query)) {
      foreach ($response->feed as $item) {
        $feed[] = $this->parsePost($item->post);
      }
    }
    return $feed;
  }


  /**
   * Search Posts
   *
   * @var
   *   string $keyword
   */
  public function searchPosts($keyword) {
    $feed = [];
    $endpoint = $this->endpoints->searchPosts();
    $query    = ['q' => $keyword];

    if ($response = $this->makeAuthCall($endpoint, $query)) {
      foreach ($response->posts as $post) {
          $feed[] = $this->parsePost($post);
      }
    }
    return $feed;
  }


  /**
   * Get followers
   *
   */
  public function getFollowers() {

    $followers = [];
    $endpoint  = $this->endpoints->getFollowers();
    $query     = ['actor' => $this->handle];
    if ($response = $this->makeAuthCall($endpoint, $query)) {
      foreach($response->followers as $follower){
        $followers[] = $this->parseProfile($follower);
      }
    }
    return $followers;
  }

  /**
   * Get follows
   *
   */
  public function getFollows() {

    $follows  = [];
    $endpoint = $this->endpoints->getFollows();
    $query    = ['actor' => $this->handle];
    if ($response = $this->makeAuthCall($endpoint, $query)) {

      foreach($response->follows as $follow){
        $follows[] = $this->parseProfile($follow);
      }
    }
    return $follows;
  }



  /**
   * Get Posts
   *
   */
  public function getPosts() {
    $feed   = [];
    $endpoint = $this->endpoints->getAuthorFeed();
    $query    = ['actor' => $this->handle];

    if ($response = $this->makeAuthCall($endpoint, $query)) {
      foreach ($response->feed as $item) {
        $feed[] = $this->parsePost($item->post);
      }
    }
    return $feed;
  }


  /**
   * Get thread
   *
   */
  public function getThread($uri){
    $thread   = [];
    $endpoint = $this->endpoints->getPostThread();
    $query    = ['actor' => $this->handle,'uri' => $uri ];

    if ($response = $this->makeAuthCall($endpoint, $query)) {
      $thread[] = $this->parsePost($response->thread->post);
      foreach( $response->thread->replies as $reply) {
        $thread[] = $this->parsePost($reply->post);
      }
      return($thread);
    }
  }

  /**
   * Parse post
   *
   * Return array
   */
  private function parsePost($post){

    $ext  = [];
    $parent = '';

    if (isset($post->embed->images)) {
      $image = $post->embed->images[0];
      $ext = [
        'thumb' => $image->thumb,
        'alt'   => $image->alt,
      ];
    }
    elseif (isset($post->embed->external)) {
      $ext = $post->embed->external;
    }

    if (isset($post->record->reply)){
      $parent = $post->record->reply->parent->uri;
    }

    return [
      'author' => !empty($post->author->displayName) ? $post->author->displayName : "",
      'avatar' => !empty($post->author->avatar) ? $post->author->avatar : null,
      'date'   => $this->getDate($post->record->createdAt),
      'text'   => $post->record->text,
      'url'  => $this->atUriToBskyAppUrl($post->uri),
      'ext'      => $ext,
      'parent' => $parent,
    ];
  }


  /**
   * Get blob
   *
   * No idea yet
   */
  private function getBlob($cid, $did) {
  }


  /**
   * getDid
   *
   * Gets DID for Handle
   *
   */
  private function getDid($handle){

    $request = $this->httpClient->request('GET',
      "https://public.api.bsky.app/xrpc/app.bsky.actor.getProfile", [
      'query' => [
        'actor' => $handle,
      ],
    ]);

    if ($request->getStatusCode() == 200) {
      $profile = json_decode($request->getBody()->getContents());
     return($profile->did);
    }
    return FLASE;
  }


  /**
   * getPds for DID
   *
   * Uses plc.directory
   *
   */
  private function getPds($did){
    $request = $this->httpClient->request('GET', "https://plc.directory/" . $did);
    if ($request->getStatusCode() == 200) {
      $results = json_decode($request->getBody()->getContents());
      return $results->service[0]->serviceEndpoint;
    }
    return FLASE;
  }

  /**
   * Format date
   *
   */
  private function getDate($date){
    return date('M d, Y H:i', strtotime($date));
  }


	/**
	 * Converts an AT URI for a Bluesky post to a https://bsky.app.
	 *
	 * @param atUri The AT URI of the post.  Must be in the format at://<DID>/<COLLECTION>/<RKEY>
	 * @returns The HTTPS URL to view the post on bsky.app, or null if the AT URI is invalid or not a post.
	 */
	private function atUriToBskyAppUrl($uri) {

	// at://did:plc:6aapcgkhjeffsdjc656mshnp/app.bsky.feed.post/3lhlw4gq4uj2t
  // at://did:plc:xgiwtxbtt6xc7low5vdz7dq4/app.bsky.feed.post/3lj4wjx2gds2g"

		$regex = "/^at:\/\/(did:plc:.+)\/(.+)\/(.+)$/";
		$count = preg_match($regex, $uri, $matches);

		$did        = $matches[1];
		$collection = $matches[2];
		$rkey       = $matches[3];

		if ($collection === 'app.bsky.feed.post') {
			return "https://bsky.app/profile/" . $did . "/post/" . $rkey;
		}
		else {
			return null; // Not a post record
		}
	}

// End of class
}
