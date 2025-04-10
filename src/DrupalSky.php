<?php

declare(strict_types=1);

namespace Drupal\drupalsky;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
Use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\ClientInterface;

use Drupal\drupalsky\EndPoints;
use Drupal\drupalsky\Model\Profile;
use Drupal\drupalsky\Model\People;
use Drupal\drupalsky\Model\Feed;
use Drupal\drupalsky\Model\Thread;


/**
 *
 */
 class DrupalSky{

  /**
   * @var \Drupal\Core\Config\ImmutableConfig Module configuration.
   */
  protected $settings;      // Drupal\Core\Config\ConfigFactoryInterface
  protected $logger;        // Drupal\Core\Logger\LoggerChannelFactoryInterface
  protected $httpClient;    // GuzzleHttp\ClientInterface
  protected $endpoints;     // Drupal\drupalsky\EndPoints
  protected $handle;        // Bluesky identifier
  protected $session;       // session data
  protected $tempstore;     // PrivateTempStoreFactory
  protected $baseUrl = "https://bsky.social";

  public function __construct(
    LoggerChannelFactoryInterface $loggerFactory,
    ConfigFactoryInterface        $configFactory,
    KeyRepositoryInterface        $keyRepository,
    ClientInterface               $http_client,
    PrivateTempStoreFactory       $tempStore,
    EndPoints                     $endpoints
  )
  {
    $this->logger       = $loggerFactory->get('drupalsky');
    $this->endpoints    = $endpoints;
    $this->httpClient   = $http_client;
    $this->settings     = $configFactory->get('drupalsky.settings');
    $this->tempstore    = $tempStore->get('drupalsky');

    if ($session = $this->tempstore->get('session')) {
      // Restore saved session
      $this->handle  = $session->handle;
      $this->session = $session;
    }
    else {
      // Create new session
      $this->handle   = $this->settings->get('handle');
      $app_key_name   = $this->settings->get('app_key');
      $key            = $keyRepository->getKey($app_key_name)->getKeyValue();
      $this->session  = $this->createSession($this->handle, $key);
    }
    $this->tempstore->set('session', $this->session);
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

    $endpoint = $this->endpoints->getProfile();
    $query    = ['actor' => $this->handle];
    $data     = $this->makeAuthCall($endpoint, $query);

    $profile = new Profile($data);
    return $profile->getProfile();
  }

  /**
   * Get followers
   *
   */
  public function getFollowers() {

    $endpoint  = $this->endpoints->getFollowers();
    $query     = ['actor' => $this->handle];
    $data      = $this->makeAuthCall($endpoint, $query);

    $people = new People($data->followers);
    return $people->getPeople();
  }

  /**
   * Get follows
   *
   */
  public function getFollows() {

    $endpoint = $this->endpoints->getFollows();
    $query    = ['actor' => $this->handle];
    $data      = $this->makeAuthCall($endpoint, $query);

    $people = new People($data->follows);
    return $people->getPeople();

  }


  /**
   * getTimeline
   */
  public function getTimeline(){

    $endpoint = $this->endpoints->getTimeline();
    $query    = ['actor' => $this->handle];
    $data     = $this->makeAuthCall($endpoint, $query);

    $feed = new Feed($data->feed);
    return $feed->getFeed();

  }

 /**
   * Get Posts
   *
   */
  public function getPosts() {

    $endpoint = $this->endpoints->getAuthorFeed();
    $query    = ['actor' => $this->handle];
    $data     = $this->makeAuthCall($endpoint, $query);

    $feed = new Feed($data->feed);
    return $feed->getFeed();
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
    $data     = $this->makeAuthCall($endpoint, $query);

    $feed = new Feed($data->posts);
    return $feed->getFeed();
  }



  /**
   * Get thread
   *
   */
  public function getThread($parent){
    if (preg_match('/([^\/]+)\|([^\/]+)/', $parent, $matches)) {
      $uri = "at://did:plc:" . $matches[1] . "/app.bsky.feed.post/" . $matches[2];

      $endpoint = $this->endpoints->getPostThread();
      $query    = ['actor' => $this->handle,'uri' => $uri ];
      $data     = $this->makeAuthCall($endpoint, $query);
      $feed = new Thread($data);
      return $feed->getFeed();
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





// End of class
}
