<?php

declare(strict_types=1);

namespace Drupal\drupalsky;


use Drupal\drupalsky\AtprotoClientService;
use Drupal\drupalsky\EndPoints;
use Drupal\drupalsky\Model\Profile;
use Drupal\drupalsky\Model\People;
use Drupal\drupalsky\Model\Feed;
use Drupal\drupalsky\Model\Rides;
use Drupal\drupalsky\Model\Thread;


/**
 * Get data from Bluesky
 *
 */
class BlueskyContentService {

	protected $handle;
	protected $did;

    public function __construct(
    	protected AtprotoClientService $atprotoClient,
    	protected EndPoints $endpoints
    ){
    	$this->handle = $atprotoClient->getHandle();
    	$this->did    = $atprotoClient->getDid();
    }
     

    /**
     * getProfile
     */
    public function getProfile()
    {
        $endpoint = $this->endpoints->getProfile();
        $query    = ['query' => ['actor' => $this->handle]];
        $data     = $this->atprotoClient->request('GET', $endpoint, $query);

        $profile = new Profile($data);
        return $profile->getProfile();
    }

    /**
     * Get followers
     */
    public function getFollowers()
    {

        $endpoint  = $this->endpoints->getFollowers();
        $query     = ['query' => ['actor' => $this->handle]];
        $data      = $this->atprotoClient->request('GET', $endpoint, $query);

        $people = new People($data->followers);
        return $people->getPeople();
    }

    /**
     * Get follows
     */
    public function getFollows()
    {

        $endpoint = $this->endpoints->getFollows();
        $query    = ['query' => ['actor' => $this->handle]];
        $data      = $this->atprotoClient->request('GET', $endpoint, $query);

        $people = new People($data->follows);
        return $people->getPeople();

    }


    /**
     * getTimeline
     */
    public function getTimeline()
    {

        $endpoint = $this->endpoints->getTimeline();
        $query    = ['query' => ['actor' => $this->handle]];
        $data     = $this->atprotoClient->request('GET', $endpoint, $query);

        $feed = new Feed($data->feed);
        return $feed->getFeed();

    }

    /**
     * Get Posts
     */
    public function getPosts()
    {

        $endpoint = $this->endpoints->getAuthorFeed();
        $query    = ['query' => ['actor' => $this->handle]];
        $data     = $this->atprotoClient->request('GET', $endpoint, $query);

        $feed = new Feed($data->feed);
        return $feed->getFeed();
    }

    /**
     * Get Rides
     */
    public function getRides()
    {
        $endpoint = $this->endpoints->listRecords();
        $query = ['query' => [
        	'repo' => $this->did,
        	'collection' => 'net.paullieberman.bike.ride'
        ]];
        $data  = $this->atprotoClient->request('GET', $endpoint, $query);
		$rides = new Rides($data->records);
		return $rides->getRides();
    }


    /**
     * Search Posts
     *
     * @var
     *   string $keyword
     */
    public function searchPosts($keyword)
    {
        $feed = [];
        $endpoint = $this->endpoints->searchPosts();
        $query    = ['query' => ['q' => $keyword]];
        $data     = $this->atprotoClient->request('GET', $endpoint, $query);

        $feed = new Feed($data->posts);
        return $feed->getFeed();
    }



    /**
     * Get thread
     */
    public function getThread($parent)
    {
        if (preg_match('/([^\/]+)\|([^\/]+)/', $parent, $matches)) {
            $uri = "at://did:plc:" . $matches[1] . "/app.bsky.feed.post/" . $matches[2];

            $endpoint = $this->endpoints->getPostThread();
            $query    = ['query' => ['actor' => $this->handle,'uri' => $uri ]];
            $data     = $this->atprotoClient->request('GET', $endpoint, $query);
            $feed = new Thread($data);
            return $feed->getFeed();
        }
    }
    
    public function logout(){
    	$this->atprotoClient->logout();
    }
    

    /**
     * Parse post
     *
     * Return array
     */
    private function parsePost($post)
    {

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

        if (isset($post->record->reply)) {
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
    private function getBlob($cid, $did)
    {
    }


    /**
     * getDid
     *
     * Gets DID for Handle
     */
    private function getDid($handle)
    {

        $request = $this->httpClient->request(
            'GET',
            "https://public.api.bsky.app/xrpc/app.bsky.actor.getProfile", [
            'query' => [
            'actor' => $handle,
            ],
            ]
        );

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
     */
    private function getPds($did)
    {
        $request = $this->httpClient->request('GET', "https://plc.directory/" . $did);
        if ($request->getStatusCode() == 200) {
            $results = json_decode($request->getBody()->getContents());
            return $results->service[0]->serviceEndpoint;
        }
        return FLASE;
    }





    // End of class
}
